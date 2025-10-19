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
 * Course manager class for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage course enrollments and pricing
 */
class course_manager {

    /**
     * Initialize course enrollments with pricing from category prices table
     * Creates a fee-based enrollment method if one doesn't exist
     * and initializes it with the active category price
     *
     * @param int $courseid Course ID
     * @return bool Success status
     * @throws \Exception
     */
    public static function initialize_course_enrolments($courseid) {
        global $DB;

        $course = get_course($courseid);
        if (!$course) {
            throw new \Exception("Course not found: $courseid");
        }

        // Get active pricing for the course's category
        $activeprice = category_price_manager::get_active_price($course->category);

        if (!$activeprice) {
            // No active price for this category, just ensure a manual enrollment exists
            return self::ensure_manual_enrolment($courseid);
        }

        // Check if a fee enrollment already exists
        $feeenrol = self::get_or_create_fee_enrolment($courseid);

        if ($feeenrol) {
            // Update the fee enrollment with the active price
            self::update_fee_enrolment($feeenrol->id, $activeprice);
        }

        // Ensure manual enrollment also exists (for free access)
        self::ensure_manual_enrolment($courseid);

        return true;
    }

    /**
     * Get or create a fee-based enrollment instance for the course
     *
     * @param int $courseid Course ID
     * @return object|null The enrollment instance or null if cannot be created
     */
    private static function get_or_create_fee_enrolment($courseid) {
        global $DB;

        // Try to get existing fee enrollment
        $feeenrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'fee'), '*', IGNORE_MULTIPLE);

        if ($feeenrol) {
            return $feeenrol;
        }

        // Try to create one using native Moodle enrol_fee plugin
        $enrolfee = enrol_get_plugin('fee');
        if (!$enrolfee) {
            // Fee enrollment plugin not enabled
            return null;
        }

        // Create new fee enrollment
        $data = new \stdClass();
        $data->enrol = 'fee';
        $data->status = ENROL_INSTANCE_ENABLED;
        $data->courseid = $courseid;
        $data->cost = 0; // Will be updated with actual price
        $data->currency = 'USD';
        $data->roleid = $DB->get_field('role', 'id', array('shortname' => 'student'), IGNORE_MULTIPLE) ?? 5;

        $id = $DB->insert_record('enrol', $data);

        return $DB->get_record('enrol', array('id' => $id));
    }

    /**
     * Ensure manual enrollment exists for a course (free access)
     *
     * @param int $courseid Course ID
     * @return object|null The enrollment instance
     */
    private static function ensure_manual_enrolment($courseid) {
        global $DB;

        // Try to get existing manual enrollment
        $manualenrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'), '*', IGNORE_MULTIPLE);

        if ($manualenrol) {
            return $manualenrol;
        }

        // Get manual enrol plugin
        $enrolmanual = enrol_get_plugin('manual');
        if (!$enrolmanual) {
            return null;
        }

        // Create new manual enrollment
        $data = new \stdClass();
        $data->enrol = 'manual';
        $data->status = ENROL_INSTANCE_ENABLED;
        $data->courseid = $courseid;
        $data->roleid = $DB->get_field('role', 'id', array('shortname' => 'student'), IGNORE_MULTIPLE) ?? 5;

        $id = $DB->insert_record('enrol', $data);

        return $DB->get_record('enrol', array('id' => $id));
    }

    /**
     * Update fee enrollment with pricing information
     *
     * @param int $enrolid Enrollment instance ID
     * @param object $price Price object from category_prices table
     * @return bool
     */
    private static function update_fee_enrolment($enrolid, $price) {
        global $DB;

        $enrol = new \stdClass();
        $enrol->id = $enrolid;
        $enrol->cost = $price->price;
        $enrol->timemodified = time();

        return $DB->update_record('enrol', $enrol);
    }

    /**
     * Get all active enrollments for a course
     *
     * @param int $courseid Course ID
     * @return array Array of enrollment instances
     */
    public static function get_course_enrolments($courseid) {
        return enrol_get_instances($courseid, true);
    }

    /**
     * Get enrollment method statistics for a course
     *
     * @param int $courseid Course ID
     * @return array Statistics array
     */
    public static function get_enrolment_stats($courseid) {
        global $DB;

        $stats = array(
            'total' => 0,
            'by_method' => array()
        );

        $enrolments = self::get_course_enrolments($courseid);

        foreach ($enrolments as $enrol) {
            $count = $DB->count_records('user_enrolments', array('enrolid' => $enrol->id));
            $stats['total'] += $count;
            $stats['by_method'][$enrol->enrol] = $count;
        }

        return $stats;
    }
}
