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
 * Plugin settings page
 *
 * @package    local_localcustomadmin
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a settings page
    $settings = new admin_settingpage('local_localcustomadmin', get_string('pluginname', 'local_localcustomadmin'));

    // Add back button via HTML
    $backbutton = html_writer::start_div('back-button-container');
    $backurl = new moodle_url('/local/localcustomadmin/index.php');
    $backbutton .= html_writer::link($backurl, 
        html_writer::tag('i', '', ['class' => 'fas fa-arrow-left']) . ' ' . get_string('back', 'local_localcustomadmin'),
        ['class' => 'btn-back']
    );
    $backbutton .= html_writer::end_div();
    
    $settings->add(new admin_setting_heading(
        'local_localcustomadmin/backbutton',
        '',
        $backbutton
    ));

    // Add heading
    $settings->add(new admin_setting_heading(
        'local_localcustomadmin/general',
        get_string('settings', 'local_localcustomadmin'),
        ''
    ));

    // Display Name Setting
    $settings->add(new admin_setting_configtext(
        'local_localcustomadmin/displayname',
        get_string('displayname', 'local_localcustomadmin'),
        get_string('displayname_desc', 'local_localcustomadmin'),
        'Admin Personalizado Local',
        PARAM_TEXT
    ));

    // WordPress Integration Heading
    $settings->add(new admin_setting_heading(
        'local_localcustomadmin/wordpress_integration',
        get_string('wordpress_integration', 'local_localcustomadmin'),
        get_string('wordpress_integration_desc', 'local_localcustomadmin')
    ));

    // Enable WordPress Integration
    $settings->add(new admin_setting_configcheckbox(
        'local_localcustomadmin/enable_wordpress',
        get_string('enable_wordpress', 'local_localcustomadmin'),
        get_string('enable_wordpress_desc', 'local_localcustomadmin'),
        0
    ));

    // WordPress Endpoint URL
    $settings->add(new admin_setting_configtext(
        'local_localcustomadmin/wordpress_endpoint',
        get_string('wordpress_endpoint', 'local_localcustomadmin'),
        get_string('wordpress_endpoint_desc', 'local_localcustomadmin'),
        '',
        PARAM_URL
    ));

    // WordPress Username
    $settings->add(new admin_setting_configtext(
        'local_localcustomadmin/wordpress_username',
        get_string('wordpress_username', 'local_localcustomadmin'),
        get_string('wordpress_username_desc', 'local_localcustomadmin'),
        '',
        PARAM_TEXT
    ));

    // WordPress Application Password
    $settings->add(new admin_setting_configpasswordunmask(
        'local_localcustomadmin/wordpress_apppassword',
        get_string('wordpress_apppassword', 'local_localcustomadmin'),
        get_string('wordpress_apppassword_desc', 'local_localcustomadmin'),
        ''
    ));

    // Add the settings page to the navigation tree
    $ADMIN->add('localplugins', $settings);
}
