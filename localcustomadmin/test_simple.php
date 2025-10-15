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
 * Simple test page to verify string loading.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:view', $context);

$PAGE->set_url(new moodle_url('/local/localcustomadmin/test_simple.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('String Test');
$PAGE->set_heading('String Test');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'Testing String Loading');

// Test basic strings with fallbacks
$pluginname = 'Local Custom Admin'; // Fallback
try {
    $pluginname = get_string('pluginname', 'local_localcustomadmin');
} catch (Exception $e) {
    echo html_writer::tag('div', 'ERROR loading pluginname: ' . $e->getMessage(), ['class' => 'alert alert-danger']);
}

$localcustomadmin = 'Local Custom Admin'; // Fallback
try {
    $localcustomadmin = get_string('localcustomadmin', 'local_localcustomadmin');
} catch (Exception $e) {
    echo html_writer::tag('div', 'ERROR loading localcustomadmin: ' . $e->getMessage(), ['class' => 'alert alert-danger']);
}

$dashboard = 'Dashboard'; // Fallback
try {
    $dashboard = get_string('dashboard', 'local_localcustomadmin');
} catch (Exception $e) {
    echo html_writer::tag('div', 'ERROR loading dashboard: ' . $e->getMessage(), ['class' => 'alert alert-danger']);
}

// Display results
echo html_writer::start_tag('table', ['class' => 'table']);
echo html_writer::start_tag('tr');
echo html_writer::tag('td', '<strong>pluginname:</strong>');
echo html_writer::tag('td', $pluginname);
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', '<strong>localcustomadmin:</strong>');
echo html_writer::tag('td', $localcustomadmin);
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', '<strong>dashboard:</strong>');
echo html_writer::tag('td', $dashboard);
echo html_writer::end_tag('tr');
echo html_writer::end_tag('table');

// Test template with simple context
echo html_writer::tag('h3', 'Testing Template with Simple Context');

$templatecontext = [
    'pagetitle' => $localcustomadmin,
    'welcome_message' => 'Welcome to ' . $pluginname,
    'cards' => [
        [
            'title' => $dashboard,
            'description' => 'Access the administrative dashboard.',
            'url' => (new moodle_url('/local/localcustomadmin/dashboard.php'))->out(),
            'btntext' => 'Open Dashboard',
            'icon' => 'fa-tachometer-alt'
        ]
    ]
];

try {
    echo $OUTPUT->render_from_template('local_localcustomadmin/index', $templatecontext);
} catch (Exception $e) {
    echo html_writer::tag('div', 'ERROR rendering template: ' . $e->getMessage(), ['class' => 'alert alert-danger']);
}

echo $OUTPUT->footer();
?>