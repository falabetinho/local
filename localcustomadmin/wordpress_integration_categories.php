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
 * WordPress Integration Page
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/wordpress_category_sync.php');

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
$PAGE->set_url(new moodle_url('/local/localcustomadmin/wordpress_integration.php'));
$PAGE->set_context(context_system::instance());
require_login();
require_capability('local/localcustomadmin:manage', context_system::instance());

$PAGE->set_title(get_string('wordpress_integration', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('wordpress_integration', 'local_localcustomadmin'));
$PAGE->navbar->add(get_string('pluginname', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add(get_string('wordpress_integration', 'local_localcustomadmin'));

// Prepare template context
$templatecontext = [];

// Statistics for categories - Use real data from sync class
$sync = new \local_localcustomadmin\wordpress_category_sync();
$stats = $sync->get_sync_stats();

$totalcategories = $stats['total'];
$syncedcategories = $stats['synced'];
$pendingcategories = $stats['pending'];

// Last sync time
$lastsynctime = $sync->get_last_sync_time();
$lastsync = $lastsynctime ? userdate($lastsynctime) : get_string('never_synced', 'local_localcustomadmin');

// Build statistics array
$templatecontext['statistics'] = [
    [
        'title' => get_string('total_categories', 'local_localcustomadmin'),
        'value' => $totalcategories,
        'icon' => 'fa-sitemap',
        'variant' => 'info'
    ],
    [
        'title' => get_string('synced_categories', 'local_localcustomadmin'),
        'value' => $syncedcategories,
        'icon' => 'fa-check-circle',
        'variant' => 'success'
    ],
    [
        'title' => get_string('pending_categories', 'local_localcustomadmin'),
        'value' => $pendingcategories,
        'icon' => 'fa-clock',
        'variant' => 'warning'
    ]
];

// WordPress connection info
$templatecontext['wordpress_info'] = [
    'endpoint' => $wordpress_endpoint,
    'connected' => true,
    'taxonomy' => 'niveis',
    'last_sync' => $lastsynctime
];

// Quick actions for WordPress integration
$templatecontext['actions'] = [
    [
        'title' => get_string('sync_categories', 'local_localcustomadmin'),
        'description' => get_string('sync_categories_desc', 'local_localcustomadmin'),
        'url' => '#',
        'icon' => 'fa-sync',
        'variant' => 'primary',
        'action' => 'sync-categories',
        'js_action' => true
    ],
    [
        'title' => get_string('view_mappings', 'local_localcustomadmin'),
        'description' => get_string('view_mappings_desc', 'local_localcustomadmin'),
        'url' => new moodle_url('/local/localcustomadmin/wordpress_mappings.php'),
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

// Get recent categories for display
$categories = $DB->get_records('course_categories', null, 'timemodified DESC', '*', 0, 10);
$templatecontext['categories'] = [];

foreach ($categories as $category) {
    $categoryurl = new moodle_url('/course/index.php', ['categoryid' => $category->id]);
    $editurl = new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => $category->id]);
    
    // TODO: Check if category is synced
    $is_synced = false; // Placeholder
    
    // Check if category is synced
    $is_synced = $sync->is_category_synced($category->id);
    
    $templatecontext['categories'][] = [
        'id' => $category->id,
        'name' => format_string($category->name),
        'description' => format_text($category->description, $category->descriptionformat),
        'coursecount' => $DB->count_records('course', ['category' => $category->id]),
        'categoryurl' => $categoryurl->out(),
        'editurl' => $editurl->out(),
        'is_synced' => $is_synced,
        'sync_status_class' => $is_synced ? 'success' : 'warning',
        'sync_status_text' => $is_synced ? get_string('synced', 'local_localcustomadmin') : get_string('not_synced', 'local_localcustomadmin')
    ];
}

// Add back to index URL
$templatecontext['back_to_index_url'] = (new moodle_url('/local/localcustomadmin/index.php'))->out();

// Last sync information
$templatecontext['last_sync'] = $lastsync;

// Has manage capability
$templatecontext['has_manage_capability'] = has_capability('local/localcustomadmin:manage', context_system::instance());

// Initialize WordPress sync JavaScript module
$PAGE->requires->js_call_amd('local_localcustomadmin/wordpress_sync', 'init');

echo $OUTPUT->header();

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/wordpress_integration_categories', $templatecontext);

echo $OUTPUT->footer();
