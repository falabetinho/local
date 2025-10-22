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
 * AJAX endpoint for category synchronization
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/wordpress_category_sync.php');

// Check if logged in and has permission
require_login();
require_sesskey();
require_capability('local/localcustomadmin:manage', context_system::instance());

// Set JSON header
header('Content-Type: application/json');

try {
    // Get action parameter (use PARAM_ALPHAEXT to allow underscores)
    $action = optional_param('action', '', PARAM_ALPHAEXT);
    
    if (empty($action)) {
        throw new \Exception('No action specified');
    }
    
    // Create sync instance
    $sync = new \local_localcustomadmin\wordpress_category_sync();
    
    switch ($action) {
        case 'sync_all':
            // Sync all categories
            $forcesync = optional_param('force', false, PARAM_BOOL);
            $results = $sync->sync_all_categories($forcesync);
            
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            break;
            
        case 'sync_single':
            // Sync a single category
            $categoryid = required_param('categoryid', PARAM_INT);
            $forcesync = optional_param('force', false, PARAM_BOOL);
            
            // Get category
            $category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
            
            // Sync it
            $sync->sync_category($category, $forcesync);
            $results = $sync->get_results();
            
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            break;
            
        case 'get_stats':
            // Get sync statistics
            $stats = $sync->get_sync_stats();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            throw new \Exception('Invalid action: ' . $action);
    }
    
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
