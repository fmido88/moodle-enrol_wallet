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
 * Tests for external balance operations.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\external;

use core_external\external_api;
use enrol_wallet\external\balance_op as balance_op_ext;
use enrol_wallet\local\wallet\balance_op;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for external balance operations.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class balance_op_test extends externallib_advanced_testcase {
    /**
     * Test get_balance_details external function.
     * @covers ::get_balance_details()
     */
    public function test_get_balance_details(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // Give user some balance.
        $op = new balance_op($user->id);
        $op->credit(100);

        $this->setAdminUser();

        // Call external function.
        $result = balance_op_ext::get_balance_details($user->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_details_returns(), $result);

        $this->assertStringContainsString(100, $result['details']);
        $this->assertStringContainsString('balance', $result['details']);
    }

    /**
     * Test get_balance external function.
     * @covers ::get_balance()
     */
    public function test_get_balance(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // Give user some balance.
        $op = new balance_op($user->id);
        $op->credit(150);

        $this->setAdminUser();

        // Call external function.
        $result = balance_op_ext::get_balance($user->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_returns(), $result);

        $this->assertEquals(150, $result['main']);
        $this->assertEquals(150, $result['total']);
    }

    /**
     * Test get_balance with category.
     * @covers ::get_balance()
     */
    public function test_get_balance_with_category(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user     = $this->getDataGenerator()->create_user();
        $category = $this->getDataGenerator()->create_category();

        // Give user main balance.
        $op = new balance_op($user->id);
        $op->credit(100);

        // Give user category balance.
        $op = new balance_op($user->id, $category->id);
        $op->credit(50);

        $this->setAdminUser();

        // Call external function for main balance.
        $result = balance_op_ext::get_balance($user->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_returns(), $result);

        $this->assertEquals(100, $result['main']);
        $this->assertEquals(150, $result['total']);

        // Call external function with category.
        $result = balance_op_ext::get_balance($user->id, $category->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_returns(), $result);

        $this->assertEquals(100, $result['main']);
        $this->assertEquals(150, $result['total']);
    }

    /**
     * Test get_balance for another user.
     * @covers ::get_balance()
     */
    public function test_get_balance_other_user(): void {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Give user1 some balance.
        $op = new balance_op($user1->id);
        $op->credit(200);

        $this->setAdminUser();

        // Get user1's balance.
        $result = balance_op_ext::get_balance($user1->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_returns(), $result);

        $this->assertEquals(200, $result['main']);

        // Get user2's balance (should be 0).
        $result = balance_op_ext::get_balance($user2->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_returns(), $result);

        $this->assertEquals(0, $result['main']);
    }

    /**
     * Test get_balance with zero balance.
     * @covers ::get_balance()
     */
    public function test_get_balance_zero(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // User has no balance.

        $this->setAdminUser();

        // Call external function.
        $result = balance_op_ext::get_balance($user->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_returns(), $result);

        $this->assertEquals(0, $result['main']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test get_balance with non-refundable balance.
     * @covers ::get_balance()
     */
    public function test_get_balance_non_refundable(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // Give user refundable balance.
        $op = new balance_op($user->id);
        $op->credit(100);

        // Give user non-refundable balance.
        $op->credit(50, $op::OTHER, 0, '', false);

        $this->setAdminUser();

        // Call external function.
        $result = balance_op_ext::get_balance($user->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_returns(), $result);

        $this->assertEquals(150, $result['main']);
        $this->assertEquals(150, $result['total']);
        $this->assertEquals(50, $result['totalnonrefundable']);
    }

    /**
     * Test get_balance_details with category.
     * @covers ::get_balance_details()
     */
    public function test_get_balance_details_with_category(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user     = $this->getDataGenerator()->create_user();
        $category = $this->getDataGenerator()->create_category();

        // Give user main balance.
        $op = new balance_op($user->id);
        $op->credit(100);

        // Give user category balance.
        $op = new balance_op($user->id, $category->id);
        $op->credit(75);

        $this->setAdminUser();

        // Call external function.
        $result = balance_op_ext::get_balance_details($user->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_details_returns(), $result);

        $this->assertStringContainsString(175, $result['details']);
        $this->assertStringContainsString(100, $result['details']);
        $this->assertStringContainsStringIgnoringCase('main balance', $result['details']);

        $this->setUser($user);
        $result = balance_op_ext::get_balance_details($user->id);
        $result = external_api::clean_returnvalue(balance_op_ext::get_balance_details_returns(), $result);

        $this->assertStringContainsString(175, $result['details']);
        $this->assertStringContainsStringIgnoringCase('my wallet', $result['details']);
        $this->assertStringContainsString(100, $result['details']);
        $this->assertStringContainsStringIgnoringCase('main balance', $result['details']);
    }
}
