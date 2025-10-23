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
 * WordPress Integration - Courses Synchronization
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/wordpress_course_sync.php');

// Check if logged in and has permission.
require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Set page context.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/localcustomadmin/wordpress_integration_courses.php'));
$PAGE->set_title(get_string('wordpress_integration_courses', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('wordpress_integration_courses', 'local_localcustomadmin'));
$PAGE->set_pagelayout('base');
$PAGE->navbar->ignore_active();

// Add JavaScript module.
$PAGE->requires->js_call_amd('local_localcustomadmin/wordpress_sync', 'init');

// Get filter parameters
$categoryid = optional_param('categoryid', 0, PARAM_INT);

// Get WordPress configuration.
$wp_enabled = get_config('local_localcustomadmin', 'enable_wordpress');
$wp_endpoint = get_config('local_localcustomadmin', 'wordpress_endpoint');

// Get courses based on filter
$sql = "SELECT c.* 
        FROM {course} c 
        WHERE c.id > :siteid";
$params = ['siteid' => SITEID];

if ($categoryid) {
    $sql .= " AND c.category = :categoryid";
    $params['categoryid'] = $categoryid;
}

$sql .= " ORDER BY c.fullname ASC";
$courses = $DB->get_records_sql($sql, $params);

// Get sync status for each course
$sync_handler = new \local_localcustomadmin\wordpress_course_sync();
$courses_data = [];

foreach ($courses as $course) {
    $sync_status = $sync_handler->get_sync_status($course->id);
    
    $courses_data[] = [
        'id' => $course->id,
        'fullname' => $course->fullname,
        'shortname' => $course->shortname,
        'idnumber' => $course->idnumber ?: '-',
        'category' => $DB->get_field('course_categories', 'name', ['id' => $course->category]),
        'visible' => $course->visible,
        'visible_label' => $course->visible ? get_string('yes') : get_string('no'),
        'courseurl' => new moodle_url('/course/view.php', ['id' => $course->id]),
        'synced' => !empty($sync_status),
        'sync_status' => $sync_status ? $sync_status->sync_status : 'not_synced',
        'sync_status_label' => $sync_status ? $sync_status->sync_status : get_string('not_synced', 'local_localcustomadmin'),
        'wordpress_id' => $sync_status ? $sync_status->wordpress_id : null,
        'last_synced' => $sync_status && $sync_status->last_synced ? 
            userdate($sync_status->last_synced) : get_string('never'),
        'sync_error' => $sync_status ? $sync_status->sync_error : null,
        'has_error' => $sync_status && $sync_status->sync_status === 'error'
    ];
}

// Get categories for filter
$categories = $DB->get_records('course_categories', null, 'name ASC');
$category_options = [
    ['value' => 0, 'label' => get_string('all'), 'selected' => $categoryid == 0]
];
foreach ($categories as $cat) {
    $category_options[] = [
        'value' => $cat->id,
        'label' => $cat->name,
        'selected' => $cat->id == $categoryid
    ];
}

// Action buttons
$actions = [
    [
        'title' => get_string('sync_all_courses', 'local_localcustomadmin'),
        'description' => get_string('sync_all_courses_desc', 'local_localcustomadmin'),
        'url' => '#',
        'icon' => 'fa-sync-alt',
        'variant' => 'primary',
        'js_action' => true,
        'action' => 'sync-all-courses'
    ],
    [
        'title' => get_string('sync_prices', 'local_localcustomadmin'),
        'description' => get_string('sync_prices_desc', 'local_localcustomadmin'),
        'url' => '#',
        'icon' => 'fa-dollar-sign',
        'variant' => 'success',
        'js_action' => true,
        'action' => 'sync-prices'
    ],
    [
        'title' => get_string('view_mappings', 'local_localcustomadmin'),
        'description' => get_string('view_mappings_desc', 'local_localcustomadmin'),
        'url' => new moodle_url('/local/localcustomadmin/wordpress_mappings.php', ['type' => 'course']),
        'icon' => 'fa-table',
        'variant' => 'secondary',
        'action' => 'view-mappings'
    ],
    [
        'title' => get_string('test_connection', 'local_localcustomadmin'),
        'description' => get_string('test_connection_desc', 'local_localcustomadmin'),
        'url' => '#',
        'icon' => 'fa-plug',
        'variant' => 'info',
        'js_action' => true,
        'action' => 'test-connection'
    ]
];

// Prepare template context.
$templatecontext = [
    'wordpress_enabled' => $wp_enabled,
    'wordpress_endpoint' => $wp_endpoint,
    'courses' => $courses_data,
    'has_courses' => !empty($courses_data),
    'actions' => $actions,
    'category_options' => $category_options,
    'selected_category' => $categoryid,
    'back_url' => new moodle_url('/local/localcustomadmin/wordpress_integration.php'),
    'has_manage_capability' => has_capability('local/localcustomadmin:manage', $context),
    'sesskey' => sesskey()
];

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_localcustomadmin/wordpress_integration_courses', $templatecontext);
echo $OUTPUT->footer();
