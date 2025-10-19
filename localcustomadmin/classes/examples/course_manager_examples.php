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
 * Example usage of course_manager class for Local Custom Admin plugin.
 *
 * This file demonstrates how to use the course_manager class to initialize
 * course enrollments with pricing information from the category_prices table.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

/**
 * Example Class
 *
 * Usage Examples for course_manager
 */
class course_manager_examples {

    /**
     * Example 1: Initialize a newly created course with pricing
     *
     * When a new course is created via the form, the system automatically:
     *
     * 1. Fetches active prices from mdl_local_customadmin_category_prices
     *    WHERE categoryid = course.category AND status = 1
     *
     * 2. Creates or updates a "fee" enrollment with the active price
     *
     * 3. Ensures a "manual" enrollment exists for free access
     *
     * Example usage:
     * ```php
     * // Create course data
     * $coursedata = new \stdClass();
     * $coursedata->fullname = 'My Course';
     * $coursedata->shortname = 'mycourse';
     * $coursedata->category = 2; // Category with active prices
     * $coursedata->visible = 1;
     *
     * // Create course using Moodle function
     * $course = create_course($coursedata);
     *
     * // Initialize enrollments with pricing
     * course_manager::initialize_course_enrolments($course->id);
     * ```
     *
     * Result:
     * - If category 2 has active price of $50, fee enrollment is created with cost=50
     * - Manual enrollment is created for admins/teachers
     * - Course is ready for student enrollments
     */
    public static function example_create_course() {
        // Example implementation shown above
    }

    /**
     * Example 2: Get course enrollment statistics
     *
     * ```php
     * $courseid = 5;
     *
     * // Get statistics for the course
     * $stats = course_manager::get_enrolment_stats($courseid);
     *
     * // Returns:
     * // [
     * //     'total' => 45,
     * //     'by_method' => [
     * //         'fee' => 40,
     * //         'manual' => 5
     * //     ]
     * // ]
     * ```
     */
    public static function example_get_stats() {
        // Example implementation shown above
    }

    /**
     * Example 3: Get all active enrollments for a course
     *
     * ```php
     * $courseid = 5;
     *
     * // Get all active enrollment instances
     * $enrolments = course_manager::get_course_enrolments($courseid);
     *
     * // Each enrolment contains:
     * // - id: enrollment instance ID
     * // - courseid: course ID
     * // - enrol: enrollment type (fee, manual, etc)
     * // - cost: price for fee enrollments
     * // - status: ENROL_INSTANCE_ENABLED or ENROL_INSTANCE_DISABLED
     * ```
     */
    public static function example_get_enrolments() {
        // Example implementation shown above
    }

    /**
     * Example 4: Integration with category pricing
     *
     * The system automatically synchronizes course enrollments with category prices:
     *
     * Scenario:
     * - Category "Premium Courses" has price list:
     *   * Jan-Mar: $100 (promotional)
     *   * Apr-Dec: $150 (regular)
     *
     * - Create course in "Premium Courses":
     *   * If today is Feb 15: fee enrollment gets $100
     *   * If today is May 20: fee enrollment gets $150
     *
     * - Change active price:
     *   * When new price becomes active, call:
     *     course_manager::initialize_course_enrolments($courseid)
     *   * Fee enrollment price is updated automatically
     */
    public static function example_pricing_sync() {
        // Example implementation shown above
    }
}
