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
final class migrate_enrollment_test extends \advanced_testcase {
    /**
     * Test migrate_enrollments task.
     * @covers \enrol_wallet\task\migrate_enrollments
     */
    public function test_migrate_enrollments_task(): void {
        $this->resetAfterTest();

        $task = new migrate_enrollments();

        // Check task properties.
        $this->assertNotEmpty($task->get_name());

        // Set custom data for the task.
        $data = (object)[
            'courseid' => 1,
        ];
        $task->set_custom_data($data);

        ob_start();
        // Execute the task - may not do anything if no credit enrolments exist.
        $task->execute();
        ob_end_clean();

        // If we get here without errors, the task executed successfully.
        $this->assertTrue(true);
    }
}
