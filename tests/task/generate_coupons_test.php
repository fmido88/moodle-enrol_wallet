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

/**
 * Tests for Wallet enrolment.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generate_coupons_test extends \advanced_testcase {
    /**
     * Test generate_coupons task.
     * @covers \enrol_wallet\task\generate_coupons
     */
    public function test_generate_coupons_task(): void {
        global $DB;
        $this->resetAfterTest();

        $task = new generate_coupons();

        // Check task properties.
        $this->assertNotEmpty($task->get_name());

        $data = (object)[
            'number'     => 5,
            'length'     => 10,
            'upper'      => true,
            'lower'      => true,
            'digits'     => true,
            'type'       => 'fixed',
            'value'      => 50,
            'to'         => 0,
            'from'       => 0,
            'maxperuser' => 0,
            'maxusage'   => 1,
            'code'       => '',
        ];
        $task->set_custom_data($data);

        // Execute the task.
        $task->execute();

        $this->assertEquals(5, $DB->count_records('enrol_wallet_coupons'));
        // If we get here without errors, the task executed successfully.
        $this->assertTrue(true);
    }
}
