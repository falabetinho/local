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
 * WordPress Integration Main Page
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

// WordPress connection info
$templatecontext['wordpress_info'] = [
    'endpoint' => $wordpress_endpoint,
    'connected' => true, // TODO: Test connection
];

// Integration options
$templatecontext['integration_options'] = [
    [
        'title' => get_string('sync_categories_title', 'local_localcustomadmin'),
        'description' => get_string('sync_categories_title_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/local/localcustomadmin/wordpress_integration_categories.php'))->out(),
        'icon' => 'fa-sitemap',
        'variant' => 'primary',
        'stats' => [
            'label' => get_string('total_categories', 'local_localcustomadmin'),
            'value' => $DB->count_records('course_categories')
        ]
    ],
    [
        'title' => get_string('sync_courses_title', 'local_localcustomadmin'),
        'description' => get_string('sync_courses_title_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/local/localcustomadmin/wordpress_integration_courses.php'))->out(),
        'icon' => 'fa-graduation-cap',
        'variant' => 'success',
        'stats' => [
            'label' => get_string('total_courses', 'local_localcustomadmin'),
            'value' => $DB->count_records('course', ['id' => $DB->sql_compare_text('id') . ' > 1']) // Exclude site course
        ]
    ]
];

// Add back to index URL
$templatecontext['back_to_index_url'] = (new moodle_url('/local/localcustomadmin/index.php'))->out();

// Has manage capability
$templatecontext['has_manage_capability'] = has_capability('local/localcustomadmin:manage', context_system::instance());

echo $OUTPUT->header();

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/wordpress_integration', $templatecontext);

echo $OUTPUT->footer();
