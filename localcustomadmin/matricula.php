<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Enrolment Management - Integration with Custom Status plugin
 *
 * @package    local_localcustomadmin
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/api/customstatus_integration.php');

use local_localcustomadmin\api\customstatus_integration;

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/matricula.php', [
    'categoryid' => $categoryid,
    'courseid' => $courseid
]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('enrolment', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('enrolment', 'local_localcustomadmin'));

// Add navigation breadcrumb
$PAGE->navbar->add('LocalCustomAdmin', new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add(get_string('enrolment', 'local_localcustomadmin'));

echo $OUTPUT->header();

// Back button
echo html_writer::start_div('mb-3');
echo html_writer::link(new moodle_url('/local/localcustomadmin/index.php'), 
    '<i class="fa fa-arrow-left"></i> ' . get_string('back', 'local_localcustomadmin'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

// Check if Custom Status is available
if (!customstatus_integration::is_available()) {
    echo $OUTPUT->notification(
        get_string('customstatus_notavailable', 'local_localcustomadmin'),
        'error'
    );
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading(get_string('enrolment_management', 'local_localcustomadmin'));

// Category and Course selector
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');

echo '<div class="card mb-4">';
echo '<div class="card-body">';
echo '<h5 class="card-title"><i class="fa fa-filter"></i> ' . get_string('selectcoursetoenrol', 'local_localcustomadmin') . '</h5>';

echo '<form method="get" action="' . $PAGE->url->out(false) . '" class="mb-0">';
echo '<div class="row">';

// Category selector
echo '<div class="col-md-5">';
echo '<div class="form-group">';
echo '<label for="categoryid">' . get_string('category') . '</label>';
echo '<select name="categoryid" id="categoryid" class="form-control" onchange="loadCourses(this.value)">';
echo '<option value="0">-- ' . get_string('selectcategory', 'local_localcustomadmin') . ' --</option>';
foreach ($categories as $id => $name) {
    $selected = ($id == $categoryid) ? 'selected' : '';
    echo "<option value=\"$id\" $selected>" . format_string($name) . "</option>";
}
echo '</select>';
echo '</div></div>';

// Course selector
echo '<div class="col-md-5">';
echo '<div class="form-group">';
echo '<label for="courseid">' . get_string('course') . '</label>';
echo '<select name="courseid" id="courseid" class="form-control">';
echo '<option value="0">-- ' . get_string('selectcourse', 'local_localcustomadmin') . ' --</option>';

if ($categoryid) {
    $courses = $DB->get_records('course', ['category' => $categoryid], 'fullname ASC');
    foreach ($courses as $course) {
        if ($course->id == SITEID) continue;
        $selected = ($course->id == $courseid) ? 'selected' : '';
        echo "<option value=\"{$course->id}\" $selected>" . format_string($course->fullname) . "</option>";
    }
}

echo '</select>';
echo '</div></div>';

echo '<div class="col-md-2 d-flex align-items-end">';
echo '<div class="form-group w-100">';
echo '<button type="submit" class="btn btn-primary btn-block">' . get_string('select') . '</button>';
echo '</div></div>';

echo '</div>'; // end row
echo '</form>';
echo '</div></div>';

// If course is selected, show enrolment interface
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    
    // Check if course has customstatus enrol
    $enrol = $DB->get_record('enrol', [
        'courseid' => $courseid,
        'enrol' => 'customstatus',
        'status' => ENROL_INSTANCE_ENABLED
    ], '*', IGNORE_MULTIPLE);
    
    if (!$enrol) {
        echo '<div class="alert alert-warning">';
        echo '<h5><i class="fa fa-exclamation-triangle"></i> ' . get_string('nocustomstatusenrol', 'local_localcustomadmin') . '</h5>';
        echo '<p>' . get_string('nocustomstatusenrol_help', 'local_localcustomadmin') . '</p>';
        echo '<a href="' . new moodle_url('/enrol/instances.php', ['id' => $courseid]) . '" class="btn btn-warning">';
        echo '<i class="fa fa-plus"></i> ' . get_string('addcustomstatusenrol', 'local_localcustomadmin');
        echo '</a>';
        echo '</div>';
    } else {
        // Show course info
        echo '<div class="alert alert-info">';
        echo '<h5><i class="fa fa-book"></i> ' . get_string('selectedcourse', 'local_localcustomadmin') . '</h5>';
        echo '<strong>' . format_string($course->fullname) . '</strong>';
        if (!empty($course->shortname)) {
            echo ' <span class="badge badge-secondary">' . s($course->shortname) . '</span>';
        }
        echo '</div>';
        
        // Enrolment statistics
        $total_enrolled = $DB->count_records('user_enrolments', ['enrolid' => $enrol->id]);
        
        echo '<div class="row mb-4">';
        echo '<div class="col-md-12">';
        echo '<div class="card border-primary">';
        echo '<div class="card-body">';
        echo '<div class="row text-center">';
        
        // Total enrolled
        echo '<div class="col-md-4">';
        echo '<h3 class="text-primary">' . $total_enrolled . '</h3>';
        echo '<p class="text-muted mb-0">' . get_string('totalenrolled', 'local_localcustomadmin') . '</p>';
        echo '</div>';
        
        // Get status breakdown
        if (class_exists('enrol_customstatus\status_manager')) {
            $manager = new \enrol_customstatus\status_manager();
            
            $paid = $DB->count_records_sql("
                SELECT COUNT(DISTINCT csu.userid)
                FROM {enrol_customstatus_user} csu
                JOIN {enrol_customstatus_status} css ON css.id = csu.statusid
                WHERE csu.enrolid = :enrolid
                AND css.shortname = 'paid'
            ", ['enrolid' => $enrol->id]);
            
            $payment_due = $DB->count_records_sql("
                SELECT COUNT(DISTINCT csu.userid)
                FROM {enrol_customstatus_user} csu
                JOIN {enrol_customstatus_status} css ON css.id = csu.statusid
                WHERE csu.enrolid = :enrolid
                AND css.shortname = 'payment_due'
            ", ['enrolid' => $enrol->id]);
            
            echo '<div class="col-md-4">';
            echo '<h3 class="text-success">' . $paid . '</h3>';
            echo '<p class="text-muted mb-0">' . get_string('paidstudents', 'local_localcustomadmin') . '</p>';
            echo '</div>';
            
            echo '<div class="col-md-4">';
            echo '<h3 class="text-warning">' . $payment_due . '</h3>';
            echo '<p class="text-muted mb-0">' . get_string('paymentdue', 'local_localcustomadmin') . '</p>';
            echo '</div>';
        }
        
        echo '</div></div></div></div></div>';
        
        // Action buttons
        echo '<div class="row">';
        
        // Enrol users button
        echo '<div class="col-md-4 mb-3">';
        echo '<div class="card h-100 border-primary">';
        echo '<div class="card-body text-center">';
        echo '<i class="fa fa-user-plus fa-3x text-primary mb-3"></i>';
        echo '<h5 class="card-title">' . get_string('enrolusers', 'local_localcustomadmin') . '</h5>';
        echo '<p class="card-text">' . get_string('enrolusers_desc', 'local_localcustomadmin') . '</p>';
        echo '<a href="' . new moodle_url('/enrol/customstatus/matricula.php', ['courseid' => $courseid]) . '" class="btn btn-primary btn-block">';
        echo '<i class="fa fa-users"></i> ' . get_string('enrolnow', 'local_localcustomadmin');
        echo '</a>';
        echo '</div></div></div>';
        
        // Manage status button
        echo '<div class="col-md-4 mb-3">';
        echo '<div class="card h-100 border-info">';
        echo '<div class="card-body text-center">';
        echo '<i class="fa fa-tags fa-3x text-info mb-3"></i>';
        echo '<h5 class="card-title">' . get_string('managestatus', 'local_localcustomadmin') . '</h5>';
        echo '<p class="card-text">' . get_string('managestatus_desc', 'local_localcustomadmin') . '</p>';
        echo '<a href="' . new moodle_url('/enrol/customstatus/assign_status.php', ['courseid' => $courseid]) . '" class="btn btn-info btn-block">';
        echo '<i class="fa fa-edit"></i> ' . get_string('assignstatus', 'local_localcustomadmin');
        echo '</a>';
        echo '</div></div></div>';
        
        // View report button
        echo '<div class="col-md-4 mb-3">';
        echo '<div class="card h-100 border-success">';
        echo '<div class="card-body text-center">';
        echo '<i class="fa fa-chart-line fa-3x text-success mb-3"></i>';
        echo '<h5 class="card-title">' . get_string('viewreport', 'local_localcustomadmin') . '</h5>';
        echo '<p class="card-text">' . get_string('viewreport_desc', 'local_localcustomadmin') . '</p>';
        echo '<a href="' . new moodle_url('/enrol/customstatus/report.php', ['courseid' => $courseid]) . '" class="btn btn-success btn-block">';
        echo '<i class="fa fa-chart-bar"></i> ' . get_string('openreport', 'local_localcustomadmin');
        echo '</a>';
        echo '</div></div></div>';
        
        echo '</div>'; // end row
    }
}

// JavaScript for dynamic course loading
echo '<script>
function loadCourses(categoryid) {
    if (categoryid == 0) {
        document.getElementById("courseid").innerHTML = \'<option value="0">-- ' . get_string('selectcourse', 'local_localcustomadmin') . ' --</option>\';
        return;
    }
    
    // For now, just submit the form
    // In production, you could use AJAX here
    document.querySelector("form").submit();
}
</script>';

echo $OUTPUT->footer();
