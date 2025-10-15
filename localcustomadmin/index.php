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

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:view', $context);

// SOLUTION: Use string instead of moodle_url object to avoid state conflicts
$PAGE->set_url('/local/localcustomadmin/index.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('localcustomadmin', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('localcustomadmin', 'local_localcustomadmin'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('welcome', 'local_localcustomadmin'));

// Simple card layout
echo html_writer::start_div('row');


echo html_writer::end_div(); // row

echo $OUTPUT->footer();