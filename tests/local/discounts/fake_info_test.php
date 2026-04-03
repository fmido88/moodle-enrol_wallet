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

namespace enrol_wallet\local\discounts;

/**
 * Tests for Wallet enrolment
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\fake_info
 */
final class fake_info_test extends \advanced_testcase {
    /**
     * Test fake_info class.
     * @covers ::__construct()
     */
    public function test_constructor(): void {
        $this->resetAfterTest();

        // Create fake info.
        $fakeinfo = new fake_info();
        $this->assertInstanceOf(\core_availability\info::class, $fakeinfo);
    }
    /**
     * Test the most probably called methods and just
     * make sure that non will throw errors.
     * @covers ::get_context()
     * @covers ::get_course()
     * @return void
     */
    public function test_methods(): void {
        $this->resetAfterTest();
        $fakeinfo = new fake_info();
        $context = $fakeinfo->get_context();
        $course = $fakeinfo->get_course();
        // Reached without errors.
        $this->assertTrue(true);
    }
}
