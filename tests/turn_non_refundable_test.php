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
 * Testing the adhoc task to transform certain amount to nonrefundable.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;

use enrol_wallet\transactions;
use enrol_wallet\task\turn_non_refundable;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
/**
 * Testing the adhoc task to transform certain amount to nonrefundable.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class turn_non_refundable_test extends \advanced_testcase {
    /**
     * Test adhoc task turn_non_refundable.
     * @covers \turn_non_refundable
     */
    public function test_turn_non_refundable() {
        $this->resetAfterTest();
        enrol_wallet_enable_plugin();
        $user = $this->getDataGenerator()->create_user();

        $period = get_config('enrol_wallet', 'refundperiod');
        $this->assertEquals(14 * DAYSECS, $period);

        transactions::payment_topup(200, $user->id);
        $balance = transactions::get_user_balance($user->id);
        $norefund = transactions::get_nonrefund_balance($user->id);

        $this->assertEquals(200, $balance);
        $this->assertEquals(0, $norefund);

        $taskdata = [
            'userid' => $user->id,
            'amount' => 50,
        ];

        $task = new turn_non_refundable;
        $task->set_custom_data($taskdata);
        $task->execute();

        $balance = transactions::get_user_balance($user->id);
        $norefund = transactions::get_nonrefund_balance($user->id);

        $this->assertEquals(200, $balance);
        $this->assertEquals(50, $norefund);

        // Test that the debited amount not transformed as it already used.
        transactions::debit($user->id, 40);

        $taskdata['amount'] = 50;

        $task = new turn_non_refundable;
        $task->set_custom_data($taskdata);
        $task->execute();

        $balance = transactions::get_user_balance($user->id);
        $norefund = transactions::get_nonrefund_balance($user->id);

        $this->assertEquals(160, $balance);
        $this->assertEquals(60, $norefund);

        // Test that if the amount is greater than the balance, nonrefundable not exceed balance.
        $taskdata['amount'] = 250;

        $task = new turn_non_refundable;
        $task->set_custom_data($taskdata);
        $task->execute();

        $balance = transactions::get_user_balance($user->id);
        $norefund = transactions::get_nonrefund_balance($user->id);

        $this->assertEquals(160, $balance);
        $this->assertEquals(160, $norefund);
    }
}
