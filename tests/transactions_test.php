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
class transactions_test extends \advanced_testcase {
    /**
     * The transactions class.
     * @var
     */
    private $transactions;

    /**
     * Setup.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);

        $this->transactions = new \enrol_wallet\transactions();

    }
    /**
     * Testing the functionalities of adding and deducting credits
     * from user's wallet.
     *
     * @covers ::payment_topup()
     * @covers ::debit()
     * @covers ::get_user_balance()
     */
    public function test_credit_debit() {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        enrol_wallet_enable_plugin();

        $this->assertTrue(enrol_is_enabled('wallet'));
        $plugin = enrol_get_plugin('wallet');
        $this->assertInstanceOf('enrol_wallet_plugin', $plugin);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Mo', 'lastname' => 'Farouk']);

        $balance = $this->transactions->get_user_balance($user->id);
        $this->assertEquals(0, $balance);

        $sink = $this->redirectEvents();
        $this->transactions->payment_topup(250, $user->id);
        $events = $sink->get_events();
        $sink->clear();

        $balance = $this->transactions->get_user_balance($user->id);
        $this->assertEquals(250, $balance);
        // Two events: transaction_triggered and notification_sent.
        $this->assertEquals(2, count($events));
        $this->assertInstanceOf('\enrol_wallet\event\transactions_triggered', $events[0]);
        $this->assertEquals(250, $events[0]->other['amount']);
        $this->assertEquals('credit', $events[0]->other['type']);

        $this->transactions->debit($user->id, 50);
        $events = $sink->get_events();
        $sink->close();

        $this->assertEquals(2, count($events));
        $this->assertInstanceOf('\enrol_wallet\event\transactions_triggered', $events[0]);
        $this->assertEquals(50, $events[0]->other['amount']);
        $this->assertEquals('debit', $events[0]->other['type']);

        $count = $DB->count_records('enrol_wallet_transactions', ['userid' => $user->id]);
        $this->assertEquals(2, $count);

        $balance = $this->transactions->get_user_balance($user->id);
        $this->assertEquals(200, $balance);

        $this->transactions->debit($user->id, 250);
        $balance = $this->transactions->get_user_balance($user->id);
        $this->assertEquals(200, $balance);
    }

    /**
     * Testing the functions get_coupon_value and mark_coupon_used
     * This is for fixed value coupons only.
     *
     * @covers ::get_coupon_value()
     * @covers ::mark_coupon_used()
     */
    public function test_get_coupon_value() {
        global $CFG, $DB;
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

        $coupondata = transactions::get_coupon_value('test1', $user->id);

        $this->assertEquals(50, $coupondata['value']);
        $this->assertEquals('fixed', $coupondata['type']);

        $sink = $this->redirectEvents();
        transactions::mark_coupon_used('test1', $user->id, 0);
        // Check the event triggered.
        $events = $sink->get_events();
        $sink->close();
        $this->assertEquals(1, count($events));
        $this->assertInstanceOf('\enrol_wallet\event\coupon_used', $events[0]);
        $this->assertEquals('test1', $events[0]->other['code']);

        $coupondata = transactions::get_coupon_value('test1', $user->id);

        $this->assertTrue(is_string($coupondata));

        $usage = $DB->get_record('enrol_wallet_coupons_usage', ['code' => 'test1']);

        $this->assertEquals($user->id, $usage->userid);
    }

}
