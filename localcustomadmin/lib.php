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
 * Library functions for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the display name for the plugin
 *
 * @return string The custom display name or the default plugin name
 */
function local_localcustomadmin_get_display_name() {
    $displayname = get_config('local_localcustomadmin', 'displayname');
    
    if (empty($displayname)) {
        $displayname = get_string('pluginname', 'local_localcustomadmin');
    }
    
    return $displayname;
}

/**
 * Serves the plugin files.
 *
 * @param stdClass $course The course object
 * @param stdClass $cm The course module object
 * @param stdClass $context The context
 * @param string $filearea The name of the file area
 * @param array $args Extra arguments (itemid, path)
 * @param bool $forcedownload Whether or not force download
 * @param array $options Additional options affecting the file serving
 * @return bool False if the file not found, just send the file otherwise and do not return anything
 */
function local_localcustomadmin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // No files to serve currently.
    return false;
}

/**
 * Hook to add custom CSS to pages.
 *
 * @return void
 */
function local_localcustomadmin_before_http_headers() {
    global $PAGE;
    
    // Add custom CSS for local_localcustomadmin pages
    if (strpos($PAGE->url->get_path(), '/local/localcustomadmin/') === 0) {
        $PAGE->requires->css('/local/localcustomadmin/styles/styles.css');
    }
}

/**
 * Add nodes to the admin tree.
 *
 * @param admin_root $adminroot Admin root object.
 * @return void
 */
function local_localcustomadmin_extend_navigation_category_settings(
    $navigation,
    $coursecategorycontext
): void {
    // Add category-specific navigation if needed.
}

/**
 * Extend the global navigation.
 *
 * @param global_navigation $navigation Navigation object.
 * @return void
 */
function local_localcustomadmin_extend_navigation(global_navigation $navigation): void {
    global $PAGE, $USER;

    // Only add navigation for logged in users with appropriate capabilities.
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Check if user has permission to view admin panel.
    $context = context_system::instance();
    if (!has_capability('local/localcustomadmin:view', $context)) {
        return;
    }

    // Add admin panel node to navigation.
    $adminnode = $navigation->add(
        get_string('localcustomadmin', 'local_localcustomadmin'),
        new moodle_url('/local/localcustomadmin/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'localcustomadmin'
    );

    $adminnode->add(
        get_string('dashboard', 'local_localcustomadmin'),
        new moodle_url('/local/localcustomadmin/dashboard.php'),
        navigation_node::TYPE_CUSTOM
    );

    $adminnode->add(
        get_string('reports', 'local_localcustomadmin'),
        new moodle_url('/local/localcustomadmin/reports.php'),
        navigation_node::TYPE_CUSTOM
    );

    if (has_capability('local/localcustomadmin:manage', $context)) {
        $adminnode->add(
            get_string('settings', 'local_localcustomadmin'),
            new moodle_url('/local/localcustomadmin/settings.php'),
            navigation_node::TYPE_CUSTOM
        );
    }
}

/**
 * Add items to the admin tree.
 *
 * @param admin_root $adminroot Admin tree root.
 * @return void
 */
function local_localcustomadmin_extend_settings_navigation($settings, $context): void {
    global $PAGE;

    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return;
    }

    // Add settings to the site administration.
    if (has_capability('local/localcustomadmin:manage', $context)) {
        $admin = $settings->get('root');
        if ($admin) {
            $localcustomadmin = $admin->add(
                get_string('localcustomadmin', 'local_localcustomadmin'),
                null,
                navigation_node::TYPE_CATEGORY,
                null,
                'localcustomadmin'
            );

            $localcustomadmin->add(
                get_string('admindashboard', 'local_localcustomadmin'),
                new moodle_url('/local/localcustomadmin/dashboard.php')
            );

            $localcustomadmin->add(
                get_string('adminreports', 'local_localcustomadmin'),
                new moodle_url('/local/localcustomadmin/reports.php')
            );

            $localcustomadmin->add(
                get_string('adminsettings', 'local_localcustomadmin'),
                new moodle_url('/local/localcustomadmin/settings.php')
            );
        }
    }
}