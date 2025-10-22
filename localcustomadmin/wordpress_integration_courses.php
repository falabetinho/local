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
 * WordPress Integration - Courses Sync Page
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Check if WordPress integration is enabled
$wordpress_enabled = get_config('local_localcustomadmin', 'enable_wordpress');
if (!$wordpress_enabled) {
    redirect(new moodle_url('/local/localcustomadmin/index.php'), 
             get_string('wordpress_integration_disabled', 'local_localcustomadmin'), 
             null, 
             \core\output\notification::NOTIFY_WARNING);
}

// Get WordPress settings
$wordpress_endpoint = get_config('local_localcustomadmin', 'wordpress_endpoint');
$wordpress_apikey = get_config('local_localcustomadmin', 'wordpress_apikey');

// Validate settings
if (empty($wordpress_endpoint) || empty($wordpress_apikey)) {
    redirect(new moodle_url('/local/localcustomadmin/index.php'), 
             get_string('wordpress_settings_incomplete', 'local_localcustomadmin'), 
             null, 
             \core\output\notification::NOTIFY_ERROR);
}

// Check for admin pages or set external page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/wordpress_integration_courses.php'));
$PAGE->set_context(context_system::instance());
require_login();
require_capability('local/localcustomadmin:manage', context_system::instance());

$PAGE->set_title(get_string('sync_courses_title', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('sync_courses_title', 'local_localcustomadmin'));
$PAGE->navbar->add(get_string('pluginname', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add(get_string('wordpress_integration', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/wordpress_integration.php'));
$PAGE->navbar->add(get_string('sync_courses_title', 'local_localcustomadmin'));

// Prepare template context
$templatecontext = [];

// Get statistics
// Total courses (excluding site course)
$totalcourses = $DB->count_records_select('course', 'id > 1');

// Synced courses (courses that have a WordPress mapping)
// TODO: This will need the mapping table
$syncedcourses = 0; // Placeholder

// Pending courses (courses without WordPress mapping)
$pendingcourses = $totalcourses - $syncedcourses;

// Last sync time
// TODO: Get from database
$lastsync = get_string('never_synced', 'local_localcustomadmin');

// Build statistics array
$templatecontext['statistics'] = [
    [
        'title' => get_string('total_courses', 'local_localcustomadmin'),
        'value' => $totalcourses,
        'icon' => 'fa-graduation-cap',
        'variant' => 'info'
    ],
    [
        'title' => get_string('synced_courses', 'local_localcustomadmin'),
        'value' => $syncedcourses,
        'icon' => 'fa-check-circle',
        'variant' => 'success'
    ],
    [
        'title' => get_string('pending_courses', 'local_localcustomadmin'),
        'value' => $pendingcourses,
        'icon' => 'fa-clock',
        'variant' => 'warning'
    ]
];

// WordPress connection info
$templatecontext['wordpress_info'] = [
    'endpoint' => $wordpress_endpoint,
    'connected' => true, // TODO: Test connection
    'post_type' => 'curso'
];

// Quick actions for WordPress integration
$templatecontext['actions'] = [
    [
        'title' => get_string('sync_courses', 'local_localcustomadmin'),
        'description' => get_string('sync_courses_action_desc', 'local_localcustomadmin'),
        'url' => '#', // TODO: Will be handled by JavaScript
        'icon' => 'fa-sync',
        'variant' => 'primary',
        'action' => 'sync-courses'
    ],
    [
        'title' => get_string('view_mappings', 'local_localcustomadmin'),
        'description' => get_string('view_course_mappings_desc', 'local_localcustomadmin'),
        'url' => '#', // TODO: Create mappings page
        'icon' => 'fa-table',
        'variant' => 'secondary',
        'action' => 'view-mappings'
    ],
    [
        'title' => get_string('test_connection', 'local_localcustomadmin'),
        'description' => get_string('test_connection_desc', 'local_localcustomadmin'),
        'url' => '#', // TODO: Will be handled by JavaScript
        'icon' => 'fa-plug',
        'variant' => 'info',
        'action' => 'test-connection'
    ]
];

// Get recent courses for display
$courses = $DB->get_records_select('course', 'id > 1', null, 'timemodified DESC', '*', 0, 10);
$templatecontext['courses'] = [];

foreach ($courses as $course) {
    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $editurl = new moodle_url('/local/localcustomadmin/edit_curso.php', ['id' => $course->id]);
    
    // TODO: Check if course is synced
    $is_synced = false; // Placeholder
    
    // Get enrollment count
    $enrollments = $DB->count_records('user_enrolments', [
        'enrolid' => $DB->sql_concat_join("','", ['id']),
    ]);
    
    $templatecontext['courses'][] = [
        'id' => $course->id,
        'fullname' => format_string($course->fullname),
        'shortname' => format_string($course->shortname),
        'visible' => $course->visible,
        'courseurl' => $courseurl->out(),
        'editurl' => $editurl->out(),
        'is_synced' => $is_synced,
        'sync_status_class' => $is_synced ? 'success' : 'warning',
        'sync_status_text' => $is_synced ? get_string('synced', 'local_localcustomadmin') : get_string('not_synced', 'local_localcustomadmin'),
        'visibility_class' => $course->visible ? 'success' : 'secondary',
        'visibility_text' => $course->visible ? get_string('visible') : get_string('hidden')
    ];
}

// Add back URLs
$templatecontext['back_to_integration_url'] = (new moodle_url('/local/localcustomadmin/wordpress_integration.php'))->out();
$templatecontext['back_to_index_url'] = (new moodle_url('/local/localcustomadmin/index.php'))->out();

// Last sync information
$templatecontext['last_sync'] = $lastsync;

// Has manage capability
$templatecontext['has_manage_capability'] = has_capability('local/localcustomadmin:manage', context_system::instance());

echo $OUTPUT->header();

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/wordpress_integration_courses', $templatecontext);

echo $OUTPUT->footer();
