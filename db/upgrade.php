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
 * This file keeps track of upgrades to the wallet enrolment plugin
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgarding database.
 *
 * @param int $oldversion the old version of this plugin.
 * @return bool
 */
function xmldb_enrol_wallet_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023042915) {

        // Define table enrol_wallet_awards and its fields.
        $table = new xmldb_table('enrol_wallet_awards');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('grade', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false);
        $table->add_field('maxgrade', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false);
        $table->add_field('percent', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false);
        $table->add_field('amount', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Conditionally launch create table responsive.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_wallet_transactions and its fields.
        $table = new xmldb_table('enrol_wallet_transactions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('type', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, false);
        $table->add_field('amount', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false, 0);
        $table->add_field('descripe', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, false);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Conditionally launch create table responsive.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // ...enrol_wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2023042915, 'enrol', 'wallet');
    }

    if ($oldversion < 2023050820) {
        $table = new xmldb_table('enrol_wallet_transactions');
        $field = new xmldb_field('balbefore', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, null, 0, 'amount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('balance', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, null, 0, 'before');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // ...enrol_wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2023050820, 'enrol', 'wallet');
    }

    if ($oldversion < 2023051416) {
        // Define table enrol_wallet_coupons and its fields.
        $table = new xmldb_table('enrol_wallet_coupons');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('code', XMLDB_TYPE_CHAR, 20, null, XMLDB_NOTNULL, false);
        $table->add_field('type', XMLDB_TYPE_CHAR, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('value', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false, 0);
        $table->add_field('maxusage', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('usetimes', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('validfrom', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('validto', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('lastuse', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('code', XMLDB_KEY_UNIQUE, ['code']);
        // Conditionally launch create table responsive.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_wallet_coupons and its fields.
        $table = new xmldb_table('enrol_wallet_coupons_usage');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('code', XMLDB_TYPE_CHAR, 20, null, XMLDB_NOTNULL, false);
        $table->add_field('type', XMLDB_TYPE_CHAR, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('value', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false, 0);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('timeused', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Conditionally launch create table responsive.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // ...enrol_wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2023051416, 'enrol', 'wallet');
    }
    if ($oldversion < 2023060608) {
        $table = new xmldb_table('enrol_wallet_transactions');
        $field = new xmldb_field('norefund', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, null, 0, 'balance');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ...enrol_wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2023060608, 'enrol', 'wallet');
    }
    return true;
}
