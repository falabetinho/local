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
 * WordPress Integration Panel
 *
 * @package    local_localcustomadmin
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php'); // Ajuste no caminho do config.php

// Check authentication and permissions.
require_login();
$context = context_system::instance();
if (!has_capability('local/localcustomadmin:manage', $context)) {
    throw new required_capability_exception($context, 'local/localcustomadmin:manage', 'nopermissions', '');
}

// Page setup.
$PAGE->set_url(new moodle_url('/local/localcustomadmin/wordpress_integration.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('wordpress_integration', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('wordpress_integration', 'local_localcustomadmin'));
$PAGE->set_pagelayout('base'); 

// Output starts here.
echo $OUTPUT->header();

// Prepare template context
$templatecontext = [
    'title' => get_string('wordpress_integration', 'local_localcustomadmin'),
    'description' => get_string('wordpress_integration_desc', 'local_localcustomadmin'),
    'back_to_index_url' => (new moodle_url('/local/localcustomadmin/index.php'))->out(),
    'cards' => [
        [
            'title' => get_string('sync_categories', 'local_localcustomadmin'),
            'description' => get_string('sync_categories_desc', 'local_localcustomadmin'),
            'url' => (new moodle_url('/local/localcustomadmin/sync_categories.php'))->out(),
            'icon' => 'fa-sitemap',
            'variant' => 'primary'
        ],
        [
            'title' => get_string('sync_courses', 'local_localcustomadmin'),
            'description' => get_string('sync_courses_desc', 'local_localcustomadmin'),
            'url' => (new moodle_url('/local/localcustomadmin/sync_courses.php'))->out(),
            'icon' => 'fa-graduation-cap',
            'variant' => 'success'
        ],
        [
            'title' => get_string('sync_prices', 'local_localcustomadmin'),
            'description' => get_string('sync_prices_desc', 'local_localcustomadmin'),
            'url' => (new moodle_url('/local/localcustomadmin/sync_prices.php'))->out(),
            'icon' => 'fa-dollar-sign',
            'variant' => 'info'
        ]
    ]
];

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/wordpress_integration', $templatecontext);

echo $OUTPUT->footer();
