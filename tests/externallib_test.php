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

namespace enrol_wallet;

use core_external\external_api;
use enrol_wallet\external\enrol as enrol_wallet_external;
use enrol_wallet\local\config;
use externallib_advanced_testcase;
use enrol_wallet\transactions;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * wallet enrol external PHPunit tests
 *
 * @package   enrol_wallet
 * @copyright 2023 Mohammad Farouk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @runTestsInSeparateProcesses
 */
final class externallib_test extends externallib_advanced_testcase {

    /**
     * Test get_instance_info
     * @covers ::get_instance_info()
     */
    public function test_get_instance_info(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Check if wallet enrolment plugin is enabled.
        $walletplugin = enrol_get_plugin('wallet');
        $this->assertNotEmpty($walletplugin);
        // In this test we will add instances manually.
        config::make()->defaultenrol = 0;

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);

        $coursedata = new \stdClass();
        $coursedata->visible = 0;
        $course = $this->getDataGenerator()->create_course($coursedata);

        // Add enrolment methods for course.
        $instanceid1 = $walletplugin->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED,
                                                                'name' => 'Test instance 1',
                                                                'customint6' => 1,
                                                                'cost' => 50,
                                                                'roleid' => $studentrole->id,
                                                            ]);
        $instanceid2 = $walletplugin->add_instance($course, ['status' => ENROL_INSTANCE_DISABLED,
                                                                'customint6' => 1,
                                                                'cost' => 100,
                                                                'name' => 'Test instance 2',
                                                                'roleid' => $studentrole->id,
                                                            ]);

        $enrolmentmethods = $DB->get_records('enrol', ['courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED]);
        $this->assertCount(2, $enrolmentmethods);

        $this->setAdminUser();
        $instanceinfo1 = enrol_wallet_external::get_instance_info($instanceid1);
        $instanceinfo1 = external_api::clean_returnvalue(enrol_wallet_external::get_instance_info_returns(), $instanceinfo1);

        $this->assertEquals($instanceid1, $instanceinfo1['id']);
        $this->assertEquals($course->id, $instanceinfo1['courseid']);
        $this->assertEquals('wallet', $instanceinfo1['type']);
        $this->assertEquals('Test instance 1', $instanceinfo1['name']);
        $this->assertEquals(50, $instanceinfo1['cost']);
        $this->assertEquals(\enrol_wallet_plugin::INSUFFICIENT_BALANCE, $instanceinfo1['status']);

        $instanceinfo2 = enrol_wallet_external::get_instance_info($instanceid2);
        $instanceinfo2 = external_api::clean_returnvalue(enrol_wallet_external::get_instance_info_returns(), $instanceinfo2);
        $this->assertEquals($instanceid2, $instanceinfo2['id']);
        $this->assertEquals($course->id, $instanceinfo2['courseid']);
        $this->assertEquals('wallet', $instanceinfo2['type']);
        $this->assertEquals('Test instance 2', $instanceinfo2['name']);
        $this->assertEquals(100, $instanceinfo2['cost']);
        $this->assertEquals(get_string('canntenrol', 'enrol_wallet'), $instanceinfo2['status']);

        // Try to retrieve information using a normal user for a hidden course.
        $user = $this->getDataGenerator()->create_user();
        transactions::payment_topup(60, $user->id);
        $this->setUser($user);
        try {
            enrol_wallet_external::get_instance_info($instanceid1);
        } catch (\moodle_exception $e) {
            $this->assertEquals('coursehidden', $e->errorcode);
        }
        // Make the course visible.
        $course->visible = 1;
        update_course($course);
        // Can enrol in instance 1 but not in 2.
        $instanceinfo1 = enrol_wallet_external::get_instance_info($instanceid1);
        $instanceinfo1 = external_api::clean_returnvalue(enrol_wallet_external::get_instance_info_returns(), $instanceinfo1);
        $this->assertTrue($instanceinfo1['status']);

        // Enable the instance.
        $instance2 = $DB->get_record('enrol', ['id' => $instanceid2], '*', MUST_EXIST);
        $walletplugin->update_status($instance2, ENROL_INSTANCE_ENABLED);
        $instanceinfo2 = enrol_wallet_external::get_instance_info($instanceid2);
        $instanceinfo2 = external_api::clean_returnvalue(enrol_wallet_external::get_instance_info_returns(), $instanceinfo2);
        $this->assertEquals(\enrol_wallet_plugin::INSUFFICIENT_BALANCE, $instanceinfo2['status']);
    }

    /**
     * Test enrol_user
     * @covers ::enrol_user()
     */
    public function test_enrol_user(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $walletplugin = enrol_get_plugin('wallet');

        // In this test we will add instances manually.
        config::make()->defaultenrol = 0;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        transactions::payment_topup(100, $user1->id);

        $context1 = \context_course::instance($course1->id);
        $context2 = \context_course::instance($course2->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $instance1id = $walletplugin->add_instance($course1, ['status' => ENROL_INSTANCE_ENABLED,
                                                                'name' => 'Test instance 1',
                                                                'customint6' => 1,
                                                                'cost' => 50,
                                                                'roleid' => $studentrole->id,
                                                            ]);
        $instance2id = $walletplugin->add_instance($course2, ['status' => ENROL_INSTANCE_DISABLED,
                                                                'customint6' => 1,
                                                                'name' => 'Test instance 2',
                                                                'cost' => 200,
                                                                'roleid' => $studentrole->id,
                                                            ]);
        $instance1 = $DB->get_record('enrol', ['id' => $instance1id], '*', MUST_EXIST);
        $instance2 = $DB->get_record('enrol', ['id' => $instance2id], '*', MUST_EXIST);

        $this->setUser($user1);

        // Self enrol me.
        $result = enrol_wallet_external::enrol_user($course1->id);
        $result = external_api::clean_returnvalue(enrol_wallet_external::enrol_user_returns(), $result);

        $this->assertTrue($result['status']);
        $this->assertEquals(1, $DB->count_records('user_enrolments', ['enrolid' => $instance1->id]));
        $this->assertTrue(is_enrolled($context1, $user1));
        $balance = transactions::get_user_balance($user1->id);
        $this->assertEquals(50, $balance);

        // Try instance not enabled.
        try {
            enrol_wallet_external::enrol_user($course2->id);
        } catch (\moodle_exception $e) {
            $this->assertEquals('canntenrol', $e->errorcode);
        }

        // Enable the instance.
        $walletplugin->update_status($instance2, ENROL_INSTANCE_ENABLED);

        // Try insufficient balance.
        $result = enrol_wallet_external::enrol_user($course2->id);
        $result = external_api::clean_returnvalue(enrol_wallet_external::enrol_user_returns(), $result);
        $this->assertFalse($result['status']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('1', $result['warnings'][0]['warningcode']);
        $this->assertFalse(is_enrolled($context2, $user1));
        // Make sure no balance deducted.
        $balance = transactions::get_user_balance($user1->id);
        $this->assertEquals(50, $balance);
    }
}
