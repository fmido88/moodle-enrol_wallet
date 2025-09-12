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

namespace enrol_wallet\util;

use enrol_wallet\local\config;
use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/enrol/wallet/lib.php');

use enrol_wallet_plugin;

/**
 * Tests for Wallet enrolment
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class instance_test extends \advanced_testcase {
    /**
     * Testing get cost after discount.
     *
     * @covers ::get_cost_after_discount()
     */
    public function test_get_cost_after_discount(): void {
        global $DB;
        self::resetAfterTest(true);

        $walletplugin = new enrol_wallet_plugin;
        // Check that cost after discount return the original cost.
        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
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

        $config = config::make();
        $config->discount_field = $fieldid;
        $op = new balance_op($user1->id);
        $op->credit(150);
        $userfielddata = (object)[
            'userid'  => $user1->id,
            'fieldid' => $fieldid,
            'data'    => 'free',
        ];
        $userdataid = $DB->insert_record('user_info_data', $userfielddata);
        $costafter = $walletplugin->get_cost_after_discount($user1->id, $instance1, true);
        $this->assertEquals(0, $costafter);

        $dataupdate = (object)[
            'id' => $userdataid,
            'data' => '20% discount',
        ];
        $DB->update_record('user_info_data', $dataupdate);
        $costafter = $walletplugin->get_cost_after_discount($user1->id, $instance1, true);
        $this->assertEquals(200 * 80 / 100, $costafter);

        // Check coupon discounts.
        $user2 = $this->getDataGenerator()->create_user();
        $op = new balance_op($user2->id);
        $op->credit(150);

        // Create percent discount coupon.
        $all = implode(',', coupons::TYPES);
        $config->coupons = $all;
        $coupon = [
            'code'     => 'test1',
            'type'     => 'percent',
            'value'    => 50,
            'maxusage' => 1,
        ];
        $DB->insert_record('enrol_wallet_coupons', $coupon);
        coupons::set_session_coupon('test1');
        $this->assertEquals('test1', coupons::check_discount_coupon());
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, true);
        $this->assertEquals(100, $costafter);
        $config->coupons = coupons::DISCOUNT;
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, true);
        $this->assertEquals(100, $costafter);
        $config->coupons = coupons::FIXED . ',' . coupons::ENROL;
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, true);
        $this->assertEquals(200, $costafter);
        $config->coupons = coupons::NOCOUPONS;
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, true);
        $this->assertEquals(200, $costafter);
        coupons::unset_session_coupon();

        // Create a fixed & category coupons and check that there is no discount.
        $config->coupons = enrol_wallet_plugin::WALLET_COUPONSALL;
        $coupon = [
            'code' => 'test2',
            'type' => 'fixed',
            'value' => 50,
            'maxusage' => 1,
        ];
        $DB->insert_record('enrol_wallet_coupons', $coupon);
        $coupon = [
            'code' => 'test3',
            'type' => 'category',
            'value' => 50,
            'maxusage' => 1,
            'category' => $course1->category,
        ];
        $DB->insert_record('enrol_wallet_coupons', $coupon);
        coupons::set_session_coupon('test2');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, true);
        $this->assertEquals(200, $costafter);
        coupons::set_session_coupon('test3');
        $costafter = $walletplugin->get_cost_after_discount($user2->id, $instance1, true);
        $this->assertEquals(200, $costafter);
        coupons::unset_session_coupon();
    }

    /**
     * Testing discounts after first and second repurchase
     * @covers ::get_cost_after_discount
     */
    public function test_repurchase_discount_and_function(): void {
        global $DB;
        $this->resetAfterTest();
        $wallet = enrol_wallet_plugin::get_plugin();
        $config = config::make();
        // Now lets check the discount for repurchace.
        $course2 = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course2->id);

        $instance = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance->customint6 = 1;
        $instance->cost = 100;
        $instance->enrolperiod = DAYSECS;
        $DB->update_record('enrol', $instance);
        $wallet->update_status($instance, ENROL_INSTANCE_ENABLED);

        $user = $this->getDataGenerator()->create_user();
        $op = new balance_op($user->id);
        $op->credit(450);

        $wallet->enrol_self($instance, $user);
        $record = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id]);
        $record->timemodified = timedate::time() - 10 * DAYSECS;
        $record->timecreated = timedate::time() - 10 * DAYSECS;
        $DB->update_record('user_enrolments', $record);

        $op = new balance_op($user->id);
        $this->assertTrue(is_enrolled($context, $user));
        $this->assertEquals(350, $op->get_total_balance());

        $now = timedate::time();
        // The enrolment expired.
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, $now - 5 * DAYSECS, $now - 3 * DAYSECS);
        $DB->update_record('user_enrolments', (object)['id' => $record->id, 'timemodified' => $now - 5 * DAYSECS]);

        $this->assertFalse(is_enrolled($context, $user, '', true));
        // Check the cost again.
        $inst = new instance($instance, $user->id);
        $this->setUser($user);
        $this->assertNotTrue($wallet->can_self_enrol($instance));
        $this->assertEquals(100, $inst->get_cost_after_discount(true));

        // Enable the repurchase.
        $config->repurchase = 1;
        $wallet = new enrol_wallet_plugin;
        $this->assertTrue($wallet->can_self_enrol($instance));
        $this->assertEquals(100, $inst->get_cost_after_discount(true));

        // Set first discount.
        $config->repurchase_firstdis = 40;
        $this->assertTrue($wallet->can_self_enrol($instance));
        $this->assertEquals(60, $inst->get_cost_after_discount(true));

        // Make sure it is not affected by second discount option.
        $config->repurchase_seconddis = 60;
        $wallet = new enrol_wallet_plugin;

        $this->assertEquals(60, $inst->get_cost_after_discount(true));
        $op = new balance_op($user->id);
        $this->assertTrue(is_enrolled($context, $user));
        $this->assertEquals(350, $op->get_total_balance());
        // Enrol the user.
        $wallet->enrol_self($instance, $user);
        $this->assertTrue(is_enrolled($context, $user, '', true));
        $balance = balance::create_from_instance($instance, $user->id);
        $this->assertEquals(290, $balance->get_valid_balance());

        $now = timedate::time();
        // Expire the user enrolment.
        $wallet->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE, $now - 2 * DAYSECS, $now - 2 * HOURSECS);
        $record = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id]);
        $DB->update_record('user_enrolments', (object)['id' => $record->id, 'timemodified' => $now - 2 * DAYSECS]);

        $this->assertFalse(is_enrolled($context, $user, '', true));
        $this->assertEquals(40, $inst->get_cost_after_discount(true));
    }
}
