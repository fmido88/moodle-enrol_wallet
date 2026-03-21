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
 * Timedate utility tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\utils;

/**
 * Timedate utility tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class timedate_test extends \advanced_testcase {
    /**
     * Test clock method.
     * @covers ::clock()
     */
    public function test_clock(): void {
        $this->resetAfterTest();

        $clock = timedate::clock();
        $this->assertInstanceOf(\core\clock::class, $clock);
    }

    /**
     * Test time method.
     * @covers ::time()
     */
    public function test_time(): void {
        $this->resetAfterTest();

        $time = timedate::time();
        $this->assertIsInt($time);
        $this->assertGreaterThan(0, $time);

        // Should be close to current time.
        $this->assertLessThanOrEqual(time() + 1, $time);
        $this->assertGreaterThanOrEqual(time() - 1, $time);
    }
}
