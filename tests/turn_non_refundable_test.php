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
use enrol_wallet\util\balance;
use enrol_wallet\util\balance_op;

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
     * @return void
     */
    public function test_turn_non_refundable(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $period = get_config('enrol_wallet', 'refundperiod');
        $this->assertEquals(14 * DAYSECS, $period);

        $op = new balance_op($user->id);
        $op->credit(200);
        $balance = $op->get_main_balance();
        $norefund = $op->get_main_nonrefundable();

        $this->assertEquals(200, $balance);
        $this->assertEquals(0, $norefund);

        $taskdata = [
            'userid' => $user->id,
            'amount' => 50,
        ];

        $task = new turn_non_refundable;
        $task->set_custom_data($taskdata);

        ob_start();
        $task->execute();
        ob_end_clean();
        $op = new balance_op($user->id);
        $balance = $op->get_main_balance();
        $norefund = $op->get_main_nonrefundable();

        $this->assertEquals(200, $balance);
        $this->assertEquals(50, $norefund);
        $this->assertEquals(0, $op->get_total_free());

        // Test that the debited amount not transformed as it already used.
        $op->debit(40, $op::OTHER);

        $taskdata['amount'] = 50;

        $task = new turn_non_refundable;
        $task->set_custom_data($taskdata);

        ob_start();
        $task->execute();
        ob_end_clean();

        $op = new balance_op($user->id);

        $this->assertEquals(160, $op->get_main_balance());
        $this->assertEquals(60, $op->get_main_nonrefundable());
        $this->assertEquals(0, $op->get_total_free());

        // Test that if the amount is greater than the balance, nonrefundable not exceed balance.
        $taskdata['amount'] = 250;

        $task = new turn_non_refundable;
        $task->set_custom_data($taskdata);

        ob_start();
        $task->execute();
        ob_end_clean();

        $bal = new balance($user->id);
        $balance = $bal->get_main_balance();
        $norefund = $bal->get_main_nonrefundable();

        $this->assertEquals(160, $balance);
        $this->assertEquals(160, $norefund);
        $this->assertEquals(0, $bal->get_total_free());
    }

    /**
     * test_check_transform_validation
     * @covers ::check_transform_validation()
     * @return void
     */
    public function test_check_transform_validation(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        // Charge the wallet with already nonrefundable balance.
        $op = new balance_op($user->id);
        $op->credit(200, $op::OTHER, 0, '', false);

        $data = (object)[
            'userid' => $user->id,
            'amount' => 200,
        ];
        $task = new turn_non_refundable;
        $trace = new \null_progress_trace;
        $output1 = $task->check_transform_validation($data, $trace);
        $this->assertStringContainsString('Non refundable amount grater than or equal user\'s balance', $output1);

        // Charge more with refundable balance.
        $op->credit(100);
        $output2 = $task->check_transform_validation($data, $trace);
        $this->assertEquals($output2, 200);

        // Not transform what already used.
        $op->debit(50, $op::OTHER);
        $output3 = $task->check_transform_validation($data, $trace);
        $this->assertEquals($output3, 150);

        $data->amount = 40;
        $output4 = $task->check_transform_validation($data, $trace);
        $this->assertStringContainsString('user spent this amount in the grace period already', $output4);
    }
}
