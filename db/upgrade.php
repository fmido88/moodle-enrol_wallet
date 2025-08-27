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
 * Upgrading database.
 *
 * @param int $oldversion the old version of this plugin.
 * @return bool
 */
function xmldb_enrol_wallet_upgrade($oldversion) {
    global $DB;

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
        $table->add_field('maxusage', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
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

    if ($oldversion < 2023071512) {
        $table = new xmldb_table('enrol_wallet_items');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('cost', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false, 0);
        $table->add_field('currency', XMLDB_TYPE_CHAR, 3, null, XMLDB_NOTNULL, false, 'EGP');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // ...enrol_wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2023071512, 'enrol', 'wallet');
    }

    if ($oldversion < 2023071707) {
        $table = new xmldb_table('enrol_wallet_cond_discount');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('cond', XMLDB_TYPE_FLOAT, '10,5', null, XMLDB_NOTNULL, false);
        $table->add_field('percent', XMLDB_TYPE_INTEGER, 3, null, XMLDB_NOTNULL, false);
        $table->add_field('timefrom', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('timeto', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023071707, 'enrol', 'wallet');
    }

    if ($oldversion < 2023072509) {
        $table = new xmldb_table('enrol_wallet_referral');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('code', XMLDB_TYPE_CHAR, 30, null, null, false);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 10, null, null, false);
        $table->add_field('usetimes', XMLDB_TYPE_INTEGER, 10, null, null, false);
        $table->add_field('users', XMLDB_TYPE_TEXT, 3000, null, null, false);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('code', XMLDB_KEY_UNIQUE, ['code']);
        $table->add_key('userid', XMLDB_KEY_UNIQUE, ['userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('enrol_wallet_hold_gift');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, true);
        $table->add_field('referrer', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false);
        $table->add_field('referred', XMLDB_TYPE_CHAR, 225, null, XMLDB_NOTNULL, false);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, 10, null, null, false);
        $table->add_field('amount', XMLDB_TYPE_NUMBER, '10,5', null, XMLDB_NOTNULL, false);
        $table->add_field('released', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('referred', XMLDB_KEY_UNIQUE, ['referred']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2023072509, 'enrol', 'wallet');
    }

    if ($oldversion < 2023080814) {
        $table = new xmldb_table('enrol_wallet_coupons');
        $field = new xmldb_field('category', XMLDB_TYPE_INTEGER, 10, null, null, false, null, 'value');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('courses', XMLDB_TYPE_TEXT, 3000, null, null, false, null, 'category');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2023080814, 'enrol', 'wallet');
    }

    if ($oldversion < 2023091617) {
        $table = new xmldb_table('enrol_wallet_items');
        $field = new xmldb_field('instanceid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0, 'userid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // ...enrol_wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2023091617, 'enrol', 'wallet');
    }

    if ($oldversion < 2023102303) {
        $table = new xmldb_table('enrol_wallet_coupons');
        $field = new xmldb_field('maxperuser', XMLDB_TYPE_INTEGER, 10, null, null, false, 0, 'maxusage');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2023102303, 'enrol', 'wallet');
    }

    if ($oldversion < 2023112606) {
        $table = new xmldb_table('enrol_wallet_items');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0, 'instanceid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2023112606, 'enrol', 'wallet');
    }

    if ($oldversion < 2024020300) {

        // Define table enrol_wallet_balance to be created.
        $table = new xmldb_table('enrol_wallet_balance');

        // Adding fields to table enrol_wallet_balance.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('refundable', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('nonrefundable', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '0');
        $table->add_field('freegift', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '0');
        $table->add_field('cat_balance', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table enrol_wallet_balance.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('kuserid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);

        // Conditionally launch create table for enrol_wallet_balance.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field category to be added to enrol_wallet_cond_discount.
        $table = new xmldb_table('enrol_wallet_cond_discount');
        $field = new xmldb_field('category', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'percent');

        // Conditionally launch add field category.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field usermodified to be added to enrol_wallet_cond_discount.
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timeto');

        // Conditionally launch add field usermodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field timecreated to be added to enrol_wallet_cond_discount.
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'usermodified');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field timemodified to be added to enrol_wallet_cond_discount.
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field category to be added to enrol_wallet_transactions.
        $table = new xmldb_table('enrol_wallet_transactions');
        $field = new xmldb_field('category', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'norefund');

        // Conditionally launch add field category.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field category to be added to enrol_wallet_items.
        $table = new xmldb_table('enrol_wallet_items');
        $field = new xmldb_field('category', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'instanceid');

        // Conditionally launch add field category.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2024020300, 'enrol', 'wallet');
    }

    if ($oldversion < 2024022500) {

        // Define field bundle to be added to enrol_wallet_cond_discount.
        $table = new xmldb_table('enrol_wallet_cond_discount');
        $field = new xmldb_field('bundle', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'timeto');

        // Conditionally launch add field bundle.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('bundledesc', XMLDB_TYPE_TEXT, null, null, null, null, null, 'bundle');

        // Conditionally launch add field bundledesc.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('descformat', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'bundledesc');

        // Conditionally launch add field descformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2024022500, 'enrol', 'wallet');
    }

    if ($oldversion < 2024022619) {

        // Changing type of field percent on table enrol_wallet_cond_discount to number.
        $table = new xmldb_table('enrol_wallet_cond_discount');
        $field = new xmldb_field('percent', XMLDB_TYPE_NUMBER, '4, 2', null, XMLDB_NOTNULL, null, null, 'cond');

        // Launch change of type for field percent.
        $dbman->change_field_precision($table, $field);
        $dbman->change_field_type($table, $field);

        // Wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2024022619, 'enrol', 'wallet');
    }

    // Changing all precisions of fields handle cost, balance, ..
    if ($oldversion < 2024061600) {

        // Changing precision of field cost on table enrol_wallet_items to (25, 5).
        $table = new xmldb_table('enrol_wallet_items');
        $field = new xmldb_field('cost', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'id');

        // Launch change of precision for field cost.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field amount on table enrol_wallet_awards to (25, 5).
        $table = new xmldb_table('enrol_wallet_awards');
        $field = new xmldb_field('amount', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, null, 'percent');

        // Launch change of precision for field amount.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field amount on table enrol_wallet_transactions to (25, 5).
        $table = new xmldb_table('enrol_wallet_transactions');
        $field = new xmldb_field('amount', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'type');

        // Launch change of precision for field amount.
        $dbman->change_field_precision($table, $field);
        // Changing precision of field balbefore on table enrol_wallet_transactions to (25, 5).

        $field = new xmldb_field('balbefore', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'amount');

        // Launch change of precision for field balbefore.
        $dbman->change_field_precision($table, $field);
        // Changing precision of field balance on table enrol_wallet_transactions to (25, 5).

        $field = new xmldb_field('balance', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'balbefore');

        // Launch change of precision for field balance.
        $dbman->change_field_precision($table, $field);
            // Changing precision of field norefund on table enrol_wallet_transactions to (25, 5).
        $field = new xmldb_field('norefund', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'balance');

        // Launch change of precision for field norefund.
        $dbman->change_field_precision($table, $field);
        // Changing precision of field descripe on table enrol_wallet_transactions to (1333).
        $field = new xmldb_field('descripe', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'category');

        // Launch change of precision for field descripe.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field value on table enrol_wallet_coupons to (25, 5).
        $table = new xmldb_table('enrol_wallet_coupons');
        $field = new xmldb_field('value', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'type');

        // Launch change of precision for field value.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field value on table enrol_wallet_coupons_usage to (25, 5).
        $table = new xmldb_table('enrol_wallet_coupons_usage');
        $field = new xmldb_field('value', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'type');

        // Launch change of precision for field value.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field cond on table enrol_wallet_cond_discount to (25, 5).
        $table = new xmldb_table('enrol_wallet_cond_discount');
        $field = new xmldb_field('cond', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch change of precision for field cond.
        $dbman->change_field_precision($table, $field);
        // Changing precision of field bundle on table enrol_wallet_cond_discount to (25, 5).
        $field = new xmldb_field('bundle', XMLDB_TYPE_NUMBER, '25, 5', null, null, null, null, 'timeto');

        // Launch change of precision for field bundle.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field amount on table enrol_wallet_hold_gift to (25, 5).
        $table = new xmldb_table('enrol_wallet_hold_gift');
        $field = new xmldb_field('amount', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Launch change of precision for field amount.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field refundable on table enrol_wallet_balance to (25, 5).
        $table = new xmldb_table('enrol_wallet_balance');
        $field = new xmldb_field('refundable', XMLDB_TYPE_NUMBER, '25, 5', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Launch change of precision for field refundable.
        $dbman->change_field_precision($table, $field);
        // Changing precision of field nonrefundable on table enrol_wallet_balance to (25, 5).
        $field = new xmldb_field('nonrefundable', XMLDB_TYPE_NUMBER, '25, 5', null, null, null, '0', 'refundable');

        // Launch change of precision for field nonrefundable.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field freegift on table enrol_wallet_balance to (25, 5).
        $field = new xmldb_field('freegift', XMLDB_TYPE_NUMBER, '25, 5', null, null, null, '0', 'nonrefundable');

        // Launch change of precision for field freegift.
        $dbman->change_field_precision($table, $field);
        // Wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2024061600, 'enrol', 'wallet');
    }

    if ($oldversion < 2024062300) {

        // Define field opby to be added to enrol_wallet_transactions.
        $table = new xmldb_table('enrol_wallet_transactions');
        $field = new xmldb_field('opby', XMLDB_TYPE_CHAR, '25', null, null, null, null, 'norefund');

        // Conditionally launch add field opby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field thingid to be added to enrol_wallet_transactions.
        $field = new xmldb_field('thingid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'opby');

        // Conditionally launch add field thingid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field area to be added to enrol_wallet_coupons_usage.
        $table = new xmldb_table('enrol_wallet_coupons_usage');
        $field = new xmldb_field('area', XMLDB_TYPE_INTEGER, '5', null, null, null, null, 'userid');

        // Conditionally launch add field area.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2024062300, 'enrol', 'wallet');
    }

    if ($oldversion < 2025070300) {

        // Define table enrol_wallet_overrides to be created.
        $table = new xmldb_table('enrol_wallet_overrides');

        // Adding fields to table enrol_wallet_overrides.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('thing', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('thingid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rules', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table enrol_wallet_overrides.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('cohortid', XMLDB_KEY_FOREIGN, ['cohortid'], 'cohort', ['id']);

        // Conditionally launch create table for enrol_wallet_overrides.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Wallet savepoint reached.
        upgrade_plugin_savepoint(true, 2025070300, 'enrol', 'wallet');
    }

    return true;
}
