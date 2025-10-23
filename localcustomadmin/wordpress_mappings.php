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
 * WordPress Mappings Viewer
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Check if logged in and has permission.
require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Set page context.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/localcustomadmin/wordpress_mappings.php'));
$PAGE->set_title(get_string('wordpress_mappings', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('wordpress_mappings', 'local_localcustomadmin'));
$PAGE->set_pagelayout('base');
$PAGE->navbar->ignore_active();

// Get filter parameters
$type = optional_param('type', 'all', PARAM_ALPHA); // all, category, course
$status = optional_param('status', 'all', PARAM_ALPHA); // all, synced, pending, error

// Build SQL query
$sql = "SELECT m.*, 
               CASE 
                   WHEN m.moodle_type = 'category' THEN cc.name
                   WHEN m.moodle_type = 'course' THEN c.fullname
               END as moodle_name,
               CASE 
                   WHEN m.moodle_type = 'category' THEN cc.idnumber
                   WHEN m.moodle_type = 'course' THEN c.idnumber
               END as moodle_idnumber
        FROM {local_customadmin_wp_mapping} m
        LEFT JOIN {course_categories} cc ON m.moodle_type = 'category' AND m.moodle_id = cc.id
        LEFT JOIN {course} c ON m.moodle_type = 'course' AND m.moodle_id = c.id
        WHERE 1=1";

$params = [];

if ($type !== 'all') {
    $sql .= " AND m.moodle_type = :type";
    $params['type'] = $type;
}

if ($status !== 'all') {
    $sql .= " AND m.sync_status = :status";
    $params['status'] = $status;
}

$sql .= " ORDER BY m.timemodified DESC";

// Get mappings
$mappings = $DB->get_records_sql($sql, $params);

// Prepare template context
$templatecontext = [
    'mappings' => [],
    'has_mappings' => !empty($mappings),
    'total_count' => count($mappings),
    'back_url' => new moodle_url('/local/localcustomadmin/wordpress_integration_categories.php'),
    'filter_type' => $type,
    'filter_status' => $status,
    'type_options' => [
        ['value' => 'all', 'label' => get_string('all'), 'selected' => $type === 'all'],
        ['value' => 'category', 'label' => get_string('categories'), 'selected' => $type === 'category'],
        ['value' => 'course', 'label' => get_string('courses'), 'selected' => $type === 'course'],
    ],
    'status_options' => [
        ['value' => 'all', 'label' => get_string('all'), 'selected' => $status === 'all'],
        ['value' => 'synced', 'label' => get_string('synced', 'local_localcustomadmin'), 'selected' => $status === 'synced'],
        ['value' => 'pending', 'label' => get_string('pending', 'local_localcustomadmin'), 'selected' => $status === 'pending'],
        ['value' => 'error', 'label' => get_string('error'), 'selected' => $status === 'error'],
    ],
];

// Process mappings for display
foreach ($mappings as $mapping) {
    $mappingdata = [
        'id' => $mapping->id,
        'moodle_type' => $mapping->moodle_type,
        'moodle_type_label' => $mapping->moodle_type === 'category' 
            ? get_string('category') 
            : get_string('course'),
        'moodle_id' => $mapping->moodle_id,
        'moodle_name' => $mapping->moodle_name ?: get_string('notfound', 'local_localcustomadmin'),
        'moodle_idnumber' => $mapping->moodle_idnumber ?: '-',
        'wordpress_type' => $mapping->wordpress_type,
        'wordpress_id' => $mapping->wordpress_id,
        'wordpress_taxonomy' => $mapping->wordpress_taxonomy ?: '-',
        'wordpress_post_type' => $mapping->wordpress_post_type ?: '-',
        'sync_status' => $mapping->sync_status,
        'sync_status_class' => $mapping->sync_status === 'synced' ? 'success' 
            : ($mapping->sync_status === 'error' ? 'danger' : 'warning'),
        'sync_status_label' => get_string($mapping->sync_status, 'local_localcustomadmin'),
        'last_synced' => $mapping->last_synced ? userdate($mapping->last_synced) : '-',
        'has_error' => !empty($mapping->sync_error),
        'sync_error' => $mapping->sync_error ?: '',
        'timecreated' => userdate($mapping->timecreated),
        'timemodified' => userdate($mapping->timemodified),
    ];
    
    $templatecontext['mappings'][] = $mappingdata;
}

// Get statistics
$stats = [
    'total' => $DB->count_records('local_customadmin_wp_mapping'),
    'categories' => $DB->count_records('local_customadmin_wp_mapping', ['moodle_type' => 'category']),
    'courses' => $DB->count_records('local_customadmin_wp_mapping', ['moodle_type' => 'course']),
    'synced' => $DB->count_records('local_customadmin_wp_mapping', ['sync_status' => 'synced']),
    'pending' => $DB->count_records('local_customadmin_wp_mapping', ['sync_status' => 'pending']),
    'errors' => $DB->count_records('local_customadmin_wp_mapping', ['sync_status' => 'error']),
];

$templatecontext['statistics'] = $stats;

// Output page
echo $OUTPUT->header();

// Render template
echo $OUTPUT->render_from_template('local_localcustomadmin/wordpress_mappings', $templatecontext);

echo $OUTPUT->footer();
