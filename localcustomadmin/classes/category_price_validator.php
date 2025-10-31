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
 * Category price validator class for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to validate category prices
 */
class category_price_validator {

    /**
     * Validate price data
     *
     * @param object $data Price data to validate
     * @return array Array of validation errors (empty if valid)
     */
    public static function validate($data) {
        $errors = array();

        // Check required fields
        if (empty($data->categoryid)) {
            $errors['categoryid'] = \get_string('errorcategoryid', 'local_localcustomadmin');
        }

        if (empty($data->name)) {
            $errors['name'] = \get_string('errorname', 'local_localcustomadmin');
        } else if (strlen($data->name) > 255) {
            $errors['name'] = \get_string('errornametoolong', 'local_localcustomadmin');
        }

        if (!isset($data->price)) {
            $errors['price'] = \get_string('errorprice', 'local_localcustomadmin');
        } else if (!is_numeric($data->price) || $data->price < 0) {
            $errors['price'] = \get_string('errorpriceinvalid', 'local_localcustomadmin');
        }

        // Validate dates if provided
        if (!empty($data->startdate)) {
            if (!is_numeric($data->startdate)) {
                $errors['startdate'] = \get_string('errorstartdateinvalid', 'local_localcustomadmin');
            }
        }

        if (!empty($data->enddate)) {
            if (!is_numeric($data->enddate)) {
                $errors['enddate'] = \get_string('errorenddateinvalid', 'local_localcustomadmin');
            }
        }

        // Validate date range
        if (!empty($data->startdate) && !empty($data->enddate)) {
            if ($data->startdate > $data->enddate) {
                $errors['daterange'] = \get_string('errordaterange', 'local_localcustomadmin');
            }
        }

        // Validate installments
        if (isset($data->installments)) {
            if (!is_numeric($data->installments) || $data->installments < 0 || $data->installments > 12) {
                $errors['installments'] = \get_string('errorinstallments', 'local_localcustomadmin');
            }
        }

        // Validate status
        if (isset($data->status)) {
            if (!in_array($data->status, array(0, 1))) {
                $errors['status'] = \get_string('errorstatus', 'local_localcustomadmin');
            }
        }

        // Validate promotional and enrollment fee flags
        if (isset($data->ispromotional)) {
            if (!in_array($data->ispromotional, array(0, 1))) {
                $errors['ispromotional'] = \get_string('errorispromotional', 'local_localcustomadmin');
            }
        }

        if (isset($data->isenrollmentfee)) {
            if (!in_array($data->isenrollmentfee, array(0, 1))) {
                $errors['isenrollmentfee'] = \get_string('errorisenrollmentfee', 'local_localcustomadmin');
            }
        }

        return $errors;
    }

    /**
     * Validate category exists
     *
     * @param int $categoryid Category ID
     * @return bool
     */
    public static function category_exists($categoryid) {
        global $DB;

        return $DB->record_exists('course_categories', array('id' => $categoryid));
    }

    /**
     * Check if price overlaps with existing prices
     *
     * @param int $categoryid Category ID
     * @param int $startdate Start date timestamp
     * @param int $enddate End date timestamp
     * @param int $excludeid Price ID to exclude from check (for updates)
     * @return bool True if overlap exists
     */
    public static function check_date_overlap($categoryid, $startdate, $enddate, $excludeid = 0) {
        global $DB;

        $sql = 'SELECT COUNT(*) FROM {local_customadmin_category_prices}
                WHERE categoryid = :categoryid
                AND status = 1
                AND id != :excludeid';

        // If enddate is 0, it means ongoing
        if ($enddate == 0) {
            $sql .= ' AND (startdate <= :startdate)';
            $params = array(
                'categoryid' => $categoryid,
                'excludeid' => $excludeid,
                'startdate' => $startdate
            );
        } else {
            // Check for overlap between date ranges
            $sql .= ' AND ((startdate <= :startdate AND (enddate = 0 OR enddate >= :startdate))
                        OR (startdate <= :enddate AND (enddate = 0 OR enddate >= :enddate))
                        OR (startdate >= :startdate AND enddate <= :enddate))';
            $params = array(
                'categoryid' => $categoryid,
                'excludeid' => $excludeid,
                'startdate' => $startdate,
                'enddate' => $enddate
            );
        }

        return $DB->count_records_sql($sql, $params) > 0;
    }

    /**
     * Sanitize price data
     *
     * @param object $data Price data
     * @return object Sanitized data
     */
    public static function sanitize($data) {
        if (isset($data->categoryid)) {
            $data->categoryid = (int) $data->categoryid;
        }

        if (isset($data->name)) {
            $data->name = trim($data->name);
            $data->name = substr($data->name, 0, 255);
        }

        if (isset($data->price)) {
            $data->price = (float) $data->price;
        }

        if (isset($data->startdate)) {
            $data->startdate = (int) $data->startdate;
        }

        if (isset($data->enddate)) {
            $data->enddate = (int) $data->enddate;
        }

        if (isset($data->status)) {
            $data->status = (int) $data->status;
        }

        if (isset($data->ispromotional)) {
            $data->ispromotional = (int) $data->ispromotional;
        }

        if (isset($data->isenrollmentfee)) {
            $data->isenrollmentfee = (int) $data->isenrollmentfee;
        }

        if (isset($data->scheduledtask)) {
            $data->scheduledtask = (int) $data->scheduledtask;
        }

        if (isset($data->installments)) {
            $data->installments = (int) $data->installments;
        }

        return $data;
    }

    /**
     * Validate complete price data for creation/update
     *
     * @param object $data Price data
     * @param int $excludeid Price ID to exclude from overlap check
     * @return array Validation errors (empty if valid)
     */
    public static function validate_complete($data, $excludeid = 0) {
        $errors = self::validate($data);

        // Check if category exists
        if (!empty($data->categoryid) && !self::category_exists($data->categoryid)) {
            $errors['categoryid'] = \get_string('errorcategorynotfound', 'local_localcustomadmin');
        }

        // Check for date overlaps if dates are provided
        if (!empty($data->startdate) && !empty($errors) === false) {
            if (self::check_date_overlap($data->categoryid, $data->startdate, $data->enddate ?? 0, $excludeid)) {
                $errors['daterange'] = \get_string('errordateoverlap', 'local_localcustomadmin');
            }
        }

        return $errors;
    }
}
