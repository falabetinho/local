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
 * AJAX handler for category price operations.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/category_price_manager.php');

// Check authentication
require_login();

// Check capability
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get action
$action = required_param('action', PARAM_ALPHA);

// Prepare response
$response = new stdClass();
$response->success = false;
$response->error = '';
$response->data = null;

try {
    $categoryid = required_param('categoryid', PARAM_INT);
    
    // Validate categoryid
    if (empty($categoryid)) {
        throw new Exception('Category ID is required');
    }
    
    switch ($action) {
        case 'getprices':
            $activeonly = optional_param('activeonly', true, PARAM_BOOL);
            $prices = \local_localcustomadmin\category_price_manager::get_category_prices($categoryid, $activeonly);
            
            // Convert Moodle records (object keyed by id) to array of objects
            if (is_array($prices) && !empty($prices)) {
                $prices = array_values($prices);
                
                // Convert numeric strings to proper types for JSON
                foreach ($prices as &$price) {
                    $price->id = (int) $price->id;
                    $price->categoryid = (int) $price->categoryid;
                    $price->price = (float) $price->price;
                    $price->startdate = (int) $price->startdate;
                    $price->enddate = $price->enddate ? (int) $price->enddate : null;
                    $price->ispromotional = (int) $price->ispromotional;
                    $price->isenrollmentfee = (int) $price->isenrollmentfee;
                    $price->scheduledtask = (int) $price->scheduledtask;
                    $price->status = (int) $price->status;
                    $price->installments = (int) $price->installments;
                    $price->timecreated = (int) $price->timecreated;
                    $price->timemodified = (int) $price->timemodified;
                }
            } else {
                $prices = array();
            }
            
            $response->data = $prices;
            $response->success = true;
            break;

        case 'createprice':
            $name = required_param('name', PARAM_TEXT);
            $price = required_param('price', PARAM_FLOAT);
            $startdate = required_param('startdate', PARAM_INT);
            $ispromotional = optional_param('ispromotional', 0, PARAM_INT);
            $isenrollmentfee = optional_param('isenrollmentfee', 0, PARAM_INT);
            $scheduledtask = optional_param('scheduledtask', 0, PARAM_INT);
            $installments = optional_param('installments', 0, PARAM_INT);
            $status = optional_param('status', 1, PARAM_INT);
            $enddate = optional_param('enddate', null, PARAM_INT);

            // Validate startdate is not empty
            if (empty($startdate)) {
                throw new Exception('Start date is required');
            }

            $pricedata = new stdClass();
            $pricedata->categoryid = $categoryid;
            $pricedata->name = $name;
            $pricedata->price = $price;
            $pricedata->startdate = $startdate;
            $pricedata->enddate = $enddate;
            $pricedata->ispromotional = $ispromotional;
            $pricedata->isenrollmentfee = $isenrollmentfee;
            $pricedata->scheduledtask = $scheduledtask;
            $pricedata->installments = $installments;
            $pricedata->status = $status;

            $result = \local_localcustomadmin\category_price_manager::create($pricedata);
            
            $response->data = $result;
            $response->success = true;
            break;

        case 'updateprice':
            $id = required_param('id', PARAM_INT);
            $name = required_param('name', PARAM_TEXT);
            $price = required_param('price', PARAM_FLOAT);
            $startdate = required_param('startdate', PARAM_INT);
            $ispromotional = optional_param('ispromotional', 0, PARAM_INT);
            $isenrollmentfee = optional_param('isenrollmentfee', 0, PARAM_INT);
            $scheduledtask = optional_param('scheduledtask', 0, PARAM_INT);
            $installments = optional_param('installments', 0, PARAM_INT);
            $status = optional_param('status', 1, PARAM_INT);
            $enddate = optional_param('enddate', null, PARAM_INT);

            // Validate startdate is not empty
            if (empty($startdate)) {
                throw new Exception('Start date is required');
            }

            $pricedata = new stdClass();
            $pricedata->id = $id;
            $pricedata->categoryid = $categoryid;
            $pricedata->name = $name;
            $pricedata->price = $price;
            $pricedata->startdate = $startdate;
            $pricedata->enddate = $enddate;
            $pricedata->ispromotional = $ispromotional;
            $pricedata->isenrollmentfee = $isenrollmentfee;
            $pricedata->scheduledtask = $scheduledtask;
            $pricedata->installments = $installments;
            $pricedata->status = $status;

            \local_localcustomadmin\category_price_manager::update($id, $pricedata);
            
            $response->success = true;
            break;

        case 'deleteprice':
            $id = required_param('id', PARAM_INT);
            \local_localcustomadmin\category_price_manager::delete($id);
            
            $response->success = true;
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response->success = false;
    $response->error = $e->getMessage();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
