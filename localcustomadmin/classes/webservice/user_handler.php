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
 * User webservice handler for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin\webservice;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_exception;

/**
 * User handler webservice class
 */
class user_handler extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function reset_password_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'The ID of the user to reset password', VALUE_REQUIRED),
                'password' => new external_value(PARAM_RAW, 'The new password', VALUE_REQUIRED)
            )
        );
    }

    /**
     * Reset user password via webservice
     * @param int $userid User ID
     * @param string $password New password
     * @return array
     */
    public static function reset_password($userid, $password) {
        global $DB, $USER;

        // Validate parameters
        $params = self::validate_parameters(
            self::reset_password_parameters(),
            array(
                'userid' => $userid,
                'password' => $password
            )
        );

        // Get the context
        $context = \context_system::instance();
        self::validate_context($context);

        // Check capability
        if (!has_capability('local/localcustomadmin:manage', $context)) {
            throw new moodle_exception('nopermission', 'local_localcustomadmin');
        }

        // Get user object
        $user = $DB->get_record('user', array('id' => $params['userid']), '*', MUST_EXIST);

        // Validate password
        $errmsg = '';
        if (!check_password_policy($params['password'], $errmsg)) {
            throw new moodle_exception('passwordpolicyerror', 'local_localcustomadmin', '', $errmsg);
        }

        // Validate password is not empty
        if (empty($params['password'])) {
            throw new moodle_exception('passwordempty', 'local_localcustomadmin');
        }

        // Update user password
        $user->password = hash_internal_user_password($params['password']);

        // Update in database
        $DB->update_record('user', $user);

        // Trigger event
        $event = \core\event\user_password_updated::create(array(
            'relateduserid' => $user->id,
            'context' => $context,
            'userid' => $USER->id,
            'other' => array('forgottenreset' => 0)
        ));
        $event->trigger();

        return array(
            'success' => true,
            'message' => 'Password updated successfully'
        );
    }

    /**
     * Returns description of the webservice return value
     * @return external_single_structure
     */
    public static function reset_password_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Whether the password was reset successfully'),
                'message' => new external_value(PARAM_TEXT, 'The returned message')
            )
        );
    }
}
