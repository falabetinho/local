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
 * AJAX endpoint for syncing courses with WordPress
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/wordpress_course_sync.php');

// Check authentication and permissions.
require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Check sesskey
$sesskey = optional_param('sesskey', '', PARAM_RAW);
if (!confirm_sesskey($sesskey)) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid session key'
    ]);
    exit;
}

// Get parameters.
$action = optional_param('action', '', PARAM_ALPHAEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);

header('Content-Type: application/json');

// Debug logging
error_log('WordPress Course Sync AJAX - Action: ' . $action . ', Course ID: ' . $courseid . ', Category ID: ' . $categoryid);

// Validate action parameter
if (empty($action)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing action parameter'
    ]);
    exit;
}

$sync = new \local_localcustomadmin\wordpress_course_sync();

try {
    switch ($action) {
        case 'sync_course':
            if (!$courseid) {
                throw new moodle_exception('missingparam', 'error', '', 'courseid');
            }
            $result = $sync->sync_course($courseid);
            break;

        case 'sync_all':
            $result = $sync->sync_all_courses($categoryid);
            break;

        case 'sync_category_courses':
            if (!$categoryid) {
                throw new moodle_exception('missingparam', 'error', '', 'categoryid');
            }
            $result = $sync->sync_all_courses($categoryid);
            break;

        case 'get_status':
            if (!$courseid) {
                throw new moodle_exception('missingparam', 'error', '', 'courseid');
            }
            $status = $sync->get_sync_status($courseid);
            $result = [
                'success' => true,
                'synced' => !empty($status),
                'status' => $status ? $status->sync_status : 'not_synced',
                'wordpress_id' => $status ? $status->wordpress_id : null,
                'last_synced' => $status ? $status->last_synced : null,
                'error' => $status ? $status->sync_error : null
            ];
            break;

        case 'bulk_sync_prices':
            // Get all synced courses and their prices
            $synced = $sync->get_synced_courses();
            $course_prices = [];
            
            foreach ($synced as $mapping) {
                $price_data = $sync->get_course_price($mapping->moodle_id);
                if ($price_data['has_price']) {
                    $course_prices[$mapping->wordpress_id] = $price_data;
                }
            }
            
            $result = $sync->bulk_sync_prices($course_prices);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action . '. Valid actions: sync_course, sync_all, sync_category_courses, get_status, bulk_sync_prices'
            ]);
            exit;
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
