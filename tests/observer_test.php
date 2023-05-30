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

use enrol_wallet\observer;
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
class observer_test extends \advanced_testcase {
    /**
     * Test event observer completion awards.
     * @covers ::wallet_completion_awards
     */
    public function test_wallet_completion_awards() {
        global $DB, $CFG;
        $this->resetAfterTest();
        enrol_wallet_enable_plugin();
        $walletplugin = enrol_get_plugin('wallet');
        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;
        // Create user and check that there is no balance.
        $user1 = $this->getDataGenerator()->create_user();
        $balance1 = transactions::get_user_balance($user1->id);

        $this->assertEquals(0, $balance1);

        transactions::payment_topup(100, $user1->id);

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        // Make an assignment.
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params = [
            'course' => $course1->id,
            'completion' => COMPLETION_ENABLED,
            'completionusegrade' => 1,
        ];
        $assign = $assigngenerator->create_instance($params);

        $instance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'wallet'), '*', MUST_EXIST);
        $instance1->customint6 = 1;
        // Enable awarding.
        $instance1->customint8 = 1;
        // Award condition.
        $instance1->customdec1 = 50;
        // Credit per each mark above condition.
        $instance1->customdec2 = 0.5;
        $instance1->cost = 50;
        $DB->update_record('enrol', $instance1);
        $walletplugin->update_status($instance1, ENROL_INSTANCE_ENABLED);

        $walletplugin->enrol_self($instance1, $user1);
        $balance2 = transactions::get_user_balance($user1->id);
        $this->assertEquals(50, $balance2);

        // Try to mark the assignment.
        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $usercm = \cm_info::create($cm, $user1->id);

        // Create a teacher account.
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'editingteacher');
        // Log in as the teacher.
        $this->setUser($teacher);

        // Grade the student for this assignment.
        $assign = new \assign($usercm->context, $cm, $cm->course);
        $data = (object)[
            'sendstudentnotifications' => false,
            'attemptnumber' => 1,
            'grade' => 90,
        ];
        $assign->save_grade($user1->id, $data);

        // The target user already received a grade, so internal_get_state should be already complete.
        $completioninfo = new \completion_info($course1);
        $this->assertEquals(COMPLETION_COMPLETE, $completioninfo->internal_get_state($cm, $user1->id, null));

        $this->setAdminUser();
        $ccompletion = new \completion_completion(array('course' => $course1->id, 'userid' => $user1->id));

        // Mark course as complete.
        $ccompletion->mark_complete();
        // The event should be triggered and caught by our observer.
        $balance3 = transactions::get_user_balance($user1->id);
        $this->assertEquals(70, $balance3);
    }
    /**
     * Testing event observer gifting new users.
     * @covers ::wallet_gifting_new_user()
     */
    public function test_wallet_gifting_new_user() {
        global $DB;
        $this->resetAfterTest();
        enrol_wallet_enable_plugin();
        $walletplugin = enrol_get_plugin('wallet');

        // Create user and check that there is no balance.
        $user1 = $this->getDataGenerator()->create_user();
        $balance1 = transactions::get_user_balance($user1->id);

        $this->assertEquals(0, $balance1);

        // Enable gifting.
        $walletplugin->set_config('newusergift', 1);
        $walletplugin->set_config('newusergiftvalue', 20);
        // Create another user.
        $user2 = $this->getDataGenerator()->create_user();
        $balance2 = transactions::get_user_balance($user2->id);

        $this->assertEquals(20, $balance2);
    }
}
