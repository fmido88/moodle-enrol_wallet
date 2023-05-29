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
     * mock_transaction_notify
     * @param array $data
     * @return bool
     */
    public static function mock_transaction_notify($data) {
        return true;
    }
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
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        if (!enrol_is_enabled('wallet')) {
            $class = \core_plugin_manager::resolve_plugininfo_class('enrol');
            $class::enable_plugin('wallet', true);
        }
        $this->assertTrue(enrol_is_enabled('wallet'));
        $plugin = enrol_get_plugin('wallet');
        $this->assertInstanceOf('enrol_wallet_plugin', $plugin);

        $mocknotifications = $this->createMock('\enrol_wallet\notifications');
        $this->transactions->notify = $mocknotifications;
        $mocknotifications->method('transaction_notify')
            ->will($this->returnCallback([$this, 'mock_transaction_notify']));

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Mo', 'lastname' => 'Farouk']);

        $balance = $this->transactions->get_user_balance($user->id);
        $this->assertEquals(0, $balance);

        $this->transactions->payment_topup(250, $user->id);
        $balance = $this->transactions->get_user_balance($user->id);
        $this->assertEquals(250, $balance);

        $debit = $this->transactions->debit($user->id, 50);
        $this->assertEquals('done', $debit);

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

        transactions::mark_coupon_used('test1', $user->id, 0);

        $coupondata = transactions::get_coupon_value('test1', $user->id);

        $this->assertTrue(is_string($coupondata));

        $usage = $DB->get_record('enrol_wallet_coupons_usage', ['code' => 'test1']);

        $this->assertEquals($user->id, $usage->userid);
    }

}
