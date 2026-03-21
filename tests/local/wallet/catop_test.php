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
final class catop_test extends \advanced_testcase {
    /**
     * Test constructor.
     * @covers ::__construct()
     * @return void
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $category = $this->getDataGenerator()->create_category();

        $catdetails = [$category->id => new catdetails(12, 5, 2)];
        $details = new details(50, 20, 10, [$category->id], $catdetails);
        $catop = new catop($category, $details);
    }
    // Todo: test steps
    // 1. create multiple nested categories at least 9 with depth up to 3 or 4
    // 2. add virtual balances to each wih a catdetails object (no need for real balance)
    // 3. test getter for balance with confirming right calculations
    // 4. test add and deduct
    // 5. retest getters of balances.
}
