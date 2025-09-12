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

use enrol_wallet\local\config;
use enrol_wallet\local\utils\timedate;
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
final class locallib_test extends \advanced_testcase {

    /**
     * test_enrol_wallet_is_borrow_eligible
     * @covers ::enrol_wallet_is_borrow_eligible()
     * @return void
     */
    public function test_enrol_wallet_is_borrow_eligible(): void {
        global $DB;
        $this->resetAfterTest();

        $now = timedate::time();
        $config = config::make();
        // Eligibal user.
        $user1 = $this->getDataGenerator()->create_user(['firstaccess' => $now - 70 * DAYSECS]);
        // No borrow for new users.
        $user2 = $this->getDataGenerator()->create_user(['firstaccess' => $now - 10 * DAYSECS]);
        // No enough transactions.
        $user3 = $this->getDataGenerator()->create_user(['firstaccess' => $now - 70 * DAYSECS]);
        // Old transactions.
        $user4 = $this->getDataGenerator()->create_user(['firstaccess' => $now - 70 * DAYSECS]);

        $this->assertFalse(enrol_wallet_is_borrow_eligible($user2));

        $config->borrowtrans = 3;
        $config->borrowperiod = 15 * DAYSECS;
        transactions::payment_topup(20, $user1->id);
        transactions::payment_topup(20, $user1->id);
        transactions::payment_topup(20, $user1->id);

        transactions::payment_topup(20, $user2->id);
        transactions::payment_topup(20, $user2->id);
        transactions::payment_topup(20, $user2->id);

        transactions::payment_topup(20, $user3->id);
        transactions::payment_topup(20, $user3->id);
        transactions::debit($user3->id, 10);

        $now = timedate::time();
        $transaction = ['userid' => $user4->id, 'amount' => 20, 'type' => 'credit', 'timecreated' => $now - 20 * DAYSECS];
        $transaction['balance'] = 20;
        $DB->insert_record('enrol_wallet_transactions', (object)$transaction, false, true);
        $transaction['balance'] = 40;
        $DB->insert_record('enrol_wallet_transactions', (object)$transaction, false, true);
        $transaction['balance'] = 60;
        $DB->insert_record('enrol_wallet_transactions', (object)$transaction, false, true);

        $this->assertFalse(enrol_wallet_is_borrow_eligible($user1));
        $this->assertFalse(enrol_wallet_is_borrow_eligible($user2));
        $this->assertFalse(enrol_wallet_is_borrow_eligible($user3));
        $this->assertFalse(enrol_wallet_is_borrow_eligible($user4));

        $config->borrowenable = 1;
        // Enable Borrwing.
        $this->assertTrue(enrol_wallet_is_borrow_eligible($user1));
        $this->assertFalse(enrol_wallet_is_borrow_eligible($user2));
        $this->assertFalse(enrol_wallet_is_borrow_eligible($user3));
        $this->assertFalse(enrol_wallet_is_borrow_eligible($user4));

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $wallet = enrol_wallet_plugin::get_plugin();
        // Update the instance such that the enrol duration is 2 hours.
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance->customint6 = 1;
        $instance->cost = 100;
        $DB->update_record('enrol', $instance);
        $wallet->update_status($instance, ENROL_INSTANCE_ENABLED);
        $this->setUser($user1);
        $this->assertTrue($wallet->can_self_enrol($instance));
        $wallet->enrol_self($instance, $user1);

        $this->setUser($user2);
        $this->assertEquals(2, $wallet->can_self_enrol($instance));
        try {
            $wallet->enrol_self($instance, $user2);
        } catch (\moodle_exception $e) {
            $error = $e;
        }
        $this->assertTrue(!empty($error));

        $this->setUser($user3);
        $this->assertEquals(2, $wallet->can_self_enrol($instance));

        $this->setAdminUser();

        $this->assertTrue(is_enrolled($context, $user1));
        $this->assertFalse(is_enrolled($context, $user2, $error->getMessage()));

        $this->assertFalse(enrol_wallet_is_borrow_eligible($user1));
        $this->assertEquals(-40, transactions::get_user_balance($user1->id));
        $this->assertEquals(60, transactions::get_user_balance($user2->id));
    }
}
