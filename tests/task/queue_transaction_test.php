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

namespace enrol_wallet\task;

use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;

/**
 * Tests for Wallet enrolment.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class queue_transaction_test extends \advanced_testcase {
    /**
     * Test queue_trasaction task.
     * @covers \enrol_wallet\task\queue_trasaction
     */
    public function test_queue_trasaction_task(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $task = new queue_trasaction();

        $b = new balance($user->id);
        $this->assertEquals(0, $b->get_total_balance());

        // Check task properties.
        $this->assertNotEmpty($task->get_name());

        // Set custom data for the task.
        $data = (object)[
            'userid'     => $user->id,
            'amount'     => 100,
            'method'     => 'credit',
            'by'         => balance_op::OTHER,
            'thingid'    => 0,
            'desc'       => '',
            'refundable' => false,
            'trigger'    => true,
        ];
        $task->set_custom_data($data);
        // Execute the task.
        $task->execute();

        $b = new balance($user->id);
        $this->assertEquals(100, $b->get_total_balance());
    }
}
