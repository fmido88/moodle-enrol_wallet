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

use core\exception\coding_exception;

/**
 * Tests for Wallet enrolment
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class catdetails_test extends \advanced_testcase {
    /**
     * Testing cat details
     * @covers ::__get()
     * @return void
     */
    public function test_cat_details(): void {
        $this->resetAfterTest();
        $det = new catdetails(50, 15, 10);
        $this->assertEquals(50, $det->refundable);
        $this->assertEquals(15, $det->nonrefundable);
        $this->assertEquals(65, $det->balance);
        $this->assertEquals(10, $det->free);

        $this->assertTrue(isset($det->balance));
        $this->assertFalse(isset($det->random));

        $this->expectException(coding_exception::class);
        $det->balance = 200;
    }
}
