<?php
/**
 * Debug script for language strings in Local Custom Admin plugin.
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
$PAGE->set_url(new moodle_url('/local/localcustomadmin/debug_strings.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Debug Language Strings');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'Language Strings Debug');

// List of strings to test
$strings_to_test = [
    'pluginname',
    'localcustomadmin',
    'dashboard',
    'courses',
    'settings',
    'welcome',
    'dashboard_desc',
    'courses_desc',
    'settings_desc',
    'open_dashboard',
    'open_courses',
    'open_settings',
    'total_courses',
    'visible_courses',
    'hidden_courses',
    'courses_management'
];

echo html_writer::start_tag('table', ['class' => 'table table-striped']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'String Key');
echo html_writer::tag('th', 'Value');
echo html_writer::tag('th', 'Status');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($strings_to_test as $stringkey) {
    try {
        $value = get_string($stringkey, 'local_localcustomadmin');
        $status = 'OK';
        $status_class = 'success';
    } catch (Exception $e) {
        $value = $e->getMessage();
        $status = 'ERROR';
        $status_class = 'danger';
    }
    
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $stringkey);
    echo html_writer::tag('td', format_string($value));
    echo html_writer::tag('td', html_writer::span($status, "badge bg-{$status_class}"));
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Show cache information
echo html_writer::tag('h3', 'Cache Information', ['class' => 'mt-4']);
echo html_writer::tag('p', 'If strings are not loading correctly, try:');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', 'Clear Moodle cache: Administration > Development > Purge all caches');
echo html_writer::tag('li', 'Check file permissions on lang/en/local_localcustomadmin.php');
echo html_writer::tag('li', 'Verify the language file syntax');
echo html_writer::end_tag('ul');

// Show current language file content
echo html_writer::tag('h3', 'Current Language File Content', ['class' => 'mt-4']);
$langfile = __DIR__ . '/lang/en/local_localcustomadmin.php';
if (file_exists($langfile)) {
    $content = file_get_contents($langfile);
    echo html_writer::tag('pre', htmlspecialchars($content), ['class' => 'bg-light p-3']);
} else {
    echo html_writer::tag('div', 'Language file not found!', ['class' => 'alert alert-danger']);
}

echo $OUTPUT->footer();
?>