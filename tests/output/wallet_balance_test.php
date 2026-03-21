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

namespace enrol_wallet\output;

use core\output\renderable;

// Todo: Merge these tests into one (three top) and assert the returned data from
// the export and test rendered data and the string contains these info.

/**
 * Tests for Wallet enrolment.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wallet_balance_test extends \advanced_testcase {
    /**
     * Test wallet_balance export.
     * @covers ::export_for_template()
     */
    public function test_wallet_balance_export(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $renderable = new wallet_balance($user->id);

        // Create a mock renderer.
        $renderer = helper::get_wallet_renderer();

        $result = $renderable->export_for_template($renderer);
        // Todo: assert each property.
    }

    /**
     * Test wallet_balance renderable instantiation.
     * @covers ::__construct()
     */
    public function test_wallet_balance_instantiation(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $renderable = new wallet_balance($user->id);

        $this->assertInstanceOf(renderable::class, $renderable);
    }

    /**
     * Test wallet_balance with zero balance.
     * @covers ::export_for_template()
     */
    public function test_wallet_balance_zero(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        // User has no balance.
        $renderable = new wallet_balance($user->id);

        $renderer = helper::get_wallet_renderer();
        $result   = $renderable->export_for_template($renderer);
    }

    /**
     * Test wallet_balance with positive balance.
     * @covers ::export_for_template()
     */
    public function test_wallet_balance_positive(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        // Give user some balance.
        $op = new \enrol_wallet\local\wallet\balance_op($user->id);
        $op->credit(100);

        $renderable = new wallet_balance($user->id);

        $renderer = helper::get_wallet_renderer();
        $result   = $renderable->export_for_template($renderer);
    }

    /**
     * Test wallet_balance with category.
     * @covers ::export_for_template()
     */
    public function test_wallet_balance_with_category(): void {
        $this->resetAfterTest();

        $user     = $this->getDataGenerator()->create_user();
        $category = $this->getDataGenerator()->create_category();

        // Give user main balance.
        $op = new \enrol_wallet\local\wallet\balance_op($user->id);
        $op->credit(100);

        // Give category balance.
        $op = new \enrol_wallet\local\wallet\balance_op($user->id, $category->id);
        $op->credit(50);

        $renderable = new wallet_balance($user->id);

        $renderer = helper::get_wallet_renderer();
        $result   = $renderable->export_for_template($renderer);
    }
}
