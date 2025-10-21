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
 * Main page for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/lib.php');

use local_localcustomadmin\api\customstatus_integration;

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:view', $context);

// Get custom display name
$displayname = local_localcustomadmin_get_display_name();

$PAGE->set_url(new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title($displayname);
$PAGE->set_heading($displayname);

echo $OUTPUT->header();

// Prepare template context data
$templatecontext = [
    'welcome_message' => get_string('welcome', 'local_localcustomadmin'),
    'cards' => []
];

// Dashboard card - always available
$templatecontext['cards'][] = [
    'title' => get_string('dashboard', 'local_localcustomadmin'),
    'description' => get_string('dashboard_desc', 'local_localcustomadmin'),
    'url' => (new moodle_url('/local/localcustomadmin/dashboard.php'))->out(),
    'btntext' => get_string('open_dashboard', 'local_localcustomadmin'),
    'icon' => 'fa-tachometer-alt'
];

// Courses card - always available
$templatecontext['cards'][] = [
    'title' => get_string('courses', 'local_localcustomadmin'),
    'description' => get_string('courses_desc', 'local_localcustomadmin'),
    'url' => (new moodle_url('/local/localcustomadmin/cursos.php'))->out(),
    'btntext' => get_string('open_courses', 'local_localcustomadmin'),
    'icon' => 'fa-graduation-cap'
];

// Users card - only for managers
if (has_capability('local/localcustomadmin:manage', $context)) {
    $templatecontext['cards'][] = [
        'title' => get_string('users', 'local_localcustomadmin'),
        'description' => get_string('users_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/local/localcustomadmin/usuarios.php'))->out(),
        'btntext' => get_string('open_users', 'local_localcustomadmin'),
        'icon' => 'fa-users'
    ];
}

// Settings card - only for managers, redirects to Moodle settings page
if (has_capability('local/localcustomadmin:manage', $context)) {
    $templatecontext['cards'][] = [
        'title' => get_string('settings', 'local_localcustomadmin'),
        'description' => get_string('settings_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/admin/settings.php', ['section' => 'local_localcustomadmin']))->out(),
        'btntext' => get_string('open_settings', 'local_localcustomadmin'),
        'icon' => 'fa-cog'
    ];
}

// Enrolment Management card - only for managers
if (has_capability('local/localcustomadmin:manage', $context)) {
    $templatecontext['cards'][] = [
        'title' => get_string('enrolment_management', 'local_localcustomadmin'),
        'description' => get_string('enrolment_management_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/local/localcustomadmin/enrolment_management.php'))->out(),
        'btntext' => get_string('manage', 'local_localcustomadmin'),
        'icon' => 'fa-user-graduate'
    ];
}

// Enrolment Data card (Custom Status) - only for managers with Custom Status available
if (has_capability('local/localcustomadmin:manage', $context) && customstatus_integration::is_available()) {
    $templatecontext['cards'][] = [
        'title' => get_string('enrolmentdata', 'local_localcustomadmin'),
        'description' => get_string('enrolmentdata_desc', 'local_localcustomadmin'),
        'url' => (new moodle_url('/enrol/customstatus/enrolment_data.php'))->out(),
        'btntext' => get_string('manage_enrolmentdata', 'local_localcustomadmin'),
        'icon' => 'fa-database'
    ];
}

// Check if no cards are available
if (empty($templatecontext['cards'])) {
    $templatecontext['no_cards'] = true;
}

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/index', $templatecontext);

echo $OUTPUT->footer();