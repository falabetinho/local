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
 * Category pricing manager class for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage category prices
 */
class category_price_manager {

    /**
     * Create a new category price
     *
     * @param object $data Price data object
     * @return int The ID of the created record
     * @throws \InvalidArgumentException
     */
    public static function create($data) {
        global $DB;

        // Validate required fields
        if (empty($data->categoryid)) {
            throw new \InvalidArgumentException('Category ID is required');
        }
        if (empty($data->name)) {
            throw new \InvalidArgumentException('Price name is required');
        }
        if (!isset($data->price)) {
            throw new \InvalidArgumentException('Price value is required');
        }

        // Set default values
        if (!isset($data->status)) {
            $data->status = 1;
        }
        if (!isset($data->ispromotional)) {
            $data->ispromotional = 0;
        }
        if (!isset($data->isenrollmentfee)) {
            $data->isenrollmentfee = 0;
        }
        if (!isset($data->scheduledtask)) {
            $data->scheduledtask = 0;
        }
        if (!isset($data->installments)) {
            $data->installments = 0;
        }

        $data->timecreated = time();
        $data->timemodified = time();

        // Insert into database
        $id = $DB->insert_record('local_customadmin_category_prices', $data);

        return $id;
    }

    /**
     * Update an existing category price
     *
     * @param int $id Price ID
     * @param object $data Updated data
     * @return bool
     */
    public static function update($id, $data) {
        global $DB;

        $data->id = $id;
        $data->timemodified = time();

        return $DB->update_record('local_customadmin_category_prices', $data);
    }

    /**
     * Get a category price by ID
     *
     * @param int $id Price ID
     * @return object|false
     */
    public static function get($id) {
        global $DB;

        return $DB->get_record('local_customadmin_category_prices', array('id' => $id));
    }

    /**
     * Get all prices for a category
     *
     * @param int $categoryid Category ID
     * @param bool $activeonly Get only active prices
     * @return array
     */
    public static function get_category_prices($categoryid, $activeonly = true) {
        global $DB;

        $params = array('categoryid' => $categoryid);
        $sql = 'SELECT * FROM {local_customadmin_category_prices} WHERE categoryid = :categoryid';

        if ($activeonly) {
            $sql .= ' AND status = 1';
        }

        $sql .= ' ORDER BY startdate DESC';

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get active price for a category at a specific time
     *
     * @param int $categoryid Category ID
     * @param int $timestamp Timestamp to check (default: now)
     * @return object|null
     */
    public static function get_active_price($categoryid, $timestamp = null) {
        global $DB;

        if ($timestamp === null) {
            $timestamp = time();
        }

        $params = array(
            'categoryid' => $categoryid,
            'status' => 1,
            'timestamp' => $timestamp,
            'timestamp2' => $timestamp
        );

        $sql = 'SELECT * FROM {local_customadmin_category_prices}
                WHERE categoryid = :categoryid
                AND status = :status
                AND startdate <= :timestamp
                AND (enddate = 0 OR enddate >= :timestamp2)
                ORDER BY startdate DESC
                LIMIT 1';

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Delete a category price
     *
     * @param int $id Price ID
     * @return bool
     */
    public static function delete($id) {
        global $DB;

        return $DB->delete_records('local_customadmin_category_prices', array('id' => $id));
    }

    /**
     * Delete all prices for a category
     *
     * @param int $categoryid Category ID
     * @return bool
     */
    public static function delete_category_prices($categoryid) {
        global $DB;

        return $DB->delete_records('local_customadmin_category_prices', array('categoryid' => $categoryid));
    }

    /**
     * Get prices with filters
     *
     * @param array $filters Array of filter conditions
     * @param int $limitfrom Start from record
     * @param int $limitnum Number of records to return
     * @return array
     */
    public static function get_prices($filters = array(), $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $sql = 'SELECT * FROM {local_customadmin_category_prices} WHERE 1=1';
        $params = array();

        // Apply filters
        if (!empty($filters['categoryid'])) {
            $sql .= ' AND categoryid = :categoryid';
            $params['categoryid'] = $filters['categoryid'];
        }

        if (isset($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['ispromotional'])) {
            $sql .= ' AND ispromotional = :ispromotional';
            $params['ispromotional'] = $filters['ispromotional'];
        }

        if (isset($filters['isenrollmentfee'])) {
            $sql .= ' AND isenrollmentfee = :isenrollmentfee';
            $params['isenrollmentfee'] = $filters['isenrollmentfee'];
        }

        $sql .= ' ORDER BY timemodified DESC';

        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Count prices matching filters
     *
     * @param array $filters Array of filter conditions
     * @return int
     */
    public static function count_prices($filters = array()) {
        global $DB;

        $sql = 'SELECT COUNT(*) FROM {local_customadmin_category_prices} WHERE 1=1';
        $params = array();

        // Apply filters
        if (!empty($filters['categoryid'])) {
            $sql .= ' AND categoryid = :categoryid';
            $params['categoryid'] = $filters['categoryid'];
        }

        if (isset($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['ispromotional'])) {
            $sql .= ' AND ispromotional = :ispromotional';
            $params['ispromotional'] = $filters['ispromotional'];
        }

        if (isset($filters['isenrollmentfee'])) {
            $sql .= ' AND isenrollmentfee = :isenrollmentfee';
            $params['isenrollmentfee'] = $filters['isenrollmentfee'];
        }

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Enable a price
     *
     * @param int $id Price ID
     * @return bool
     */
    public static function enable($id) {
        global $DB;

        return $DB->update_record('local_customadmin_category_prices', (object) array(
            'id' => $id,
            'status' => 1,
            'timemodified' => time()
        ));
    }

    /**
     * Disable a price
     *
     * @param int $id Price ID
     * @return bool
     */
    public static function disable($id) {
        global $DB;

        return $DB->update_record('local_customadmin_category_prices', (object) array(
            'id' => $id,
            'status' => 0,
            'timemodified' => time()
        ));
    }

    /**
     * Get price statistics for a category
     *
     * @param int $categoryid Category ID
     * @return object Statistics object
     */
    public static function get_category_stats($categoryid) {
        global $DB;

        $params = array('categoryid' => $categoryid);

        // Count total prices
        $total = $DB->count_records('local_customadmin_category_prices', array('categoryid' => $categoryid));

        // Count active prices
        $active = $DB->count_records('local_customadmin_category_prices', 
            array('categoryid' => $categoryid, 'status' => 1));

        // Count promotional prices
        $promotional = $DB->count_records('local_customadmin_category_prices', 
            array('categoryid' => $categoryid, 'ispromotional' => 1, 'status' => 1));

        // Get average price
        $sql = 'SELECT AVG(price) as average FROM {local_customadmin_category_prices}
                WHERE categoryid = :categoryid AND status = 1';
        $avg_result = $DB->get_record_sql($sql, $params);

        // Get min/max price
        $sql = 'SELECT MIN(price) as minprice, MAX(price) as maxprice 
                FROM {local_customadmin_category_prices}
                WHERE categoryid = :categoryid AND status = 1';
        $minmax_result = $DB->get_record_sql($sql, $params);

        return (object) array(
            'categoryid' => $categoryid,
            'total' => $total,
            'active' => $active,
            'promotional' => $promotional,
            'average' => $avg_result ? $avg_result->average : 0,
            'minprice' => $minmax_result ? $minmax_result->minprice : 0,
            'maxprice' => $minmax_result ? $minmax_result->maxprice : 0
        );
    }
}
