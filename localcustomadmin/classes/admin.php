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
 * Admin utility class for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Administrative utilities class.
 *
 * This class provides utility methods for administrative operations
 * following PSR-12 coding standards.
 *
 * @package local_localcustomadmin
 */
class Admin
{
    /**
     * Get system statistics.
     *
     * @return array Array of system statistics
     */
    public static function getSystemStatistics(): array
    {
        global $DB;

        return [
            'totalusers' => $DB->count_records('user', ['deleted' => 0]),
            'totalcourses' => $DB->count_records('course') - 1, // Exclude site course.
            'totalcategories' => $DB->count_records('course_categories'),
            'onlineusers' => self::getOnlineUsersCount(),
        ];
    }

    /**
     * Get count of online users.
     *
     * @param int $minutes Number of minutes to look back for "online" status
     * @return int Number of online users
     */
    public static function getOnlineUsersCount(int $minutes = 5): int
    {
        global $DB;

        $threshold = time() - ($minutes * 60);
        return $DB->count_records_sql(
            "SELECT COUNT(*) FROM {user} WHERE lastaccess > ? AND deleted = 0",
            [$threshold]
        );
    }

    /**
     * Check if user has administrative permissions.
     *
     * @param int $userid User ID to check
     * @return bool True if user has admin permissions
     */
    public static function hasAdminPermissions(int $userid = null): bool
    {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $context = \context_system::instance();
        return has_capability('local/localcustomadmin:manage', $context, $userid);
    }

    /**
     * Validate plugin configuration.
     *
     * @return array Array of validation results
     */
    public static function validateConfiguration(): array
    {
        $results = [];

        // Check required capabilities.
        $context = \context_system::instance();
        $results['capabilities'] = [
            'view' => get_config('local_localcustomadmin', 'view_capability') !== false,
            'manage' => get_config('local_localcustomadmin', 'manage_capability') !== false,
        ];

        // Check plugin status.
        $results['plugin_enabled'] = !empty(get_config('local_localcustomadmin', 'enabled'));

        return $results;
    }

    /**
     * Get plugin version information.
     *
     * @return \stdClass Plugin version object
     */
    public static function getPluginVersion(): \stdClass
    {
        $plugin = new \stdClass();
        
        // This would normally be loaded from version.php.
        $plugin->version = 2025101300;
        $plugin->requires = 2023100900;
        $plugin->component = 'local_localcustomadmin';
        $plugin->maturity = MATURITY_STABLE;
        $plugin->release = 'v1.0';

        return $plugin;
    }
}