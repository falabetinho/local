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
 * Edit course page with pricing integration.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Include course manager class
require_once(__DIR__ . '/classes/course_manager.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get course ID if editing
$courseid = optional_param('id', 0, PARAM_INT);

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/edit_curso.php', array('id' => $courseid)));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');

// Load or create course object
if ($courseid) {
    $course = get_course($courseid);
    $PAGE->set_title(get_string('editcourse', 'local_localcustomadmin'));
    $PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), '/local/localcustomadmin/index.php');
    $PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'), '/local/localcustomadmin/cursos.php');
    $PAGE->navbar->add($course->fullname);
} else {
    $course = null;
    $PAGE->set_title(get_string('addcourse', 'local_localcustomadmin'));
    $PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), '/local/localcustomadmin/index.php');
    $PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'), '/local/localcustomadmin/cursos.php');
    $PAGE->navbar->add(get_string('addcourse', 'local_localcustomadmin'));
}

// Get all course categories from database
$allcategoriesdata = $DB->get_records('course_categories', [], 'name ASC');
$categories = [];
foreach ($allcategoriesdata as $catdata) {
    $categories[] = $catdata;
}

// Prepare form data
$customdata = array(
    'courseid' => $courseid,
    'course' => $course,
    'categories' => $categories
);

// Include form class
require_once('form_curso.php');

// Create form
$mform = new local_localcustomadmin_course_form(null, $customdata);

// Handle form submission
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/localcustomadmin/cursos.php'));
} else if ($formdata = $mform->get_data()) {
    // Process form data
    if ($courseid) {
        // Update existing course
        $oldcategory = $course->category;
        $formdata->id = $courseid;
        
        // Use native Moodle function to update course
        update_course($formdata);

        // Check if category changed - if so, recreate enrollments with new category pricing
        if ($oldcategory !== $formdata->category) {
            try {
                \local_localcustomadmin\course_manager::handle_category_change(
                    $courseid,
                    $formdata->category,
                    $oldcategory
                );
            } catch (Exception $e) {
                debugging('Error handling category change: ' . $e->getMessage());
            }
        } else {
            // Category didn't change, just ensure enrollments are up to date
            \local_localcustomadmin\course_manager::initialize_course_enrolments($courseid);
        }

        redirect(
            new moodle_url('/local/localcustomadmin/cursos.php'),
            get_string('courseupdated', 'local_localcustomadmin'),
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Create new course
        // Set default values
        $formdata->category = $formdata->category ?? 0;
        $formdata->summaryformat = FORMAT_HTML;
        
        // Use native Moodle function to create course
        $course = create_course($formdata);
        $courseid = $course->id;

        // Initialize enrollment fees based on category pricing
        \local_localcustomadmin\course_manager::initialize_course_enrolments($courseid);

        redirect(
            new moodle_url('/local/localcustomadmin/cursos.php'),
            get_string('coursecreated', 'local_localcustomadmin'),
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

// Set form data if editing
if ($courseid && $course) {
    $mform->set_data($course);
}

echo $OUTPUT->header();

// Load course form tabs script
$PAGE->requires->js('/local/localcustomadmin/amd/src/course_form_tabs.js');

// Render form
$mform->display();

echo $OUTPUT->footer();
