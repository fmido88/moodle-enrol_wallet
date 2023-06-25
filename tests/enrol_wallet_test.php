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
 * Wallet enrolment tests.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;

use enrol_wallet\transactions;
use enrol_wallet_plugin;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/enrol/wallet/lib.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

/**
 * Wallet enrolment tests.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_wallet_test extends \advanced_testcase {
    /**
     * Basic test for enrol wallet plugin
     * @covers \enrol_wallet_plugin
     */
    public function test_basics() {
        $this->resetAfterTest();

        $this->assertTrue(enrol_is_enabled('wallet'));
        $plugin = enrol_get_plugin('wallet');
        $this->assertInstanceOf('enrol_wallet_plugin', $plugin);
        $this->assertEquals(1, get_config('enrol_wallet', 'defaultenrol'));
        $this->assertEquals(ENROL_EXT_REMOVED_KEEP, get_config('enrol_wallet', 'expiredaction'));
    }

    /**
     * Test function sync() not throw any errors when there is nothing to do.
     * @covers ::sync()
     */
    public function test_sync_nothing() {
        global $SITE;

        $walletplugin = enrol_get_plugin('wallet');

        $trace = new \null_progress_trace();

        // Just make sure the sync does not throw any errors when nothing to do.
        $walletplugin->sync($trace, null);
        $walletplugin->sync($trace, $SITE->id);
    }

    /**
     * Test longtimnosee
     * @covers ::sync
     */
    public function test_longtimnosee() {
        global $DB;
        $this->resetAfterTest();

        $walletplugin = enrol_get_plugin('wallet');
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotEmpty($manualplugin);

        $now = time();

        $trace = new \progress_trace_buffer(new \text_progress_trace(), false);

        // Prepare some data.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->assertNotEmpty($teacherrole);

        $record = array('firstaccess' => $now - DAYSECS * 800);
        $record['lastaccess'] = $now - DAYSECS * 100;
        $user1 = $this->getDataGenerator()->create_user($record);
        $record['lastaccess'] = $now - DAYSECS * 10;
        $user2 = $this->getDataGenerator()->create_user($record);
        $record['lastaccess'] = $now - DAYSECS * 1;
        $user3 = $this->getDataGenerator()->create_user($record);
        $record['lastaccess'] = $now - 10;
        $user4 = $this->getDataGenerator()->create_user($record);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $this->assertEquals(3, $DB->count_records('enrol', array('enrol' => 'wallet')));
        $instance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $id = $walletplugin->add_instance($course3, array('status' => ENROL_INSTANCE_ENABLED, 'roleid' => $teacherrole->id));
        $instance3b = $DB->get_record('enrol', array('id' => $id), '*', MUST_EXIST);
        unset($id);

        $this->assertEquals($studentrole->id, $instance1->roleid);
        $instance1->customint2 = DAYSECS * 14;
        $DB->update_record('enrol', $instance1);
        $walletplugin->enrol_user($instance1, $user1->id, $studentrole->id);
        $walletplugin->enrol_user($instance1, $user2->id, $studentrole->id);
        $walletplugin->enrol_user($instance1, $user3->id, $studentrole->id);
        $this->assertEquals(3, $DB->count_records('user_enrolments'));
        $DB->insert_record('user_lastaccess', array('userid' => $user2->id,
                                                    'courseid' => $course1->id,
                                                    'timeaccess' => $now - DAYSECS * 20));
        $DB->insert_record('user_lastaccess', array('userid' => $user3->id,
                                                    'courseid' => $course1->id,
                                                    'timeaccess' => $now - DAYSECS * 2));
        $DB->insert_record('user_lastaccess', array('userid' => $user4->id,
                                                    'courseid' => $course1->id,
                                                    'timeaccess' => $now - 60));

        $this->assertEquals($studentrole->id, $instance3->roleid);
        $instance3->customint2 = 60 * 60 * 24 * 50;
        $DB->update_record('enrol', $instance3);
        $walletplugin->enrol_user($instance3, $user1->id, $studentrole->id);
        $walletplugin->enrol_user($instance3, $user2->id, $studentrole->id);
        $walletplugin->enrol_user($instance3, $user3->id, $studentrole->id);
        $walletplugin->enrol_user($instance3b, $user1->id, $teacherrole->id);
        $walletplugin->enrol_user($instance3b, $user4->id, $teacherrole->id);
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $DB->insert_record('user_lastaccess', array('userid' => $user2->id,
                                                    'courseid' => $course3->id,
                                                    'timeaccess' => $now - DAYSECS * 11));
        $DB->insert_record('user_lastaccess', array('userid' => $user3->id,
                                                    'courseid' => $course3->id,
                                                    'timeaccess' => $now - DAYSECS * 200));
        $DB->insert_record('user_lastaccess', array('userid' => $user4->id,
                                                    'courseid' => $course3->id,
                                                    'timeaccess' => $now - DAYSECS * 200));

        $maninstance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        $manualplugin->enrol_user($maninstance2, $user1->id, $studentrole->id);
        $manualplugin->enrol_user($maninstance3, $user1->id, $teacherrole->id);

        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(9, $DB->count_records('role_assignments'));
        $this->assertEquals(7, $DB->count_records('role_assignments', array('roleid' => $studentrole->id)));
        $this->assertEquals(2, $DB->count_records('role_assignments', array('roleid' => $teacherrole->id)));

        // Execute sync - this is the same thing used from cron.
        $walletplugin->sync($trace, $course2->id);
        $output = $trace->get_buffer();
        $trace->reset_buffer();
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertStringContainsString('No expired enrol_wallet enrolments detected', $output);
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid' => $instance1->id, 'userid' => $user1->id)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid' => $instance1->id, 'userid' => $user2->id)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid' => $instance3->id, 'userid' => $user1->id)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid' => $instance3->id, 'userid' => $user3->id)));

        $walletplugin->sync($trace, null);
        $output = $trace->get_buffer();
        $trace->reset_buffer();
        $this->assertEquals(6, $DB->count_records('user_enrolments'));
        $this->assertFalse($DB->record_exists('user_enrolments', array('enrolid' => $instance1->id, 'userid' => $user1->id)));
        $this->assertFalse($DB->record_exists('user_enrolments', array('enrolid' => $instance1->id, 'userid' => $user2->id)));
        $this->assertFalse($DB->record_exists('user_enrolments', array('enrolid' => $instance3->id, 'userid' => $user1->id)));
        $this->assertFalse($DB->record_exists('user_enrolments', array('enrolid' => $instance3->id, 'userid' => $user3->id)));
        $this->assertStringContainsString('unenrolling user ' . $user1->id . ' from course ' . $course1->id .
            ' as they have did not log in for at least 14 days', $output);
        $this->assertStringContainsString('unenrolling user ' . $user1->id . ' from course ' . $course3->id .
            ' as they have did not log in for at least 50 days', $output);
        $this->assertStringContainsString('unenrolling user ' . $user2->id . ' from course ' . $course1->id .
            ' as they have did not access course for at least 14 days', $output);
        $this->assertStringContainsString('unenrolling user ' . $user3->id . ' from course ' . $course3->id .
            ' as they have did not access course for at least 50 days', $output);
        $this->assertStringNotContainsString('unenrolling user ' . $user4->id, $output);

        $this->assertEquals(6, $DB->count_records('role_assignments'));
        $this->assertEquals(4, $DB->count_records('role_assignments', array('roleid' => $studentrole->id)));
        $this->assertEquals(2, $DB->count_records('role_assignments', array('roleid' => $teacherrole->id)));
    }

    /**
     * Text expire enrolment.
     * @covers ::expired()
     */
    public function test_expired() {
        global $DB;
        $this->resetAfterTest();

        $walletplugin = enrol_get_plugin('wallet');
        $this->assertNotEmpty($walletplugin);
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotEmpty($manualplugin);

        $now = time();

        $trace = new \null_progress_trace();

        // Prepare some data.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->assertNotEmpty($teacherrole);
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $this->assertNotEmpty($managerrole);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $context1 = \context_course::instance($course1->id);
        $context2 = \context_course::instance($course2->id);
        $context3 = \context_course::instance($course3->id);

        $this->assertEquals(3, $DB->count_records('enrol', array('enrol' => 'wallet')));
        $instance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $this->assertEquals($studentrole->id, $instance1->roleid);
        $instance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $this->assertEquals($studentrole->id, $instance2->roleid);
        $instance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $this->assertEquals($studentrole->id, $instance3->roleid);
        $id = $walletplugin->add_instance($course3, array('status' => ENROL_INSTANCE_ENABLED, 'roleid' => $teacherrole->id));
        $instance3b = $DB->get_record('enrol', array('id' => $id), '*', MUST_EXIST);
        $this->assertEquals($teacherrole->id, $instance3b->roleid);
        unset($id);

        $maninstance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        $manualplugin->enrol_user($maninstance2, $user1->id, $studentrole->id);
        $manualplugin->enrol_user($maninstance3, $user1->id, $teacherrole->id);

        $this->assertEquals(2, $DB->count_records('user_enrolments'));
        $this->assertEquals(2, $DB->count_records('role_assignments'));
        $this->assertEquals(1, $DB->count_records('role_assignments', array('roleid' => $studentrole->id)));
        $this->assertEquals(1, $DB->count_records('role_assignments', array('roleid' => $teacherrole->id)));

        $walletplugin->enrol_user($instance1, $user1->id, $studentrole->id);
        $walletplugin->enrol_user($instance1, $user2->id, $studentrole->id);
        $walletplugin->enrol_user($instance1, $user3->id, $studentrole->id, 0, $now - 60);

        $walletplugin->enrol_user($instance3, $user1->id, $studentrole->id, 0, 0);
        $walletplugin->enrol_user($instance3, $user2->id, $studentrole->id, 0, $now - 60 * 60);
        $walletplugin->enrol_user($instance3, $user3->id, $studentrole->id, 0, $now + 60 * 60);
        $walletplugin->enrol_user($instance3b, $user1->id, $teacherrole->id, $now - DAYSECS * 7, $now - 60);
        $walletplugin->enrol_user($instance3b, $user4->id, $teacherrole->id);

        role_assign($managerrole->id, $user3->id, $context1->id);

        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(10, $DB->count_records('role_assignments'));
        $this->assertEquals(7, $DB->count_records('role_assignments', array('roleid' => $studentrole->id)));
        $this->assertEquals(2, $DB->count_records('role_assignments', array('roleid' => $teacherrole->id)));

        // Execute tests.
        $this->assertEquals(ENROL_EXT_REMOVED_KEEP, $walletplugin->get_config('expiredaction'));
        $walletplugin->sync($trace, null);
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(10, $DB->count_records('role_assignments'));

        $walletplugin->set_config('expiredaction', ENROL_EXT_REMOVED_SUSPENDNOROLES);
        $walletplugin->sync($trace, $course2->id);
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(10, $DB->count_records('role_assignments'));

        $walletplugin->sync($trace, null);
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(7, $DB->count_records('role_assignments'));
        $this->assertEquals(5, $DB->count_records('role_assignments', array('roleid' => $studentrole->id)));
        $this->assertEquals(1, $DB->count_records('role_assignments', array('roleid' => $teacherrole->id)));
        $this->assertFalse($DB->record_exists('role_assignments', array('contextid' => $context1->id,
                                                                        'userid' => $user3->id,
                                                                        'roleid' => $studentrole->id)));
        $this->assertFalse($DB->record_exists('role_assignments', array('contextid' => $context3->id,
                                                                        'userid' => $user2->id,
                                                                        'roleid' => $studentrole->id)));
        $this->assertFalse($DB->record_exists('role_assignments', array('contextid' => $context3->id,
                                                                        'userid' => $user1->id,
                                                                        'roleid' => $teacherrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context3->id,
                                                                        'userid' => $user1->id,
                                                                        'roleid' => $studentrole->id)));

        $walletplugin->set_config('expiredaction', ENROL_EXT_REMOVED_UNENROL);

        role_assign($studentrole->id, $user3->id, $context1->id);
        role_assign($studentrole->id, $user2->id, $context3->id);
        role_assign($teacherrole->id, $user1->id, $context3->id);
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(10, $DB->count_records('role_assignments'));
        $this->assertEquals(7, $DB->count_records('role_assignments', array('roleid' => $studentrole->id)));
        $this->assertEquals(2, $DB->count_records('role_assignments', array('roleid' => $teacherrole->id)));

        $walletplugin->sync($trace, null);
        $this->assertEquals(7, $DB->count_records('user_enrolments'));
        $this->assertFalse($DB->record_exists('user_enrolments', array('enrolid' => $instance1->id, 'userid' => $user3->id)));
        $this->assertFalse($DB->record_exists('user_enrolments', array('enrolid' => $instance3->id, 'userid' => $user2->id)));
        $this->assertFalse($DB->record_exists('user_enrolments', array('enrolid' => $instance3b->id, 'userid' => $user1->id)));
        $this->assertEquals(6, $DB->count_records('role_assignments'));
        $this->assertEquals(5, $DB->count_records('role_assignments', array('roleid' => $studentrole->id)));
        $this->assertEquals(1, $DB->count_records('role_assignments', array('roleid' => $teacherrole->id)));
    }

    /**
     * Test send expiry notification.
     * @covers ::send_expiry_notification()
     */
    public function test_send_expiry_notifications() {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Messaging does not like transactions...

        $walletplugin = enrol_get_plugin('wallet');
        $manualplugin = enrol_get_plugin('manual');
        $now = time();
        $admin = get_admin();

        $trace = new \null_progress_trace();

        // Note: hopefully nobody executes the unit tests the last second before midnight...
        $walletplugin->set_config('expirynotifylast', $now - 60 * 60 * 24);
        $walletplugin->set_config('expirynotifyhour', 0);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->assertNotEmpty($editingteacherrole);
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $this->assertNotEmpty($managerrole);

        $user1 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser1'));
        $user2 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser2'));
        $user3 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser3'));
        $user4 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser4'));
        $user5 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser5'));
        $user6 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser6'));
        $user7 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser6'));
        $user8 = $this->getDataGenerator()->create_user(array('lastname' => 'xuser6'));

        $course1 = $this->getDataGenerator()->create_course(array('fullname' => 'xcourse1'));
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => 'xcourse2'));
        $course3 = $this->getDataGenerator()->create_course(array('fullname' => 'xcourse3'));
        $course4 = $this->getDataGenerator()->create_course(array('fullname' => 'xcourse4'));

        $this->assertEquals(4, $DB->count_records('enrol', array('enrol' => 'manual')));
        $this->assertEquals(4, $DB->count_records('enrol', array('enrol' => 'wallet')));

        $maninstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $instance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance1->expirythreshold = 60 * 60 * 24 * 4;
        $instance1->expirynotify = 1;
        $instance1->notifyall = 1;
        $instance1->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $instance1);

        $instance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance2->expirythreshold = 60 * 60 * 24 * 1;
        $instance2->expirynotify = 1;
        $instance2->notifyall = 1;
        $instance2->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $instance2);

        $maninstance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $instance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance3->expirythreshold = 60 * 60 * 24 * 1;
        $instance3->expirynotify = 1;
        $instance3->notifyall = 0;
        $instance3->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $instance3);

        $maninstance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $instance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance4->expirythreshold = 60 * 60 * 24 * 1;
        $instance4->expirynotify = 0;
        $instance4->notifyall = 0;
        $instance4->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $instance4);

        // Suspended users are not notified.
        $walletplugin->enrol_user($instance1, $user1->id, $studentrole->id, 0, $now + DAYSECS * 1, ENROL_USER_SUSPENDED);
        // Above threshold are not notified.
        $walletplugin->enrol_user($instance1, $user2->id, $studentrole->id, 0, $now + DAYSECS * 5);
        // Less than one day after threshold - should be notified.
        $walletplugin->enrol_user($instance1, $user3->id, $studentrole->id, 0, $now + DAYSECS * 3 + 60 * 60);
        // Less than one day after threshold - should be notified.
        $walletplugin->enrol_user($instance1, $user4->id, $studentrole->id, 0, $now + DAYSECS * 4 - 60 * 3);
        // Should have been already notified.
        $walletplugin->enrol_user($instance1, $user5->id, $studentrole->id, 0, $now + 60 * 60);
        // Already expired.
        $walletplugin->enrol_user($instance1, $user6->id, $studentrole->id, 0, $now - 60);
        $manualplugin->enrol_user($maninstance1, $user7->id, $editingteacherrole->id);
        // Highest role --> enroller.
        $manualplugin->enrol_user($maninstance1, $user8->id, $managerrole->id);

        $walletplugin->enrol_user($instance2, $user1->id, $studentrole->id);
        // Above threshold are not notified.
        $walletplugin->enrol_user($instance2, $user2->id, $studentrole->id, 0, $now + DAYSECS * 1 + 60 * 3);
        // Less than one day after threshold - should be notified.
        $walletplugin->enrol_user($instance2, $user3->id, $studentrole->id, 0, $now + DAYSECS * 1 - 60 * 60);

        $manualplugin->enrol_user($maninstance3, $user1->id, $editingteacherrole->id);
        // Above threshold are not notified.
        $walletplugin->enrol_user($instance3, $user2->id, $studentrole->id, 0, $now + DAYSECS * 1 + 60);
        // Less than one day after threshold - should be notified.
        $walletplugin->enrol_user($instance3, $user3->id, $studentrole->id, 0, $now + DAYSECS * 1 - 60 * 60);

        $manualplugin->enrol_user($maninstance4, $user4->id, $editingteacherrole->id);
        $walletplugin->enrol_user($instance4, $user5->id, $studentrole->id, 0, $now + DAYSECS * 1 + 60);
        $walletplugin->enrol_user($instance4, $user6->id, $studentrole->id, 0, $now + DAYSECS * 1 - 60 * 60);

        // The notification is sent out in fixed order first individual users,
        // then summary per course by enrolid, user lastname, etc.
        $this->assertGreaterThan($instance1->id, $instance2->id);
        $this->assertGreaterThan($instance2->id, $instance3->id);

        $sink = $this->redirectMessages();

        $walletplugin->send_expiry_notifications($trace);

        $messages = $sink->get_messages();

        $this->assertEquals(2 + 1 + 1 + 1 + 1 + 0, count($messages));

        // First individual notifications from course1.
        $this->assertEquals($user3->id, $messages[0]->useridto);
        $this->assertEquals($user8->id, $messages[0]->useridfrom);
        $this->assertStringContainsString('xcourse1', $messages[0]->fullmessagehtml);

        $this->assertEquals($user4->id, $messages[1]->useridto);
        $this->assertEquals($user8->id, $messages[1]->useridfrom);
        $this->assertStringContainsString('xcourse1', $messages[1]->fullmessagehtml);

        // Then summary for course1.
        $this->assertEquals($user8->id, $messages[2]->useridto);
        $this->assertEquals($admin->id, $messages[2]->useridfrom);
        $this->assertStringContainsString('xcourse1', $messages[2]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser1', $messages[2]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser2', $messages[2]->fullmessagehtml);
        $this->assertStringContainsString('xuser3', $messages[2]->fullmessagehtml);
        $this->assertStringContainsString('xuser4', $messages[2]->fullmessagehtml);
        $this->assertStringContainsString('xuser5', $messages[2]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser6', $messages[2]->fullmessagehtml);

        // First individual notifications from course2.
        $this->assertEquals($user3->id, $messages[3]->useridto);
        $this->assertEquals($admin->id, $messages[3]->useridfrom);
        $this->assertStringContainsString('xcourse2', $messages[3]->fullmessagehtml);

        // Then summary for course2.
        $this->assertEquals($admin->id, $messages[4]->useridto);
        $this->assertEquals($admin->id, $messages[4]->useridfrom);
        $this->assertStringContainsString('xcourse2', $messages[4]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser1', $messages[4]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser2', $messages[4]->fullmessagehtml);
        $this->assertStringContainsString('xuser3', $messages[4]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser4', $messages[4]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser5', $messages[4]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser6', $messages[4]->fullmessagehtml);

        // Only summary in course3.
        $this->assertEquals($user1->id, $messages[5]->useridto);
        $this->assertEquals($admin->id, $messages[5]->useridfrom);
        $this->assertStringContainsString('xcourse3', $messages[5]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser1', $messages[5]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser2', $messages[5]->fullmessagehtml);
        $this->assertStringContainsString('xuser3', $messages[5]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser4', $messages[5]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser5', $messages[5]->fullmessagehtml);
        $this->assertStringNotContainsString('xuser6', $messages[5]->fullmessagehtml);

        // Make sure that notifications are not repeated.
        $sink->clear();

        $walletplugin->send_expiry_notifications($trace);
        $this->assertEquals(0, $sink->count());

        // Use invalid notification hour to verify that before the hour the notifications are not sent.
        $walletplugin->set_config('expirynotifylast', time() - DAYSECS);
        $walletplugin->set_config('expirynotifyhour', '24');

        $walletplugin->send_expiry_notifications($trace);
        $this->assertEquals(0, $sink->count());

        $walletplugin->set_config('expirynotifyhour', '0');
        $walletplugin->send_expiry_notifications($trace);
        $this->assertEquals(6, $sink->count());
    }

    /**
     * Test show enrol me link
     * @covers ::show_enrolme_link()
     */
    public function test_show_enrolme_link() {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Messaging does not like transactions...

        $walletplugin = enrol_get_plugin('wallet');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Adding credits to these users.
        transactions::payment_topup(500, $user1->id);
        transactions::payment_topup(250, $user2->id);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();
        $course7 = $this->getDataGenerator()->create_course();
        $course8 = $this->getDataGenerator()->create_course();
        $course9 = $this->getDataGenerator()->create_course();
        $course10 = $this->getDataGenerator()->create_course();
        $course11 = $this->getDataGenerator()->create_course();
        $course12 = $this->getDataGenerator()->create_course();
        $course13 = $this->getDataGenerator()->create_course();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        // New enrolments are allowed and enrolment instance is enabled.
        $instance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 250;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);

        // New enrolments are not allowed, but enrolment instance is enabled.
        $instance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance2->customint6 = 0;
        $instance2->cost = 250;
        $DB->update_record('enrol', $instance2);
        $walletplugin->update_status($instance2, ENROL_INSTANCE_ENABLED);

        // New enrolments are allowed , but enrolment instance is disabled.
        $instance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance3->customint6 = 1;
        $instance3->cost = 250;
        $DB->update_record('enrol', $instance3);
        $walletplugin->update_status($instance3, ENROL_INSTANCE_DISABLED);

        // New enrolments are not allowed and enrolment instance is disabled.
        $instance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance4->customint6 = 0;
        $instance4->cost = 250;
        $DB->update_record('enrol', $instance4);
        $walletplugin->update_status($instance4, ENROL_INSTANCE_DISABLED);

        // Cohort member test.
        $instance5 = $DB->get_record('enrol', array('courseid' => $course5->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance5->customint6 = 1;
        $instance5->customint5 = $cohort1->id;
        $instance5->cost = 250;
        $DB->update_record('enrol', $instance5);
        $walletplugin->update_status($instance5, ENROL_INSTANCE_ENABLED);

        $id = $walletplugin->add_instance($course5, $walletplugin->get_instance_defaults());
        $instance6 = $DB->get_record('enrol', array('id' => $id), '*', MUST_EXIST);
        $instance6->customint6 = 1;
        $instance6->customint5 = $cohort2->id;
        $instance6->cost = 250;
        $DB->update_record('enrol', $instance6);
        $walletplugin->update_status($instance6, ENROL_INSTANCE_ENABLED);

        // Enrol start date is in future.
        $instance7 = $DB->get_record('enrol', array('courseid' => $course6->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance7->customint6 = 1;
        $instance7->enrolstartdate = time() + 60;
        $instance7->cost = 250;
        $DB->update_record('enrol', $instance7);
        $walletplugin->update_status($instance7, ENROL_INSTANCE_ENABLED);

        // Enrol start date is in past.
        $instance8 = $DB->get_record('enrol', array('courseid' => $course7->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance8->customint6 = 1;
        $instance8->enrolstartdate = time() - 60;
        $instance8->cost = 250;
        $DB->update_record('enrol', $instance8);
        $walletplugin->update_status($instance8, ENROL_INSTANCE_ENABLED);

        // Enrol end date is in future.
        $instance9 = $DB->get_record('enrol', array('courseid' => $course8->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance9->customint6 = 1;
        $instance9->enrolenddate = time() + 60;
        $instance9->cost = 250;
        $DB->update_record('enrol', $instance9);
        $walletplugin->update_status($instance9, ENROL_INSTANCE_ENABLED);

        // Enrol end date is in past.
        $instance10 = $DB->get_record('enrol', array('courseid' => $course9->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance10->customint6 = 1;
        $instance10->enrolenddate = time() - 60;
        $instance10->cost = 250;
        $DB->update_record('enrol', $instance10);
        $walletplugin->update_status($instance10, ENROL_INSTANCE_ENABLED);

        // Maximum enrolments reached.
        $instance11 = $DB->get_record('enrol', array('courseid' => $course10->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance11->customint6 = 1;
        $instance11->customint3 = 1;
        $instance11->cost = 250;
        $DB->update_record('enrol', $instance11);
        $walletplugin->update_status($instance11, ENROL_INSTANCE_ENABLED);
        $walletplugin->enrol_user($instance11, $user2->id, $studentrole->id);

        // Maximum enrolments not reached.
        $instance12 = $DB->get_record('enrol', array('courseid' => $course11->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance12->customint6 = 1;
        $instance12->customint3 = 1;
        $instance12->cost = 250;
        $DB->update_record('enrol', $instance12);
        $walletplugin->update_status($instance12, ENROL_INSTANCE_ENABLED);

        // Enrolment restricted by enrolment in another course.
        $instance13 = $DB->get_record('enrol', array('courseid' => $course12->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance13->customint6 = 1;
        $instance13->customint7 = 1;
        $instance13->customchar3 = $course1->id;
        $instance13->cost = 250;
        $DB->update_record('enrol', $instance13);
        $walletplugin->update_status($instance13, ENROL_INSTANCE_ENABLED);
        // Empty cost.
        $instance14 = $DB->get_record('enrol', array('courseid' => $course13->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance14->customint6 = 1;
        $DB->update_record('enrol', $instance14);
        $walletplugin->update_status($instance14, ENROL_INSTANCE_ENABLED);

        $this->setUser($user1);
        $this->assertTrue($walletplugin->show_enrolme_link($instance1));
        $this->assertFalse($walletplugin->show_enrolme_link($instance2));
        $this->assertFalse($walletplugin->show_enrolme_link($instance3));
        $this->assertFalse($walletplugin->show_enrolme_link($instance4));
        $this->assertFalse($walletplugin->show_enrolme_link($instance7));
        $this->assertTrue($walletplugin->show_enrolme_link($instance8));
        $this->assertTrue($walletplugin->show_enrolme_link($instance9));
        $this->assertFalse($walletplugin->show_enrolme_link($instance10));
        $this->assertFalse($walletplugin->show_enrolme_link($instance11));
        $this->assertTrue($walletplugin->show_enrolme_link($instance12));
        $this->assertFalse($walletplugin->show_enrolme_link($instance13));

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->assertTrue($walletplugin->show_enrolme_link($instance13));

        $this->assertFalse($walletplugin->show_enrolme_link($instance14));

        require_once("$CFG->dirroot/cohort/lib.php");
        cohort_add_member($cohort1->id, $user1->id);

        $this->assertTrue($walletplugin->show_enrolme_link($instance5));
        $this->assertFalse($walletplugin->show_enrolme_link($instance6));

        // Lowering the user's balance.
        transactions::debit($user1->id, 300);
        $this->assertFalse($walletplugin->show_enrolme_link($instance1));
    }

    /**
     * This will check user enrolment only, rest has been tested in test_show_enrolme_link.
     * @covers ::can_self_enrol()
     */
    public function test_can_self_enrol() {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback();

        $walletplugin = enrol_get_plugin('wallet');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        transactions::payment_topup(250, $user1->id);
        transactions::payment_topup(250, $user2->id);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->assertNotEmpty($editingteacherrole);

        $course1 = $this->getDataGenerator()->create_course();

        $instance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 200;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);
        $walletplugin->enrol_user($instance1, $user2->id, $editingteacherrole->id);

        // Guest user cannot enrol.
        $guest = $DB->get_record('user', array('id' => $CFG->siteguest));
        $this->setUser($guest);
        $this->assertStringContainsString(get_string('noguestaccess', 'enrol'),
                $walletplugin->can_self_enrol($instance1, true));

        $this->setUser($user1);
        $this->assertTrue($walletplugin->can_self_enrol($instance1, true));

        // Active enroled user.
        $this->setUser($user2);
        $walletplugin->enrol_user($instance1, $user1->id, $studentrole->id);
        $this->setUser($user1);
        $this->assertSame(get_string('alreadyenroled', 'enrol_wallet'), $walletplugin->can_self_enrol($instance1, true));

        // Insufficient balance.
        $course2 = $this->getDataGenerator()->create_course();
        $instance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance2->customint6 = 1;
        $instance2->cost = 500;
        $DB->update_record('enrol', $instance2);
        $walletplugin->update_status($instance2, ENROL_INSTANCE_ENABLED);
        $this->assertSame(enrol_wallet_plugin::INSUFFICIENT_BALANCE, $walletplugin->can_self_enrol($instance2, true));

        // Disabled instance.
        $course3 = $this->getDataGenerator()->create_course();
        $instance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance3->customint6 = 1;
        $instance3->cost = 50;
        $DB->update_record('enrol', $instance3);
        $walletplugin->update_status($instance3, ENROL_INSTANCE_DISABLED);
        $this->assertSame(get_string('canntenrol', 'enrol_wallet'), $walletplugin->can_self_enrol($instance3, true));

        // Cannot enrol early.
        $course4 = $this->getDataGenerator()->create_course();
        $instance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance4->customint6 = 1;
        $instance4->cost = 50;
        $instance4->enrolstartdate = time() + 3 * DAYSECS;
        $DB->update_record('enrol', $instance4);
        $walletplugin->update_status($instance4, ENROL_INSTANCE_ENABLED);
        $msg = get_string('canntenrolearly', 'enrol_wallet', userdate($instance4->enrolstartdate));
        $this->assertSame($msg, $walletplugin->can_self_enrol($instance4, true));

        // Cannot enrol late.
        $course5 = $this->getDataGenerator()->create_course();
        $instance5 = $DB->get_record('enrol', array('courseid' => $course5->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance5->customint6 = 1;
        $instance5->cost = 50;
        $instance5->enrolenddate = time() - 3 * DAYSECS;
        $DB->update_record('enrol', $instance5);
        $walletplugin->update_status($instance5, ENROL_INSTANCE_ENABLED);
        $msg = get_string('canntenrollate', 'enrol_wallet', userdate($instance5->enrolenddate));
        $this->assertSame($msg, $walletplugin->can_self_enrol($instance5, true));

        // New enrols not allowed.
        $course6 = $this->getDataGenerator()->create_course();
        $instance6 = $DB->get_record('enrol', array('courseid' => $course6->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance6->customint6 = 0;
        $instance6->cost = 50;
        $DB->update_record('enrol', $instance6);
        $walletplugin->update_status($instance6, ENROL_INSTANCE_ENABLED);
        $this->assertSame(get_string('canntenrol', 'enrol_wallet'), $walletplugin->can_self_enrol($instance6, true));

        // Max enrolments reached.
        $course7 = $this->getDataGenerator()->create_course();
        $instance7 = $DB->get_record('enrol', array('courseid' => $course7->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance7->customint6 = 1;
        $instance7->customint3 = 2;
        $instance7->cost = 50;
        $DB->update_record('enrol', $instance7);
        $walletplugin->update_status($instance7, ENROL_INSTANCE_ENABLED);
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $walletplugin->enrol_user($instance7, $user3->id);
        $walletplugin->enrol_user($instance7, $user4->id);
        $this->assertSame(get_string('maxenrolledreached', 'enrol_wallet'), $walletplugin->can_self_enrol($instance7, true));

        // Check the restrictions upon other course enrollment.
        $course8 = $this->getDataGenerator()->create_course(['fullname' => 'xcourse8']);
        $course9 = $this->getDataGenerator()->create_course();
        $instance9 = $DB->get_record('enrol', array('courseid' => $course9->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance9->customint6 = 1;
        $instance9->customchar3 = $course8->id;
        $instance9->customint7 = 1;
        $instance9->cost = 50;
        $DB->update_record('enrol', $instance9);
        $walletplugin->update_status($instance9, ENROL_INSTANCE_ENABLED);
        $msg = get_string('othercourserestriction', 'enrol_wallet', '(xcourse8)');
        $this->assertSame($msg, $walletplugin->can_self_enrol($instance9, true));

        // TODO Check the cohorts restrictions.

        // Non valid cost.
        $course10 = $this->getDataGenerator()->create_course();
        $instance10 = $DB->get_record('enrol', array('courseid' => $course10->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance10->customint6 = 1;
        $DB->update_record('enrol', $instance10);
        $walletplugin->update_status($instance10, ENROL_INSTANCE_ENABLED);
        $this->assertSame(get_string('nocost', 'enrol_wallet'), $walletplugin->can_self_enrol($instance10, true));
    }

    /**
     * Test get_welcome_email_contact().
     * @covers ::get_welcome_email_contact()
     */
    public function test_get_welcome_email_contact() {
        global $DB;
        self::resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Marsh']);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Victoria']);
        $user3 = $this->getDataGenerator()->create_user(['lastname' => 'Burch']);
        $user4 = $this->getDataGenerator()->create_user(['lastname' => 'Cartman']);
        $noreplyuser = \core_user::get_noreply_user();

        $course1 = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course1->id);

        // Get editing teacher role.
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->assertNotEmpty($editingteacherrole);

        // Enable wallet enrolment plugin and set to send email from course contact.
        $walletplugin = enrol_get_plugin('wallet');
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->customint4 = ENROL_SEND_EMAIL_FROM_COURSE_CONTACT;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);

        // We do not have a teacher enrolled at this point, so it should send as no reply user.
        $contact = $walletplugin->get_welcome_email_contact(ENROL_SEND_EMAIL_FROM_COURSE_CONTACT, $context);
        $this->assertEquals($noreplyuser, $contact);

        // By default, course contact is assigned to teacher role.
        // Enrol a teacher, now it should send emails from teacher email's address.
        $walletplugin->enrol_user($instance1, $user1->id, $editingteacherrole->id);

        // We should get the teacher email.
        $contact = $walletplugin->get_welcome_email_contact(ENROL_SEND_EMAIL_FROM_COURSE_CONTACT, $context);
        $this->assertEquals($user1->username, $contact->username);
        $this->assertEquals($user1->email, $contact->email);

        // Now let's enrol another teacher.
        $walletplugin->enrol_user($instance1, $user2->id, $editingteacherrole->id);
        $contact = $walletplugin->get_welcome_email_contact(ENROL_SEND_EMAIL_FROM_COURSE_CONTACT, $context);
        $this->assertEquals($user1->username, $contact->username);
        $this->assertEquals($user1->email, $contact->email);

        $instance1->customint4 = ENROL_SEND_EMAIL_FROM_NOREPLY;
        $DB->update_record('enrol', $instance1);

        $contact = $walletplugin->get_welcome_email_contact(ENROL_SEND_EMAIL_FROM_NOREPLY, $context);
        $this->assertEquals($noreplyuser, $contact);
    }

    /**
     * Test for getting user enrolment actions.
     * @covers ::get_user_enrolment_actions()
     */
    public function test_get_user_enrolment_actions() {
        global $CFG, $PAGE;
        $this->resetAfterTest();

        // Set page URL to prevent debugging messages.
        $PAGE->set_url('/enrol/editinstance.php');

        $pluginname = 'wallet';

        // Only enable the wallet enrol plugin.
        $CFG->enrol_plugins_enabled = $pluginname;

        $generator = $this->getDataGenerator();

        // Get the enrol plugin.
        $plugin = enrol_get_plugin($pluginname);

        // Create a course.
        $course = $generator->create_course();

        // Create a teacher.
        $teacher = $generator->create_user();
        // Enrol the teacher to the course.
        $enrolresult = $generator->enrol_user($teacher->id, $course->id, 'editingteacher', $pluginname);
        $this->assertTrue($enrolresult);
        // Create a student.
        $student = $generator->create_user();
        // Enrol the student to the course.
        $enrolresult = $generator->enrol_user($student->id, $course->id, 'student', $pluginname);
        $this->assertTrue($enrolresult);

        // Login as the teacher.
        $this->setUser($teacher);
        require_once($CFG->dirroot . '/enrol/locallib.php');
        $manager = new \course_enrolment_manager($PAGE, $course);
        $userenrolments = $manager->get_user_enrolments($student->id);
        $this->assertCount(1, $userenrolments);

        $ue = reset($userenrolments);
        $actions = $plugin->get_user_enrolment_actions($manager, $ue);
        // Wallet enrol has 2 enrol actions -- edit and unenrol.
        $this->assertCount(2, $actions);
    }

    /**
     * Testing get cost after discount.
     *
     * @covers ::get_cost_after_discount()
     */
    public function test_get_cost_after_discount() {
        global $DB;
        self::resetAfterTest(true);

        $walletplugin = enrol_get_plugin('wallet');
        // Check that cost after discount return the original cost.
        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $instance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 200;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);

        $costafter = $walletplugin->get_cost_after_discount($user1->id, $instance1);
        $this->assertEquals($costafter, $instance1->cost);
        // Check the discounts according to user profile field.
        // Create a custom profile field.
        $fielddata = (object)[
            'name' => 'discount',
            'shortname' => 'discount',
        ];
        $fieldid = $DB->insert_record('user_info_field', $fielddata, true);

        $walletplugin->set_config('discount_field', $fieldid);
        transactions::payment_topup(150, $user1->id);
        $userfielddata = (object)[
            'userid' => $user1->id,
            'fieldid' => $fieldid,
            'data' => 'free'
        ];
        $userdataid = $DB->insert_record('user_info_data', $userfielddata);
        $costafter = $walletplugin->get_cost_after_discount($user1->id, $instance1);
        $this->assertEquals(0, $costafter);

        $dataupdate = (object)[
            'id' => $userdataid,
            'data' => '20% discount'
        ];
        $DB->update_record('user_info_data', $dataupdate);
        $costafter = $walletplugin->get_cost_after_discount($user1->id, $instance1);
        $this->assertEquals(200 * 80 / 100, $costafter);

        // Check coupon discounts.
        $user2 = $this->getDataGenerator()->create_user();
        transactions::payment_topup(150, $user2->id);
        $course2 = $this->getDataGenerator()->create_course();

        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance2->customint6 = 1;
        $instance2->cost = 200;
        $DB->update_record('enrol', $instance2);
        $walletplugin->update_status($instance2, ENROL_INSTANCE_ENABLED);
        // Create percent discount coupon.
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSALL, 'enrol_wallet');
        $coupon = [
            'code' => 'test1',
            'type' => 'percent',
            'value' => 50,
            'maxusage' => 1,
        ];
        $DB->insert_record('enrol_wallet_coupons', $coupon);
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test1');
        $this->assertEquals(100, $costafter);
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSDISCOUNT, 'enrol_wallet');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test1');
        $this->assertEquals(100, $costafter);
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSFIXED, 'enrol_wallet');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test1');
        $this->assertEquals(200, $costafter);
        set_config('coupons', \enrol_wallet_plugin::WALLET_NOCOUPONS, 'enrol_wallet');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test1');
        $this->assertEquals(200, $costafter);
        // Create fixed discount coupon.
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSALL, 'enrol_wallet');
        $coupon = [
            'code' => 'test2',
            'type' => 'fixed',
            'value' => 50,
            'maxusage' => 1,
        ];
        $DB->insert_record('enrol_wallet_coupons', $coupon);
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test2');
        $this->assertEquals(150, $costafter);
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSDISCOUNT, 'enrol_wallet');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test2');
        $this->assertEquals(200, $costafter);
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSFIXED, 'enrol_wallet');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test2');
        $this->assertEquals(150, $costafter);
        set_config('coupons', \enrol_wallet_plugin::WALLET_NOCOUPONS, 'enrol_wallet');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, 'test2');
        $this->assertEquals(200, $costafter);
        // Check both discounts works together.
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSDISCOUNT, 'enrol_wallet');
        $costafter = $walletplugin->get_cost_after_discount($user1->id, $instance1, 'test1');
        $this->assertEquals(80, $costafter);
    }

    /**
     * Test that enrol_self deduct the users credit and that cashback program works.
     * @covers ::enrol_self()
     */
    public function test_enrol_self() {
        global $DB;
        self::resetAfterTest(true);

        $walletplugin = enrol_get_plugin('wallet');
        $user1 = $this->getDataGenerator()->create_user();
        transactions::payment_topup(250, $user1->id);
        $course1 = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course1->id);

        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 200;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);

        $this->setUser($user1);
        // Enrol the user and makesure the cost deducted.
        $walletplugin->enrol_self($instance1, $user1);

        $balance1 = transactions::get_user_balance($user1->id);
        $this->assertEquals(50, $balance1);
        $this->assertTrue(is_enrolled($context));
        // Now testing the functionality of cashbackprogram.
        $walletplugin->set_config('cashback', 1);
        $walletplugin->set_config('cashbackpercent', 20);
        $user2 = $this->getDataGenerator()->create_user();
        transactions::payment_topup(250, $user2->id);
        $this->setUser($user2);
        // Enrol the user and makesure the cost deducted.
        $walletplugin->enrol_self($instance1, $user2);

        $balance2 = transactions::get_user_balance($user2->id);
        $norefund = transactions::get_nonrefund_balance($user2->id);
        $this->assertEquals(90, $balance2);
        $this->assertEquals(40, $norefund);
        $this->assertTrue(is_enrolled($context));
    }

    /**
     * test for hide_due_cheaper_instance function
     * @covers ::hide_due_cheaper_instance()
     */
    public function test_hide_due_cheaper_instance() {
        global $DB;
        self::resetAfterTest(true);

        $walletplugin = enrol_get_plugin('wallet');
        $user1 = $this->getDataGenerator()->create_user();
        transactions::payment_topup(250, $user1->id);

        $user2 = $this->getDataGenerator()->create_user();
        transactions::payment_topup(50, $user2->id);

        $user3 = $this->getDataGenerator()->create_user();
        transactions::payment_topup(100, $user3->id);

        $course = $this->getDataGenerator()->create_course();

        $instance1 = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 200;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);

        $instance2id = $walletplugin->add_default_instance($course);
        $instance2 = $walletplugin->get_instance_by_id($instance2id);
        $data2['cost'] = 100;
        $walletplugin->update_instance($instance2, (object) $data2);

        // Sufficient balance for both.
        $this->setUser($user1);
        $this->assertTrue($walletplugin->hide_due_cheaper_instance($instance1));
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance2));

        // Insufficient for both.
        $this->setUser($user2);
        $this->assertTrue($walletplugin->hide_due_cheaper_instance($instance1));
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance2));

        // Sufficient for one but not the other.
        $this->setUser($user3);
        $this->assertTrue($walletplugin->hide_due_cheaper_instance($instance1));
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance2));
    }
}
