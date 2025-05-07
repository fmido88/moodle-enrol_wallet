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
final class transactions_test extends \advanced_testcase {

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Testing the functionalities of adding and deducting credits
     * from user's wallet.
     *
     * @covers ::credit()
     * @covers ::debit()
     * @covers ::get_valid_balance()
     * @return void
     */
    public function test_credit_debit(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();

        $this->assertTrue(enrol_is_enabled('wallet'));
        $plugin = enrol_get_plugin('wallet');
        $this->assertInstanceOf('enrol_wallet_plugin', $plugin);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Mo', 'lastname' => 'Farouk']);

        $balance_op = new \enrol_wallet\util\balance_op($user->id);
        $balance = $balance_op->get_valid_balance();
        $this->assertEquals(0, $balance);

        $sink = $this->redirectEvents();
        $balance_op->credit(250, \enrol_wallet\util\balance_op::OTHER, 0, 'Test credit');
        $events = $sink->get_events();
        $sink->clear();

        $balance = $balance_op->get_valid_balance();
        $norefund = $balance_op->get_valid_nonrefundable();
        $this->assertEquals(250, $balance);
        $this->assertEquals(0, $norefund);
        // Two events: transaction_triggered and notification_sent.
        $this->assertEquals(2, count($events));
        $this->assertInstanceOf('\enrol_wallet\event\transactions_triggered', $events[1]);
        $this->assertEquals(250, $events[1]->other['amount']);
        $this->assertEquals('credit', $events[1]->other['type']);

        $balance_op->debit(50, \enrol_wallet\util\balance_op::OTHER, 0, 'Test debit');
        $events = $sink->get_events();
        $sink->close();

        $this->assertEquals(2, count($events));
        $this->assertInstanceOf('\enrol_wallet\event\transactions_triggered', $events[1]);
        $this->assertEquals(50, $events[1]->other['amount']);
        $this->assertEquals('debit', $events[1]->other['type']);

        $count = $DB->count_records('enrol_wallet_transactions', ['userid' => $user->id]);
        $this->assertEquals(2, $count);

        $balance = $balance_op->get_valid_balance();
        $this->assertEquals(200, $balance);

        try {
            $balance_op->debit(250, \enrol_wallet\util\balance_op::OTHER, 0, 'Test debit');
        } catch (\moodle_exception $e) {
            $msg = $e->getMessage();
        }
        $balance = $balance_op->get_valid_balance();
        $this->assertEquals(200, $balance, $msg);

        // Check that the value is nonrefundable.
        $user2 = $this->getDataGenerator()->create_user();
        $balance_op2 = new \enrol_wallet\util\balance_op($user2->id);
        $balance_op2->credit(50, \enrol_wallet\util\balance_op::OTHER, 0, 'Test credit', false);
        $balance = $balance_op2->get_valid_balance();
        $norefund = $balance_op2->get_valid_nonrefundable();
        $this->assertEquals(50, $balance);
        $this->assertEquals(50, $norefund);

        // Check disable refund will make all values nonrefundable.
        set_config('enablerefund', 0, 'enrol_wallet');
        $user3 = $this->getDataGenerator()->create_user();
        $balance_op3 = new \enrol_wallet\util\balance_op($user3->id);
        $balance_op3->credit(100, \enrol_wallet\util\balance_op::OTHER, 0, 'Test credit');
        $balance = $balance_op3->get_valid_balance();
        $norefund = $balance_op3->get_valid_nonrefundable();
        $this->assertEquals(100, $balance);
        $this->assertEquals(100, $norefund);

        // Testing that debit process will deduct from the refundable credit first.
        set_config('enablerefund', 1, 'enrol_wallet');
        $user4 = $this->getDataGenerator()->create_user();
        $balance_op4 = new \enrol_wallet\util\balance_op($user4->id);
        $balance_op4->credit(100, \enrol_wallet\util\balance_op::OTHER, 0, 'Test credit');
        $balance_op4->credit(100, \enrol_wallet\util\balance_op::OTHER, 0, 'Test credit', false);
        $balance = $balance_op4->get_valid_balance();
        $norefund = $balance_op4->get_valid_nonrefundable();
        $this->assertEquals(200, $balance);
        $this->assertEquals(100, $norefund);

        $balance_op4->debit(50, \enrol_wallet\util\balance_op::OTHER, 0, 'Test debit');
        $balance = $balance_op4->get_valid_balance();
        $norefund = $balance_op4->get_valid_nonrefundable();
        $this->assertEquals(150, $balance);
        $this->assertEquals(100, $norefund);

        $balance_op4->debit(100, \enrol_wallet\util\balance_op::OTHER, 0, 'Test debit');
        $balance = $balance_op4->get_valid_balance();
        $norefund = $balance_op4->get_valid_nonrefundable();
        $this->assertEquals(50, $balance);
        $this->assertEquals(50, $norefund);
    }

    /**
     * Testing the functions get_coupon_value and mark_coupon_used
     * This is for fixed value coupons only.
     *
     * @covers ::get_coupon_value()
     * @covers ::mark_coupon_used()
     * @return void
     */
    public function test_get_coupon_value(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Messaging does not like transactions...

        $user = $this->getDataGenerator()->create_user();
        set_config('coupons', \enrol_wallet_plugin::WALLET_COUPONSALL, 'enrol_wallet');
        $coupon = [
            'code' => 'test1',
            'type' => 'fixed',
            'value' => 50,
            'maxusage' => 1,
        ];
        $DB->insert_record('enrol_wallet_coupons', $coupon);
        $couponhelper = new coupons('test1', $user->id);

        $this->assertEquals(50, $couponhelper->get_value());
        $this->assertEquals('fixed', $couponhelper->get_type());
        $this->assertTrue($couponhelper->validate_coupon(coupons::AREA_TOPUP));
        $sink = $this->redirectEvents();
        $couponhelper->mark_coupon_used();
        // Check the event triggered.
        $events = $sink->get_events();
        $sink->close();
        $this->assertEquals(1, count($events));
        $this->assertInstanceOf('\enrol_wallet\event\coupon_used', $events[0]);
        $this->assertEquals('test1', $events[0]->other['code']);

        $coupondata = coupons::get_coupon_value('test1', $user->id);

        $this->assertTrue(is_string($coupondata));

        $usage = $DB->get_record('enrol_wallet_coupons_usage', ['code' => 'test1']);

        $this->assertEquals($user->id, $usage->userid);
    }

    /**
     * Test referral on wallet top-up functionality.
     */
    public function test_referral_on_topup() {
        $this->resetAfterTest();

        // Enable referral on top-up and set minimum amount.
        set_config('referral_on_topup', 1, 'enrol_wallet');
        set_config('referral_topup_minimum', 50, 'enrol_wallet');
        set_config('referral_amount', 10, 'enrol_wallet');

        // Create users.
        $referrer = $this->getDataGenerator()->create_user();
        $referred = $this->getDataGenerator()->create_user();

        // Set up referral.
        $holdgift = new \stdClass();
        $holdgift->referrer = $referrer->id;
        $holdgift->referred = $referred->id;
        $holdgift->amount = 10;
        $holdgift->released = 0;
        $holdgift->timecreated = time();
        $holdgift->timemodified = time();
        $DB->insert_record('enrol_wallet_hold_gift', $holdgift);

        $balance_op_referrer = new \enrol_wallet\util\balance_op($referrer->id);
        $balance_op_referred = new \enrol_wallet\util\balance_op($referred->id);

        // Top up below minimum - should not trigger referral.
        $balance_op_referred->credit(40, \enrol_wallet\util\balance_op::OTHER, 0, 'Test top-up');
        $this->assertEquals(0, $balance_op_referrer->get_valid_balance());
        $this->assertEquals(40, $balance_op_referred->get_valid_balance());

        // Top up above minimum - should trigger referral.
        $balance_op_referred->credit(60, \enrol_wallet\util\balance_op::OTHER, 0, 'Test top-up');
        $this->assertEquals(10, $balance_op_referrer->get_valid_balance());
        $this->assertEquals(110, $balance_op_referred->get_valid_balance());

        // Check that referral is marked as released.
        $this->assertTrue($DB->record_exists('enrol_wallet_hold_gift', ['referred' => $referred->id, 'released' => 1]));
    }
}
