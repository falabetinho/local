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
 * Upgrade script for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin database
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_local_localcustomadmin_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table local_customadmin_category_prices to be created.
    if ($oldversion < 2025101802) {

        // Define table local_customadmin_category_prices to be created.
        $table = new xmldb_table('local_customadmin_category_prices');

        // Adding fields to table local_customadmin_category_prices.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('price', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ispromotional', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('isenrollmentfee', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('scheduledtask', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('installments', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_customadmin_category_prices.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('categoryid', XMLDB_KEY_FOREIGN, array('categoryid'), 'course_categories', array('id'));

        // Adding indexes to table local_customadmin_category_prices.
        $table->add_index('categoryid_idx', XMLDB_INDEX_NOTUNIQUE, array('categoryid'));
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, array('status'));

        // Conditionally launch create table for local_customadmin_category_prices.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Localcustomadmin savepoint reached.
        upgrade_plugin_savepoint(true, 2025101802, 'local', 'localcustomadmin');
    }

    // Add WordPress mapping table.
    if ($oldversion < 2025102202) {

        // Define table local_customadmin_wp_mapping to be created.
        $table = new xmldb_table('local_customadmin_wp_mapping');

        // Adding fields to table local_customadmin_wp_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('moodle_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('moodle_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('wordpress_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('wordpress_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('wordpress_taxonomy', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('wordpress_post_type', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('sync_status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('last_synced', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('sync_error', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_customadmin_wp_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table local_customadmin_wp_mapping.
        $table->add_index('moodle_item_idx', XMLDB_INDEX_UNIQUE, array('moodle_type', 'moodle_id'));
        $table->add_index('wordpress_item_idx', XMLDB_INDEX_NOTUNIQUE, array('wordpress_type', 'wordpress_id'));
        $table->add_index('sync_status_idx', XMLDB_INDEX_NOTUNIQUE, array('sync_status'));
        $table->add_index('moodle_type_idx', XMLDB_INDEX_NOTUNIQUE, array('moodle_type'));

        // Conditionally launch create table for local_customadmin_wp_mapping.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Localcustomadmin savepoint reached.
        upgrade_plugin_savepoint(true, 2025102202, 'local', 'localcustomadmin');
    }

    // Force recreation of WordPress mapping table if needed.
    if ($oldversion < 2025102203) {
        
        // Get the table
        $table = new xmldb_table('local_customadmin_wp_mapping');
        
        // Drop and recreate if exists to ensure clean state
        if ($dbman->table_exists($table)) {
            // Clear any error mappings
            $DB->delete_records_select('local_customadmin_wp_mapping', 'sync_status = ? OR wordpress_id = 0', ['error']);
        }

        // Localcustomadmin savepoint reached.
        upgrade_plugin_savepoint(true, 2025102203, 'local', 'localcustomadmin');
    }

    return true;
}
