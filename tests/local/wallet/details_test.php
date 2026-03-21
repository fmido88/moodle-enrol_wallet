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

namespace enrol_wallet\local\wallet;

/**
 * Tests for Wallet enrolment
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class details_test extends \advanced_testcase {
    /**
     * Test calculations of balances.
     * @covers ::__get()
     * @covers ::calculate()
     * @return void
     */
    public function test_no_category_calculations(): void {
        $this->resetAfterTest();
        $details = new details(50, 20, 5);

        $this->assertEquals(50, $details->mainrefundable);
        $this->assertEquals(50, $details->total_refundable);
        $this->assertEquals(50, $details->total_refundable);
        $this->assertEquals(20, $details->nonrefundable);
        $this->assertEquals(20, $details->mainnonrefund);
        $this->assertEquals(20, $details->norefund);
        $this->assertEquals(20, $details->valid_nonrefundable);
        $this->assertEquals(20, $details->validnonrefundable);
    }
    // Todo: Test calculations with at least 5 categories
    // some included in catid and other not, but all should have catdetails objects.
}
