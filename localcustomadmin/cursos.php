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
 * Courses management page for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:view', $context);

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/cursos.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('courses_management', 'local_localcustomadmin'));

// Add navigation breadcrumb
$PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), '/local/localcustomadmin/index.php');
$PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'));

echo $OUTPUT->header();

// Get courses data
$courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC');
$coursestats = [];

// Get course statistics
$totalcourses = $DB->count_records('course') - 1; // Exclude site course
$visiblecourses = $DB->count_records('course', ['visible' => 1]) - 1;
$hiddencourses = $totalcourses - $visiblecourses;

// Get courses with most enrollments
$sql = "SELECT c.id, c.fullname, c.shortname, c.visible, COUNT(ue.id) as enrollments
        FROM {course} c
        LEFT JOIN {enrol} e ON e.courseid = c.id
        LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE c.id != ?
        GROUP BY c.id, c.fullname, c.shortname, c.visible
        ORDER BY enrollments DESC, c.fullname ASC";

$popularcourses = $DB->get_records_sql($sql, [SITEID], 0, 10);

// Prepare template context
$templatecontext = [
    'statistics' => [
        [
            'title' => get_string('total_courses', 'local_localcustomadmin'),
            'value' => $totalcourses,
            'icon' => 'fa-graduation-cap',
            'variant' => 'primary'
        ],
        [
            'title' => get_string('visible_courses', 'local_localcustomadmin'),
            'value' => $visiblecourses,
            'icon' => 'fa-eye',
            'variant' => 'success'
        ],
        [
            'title' => get_string('hidden_courses', 'local_localcustomadmin'),
            'value' => $hiddencourses,
            'icon' => 'fa-eye-slash',
            'variant' => 'warning'
        ]
    ],
    'courses' => [],
    'has_manage_capability' => has_capability('local/localcustomadmin:manage', $context)
];

// Populate courses data
foreach ($popularcourses as $course) {
    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $editurl = new moodle_url('/course/edit.php', ['id' => $course->id]);
    
    $templatecontext['courses'][] = [
        'id' => $course->id,
        'fullname' => format_string($course->fullname),
        'shortname' => format_string($course->shortname),
        'enrollments' => $course->enrollments,
        'visible' => $course->visible,
        'courseurl' => $courseurl->out(),
        'editurl' => $editurl->out(),
        'status_class' => $course->visible ? 'success' : 'secondary',
        'status_text' => $course->visible ? get_string('visible') : get_string('hidden')
    ];
}

// Quick actions for course management
$templatecontext['actions'] = [
    [
        'title' => get_string('create_course', 'local_localcustomadmin'),
        'description' => get_string('create_course_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/local/localcustomadmin/edit_curso.php'))->out(),
        'icon' => 'fa-plus-circle',
        'variant' => 'primary'
    ],
    [
        'title' => get_string('manage_categories', 'local_localcustomadmin'),
        'description' => get_string('manage_categories_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/local/localcustomadmin/categorias.php'))->out(),
        'icon' => 'fa-sitemap',
        'variant' => 'secondary'
    ],
    [
        'title' => get_string('course_backups', 'local_localcustomadmin'),
        'description' => get_string('course_backups_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/backup/restorefile.php'))->out(),
        'icon' => 'fa-download',
        'variant' => 'info'
    ]
];

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/cursos', $templatecontext);

echo $OUTPUT->footer();