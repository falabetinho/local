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
 * Enrolment price manager class for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage course enrollment prices imported from category prices
 */
class enrolment_price_manager {

    /**
     * Import category prices to course enrollment methods
     * 
     * @param int $courseid Course ID
     * @param array $priceids Array of price IDs to import (from local_customadmin_category_prices)
     * @return array Array with success status and created enrol instances
     * @throws \Exception
     */
    public static function import_category_prices_to_course($courseid, $priceids) {
        global $DB;

        if (empty($priceids)) {
            throw new \Exception('No price IDs provided');
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $results = [
            'success' => true,
            'created' => [],
            'errors' => []
        ];

        foreach ($priceids as $priceid) {
            try {
                // Get the category price
                $price = $DB->get_record('local_customadmin_category_prices', ['id' => $priceid], '*', MUST_EXIST);
                
                // Verify the price belongs to the course's category
                if ($price->categoryid != $course->category) {
                    $results['errors'][] = "Price ID {$priceid} does not belong to course category";
                    continue;
                }

                // Create enrollment instance with this price
                $enrolid = self::create_enrol_instance_from_price($courseid, $price);
                
                if ($enrolid) {
                    $results['created'][] = $enrolid;
                } else {
                    $results['errors'][] = "Failed to create enrollment for price ID {$priceid}";
                }
                
            } catch (\Exception $e) {
                $results['errors'][] = $e->getMessage();
                $results['success'] = false;
            }
        }

        return $results;
    }

    /**
     * Create an enrollment instance from a category price
     * 
     * @param int $courseid Course ID
     * @param object $price Price object from local_customadmin_category_prices
     * @return int|false Enrollment instance ID or false on failure
     */
    private static function create_enrol_instance_from_price($courseid, $price) {
        global $DB;

        // Always use customstatus enrollment type
        $enroltype = 'customstatus';
        $enrolplugin = enrol_get_plugin('customstatus');
        
        if (!$enrolplugin) {
            debugging('Custom Status enrol plugin not found or not enabled', DEBUG_DEVELOPER);
            return false;
        }

        // Prepare enrollment data
        $enroldata = new \stdClass();
        $enroldata->courseid = $courseid;
        $enroldata->enrol = $enroltype;
        
        // IMPORTANT: Moodle enrol status is inverted
        // status = 0 means ENABLED (ENROL_INSTANCE_ENABLED)
        // status = 1 means DISABLED (ENROL_INSTANCE_DISABLED)
        // Our price table uses: status = 1 (active), status = 0 (inactive)
        // So we need to invert: price active (1) -> enrol enabled (0), price inactive (0) -> enrol disabled (1)
        $enroldata->status = $price->status == 1 ? ENROL_INSTANCE_ENABLED : ENROL_INSTANCE_DISABLED;
        
        $enroldata->name = $price->name;
        $enroldata->cost = $price->price;
        $enroldata->currency = 'BRL'; // Brazilian Real
        $enroldata->roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MULTIPLE) ?? 5;
        $enroldata->sortorder = 0;
        
        // Use customint1 to reference the category price ID
        $enroldata->customint1 = $price->id;
        
        // Use other custom fields for additional price information
        $enroldata->customint2 = $price->ispromotional;
        $enroldata->customint3 = $price->isenrollmentfee;
        $enroldata->customint4 = $price->installments;
        $enroldata->customint5 = $price->scheduledtask;
        
        // Store dates
        $enroldata->enrolstartdate = $price->startdate;
        $enroldata->enrolenddate = $price->enddate;
        
        $enroldata->timecreated = time();
        $enroldata->timemodified = time();

        // Insert the enrollment instance
        $enrolid = $DB->insert_record('enrol', $enroldata);
        
        // Log for debugging
        debugging('Created customstatus enrol instance: ID=' . $enrolid . ', customint1=' . $price->id, DEBUG_DEVELOPER);
        
        return $enrolid;
    }

    /**
     * Get all enrollment instances for a course that are linked to category prices
     * 
     * @param int $courseid Course ID
     * @return array Array of enrollment instances with linked price data
     */
    public static function get_course_price_enrolments($courseid) {
        global $DB;

        $sql = "SELECT e.*, p.name as price_name, p.price as original_price, 
                       p.categoryid, p.ispromotional, p.installments
                FROM {enrol} e
                LEFT JOIN {local_customadmin_category_prices} p ON e.customint1 = p.id
                WHERE e.courseid = :courseid 
                AND e.customint1 IS NOT NULL 
                AND e.customint1 > 0
                ORDER BY e.sortorder, e.id";

        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Update enrollment instance when category price changes
     * 
     * @param int $priceid Price ID from local_customadmin_category_prices
     * @return int Number of enrollment instances updated
     */
    public static function update_enrolments_from_price($priceid) {
        global $DB;

        $price = $DB->get_record('local_customadmin_category_prices', ['id' => $priceid], '*', MUST_EXIST);
        
        // Find all enrollment instances linked to this price
        $enrols = $DB->get_records('enrol', ['customint1' => $priceid]);
        
        $updated = 0;
        foreach ($enrols as $enrol) {
            $enrol->name = $price->name;
            $enrol->cost = $price->price;
            
            // Invert status: price active (1) -> enrol enabled (0), price inactive (0) -> enrol disabled (1)
            $enrol->status = $price->status == 1 ? ENROL_INSTANCE_ENABLED : ENROL_INSTANCE_DISABLED;
            
            $enrol->customint2 = $price->ispromotional;
            $enrol->customint3 = $price->isenrollmentfee;
            $enrol->customint4 = $price->installments;
            $enrol->customint5 = $price->scheduledtask;
            $enrol->enrolstartdate = $price->startdate;
            $enrol->enrolenddate = $price->enddate;
            $enrol->timemodified = time();
            
            if ($DB->update_record('enrol', $enrol)) {
                $updated++;
            }
        }
        
        return $updated;
    }

    /**
     * Remove link between enrollment instance and category price
     * 
     * @param int $enrolid Enrollment instance ID
     * @return bool Success status
     */
    public static function unlink_enrolment_from_price($enrolid) {
        global $DB;

        $enrol = $DB->get_record('enrol', ['id' => $enrolid], '*', MUST_EXIST);
        
        // Clear the reference to category price
        $enrol->customint1 = 0;
        $enrol->timemodified = time();
        
        return $DB->update_record('enrol', $enrol);
    }

    /**
     * Get category price information from enrollment instance
     * 
     * @param int $enrolid Enrollment instance ID
     * @return object|null Price object or null if not linked
     */
    public static function get_price_from_enrolment($enrolid) {
        global $DB;

        $enrol = $DB->get_record('enrol', ['id' => $enrolid], '*', MUST_EXIST);
        
        if (empty($enrol->customint1)) {
            return null;
        }
        
        return $DB->get_record('local_customadmin_category_prices', ['id' => $enrol->customint1]);
    }

    /**
     * Get available category prices for a course
     * 
     * @param int $courseid Course ID
     * @return array Array of available prices that can be imported
     */
    public static function get_available_prices_for_course($courseid) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        
        // Get all active prices for the course's category
        $sql = "SELECT p.*
                FROM {local_customadmin_category_prices} p
                WHERE p.categoryid = :categoryid
                AND p.status = 1
                ORDER BY p.ispromotional DESC, p.price ASC";
        
        return $DB->get_records_sql($sql, ['categoryid' => $course->category]);
    }

    /**
     * Check if a price is already imported to a course
     * 
     * @param int $courseid Course ID
     * @param int $priceid Price ID
     * @return bool True if already imported
     */
    public static function is_price_imported($courseid, $priceid) {
        global $DB;

        return $DB->record_exists('enrol', [
            'courseid' => $courseid,
            'customint1' => $priceid
        ]);
    }

    /**
     * Get statistics about price imports
     * 
     * @param int $priceid Price ID
     * @return array Statistics array
     */
    public static function get_price_import_stats($priceid) {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT e.courseid) as course_count,
                       COUNT(ue.id) as total_enrolments,
                       SUM(e.cost) as total_revenue
                FROM {enrol} e
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.customint1 = :priceid";
        
        return $DB->get_record_sql($sql, ['priceid' => $priceid]);
    }
}
