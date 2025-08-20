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

use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet\local\utils\options;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/enrol/wallet/lib.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
use enrol_wallet_plugin;

/**
 * Wallet enrolment tests.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class enrol_wallet_test extends \advanced_testcase {
    /**
     * Basic test for enrol wallet plugin
     * @covers \enrol_wallet_plugin
     */
    public function test_basics(): void {
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
    public function test_sync_nothing(): void {
        global $SITE;

        $walletplugin = new enrol_wallet_plugin;

        $trace = new \null_progress_trace();

        // Just make sure the sync does not throw any errors when nothing to do.
        $walletplugin->sync($trace, null);
        $walletplugin->sync($trace, $SITE->id);
    }

    /**
     * Test longtimnosee
     * @covers ::sync
     * @return void
     */
    public function test_longtimnosee(): void {
        global $DB;
        $this->resetAfterTest();

        $walletplugin = new enrol_wallet_plugin;
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotEmpty($manualplugin);

        $now = time();

        $trace = new \progress_trace_buffer(new \text_progress_trace(), false);

        // Prepare some data.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->assertNotEmpty($teacherrole);

        $record = ['firstaccess' => $now - DAYSECS * 800];
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

        $this->assertEquals(3, $DB->count_records('enrol', ['enrol' => 'wallet']));
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $id = $walletplugin->add_instance($course3, ['status' => ENROL_INSTANCE_ENABLED, 'roleid' => $teacherrole->id]);
        $instance3b = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);
        unset($id);

        $this->assertEquals($studentrole->id, $instance1->roleid);
        $instance1->customint2 = DAYSECS * 14;
        $DB->update_record('enrol', $instance1);
        $walletplugin->enrol_user($instance1, $user1->id, $studentrole->id);
        $walletplugin->enrol_user($instance1, $user2->id, $studentrole->id);
        $walletplugin->enrol_user($instance1, $user3->id, $studentrole->id);
        $this->assertEquals(3, $DB->count_records('user_enrolments'));
        $DB->insert_record('user_lastaccess', ['userid' => $user2->id,
                                                    'courseid' => $course1->id,
                                                    'timeaccess' => $now - DAYSECS * 20,
                                                ]);
        $DB->insert_record('user_lastaccess', ['userid' => $user3->id,
                                                    'courseid' => $course1->id,
                                                    'timeaccess' => $now - DAYSECS * 2,
                                                ]);
        $DB->insert_record('user_lastaccess', ['userid' => $user4->id,
                                                    'courseid' => $course1->id,
                                                    'timeaccess' => $now - 60,
                                                ]);

        $this->assertEquals($studentrole->id, $instance3->roleid);
        $instance3->customint2 = 60 * 60 * 24 * 50;
        $DB->update_record('enrol', $instance3);
        $walletplugin->enrol_user($instance3, $user1->id, $studentrole->id);
        $walletplugin->enrol_user($instance3, $user2->id, $studentrole->id);
        $walletplugin->enrol_user($instance3, $user3->id, $studentrole->id);
        $walletplugin->enrol_user($instance3b, $user1->id, $teacherrole->id);
        $walletplugin->enrol_user($instance3b, $user4->id, $teacherrole->id);
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $DB->insert_record('user_lastaccess', ['userid' => $user2->id,
                                                    'courseid' => $course3->id,
                                                    'timeaccess' => $now - DAYSECS * 11,
                                                ]);
        $DB->insert_record('user_lastaccess', ['userid' => $user3->id,
                                                    'courseid' => $course3->id,
                                                    'timeaccess' => $now - DAYSECS * 200,
                                                ]);
        $DB->insert_record('user_lastaccess', ['userid' => $user4->id,
                                                    'courseid' => $course3->id,
                                                    'timeaccess' => $now - DAYSECS * 200,
                                                ]);

        $maninstance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'manual'], '*', MUST_EXIST);

        $manualplugin->enrol_user($maninstance2, $user1->id, $studentrole->id);
        $manualplugin->enrol_user($maninstance3, $user1->id, $teacherrole->id);

        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(9, $DB->count_records('role_assignments'));
        $this->assertEquals(7, $DB->count_records('role_assignments', ['roleid' => $studentrole->id]));
        $this->assertEquals(2, $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]));

        // Execute sync - this is the same thing used from cron.
        $walletplugin->sync($trace, $course2->id);
        $output = $trace->get_buffer();
        $trace->reset_buffer();
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertStringContainsString('No expired enrol_wallet enrolments detected', $output);
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $instance1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $instance1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $instance3->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $instance3->id, 'userid' => $user3->id]));

        $walletplugin->sync($trace, null);
        $output = $trace->get_buffer();
        $trace->reset_buffer();
        $this->assertEquals(6, $DB->count_records('user_enrolments'));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $instance1->id, 'userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $instance1->id, 'userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $instance3->id, 'userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $instance3->id, 'userid' => $user3->id]));
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
        $this->assertEquals(4, $DB->count_records('role_assignments', ['roleid' => $studentrole->id]));
        $this->assertEquals(2, $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]));
    }

    /**
     * Text expire enrolment.
     * @covers ::expired()
     */
    public function test_expired(): void {
        global $DB;
        $this->resetAfterTest();

        $walletplugin = new enrol_wallet_plugin;
        $this->assertNotEmpty($walletplugin);
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotEmpty($manualplugin);

        $now = time();

        $trace = new \null_progress_trace();

        // Prepare some data.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->assertNotEmpty($teacherrole);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
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

        $this->assertEquals(3, $DB->count_records('enrol', ['enrol' => 'wallet']));
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $this->assertEquals($studentrole->id, $instance1->roleid);
        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $this->assertEquals($studentrole->id, $instance2->roleid);
        $instance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $this->assertEquals($studentrole->id, $instance3->roleid);
        $id = $walletplugin->add_instance($course3, ['status' => ENROL_INSTANCE_ENABLED, 'roleid' => $teacherrole->id]);
        $instance3b = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);
        $this->assertEquals($teacherrole->id, $instance3b->roleid);
        unset($id);

        $maninstance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'manual'], '*', MUST_EXIST);

        $manualplugin->enrol_user($maninstance2, $user1->id, $studentrole->id);
        $manualplugin->enrol_user($maninstance3, $user1->id, $teacherrole->id);

        $this->assertEquals(2, $DB->count_records('user_enrolments'));
        $this->assertEquals(2, $DB->count_records('role_assignments'));
        $this->assertEquals(1, $DB->count_records('role_assignments', ['roleid' => $studentrole->id]));
        $this->assertEquals(1, $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]));

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
        $this->assertEquals(7, $DB->count_records('role_assignments', ['roleid' => $studentrole->id]));
        $this->assertEquals(2, $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]));

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
        $this->assertEquals(5, $DB->count_records('role_assignments', ['roleid' => $studentrole->id]));
        $this->assertEquals(1, $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]));
        $this->assertFalse($DB->record_exists('role_assignments', ['contextid' => $context1->id,
                                                                        'userid' => $user3->id,
                                                                        'roleid' => $studentrole->id,
                                                                    ]));
        $this->assertFalse($DB->record_exists('role_assignments', ['contextid' => $context3->id,
                                                                        'userid' => $user2->id,
                                                                        'roleid' => $studentrole->id,
                                                                    ]));
        $this->assertFalse($DB->record_exists('role_assignments', ['contextid' => $context3->id,
                                                                        'userid' => $user1->id,
                                                                        'roleid' => $teacherrole->id,
                                                                    ]));
        $this->assertTrue($DB->record_exists('role_assignments', ['contextid' => $context3->id,
                                                                        'userid' => $user1->id,
                                                                        'roleid' => $studentrole->id,
                                                                    ]));

        $walletplugin->set_config('expiredaction', ENROL_EXT_REMOVED_UNENROL);

        role_assign($studentrole->id, $user3->id, $context1->id);
        role_assign($studentrole->id, $user2->id, $context3->id);
        role_assign($teacherrole->id, $user1->id, $context3->id);
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(10, $DB->count_records('role_assignments'));
        $this->assertEquals(7, $DB->count_records('role_assignments', ['roleid' => $studentrole->id]));
        $this->assertEquals(2, $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]));

        $walletplugin->sync($trace, null);
        $this->assertEquals(7, $DB->count_records('user_enrolments'));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $instance1->id, 'userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $instance3->id, 'userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $instance3b->id, 'userid' => $user1->id]));
        $this->assertEquals(6, $DB->count_records('role_assignments'));
        $this->assertEquals(5, $DB->count_records('role_assignments', ['roleid' => $studentrole->id]));
        $this->assertEquals(1, $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]));
    }

    /**
     * Test send expiry notification.
     * @covers ::send_expiry_notification()
     */
    public function test_send_expiry_notifications(): void {
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

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->assertNotEmpty($editingteacherrole);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        $this->assertNotEmpty($managerrole);

        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'xuser1']);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'xuser2']);
        $user3 = $this->getDataGenerator()->create_user(['lastname' => 'xuser3']);
        $user4 = $this->getDataGenerator()->create_user(['lastname' => 'xuser4']);
        $user5 = $this->getDataGenerator()->create_user(['lastname' => 'xuser5']);
        $user6 = $this->getDataGenerator()->create_user(['lastname' => 'xuser6']);
        $user7 = $this->getDataGenerator()->create_user(['lastname' => 'xuser6']);
        $user8 = $this->getDataGenerator()->create_user(['lastname' => 'xuser6']);

        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'xcourse1']);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'xcourse2']);
        $course3 = $this->getDataGenerator()->create_course(['fullname' => 'xcourse3']);
        $course4 = $this->getDataGenerator()->create_course(['fullname' => 'xcourse4']);

        $this->assertEquals(4, $DB->count_records('enrol', ['enrol' => 'manual']));
        $this->assertEquals(4, $DB->count_records('enrol', ['enrol' => 'wallet']));

        $maninstance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->expirythreshold = 60 * 60 * 24 * 4;
        $instance1->expirynotify = 1;
        $instance1->notifyall = 1;
        $instance1->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $instance1);

        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance2->expirythreshold = 60 * 60 * 24 * 1;
        $instance2->expirynotify = 1;
        $instance2->notifyall = 1;
        $instance2->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $instance2);

        $maninstance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $instance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance3->expirythreshold = 60 * 60 * 24 * 1;
        $instance3->expirynotify = 1;
        $instance3->notifyall = 0;
        $instance3->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $instance3);

        $maninstance4 = $DB->get_record('enrol', ['courseid' => $course4->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $instance4 = $DB->get_record('enrol', ['courseid' => $course4->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
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
    public function test_show_enrolme_link(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Messaging does not like transactions...

        $walletplugin = enrol_get_plugin('wallet');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Adding credits to these users.
        $op = new balance_op($user1->id);
        $op->credit(500);
        $op = new balance_op($user2->id);
        $op->credit(250);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
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
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 250;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);

        // New enrolments are not allowed, but enrolment instance is enabled.
        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance2->customint6 = 0;
        $instance2->cost = 250;
        $DB->update_record('enrol', $instance2);
        $walletplugin->update_status($instance2, ENROL_INSTANCE_ENABLED);

        // New enrolments are allowed , but enrolment instance is disabled.
        $instance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance3->customint6 = 1;
        $instance3->cost = 250;
        $DB->update_record('enrol', $instance3);
        $walletplugin->update_status($instance3, ENROL_INSTANCE_DISABLED);

        // New enrolments are not allowed and enrolment instance is disabled.
        $instance4 = $DB->get_record('enrol', ['courseid' => $course4->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance4->customint6 = 0;
        $instance4->cost = 250;
        $DB->update_record('enrol', $instance4);
        $walletplugin->update_status($instance4, ENROL_INSTANCE_DISABLED);

        // Cohort member test.
        $instance5 = $DB->get_record('enrol', ['courseid' => $course5->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance5->customint6 = 1;
        $instance5->customint5 = $cohort1->id;
        $instance5->cost = 250;
        $DB->update_record('enrol', $instance5);
        $walletplugin->update_status($instance5, ENROL_INSTANCE_ENABLED);

        $id = $walletplugin->add_instance($course5, $walletplugin->get_instance_defaults());
        $instance6 = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);
        $instance6->customint6 = 1;
        $instance6->customint5 = $cohort2->id;
        $instance6->cost = 250;
        $DB->update_record('enrol', $instance6);
        $walletplugin->update_status($instance6, ENROL_INSTANCE_ENABLED);

        // Enrol start date is in future.
        $instance7 = $DB->get_record('enrol', ['courseid' => $course6->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance7->customint6 = 1;
        $instance7->enrolstartdate = time() + 60;
        $instance7->cost = 250;
        $DB->update_record('enrol', $instance7);
        $walletplugin->update_status($instance7, ENROL_INSTANCE_ENABLED);

        // Enrol start date is in past.
        $instance8 = $DB->get_record('enrol', ['courseid' => $course7->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance8->customint6 = 1;
        $instance8->enrolstartdate = time() - 60;
        $instance8->cost = 250;
        $DB->update_record('enrol', $instance8);
        $walletplugin->update_status($instance8, ENROL_INSTANCE_ENABLED);

        // Enrol end date is in future.
        $instance9 = $DB->get_record('enrol', ['courseid' => $course8->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance9->customint6 = 1;
        $instance9->enrolenddate = time() + 60;
        $instance9->cost = 250;
        $DB->update_record('enrol', $instance9);
        $walletplugin->update_status($instance9, ENROL_INSTANCE_ENABLED);

        // Enrol end date is in past.
        $instance10 = $DB->get_record('enrol', ['courseid' => $course9->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance10->customint6 = 1;
        $instance10->enrolenddate = time() - 60;
        $instance10->cost = 250;
        $DB->update_record('enrol', $instance10);
        $walletplugin->update_status($instance10, ENROL_INSTANCE_ENABLED);

        // Maximum enrolments reached.
        $instance11 = $DB->get_record('enrol', ['courseid' => $course10->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance11->customint6 = 1;
        $instance11->customint3 = 1;
        $instance11->cost = 250;
        $DB->update_record('enrol', $instance11);
        $walletplugin->update_status($instance11, ENROL_INSTANCE_ENABLED);
        $walletplugin->enrol_user($instance11, $user2->id, $studentrole->id);

        // Maximum enrolments not reached.
        $instance12 = $DB->get_record('enrol', ['courseid' => $course11->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance12->customint6 = 1;
        $instance12->customint3 = 1;
        $instance12->cost = 250;
        $DB->update_record('enrol', $instance12);
        $walletplugin->update_status($instance12, ENROL_INSTANCE_ENABLED);

        // Enrolment restricted by enrolment in another course.
        $instance13 = $DB->get_record('enrol', ['courseid' => $course12->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance13->customint6 = 1;
        $instance13->customint7 = 1;
        $instance13->customchar3 = $course1->id;
        $instance13->cost = 250;
        $DB->update_record('enrol', $instance13);
        $walletplugin->update_status($instance13, ENROL_INSTANCE_ENABLED);
        // Empty cost.
        $instance14 = $DB->get_record('enrol', ['courseid' => $course13->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
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
        $op = new balance_op($user1->id);
        $op->debit(300, $op::OTHER);
        $this->assertFalse($walletplugin->show_enrolme_link($instance1));
    }

    /**
     * This will check user enrolment only, rest has been tested in test_show_enrolme_link.
     * @covers ::can_self_enrol()
     */
    public function test_can_self_enrol(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback();

        $walletplugin = enrol_get_plugin('wallet');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $op = new balance_op($user1->id);
        $op->credit(250);

        $op = new balance_op($user2->id);
        $op->credit(250);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->assertNotEmpty($editingteacherrole);

        $course1 = $this->getDataGenerator()->create_course();

        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 200;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);
        $walletplugin->enrol_user($instance1, $user2->id, $editingteacherrole->id);

        // Guest user cannot enrol.
        $guest = $DB->get_record('user', ['id' => $CFG->siteguest]);
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
        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance2->customint6 = 1;
        $instance2->cost = 500;
        $DB->update_record('enrol', $instance2);
        $walletplugin->update_status($instance2, ENROL_INSTANCE_ENABLED);
        $this->assertSame(enrol_wallet_plugin::INSUFFICIENT_BALANCE, $walletplugin->can_self_enrol($instance2, true));

        // Disabled instance.
        $course3 = $this->getDataGenerator()->create_course();
        $instance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance3->customint6 = 1;
        $instance3->cost = 50;
        $DB->update_record('enrol', $instance3);
        $walletplugin->update_status($instance3, ENROL_INSTANCE_DISABLED);
        $this->assertSame(get_string('canntenrol', 'enrol_wallet'), $walletplugin->can_self_enrol($instance3, true));

        // Cannot enrol early.
        $course4 = $this->getDataGenerator()->create_course();
        $instance4 = $DB->get_record('enrol', ['courseid' => $course4->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance4->customint6 = 1;
        $instance4->cost = 50;
        $instance4->enrolstartdate = time() + 3 * DAYSECS;
        $DB->update_record('enrol', $instance4);
        $walletplugin->update_status($instance4, ENROL_INSTANCE_ENABLED);
        $msg = get_string('canntenrolearly', 'enrol_wallet', userdate($instance4->enrolstartdate));
        $this->assertSame($msg, $walletplugin->can_self_enrol($instance4, true));

        // Cannot enrol late.
        $course5 = $this->getDataGenerator()->create_course();
        $instance5 = $DB->get_record('enrol', ['courseid' => $course5->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance5->customint6 = 1;
        $instance5->cost = 50;
        $instance5->enrolenddate = time() - 3 * DAYSECS;
        $DB->update_record('enrol', $instance5);
        $walletplugin->update_status($instance5, ENROL_INSTANCE_ENABLED);
        $msg = get_string('canntenrollate', 'enrol_wallet', userdate($instance5->enrolenddate));
        $this->assertSame($msg, $walletplugin->can_self_enrol($instance5, true));

        // New enrols not allowed.
        $course6 = $this->getDataGenerator()->create_course();
        $instance6 = $DB->get_record('enrol', ['courseid' => $course6->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance6->customint6 = 0;
        $instance6->cost = 50;
        $DB->update_record('enrol', $instance6);
        $walletplugin->update_status($instance6, ENROL_INSTANCE_ENABLED);
        $this->assertSame(get_string('canntenrol', 'enrol_wallet'), $walletplugin->can_self_enrol($instance6, true));

        // Max enrolments reached.
        $course7 = $this->getDataGenerator()->create_course();
        $instance7 = $DB->get_record('enrol', ['courseid' => $course7->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
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
        $instance9 = $DB->get_record('enrol', ['courseid' => $course9->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance9->customint6 = 1;
        $instance9->customchar3 = $course8->id;
        $instance9->customint7 = 1;
        $instance9->cost = 50;
        $DB->update_record('enrol', $instance9);
        $walletplugin->update_status($instance9, ENROL_INSTANCE_ENABLED);
        $a = [
            'number' => 1,
            'courses' => '(xcourse8)',
        ];
        $msg = get_string('othercourserestriction', 'enrol_wallet', $a);
        $this->assertSame($msg, $walletplugin->can_self_enrol($instance9, true));

        // Todo: Check the cohorts restrictions.
        // Todo: Test restriction rules.
        // Non valid cost.
        $course10 = $this->getDataGenerator()->create_course();
        $instance10 = $DB->get_record('enrol', ['courseid' => $course10->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance10->customint6 = 1;
        $DB->update_record('enrol', $instance10);
        $walletplugin->update_status($instance10, ENROL_INSTANCE_ENABLED);
        $this->assertSame(get_string('nocost', 'enrol_wallet'), $walletplugin->can_self_enrol($instance10, true));
    }

    /**
     * Test get_welcome_email_contact().
     * @covers ::get_welcome_email_contact()
     */
    public function test_get_welcome_email_contact(): void {
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
        $walletplugin = enrol_wallet_plugin::get_plugin();
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
    public function test_get_user_enrolment_actions(): void {
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
     * Test that enrol_self deduct the users credit and that cashback program works.
     * @covers ::enrol_self()
     */
    public function test_enrol_self(): void {
        global $DB;
        self::resetAfterTest(true);

        $wallet = new enrol_wallet_plugin;
        $user1 = $this->getDataGenerator()->create_user();
        $op = new balance_op($user1->id);
        $op->credit(250);

        $course1 = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course1->id);

        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->customint6 = 1;
        $instance1->cost = 200;
        $DB->update_record('enrol', $instance1);
        $wallet->update_status($instance1, ENROL_INSTANCE_ENABLED);

        $this->setUser($user1);
        // Enrol the user and makesure the cost deducted.
        $wallet->enrol_self($instance1, $user1);
        $bal = new balance();
        $balance1 = $bal->get_valid_balance();
        $this->assertEquals(50, $balance1);
        $this->assertTrue(is_enrolled($context));

        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);
        // Add to main balance.
        $op = new balance_op($user2->id);
        $op->credit(300);
        // Add to category balance.
        $op = new balance_op($user2->id, $course1->category);
        $op->credit(50);
        $wallet->enrol_self($instance1);

        $balance = balance::create_from_instance($instance1);
        $this->assertEquals(150, $balance->get_total_balance());
        $this->assertEquals(150, $balance->get_valid_balance());
        $this->assertEquals(150, $balance->get_main_balance());
        $this->assertEquals(0, $balance->get_cat_balance($course1->category));

        // Now testing the functionality of cashbackprogram.
        $wallet->set_config('cashback', 1);
        $wallet->set_config('cashbackpercent', 20);
        $user3 = $this->getDataGenerator()->create_user();
        $op = new balance_op($user3->id);
        $op->credit(250);

        $this->setUser($user3);
        // Enrol the user and makesure the cost deducted.
        $wallet->enrol_self($instance1, $user3);
        $balance = balance::create_from_instance($instance1, $user3->id);
        $this->assertEquals(90, $balance->get_valid_balance());
        $this->assertEquals(40, $balance->get_valid_nonrefundable());
        $this->assertEquals(0, $balance->get_main_nonrefundable());
        $this->assertEquals(40, $balance->get_cat_balance($course1->category));
        $this->assertTrue(is_enrolled($context));
    }

    /**
     * Summary of test_is_course_enrolment_restriction
     * @covers ::is_course_enrolment_restriction()
     * @return void
     */
    public function test_is_course_enrolment_restriction(): void {
        global $DB;
        $this->resetAfterTest();
        $wallet = enrol_get_plugin('wallet');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();
        $course7 = $this->getDataGenerator()->create_course();
        $course8 = $this->getDataGenerator()->create_course();

        $courses = [];
        $courses[] = $course2->id;
        $courses[] = $course3->id;
        $courses[] = $course4->id;
        $courses[] = $course5->id;
        $courses[] = $course6->id;

        $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'courseid' => $course1->id]);
        $data = new \stdClass;
        $data->status = ENROL_INSTANCE_ENABLED;
        $data->cost = 50;
        $data->customint1 = 1;
        $data->customint7 = 4;
        $data->customchar3 = implode(',', $courses);
        $wallet->update_instance($instance, $data);

        $this->assertCount(7, options::get_courses_options($course1->id));
        $options = array_keys(options::get_courses_options($course1->id));
        $this->assertTrue(in_array($course2->id, $options));
        $this->assertTrue(in_array($course3->id, $options));
        $this->assertTrue(in_array($course4->id, $options));
        $this->assertTrue(in_array($course5->id, $options));
        $this->assertTrue(in_array($course6->id, $options));
        $this->assertTrue(in_array($course7->id, $options));
        $this->assertTrue(in_array($course8->id, $options));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course5->id);

        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course5->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course6->id);

        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course4->id);

        $this->getDataGenerator()->enrol_user($user4->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course6->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course7->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course8->id);

        // Not restricted.
        $this->setUser($user1);
        $this->assertFalse($wallet->is_course_enrolment_restriction($instance));

        // Not restricted.
        $this->setUser($user2);
        $this->assertFalse($wallet->is_course_enrolment_restriction($instance));

        // Restricted.
        $this->setUser($user3);
        $this->assertIsString($wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course3->fullname, $wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course5->fullname, $wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course6->fullname, $wallet->is_course_enrolment_restriction($instance));

        // Restricted.
        $this->setUser($user4);
        $this->assertIsString($wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course4->fullname, $wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course5->fullname, $wallet->is_course_enrolment_restriction($instance));

        // Restricted.
        $this->setUser($user5);
        $this->assertIsString($wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course2->fullname, $wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course3->fullname, $wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course4->fullname, $wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course5->fullname, $wallet->is_course_enrolment_restriction($instance));
        $this->assertStringContainsString($course6->fullname, $wallet->is_course_enrolment_restriction($instance));
    }

    /**
     * test for hide_due_cheaper_instance function
     * @covers ::hide_due_cheaper_instance()
     */
    public function test_hide_due_cheaper_instance(): void {
        global $DB;
        self::resetAfterTest(true);

        $walletplugin = new enrol_wallet_plugin;
        $user1 = $this->getDataGenerator()->create_user();
        $op = new balance_op($user1->id);
        $op->credit(250);

        $user2 = $this->getDataGenerator()->create_user();
        $op = new balance_op($user2->id);
        $op->credit(50);

        $user3 = $this->getDataGenerator()->create_user();
        $op = new balance_op($user3->id);
        $op->credit(100);

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

        $walletplugin = new enrol_wallet_plugin;
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

        // Cheaper but cannot enrol late.
        $data2['enrolenddate'] = time() - 3 * DAYSECS;
        $walletplugin->update_instance($instance2, (object) $data2);

        $this->setUser($user1);
        // Sufficient balance.
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance1));
        // Cheaper but cannot enrol.
        $this->assertTrue($walletplugin->hide_due_cheaper_instance($instance2));

        $this->setUser($user2);
        // Insufficient.
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance1));
        // Cheaper but cannot enrol.
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance2));

        // Sufficient for one but not the other, cannot enrol in any so we show both to view all reasons.
        $this->setUser($user3);
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance1));
        $this->assertFalse($walletplugin->hide_due_cheaper_instance($instance2));
    }

    /**
     * Summary of test_unenrol_user
     * Mainly to test the refunds.
     * @covers ::unenrol_user()
     * @return void
     */
    public function test_unenrol_user(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $user = $this->getDataGenerator()->create_user();
        $op = new balance_op($user->id);
        $op->credit(100);

        $wallet = new enrol_wallet_plugin;
        // Update the instance such that the enrol duration is 2 hours.
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance->customint6 = 1;
        $instance->enrolperiod = HOURSECS * 2;
        $instance->cost = 50;
        $DB->update_record('enrol', $instance);
        $wallet->update_status($instance, ENROL_INSTANCE_ENABLED);

        // Enable refunding.
        set_config('unenrolrefund', 1, 'enrol_wallet');
        $wallet = new enrol_wallet_plugin;
        // Enrol the user and check the balance.
        $this->setUser($user);
        $wallet->enrol_self($instance);
        $this->assertTrue(is_enrolled($context));

        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(50, $balance);

        $wallet->unenrol_user($instance, $user->id);
        $this->assertFalse(is_enrolled($context));

        // Check the refund.
        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(100, $balance); // This assertion sometime fails and on re-run it passes?

        $this->setAdminUser();
        set_config('unenrolrefund', 0, 'enrol_wallet');
        $wallet = new enrol_wallet_plugin;
        // Repeat but disable refunding.
        $this->setUser($user);
        $wallet->enrol_self($instance);
        $this->assertTrue(is_enrolled($context));
        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(50, $balance);

        $wallet->unenrol_user($instance, $user->id);
        $this->assertFalse(is_enrolled($context));

        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(50, $balance);

        // Enable refunding with duration limit.
        $this->setAdminUser();
        set_config('unenrolrefund', 1, 'enrol_wallet');
        set_config('unenrolrefundperiod', HOURSECS, 'enrol_wallet');
        $wallet = new enrol_wallet_plugin;

        $this->setUser($user);
        $wallet->enrol_self($instance);
        $this->assertTrue(is_enrolled($context));

        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(0, $balance);

        $wallet->unenrol_user($instance, $user->id);
        $this->assertFalse(is_enrolled($context));

        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(50, $balance);

        $wallet->enrol_self($instance);

        $this->setAdminUser();
        // The user remaind in the course more than the grace period.
        $wallet->update_user_enrol($instance, $user->id, true, time() - 3 * HOURSECS, time() + DAYSECS);

        $this->setUser($user);
        $this->assertTrue(is_enrolled($context));

        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(0, $balance);

        $wallet->unenrol_user($instance, $user->id);
        $this->assertFalse(is_enrolled($context));
        // No refund.
        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(0, $balance);

        // Now test the refund fee.
        $op = new balance_op($user->id);
        $op->credit(100);

        $this->setAdminUser();
        // Set to 10%.
        set_config('unenrolrefundfee', 10, 'enrol_wallet');
        $wallet = new enrol_wallet_plugin;

        $this->setUser($user);
        $wallet->enrol_self($instance);
        $this->assertTrue(is_enrolled($context));

        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(50, $balance);

        $wallet->unenrol_user($instance, $user->id);
        $this->assertFalse(is_enrolled($context));
        // Only 45 refund.
        $helper = balance::create_from_instance($instance, $user->id);
        $balance = $helper->get_valid_balance();
        $this->assertEquals(95, $balance);
    }

    /**
     * Summary of test_get_unenrolself_link
     * @covers ::get_unenrolself_link()
     * @return void
     */
    public function test_get_unenrolself_link(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $user = $this->getDataGenerator()->create_user();
        $op = new balance_op($user->id);
        $op->credit(100);

        $wallet = new enrol_wallet_plugin;
        // Update the instance such that the enrol duration is 2 hours.
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $data = new \stdClass;
        $data->customint6 = 1;
        $data->enrolperiod = HOURSECS * 10;
        $data->cost = 50;
        $wallet->update_instance($instance, $data);
        $wallet->update_status($instance, ENROL_INSTANCE_ENABLED);

        // By default the self unenrol option is disabled.
        $this->setUser($user);
        $wallet->enrol_self($instance);
        $this->assertTrue(is_enrolled($context));
        $this->assertEmpty($wallet->get_unenrolself_link($instance));

        // Enable unconditionaly.
        $this->setAdminUser();
        set_config('unenrolselfenabled', 1, 'enrol_wallet');

        $wallet = new enrol_wallet_plugin;
        $this->setUser($user);
        $this->assertNotEmpty($wallet->get_unenrolself_link($instance));

        // Set conditions.
        $this->setAdminUser();
        set_config('unenrollimitafter', 2 * HOURSECS, 'enrol_wallet');
        set_config('unenrollimitbefor', 2 * HOURSECS, 'enrol_wallet');

        $wallet = new enrol_wallet_plugin;
        // First condition limit after enrol start time by 2 hours.
        // Second condition limit is before the enrol end time by 2 hours.
        // Can unenrol before the first condition.
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 1 * HOURSECS, time() + 9 * HOURSECS);
        $this->setUser($user);
        $this->assertNotEmpty($wallet->get_unenrolself_link($instance));

        // Cannot unenrol after the first condition and before the second.
        $this->setAdminUser();
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 6 * HOURSECS, time() + 4 * HOURSECS);
        $this->setUser($user);
        $this->assertEmpty($wallet->get_unenrolself_link($instance));

        // Can unenrol after the second condition.
        $this->setAdminUser();
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 9 * HOURSECS, time() + 1 * HOURSECS);
        $this->setUser($user);
        $this->assertNotEmpty($wallet->get_unenrolself_link($instance));

        // Remove the second condition.
        $this->setAdminUser();
        set_config('unenrollimitafter', 2 * HOURSECS, 'enrol_wallet');
        set_config('unenrollimitbefor', 0, 'enrol_wallet');

        $wallet = new enrol_wallet_plugin;
        // Can unenrol before the first condition.
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 1 * HOURSECS, time() + 9 * HOURSECS);
        $this->setUser($user);
        $this->assertNotEmpty($wallet->get_unenrolself_link($instance));

        // Cannot unenrol after.
        $this->setAdminUser();
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 6 * HOURSECS, time() + 4 * HOURSECS);
        $this->setUser($user);
        $this->assertEmpty($wallet->get_unenrolself_link($instance));

        // Cannot unenrol too.
        $this->setAdminUser();
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 9 * HOURSECS, time() + 1 * HOURSECS);
        $this->setUser($user);
        $this->assertEmpty($wallet->get_unenrolself_link($instance));

        // Remove the first condition only.
        $this->setAdminUser();
        set_config('unenrollimitafter', 0, 'enrol_wallet');
        set_config('unenrollimitbefor', 2 * HOURSECS, 'enrol_wallet');

        $wallet = new enrol_wallet_plugin;
        // Cannot unenrol before.
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 1 * HOURSECS, time() + 9 * HOURSECS);
        $this->setUser($user);
        $this->assertEmpty($wallet->get_unenrolself_link($instance));

        // Still cannot unenrol.
        $this->setAdminUser();
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 6 * HOURSECS, time() + 4 * HOURSECS);
        $this->setUser($user);
        $this->assertEmpty($wallet->get_unenrolself_link($instance));

        // Can unenrol after the second condition.
        $this->setAdminUser();
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, time() - 9 * HOURSECS, time() + 1 * HOURSECS);
        $this->setUser($user);
        $this->assertNotEmpty($wallet->get_unenrolself_link($instance));
    }
}
