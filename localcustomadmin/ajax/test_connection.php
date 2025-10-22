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
 * AJAX endpoint for WordPress connection testing
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/wordpress_api.php');

// Check if logged in and has permission
require_login();
require_sesskey();
require_capability('local/localcustomadmin:manage', context_system::instance());

// Set JSON header
header('Content-Type: application/json');

try {
    // Get WordPress settings
    $endpoint = get_config('local_localcustomadmin', 'wordpress_endpoint');
    $apikey = get_config('local_localcustomadmin', 'wordpress_apikey');
    
    if (empty($endpoint) || empty($apikey)) {
        throw new moodle_exception('wordpress_settings_incomplete', 'local_localcustomadmin');
    }
    
    // Create API instance and test connection
    $api = new \local_localcustomadmin\wordpress_api($endpoint, $apikey);
    $connected = $api->test_connection();
    
    if ($connected) {
        echo json_encode([
            'success' => true,
            'connected' => true,
            'message' => get_string('connection_success', 'local_localcustomadmin'),
            'endpoint' => $endpoint
        ]);
    } else {
        $error = $api->get_last_error();
        echo json_encode([
            'success' => false,
            'connected' => false,
            'message' => get_string('connection_failed', 'local_localcustomadmin'),
            'error' => $error['message']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'connected' => false,
        'error' => $e->getMessage()
    ]);
}
