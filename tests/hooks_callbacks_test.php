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
 * Hooks callbacks tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet;

use enrol_wallet\hooks_callbacks;

/**
 * Hooks callbacks tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hooks_callbacks_test extends \advanced_testcase {

    /**
     * Test shouldnt method.
     * @covers ::shouldnt()
     */
    public function test_shouldnt(): void {
        $this->resetAfterTest();

        // Test shouldnt with login check.
        $result = hooks_callbacks::shouldnt(true);
        $this->assertIsBool($result);

        // Test shouldnt without login check.
        $result = hooks_callbacks::shouldnt(false);
        $this->assertIsBool($result);
        // Todo: Test more cases.
    }

    /**
     * Test show_price method.
     * @covers ::show_price()
     */
    public function test_show_price(): void {

    }

    /**
     * Test low_balance_warning method.
     * @covers ::low_balance_warning()
     */
    public function test_low_balance_warning(): void {

    }

    /**
     * Test primary_navigation_tabs method.
     * @covers ::primary_navigation_tabs()
     */
    public function test_primary_navigation_tabs(): void {

    }

    /**
     * Test add_my_wallet method.
     * @covers ::add_my_wallet()
     */
    public function test_add_my_wallet(): void {

    }

    /**
     * Test add_offers method.
     * @covers ::add_offers()
     */
    public function test_add_offers(): void {

    }
}
