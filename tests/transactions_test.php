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
 * @category   phpunit
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
 * @category   phpunit
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transactions_test extends \advanced_testcase {
    /**
     * Testing the functionalities of adding and deducting credits
     * from user's wallet.
     */
    public function test_credit_debit() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $balance = transactions::get_user_balance($user->id);

        $this->assertEquals(0, $balance);

        transactions::payment_topup(250, $user->id);

        $balance = transactions::get_user_balance($user->id);

        $this->assertEquals(250, $balance);

        transactions::debit($user->id, 50);

        $balance = transactions::get_user_balance($user->id);

        $this->assertEquals(200, $balance);

        transactions::debit($user->id, 250);

        $balance = transactions::get_user_balance($user->id);

        $this->assertEquals(200, $balance);
    }


    /**
     * Testing the functions get_coupon_value and mark_coupon_as_used
     * This is for fixed value coupons only.
     */
    public function test_get_coupon_value() {
        global $CFG, $DB;

        $this->resetAfterTest();

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
