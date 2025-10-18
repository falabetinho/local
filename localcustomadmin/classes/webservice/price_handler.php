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
 * Webservice API for category pricing management.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin\webservice;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use context_system;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Web service API for price management
 */
class price_handler extends \core_external\external_api {

    /**
     * Get category price parameters
     *
     * @return \core_external\external_function_parameters
     */
    public static function get_category_prices_parameters() {
        return new \core_external\external_function_parameters(array(
            'categoryid' => new \core_external\external_value(PARAM_INT, 'Category ID', VALUE_REQUIRED),
            'activeonly' => new \core_external\external_value(PARAM_BOOL, 'Get only active prices', VALUE_DEFAULT, true),
        ));
    }

    /**
     * Get prices for a category
     *
     * @param int $categoryid Category ID
     * @param bool $activeonly Get only active prices
     * @return array Prices list
     */
    public static function get_category_prices($categoryid, $activeonly = true) {
        global $USER;

        $params = self::validate_parameters(self::get_category_prices_parameters(), array(
            'categoryid' => $categoryid,
            'activeonly' => $activeonly
        ));

        // Check capability
        $context = \context_system::instance();
        self::validate_context($context);

        if (!has_capability('local/localcustomadmin:manage', $context)) {
            throw new \moodle_exception('nopermission', 'local_localcustomadmin');
        }

        // Get prices
        $prices = \local_localcustomadmin\category_price_manager::get_category_prices(
            $params['categoryid'],
            $params['activeonly']
        );

        $result = array();
        foreach ($prices as $price) {
            $result[] = array(
                'id' => $price->id,
                'categoryid' => $price->categoryid,
                'name' => $price->name,
                'price' => $price->price,
                'startdate' => $price->startdate,
                'enddate' => $price->enddate,
                'ispromotional' => $price->ispromotional,
                'isenrollmentfee' => $price->isenrollmentfee,
                'status' => $price->status,
                'installments' => $price->installments,
            );
        }

        return $result;
    }

    /**
     * Get category price returns
     *
     * @return external_multiple_structure
     */
    public static function get_category_prices_returns() {
        return new external_multiple_structure(
            new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'Price ID'),
                'categoryid' => new external_value(PARAM_INT, 'Category ID'),
                'name' => new external_value(PARAM_TEXT, 'Price name'),
                'price' => new external_value(PARAM_FLOAT, 'Price value'),
                'startdate' => new external_value(PARAM_INT, 'Start date timestamp'),
                'enddate' => new external_value(PARAM_INT, 'End date timestamp'),
                'ispromotional' => new external_value(PARAM_INT, 'Is promotional'),
                'isenrollmentfee' => new external_value(PARAM_INT, 'Is enrollment fee'),
                'status' => new external_value(PARAM_INT, 'Status'),
                'installments' => new external_value(PARAM_INT, 'Number of installments'),
            ))
        );
    }

    /**
     * Create category price parameters
     *
     * @return external_function_parameters
     */
    public static function create_category_price_parameters() {
        return new external_function_parameters(array(
            'categoryid' => new external_value(PARAM_INT, 'Category ID', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'Price name', VALUE_REQUIRED),
            'price' => new external_value(PARAM_FLOAT, 'Price value', VALUE_REQUIRED),
            'startdate' => new external_value(PARAM_INT, 'Start date timestamp', VALUE_DEFAULT, 0),
            'enddate' => new external_value(PARAM_INT, 'End date timestamp', VALUE_DEFAULT, 0),
            'ispromotional' => new external_value(PARAM_INT, 'Is promotional', VALUE_DEFAULT, 0),
            'isenrollmentfee' => new external_value(PARAM_INT, 'Is enrollment fee', VALUE_DEFAULT, 0),
            'status' => new external_value(PARAM_INT, 'Status', VALUE_DEFAULT, 1),
            'installments' => new external_value(PARAM_INT, 'Number of installments', VALUE_DEFAULT, 0),
        ));
    }

    /**
     * Create a new category price
     *
     * @param int $categoryid Category ID
     * @param string $name Price name
     * @param float $price Price value
     * @param int $startdate Start date
     * @param int $enddate End date
     * @param int $ispromotional Is promotional
     * @param int $isenrollmentfee Is enrollment fee
     * @param int $status Status
     * @param int $installments Installments
     * @return array Created price data
     */
    public static function create_category_price(
        $categoryid,
        $name,
        $price,
        $startdate = 0,
        $enddate = 0,
        $ispromotional = 0,
        $isenrollmentfee = 0,
        $status = 1,
        $installments = 0
    ) {
        $params = self::validate_parameters(self::create_category_price_parameters(), array(
            'categoryid' => $categoryid,
            'name' => $name,
            'price' => $price,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'ispromotional' => $ispromotional,
            'isenrollmentfee' => $isenrollmentfee,
            'status' => $status,
            'installments' => $installments,
        ));

        // Check capability
        $context = \context_system::instance();
        self::validate_context($context);

        if (!has_capability('local/localcustomadmin:manage', $context)) {
            throw new \moodle_exception('nopermission', 'local_localcustomadmin');
        }

        // Sanitize data
        $data = (object) $params;
        $data = \local_localcustomadmin\category_price_validator::sanitize($data);

        // Validate data
        $errors = \local_localcustomadmin\category_price_validator::validate_complete($data);
        if (!empty($errors)) {
            throw new \invalid_parameter_exception(implode(', ', $errors));
        }

        // Create price
        $id = \local_localcustomadmin\category_price_manager::create($data);

        // Get created price
        $created = \local_localcustomadmin\category_price_manager::get($id);

        return array(
            'id' => $created->id,
            'categoryid' => $created->categoryid,
            'name' => $created->name,
            'price' => $created->price,
            'startdate' => $created->startdate,
            'enddate' => $created->enddate,
            'ispromotional' => $created->ispromotional,
            'isenrollmentfee' => $created->isenrollmentfee,
            'status' => $created->status,
            'installments' => $created->installments,
            'success' => true,
            'message' => \get_string('pricecreatorsuccess', 'local_localcustomadmin'),
        );
    }

    /**
     * Create category price returns
     *
     * @return external_single_structure
     */
    public static function create_category_price_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Price ID'),
            'categoryid' => new external_value(PARAM_INT, 'Category ID'),
            'name' => new external_value(PARAM_TEXT, 'Price name'),
            'price' => new external_value(PARAM_FLOAT, 'Price value'),
            'startdate' => new external_value(PARAM_INT, 'Start date timestamp'),
            'enddate' => new external_value(PARAM_INT, 'End date timestamp'),
            'ispromotional' => new external_value(PARAM_INT, 'Is promotional'),
            'isenrollmentfee' => new external_value(PARAM_INT, 'Is enrollment fee'),
            'status' => new external_value(PARAM_INT, 'Status'),
            'installments' => new external_value(PARAM_INT, 'Number of installments'),
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'message' => new external_value(PARAM_TEXT, 'Operation message'),
        ));
    }

    /**
     * Update category price parameters
     *
     * @return external_function_parameters
     */
    public static function update_category_price_parameters() {
        return new external_function_parameters(array(
            'id' => new external_value(PARAM_INT, 'Price ID', VALUE_REQUIRED),
            'categoryid' => new external_value(PARAM_INT, 'Category ID', VALUE_OPTIONAL),
            'name' => new external_value(PARAM_TEXT, 'Price name', VALUE_OPTIONAL),
            'price' => new external_value(PARAM_FLOAT, 'Price value', VALUE_OPTIONAL),
            'startdate' => new external_value(PARAM_INT, 'Start date timestamp', VALUE_OPTIONAL),
            'enddate' => new external_value(PARAM_INT, 'End date timestamp', VALUE_OPTIONAL),
            'ispromotional' => new external_value(PARAM_INT, 'Is promotional', VALUE_OPTIONAL),
            'isenrollmentfee' => new external_value(PARAM_INT, 'Is enrollment fee', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_INT, 'Status', VALUE_OPTIONAL),
            'installments' => new external_value(PARAM_INT, 'Number of installments', VALUE_OPTIONAL),
        ));
    }

    /**
     * Update a category price
     *
     * @param int $id Price ID
     * @param int $categoryid Category ID (optional)
     * @param string $name Price name (optional)
     * @param float $price Price value (optional)
     * @param int $startdate Start date (optional)
     * @param int $enddate End date (optional)
     * @param int $ispromotional Is promotional (optional)
     * @param int $isenrollmentfee Is enrollment fee (optional)
     * @param int $status Status (optional)
     * @param int $installments Installments (optional)
     * @return array Updated price data
     */
    public static function update_category_price($id, $categoryid = null, $name = null, $price = null,
                                                 $startdate = null, $enddate = null, $ispromotional = null,
                                                 $isenrollmentfee = null, $status = null, $installments = null) {
        $params = self::validate_parameters(self::update_category_price_parameters(), array(
            'id' => $id,
            'categoryid' => $categoryid,
            'name' => $name,
            'price' => $price,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'ispromotional' => $ispromotional,
            'isenrollmentfee' => $isenrollmentfee,
            'status' => $status,
            'installments' => $installments,
        ));

        // Check capability
        $context = \context_system::instance();
        self::validate_context($context);

        if (!has_capability('local/localcustomadmin:manage', $context)) {
            throw new \moodle_exception('nopermission', 'local_localcustomadmin');
        }

        // Build update data
        $data = (object) array('id' => $params['id']);
        foreach ($params as $key => $value) {
            if ($key !== 'id' && $value !== null) {
                $data->$key = $value;
            }
        }

        // Sanitize data
        $data = \local_localcustomadmin\category_price_validator::sanitize($data);

        // Validate data if complete update
        if (!empty($data->categoryid) || !empty($data->name) || isset($data->price)) {
            $errors = \local_localcustomadmin\category_price_validator::validate_complete($data, $params['id']);
            if (!empty($errors)) {
                throw new \invalid_parameter_exception(implode(', ', $errors));
            }
        }

        // Update price
        \local_localcustomadmin\category_price_manager::update($params['id'], $data);

        // Get updated price
        $updated = \local_localcustomadmin\category_price_manager::get($params['id']);

        return array(
            'id' => $updated->id,
            'categoryid' => $updated->categoryid,
            'name' => $updated->name,
            'price' => $updated->price,
            'startdate' => $updated->startdate,
            'enddate' => $updated->enddate,
            'ispromotional' => $updated->ispromotional,
            'isenrollmentfee' => $updated->isenrollmentfee,
            'status' => $updated->status,
            'installments' => $updated->installments,
            'success' => true,
            'message' => \get_string('priceupdatesuccess', 'local_localcustomadmin'),
        );
    }

    /**
     * Update category price returns
     *
     * @return external_single_structure
     */
    public static function update_category_price_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Price ID'),
            'categoryid' => new external_value(PARAM_INT, 'Category ID'),
            'name' => new external_value(PARAM_TEXT, 'Price name'),
            'price' => new external_value(PARAM_FLOAT, 'Price value'),
            'startdate' => new external_value(PARAM_INT, 'Start date timestamp'),
            'enddate' => new external_value(PARAM_INT, 'End date timestamp'),
            'ispromotional' => new external_value(PARAM_INT, 'Is promotional'),
            'isenrollmentfee' => new external_value(PARAM_INT, 'Is enrollment fee'),
            'status' => new external_value(PARAM_INT, 'Status'),
            'installments' => new external_value(PARAM_INT, 'Number of installments'),
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'message' => new external_value(PARAM_TEXT, 'Operation message'),
        ));
    }

    /**
     * Delete category price parameters
     *
     * @return external_function_parameters
     */
    public static function delete_category_price_parameters() {
        return new external_function_parameters(array(
            'id' => new external_value(PARAM_INT, 'Price ID', VALUE_REQUIRED),
        ));
    }

    /**
     * Delete a category price
     *
     * @param int $id Price ID
     * @return array Result
     */
    public static function delete_category_price($id) {
        $params = self::validate_parameters(self::delete_category_price_parameters(), array(
            'id' => $id,
        ));

        // Check capability
        $context = \context_system::instance();
        self::validate_context($context);

        if (!has_capability('local/localcustomadmin:manage', $context)) {
            throw new \moodle_exception('nopermission', 'local_localcustomadmin');
        }

        // Delete price
        $result = \local_localcustomadmin\category_price_manager::delete($params['id']);

        return array(
            'success' => $result,
            'message' => $result ? \get_string('pricedeletesuccess', 'local_localcustomadmin')
                                 : \get_string('pricedeletefailed', 'local_localcustomadmin'),
        );
    }

    /**
     * Delete category price returns
     *
     * @return external_single_structure
     */
    public static function delete_category_price_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'message' => new external_value(PARAM_TEXT, 'Operation message'),
        ));
    }
}
