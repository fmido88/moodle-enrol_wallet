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
        set_config('awardssite', 1, 'enrol_wallet');
        $walletplugin = enrol_get_plugin('wallet');
        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;
        // Create user and check that there is no balance.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $balance1 = transactions::get_user_balance($user1->id);

        $this->assertEquals(0, $balance1);

        transactions::payment_topup(100, $user1->id);

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

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
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, 'student');

        $balance2 = transactions::get_user_balance($user1->id);
        $this->assertEquals(50, $balance2);

        $cm = $this->course_completion_init($course1);
        $this->course_completion_trigger($cm, $user1, $course1, 90);
        $this->course_completion_trigger($cm, $user2, $course1, 50);
        // The event should be triggered and caught by our observer.
        $balance3 = transactions::get_user_balance($user1->id);
        $norefund = transactions::get_nonrefund_balance($user1->id);
        $this->assertEquals(70, $balance3);
        $this->assertEquals(20, $norefund);
        $this->assertEquals(0, transactions::get_user_balance($user2->id));
        // Trigger the completion again to make sure no more awards.
        $this->course_completion_trigger($cm, $user1, $course1, 90);
        $this->assertEquals(70, transactions::get_user_balance($user1->id));
        $this->assertEquals(1, $DB->count_records('enrol_wallet_awards'));
    }

    /**
     * Add assignment with completion to a course.
     * @param object $course
     * @return \stdClass
     */
    public function course_completion_init($course) {
        // Make an assignment.
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params = [
            'course' => $course->id,
            'completion' => COMPLETION_ENABLED,
            'completionusegrade' => 1,
        ];
        $assign = $assigngenerator->create_instance($params);

        // Try to mark the assignment.
        return get_coursemodule_from_instance('assign', $assign->id);
    }

    /**
     * Trigger completion for a given user.
     * @param object $cm
     * @param object $user
     * @param object $course
     * @param int $grade
     * @return void
     */
    public function course_completion_trigger($cm, $user, $course, $grade) {
        $usercm = \cm_info::create($cm, $user->id);

        // Create a teacher account.
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        // Log in as the teacher.
        $this->setUser($teacher);

        // Grade the student for this assignment.
        $assign = new \assign($usercm->context, $cm, $cm->course);
        $data = (object)[
            'sendstudentnotifications' => false,
            'attemptnumber' => 1,
            'grade' => $grade,
        ];
        $assign->save_grade($user->id, $data);

        // The target user already received a grade, so internal_get_state should be already complete.
        $completioninfo = new \completion_info($course);
        $this->assertEquals(COMPLETION_COMPLETE, $completioninfo->internal_get_state($cm, $user->id, null));

        $this->setAdminUser();
        $ccompletion = new \completion_completion(array('course' => $course->id, 'userid' => $user->id));

        // Mark course as complete.
        $ccompletion->mark_complete();
    }
    /**
     * Testing event observer gifting new users.
     * @covers ::wallet_gifting_new_user()
     */
    public function test_wallet_gifting_new_user() {
        $this->resetAfterTest();

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
        $norefund = transactions::get_nonrefund_balance($user2->id);
        $this->assertEquals(20, $balance2);
        $this->assertEquals(20, $norefund);
    }

    /**
     * Test conditional discounts.
     * @covers ::conditional_discount_charging()
     * @return void
     */
    public function test_conditional_discount_charging() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        set_config('conditionaldiscount_apply', 1, 'enrol_wallet');
        $params = [
            'cond' => 400,
            'percent' => 15,
        ];
        $DB->insert_record_raw('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 600,
            'percent' => 20,
        ];
        $DB->insert_record_raw('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 800,
            'percent' => 25,
        ];
        $DB->insert_record_raw('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 200,
            'percent' => 50,
            'timeto' => time() - DAYSECS, // Expired.
        ];
        $DB->insert_record_raw('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 400,
            'percent' => 50,
            'timefrom' => time() + DAYSECS, // Not available yet.
        ];
        $DB->insert_record_raw('enrol_wallet_cond_discount', $params);

        transactions::payment_topup(200, $user1->id);
        // The user tries to pay 500, this is the number passes to the function.
        $extra2 = 500 * 0.15;
        transactions::payment_topup(500 * 0.85, $user2->id);

        $extra3 = 700 * 0.2;
        transactions::payment_topup(700 * 0.8, $user3->id);

        $extra4 = 1000 * 0.25;
        transactions::payment_topup(1000 * 0.75, $user4->id);

        $balance1 = transactions::get_user_balance($user1->id);
        $norefund1 = transactions::get_nonrefund_balance($user1->id);

        $balance2 = transactions::get_user_balance($user2->id);
        $norefund2 = transactions::get_nonrefund_balance($user2->id);

        $balance3 = transactions::get_user_balance($user3->id);
        $norefund3 = transactions::get_nonrefund_balance($user3->id);

        $balance4 = transactions::get_user_balance($user4->id);
        $norefund4 = transactions::get_nonrefund_balance($user4->id);

        $this->assertEquals(200, $balance1);
        $this->assertEquals(0, $norefund1);

        $this->assertEquals(500, $balance2);
        $this->assertEquals($extra2, $norefund2);

        $this->assertEquals(700, $balance3);
        $this->assertEquals($extra3, $norefund3);

        $this->assertEquals(1000, $balance4);
        $this->assertEquals($extra4, $norefund4);
    }

    /**
     * Test Referrals.
     * @covers ::release_referral_gift()
     * @return void
     */
    public function test_release_referral_gift() {
        global $DB, $CFG;
        $this->resetAfterTest();
        require_once("$CFG->libdir/authlib.php");
        require_once("$CFG->dirroot/login/lib.php");
        require_once("$CFG->dirroot/user/editlib.php");
        require_once("$CFG->dirroot/login/signup_form.php");

        // Enable referrals.
        set_config('referral_enabled', 1, 'enrol_wallet');
        set_config('referral_amount', 50, 'enrol_wallet');
        $CFG->registerauth = 'email';

        // Create the first user.
        $user1 = $this->getDataGenerator()->create_user();
        $balance1 = transactions::get_user_balance($user1->id);

        $this->assertEquals(0, $balance1);
        // Generate a referral code.
        $data = (object)[
            'userid' => $user1->id,
            'code' => random_string(15) . $user1->id,
        ];
        $DB->insert_record('enrol_wallet_referral', $data);
        $code = $DB->get_record('enrol_wallet_referral', ['userid' => $user1->id])->code;
        $this->assertTrue(!empty($code));

        // Try to simulate the signup process as the referral program work with self-registration only.
        $this->setUser(null);
        $authplugin = signup_is_enabled();
        $user2 = new \stdClass;
        $user2->username  = 'something';
        $user2->password  = 'P@ssw0rd';
        $user2->email     = 'fake@fake.com';
        $user2->email2    = 'fake@fake.com';
        $user2->firstname = 'Mohammad';
        $user2->lastname  = 'Farouk';
        $user2->country   = 'EG';
        $user2->refcode   = $code;
        $user2->sesskey   = sesskey();

        $sink = $this->redirectEmails();
        $user2 = signup_setup_new_user($user2);

        core_login_post_signup_requests($user2);

        $authplugin->user_signup($user2, false);

        $user2 = get_complete_user_data('username', 'something');
        $DB->set_field('user', 'confirmed', 1, ['id' => $user2->id]);

        // Check the database changes.
        $refer = $DB->get_record('enrol_wallet_referral', ['userid' => $user1->id]);
        $this->assertEquals(1, $refer->usetimes);
        $users = json_decode($refer->users);
        $this->assertEquals(1, count($users));
        $this->assertEquals('something', $users[0]);

        $hold = $DB->get_record('enrol_wallet_hold_gift', ['referred' => 'something']);
        $this->assertNotEmpty($hold);
        $this->assertEquals($user1->id, $hold->referrer);
        $this->assertEmpty($hold->released);
        $this->assertEmpty($hold->courseid);

        $balance2 = transactions::get_user_balance($user2->id);
        $this->assertEquals(0, $balance2);

        $this->setAdminUser();

        // Enrol by manual enrolment not trigger the gift release.
        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);

        $balance1 = transactions::get_user_balance($user1->id);
        $balance2 = transactions::get_user_balance($user2->id);
        $this->assertEquals(0, $balance1);
        $this->assertEquals(0, $balance2);

        // Add manual enrolment to the list.
        set_config('referral_plugins', 'wallet,manual', 'enrol_wallet');

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);

        $balance1 = transactions::get_user_balance($user1->id);
        $balance2 = transactions::get_user_balance($user2->id);
        $this->assertEquals(50, $balance1);
        $this->assertEquals(50, $balance2);
        $hold = $DB->get_record('enrol_wallet_hold_gift', ['referred' => $user2->username]);
        $this->assertEquals(1, $hold->released);
        $this->assertEquals($course2->id, $hold->courseid);

        // Check that is no repetition to the gift.
        $course3 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user2->id, $course3->id);
        $balance1 = transactions::get_user_balance($user1->id);
        $balance2 = transactions::get_user_balance($user2->id);
        $this->assertEquals(50, $balance1);
        $this->assertEquals(50, $balance2);
        $sink->close();

        // Set max referrals to 1 and check validation.
        set_config('referral_max', 1, 'enrol_wallet');

        $this->setUser(null);
        $user3 = [
            'username' => 'anotheruser',
            'password' => 'P@ssw0rd',
            'email' => 'fake@fake.com',
            'email2' => 'fake@fake.com',
            'firstname' => 'Adam',
            'lastname' => 'Ali',
            'country' => 'EG',
            'refcode' => $code,
            'sesskey' => sesskey(),
        ];
        $mform = new \login_signup_form();
        $errors = $mform->validation($user3, []);
        $this->assertNotEmpty($errors['refcode']);
        $this->assertStringContainsString(get_string('referral_exceeded', 'enrol_wallet', $code), $errors['refcode']);

        // Check validation of a non-exist code.
        $user3['refcode'] = 'NotExistCode';
        $errors = $mform->validation($user3, []);
        $this->assertNotEmpty($errors['refcode']);
        $this->assertStringContainsString(get_string('referral_notexist', 'enrol_wallet', $user3['refcode']), $errors['refcode']);
    }
}
