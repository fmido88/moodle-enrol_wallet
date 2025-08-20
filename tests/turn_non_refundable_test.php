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

use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet\task\turn_non_refundable;

/**
 * Testing the adhoc task to transform certain amount to nonrefundable.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class turn_non_refundable_test extends \advanced_testcase {
    /**
     * Test adhoc task turn_non_refundable.
     * @covers \turn_non_refundable
     * @return void
     */
    public function test_turn_non_refundable(): void {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cat1 = $this->getDataGenerator()->create_category();

        $period = get_config('enrol_wallet', 'refundperiod');
        $this->assertEquals(14 * DAYSECS, $period);

        // Site balance.
        $op1 = new balance_op($user1->id);
        $op2 = new balance_op($user2->id, $cat1);
        $op1->credit(200);
        $op2->credit(300);

        $this->assertEquals(200, $op1->get_main_balance());
        $this->assertEquals(0, $op2->get_main_nonrefundable());

        $this->assertEquals(0, $op2->get_main_balance());
        $this->assertEquals(0, $op2->get_main_nonrefundable());
        $this->assertEquals(300, $op2->get_valid_balance());
        $this->assertEquals(0, $op2->get_valid_nonrefundable());
        $this->assertEquals(0, $op2->get_total_free());
        $taskdata1 = [
            'userid' => $user1->id,
            'amount' => 50,
        ];
        $taskdata2 = [
            'userid' => $user2->id,
            'amount' => 50,
            'catid'  => $cat1->id,
        ];

        $task1 = new turn_non_refundable;
        $task1->set_custom_data($taskdata1);

        $task2 = new turn_non_refundable;
        $task2->set_custom_data($taskdata2);

        ob_start();
        $task1->execute();
        $task2->execute();
        ob_end_clean();

        $op1 = new balance_op($user1->id);

        $this->assertEquals(200, $op1->get_main_balance());
        $this->assertEquals(50, $op1->get_main_nonrefundable());
        $this->assertEquals(0, $op1->get_total_free());

        $op2 = new balance_op($user2->id, $cat1);

        $this->assertEquals(0, $op2->get_main_balance());
        $this->assertEquals(0, $op2->get_main_nonrefundable());
        $this->assertEquals(300, $op2->get_valid_balance());
        $this->assertEquals(50, $op2->get_valid_nonrefundable());
        $this->assertEquals(0, $op2->get_total_free());

        $op1 = new balance_op($user1->id);
        $op2 = new balance_op($user2->id, $cat1);
        // Test that the debited amount not transformed as it already used.
        $op1->debit(40, $op1::OTHER);
        $op2->debit(40, $op2::OTHER);

        $task1 = new turn_non_refundable;
        $task1->set_custom_data($taskdata1);

        $task2 = new turn_non_refundable;
        $task2->set_custom_data($taskdata2);

        ob_start();
        $task1->execute();
        $task2->execute();
        ob_end_clean();

        $op1 = new balance_op($user1->id);
        $op2 = new balance_op($user2->id, $cat1);

        $this->assertEquals(160, $op1->get_main_balance());
        $this->assertEquals(60, $op1->get_main_nonrefundable());
        $this->assertEquals(0, $op1->get_total_free());

        $this->assertEquals(0, $op2->get_main_balance());
        $this->assertEquals(0, $op2->get_main_nonrefundable());
        $this->assertEquals(260, $op2->get_valid_balance());
        $this->assertEquals(60, $op2->get_valid_nonrefundable());
        $this->assertEquals(0, $op2->get_total_free());

        // Test that if the amount is greater than the balance, nonrefundable not exceed balance.
        $taskdata1['amount'] = 250;
        $taskdata2['amount'] = 500;

        $task1 = new turn_non_refundable;
        $task1->set_custom_data($taskdata1);

        $task2 = new turn_non_refundable;
        $task2->set_custom_data($taskdata2);

        ob_start();
        $task1->execute();
        $task2->execute();
        ob_end_clean();

        $bal1 = new balance($user1->id);
        $bal2 = new balance($user2->id, $cat1->id);

        $this->assertEquals(160, $bal1->get_main_balance());
        $this->assertEquals(160, $bal1->get_main_nonrefundable());
        $this->assertEquals(0, $bal1->get_total_free());

        $this->assertEquals(0, $bal2->get_main_balance());
        $this->assertEquals(0, $bal2->get_main_nonrefundable());
        $this->assertEquals(260, $bal2->get_valid_balance());
        $this->assertEquals(260, $bal2->get_valid_nonrefundable());
        $this->assertEquals(0, $bal2->get_total_free());
    }

    /**
     * test_check_transform_validation
     * @covers ::check_transform_validation()
     * @return void
     */
    public function test_check_transform_validation(): void {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cat1 = $this->getDataGenerator()->create_category();

        // Charge the wallet with already nonrefundable balance.
        $op1 = new balance_op($user1->id);
        $op2 = new balance_op($user2->id, $cat1);
        $op1->credit(200, $op1::OTHER, 0, '', false);
        $op2->credit(200, $op2::OTHER, 0, '', false);

        $data1 = (object)[
            'userid' => $user1->id,
            'amount' => 200,
        ];
        $data2 = (object)[
            'userid' => $user2->id,
            'amount' => 200,
            'catid'  => $cat1->id,
        ];

        $task1 = new turn_non_refundable;
        $task2 = new turn_non_refundable();

        $trace = new \null_progress_trace;
        $output1 = $task1->check_transform_validation($data1, $trace);
        $output2 = $task2->check_transform_validation($data2, $trace);

        $this->assertStringContainsString('Non refundable amount grater than or equal user\'s balance', $output1);
        $this->assertStringContainsString('Non refundable amount grater than or equal user\'s balance', $output2);

        // Charge more with refundable balance.
        $op1->credit(100);
        $op2->credit(100);
        $output3 = $task1->check_transform_validation($data1, $trace);
        $output4 = $task2->check_transform_validation($data2, $trace);

        $this->assertEquals($output3, 200);
        $this->assertEquals($output4, 200);

        // Not transform what already used.
        $op1->debit(50, $op1::OTHER);
        $op2->debit(50, $op2::OTHER);
        $output5 = $task1->check_transform_validation($data1, $trace);
        $output6 = $task1->check_transform_validation($data2, $trace);
        $this->assertEquals($output5, 150);
        $this->assertEquals($output6, 150);

        $data1->amount = 40;
        $data2->amount = 40;

        $output7 = $task1->check_transform_validation($data1, $trace);
        $output8 = $task2->check_transform_validation($data2, $trace);
        $this->assertStringContainsString('user spent this amount in the grace period already', $output7);
        $this->assertStringContainsString('user spent this amount in the grace period already', $output8);
    }
}
