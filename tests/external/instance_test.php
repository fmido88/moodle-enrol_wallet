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
 * Tests for external instance methods.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\external;

use core_external\external_api;
use enrol_wallet\local\wallet\balance_op;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for external instance methods.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class instance_test extends \externallib_advanced_testcase {
    /**
     * Test get_cost external function.
     * @covers ::get_cost()
     */
    public function test_get_cost(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $instance       = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance->cost = 150;
        $DB->update_record('enrol', $instance);

        $this->setAdminUser();

        // Call external function.
        $result = \enrol_wallet\external\instance::get_cost($instance->id, $user->id);
        $result = external_api::clean_returnvalue(\enrol_wallet\external\instance::get_cost_returns(), $result);

        $this->assertEquals(150, $result['cost']);
    }

    /**
     * Test get_cost with zero cost.
     * @covers ::get_cost()
     */
    public function test_get_cost_zero(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $instance       = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance->cost = 0;
        $DB->update_record('enrol', $instance);

        $this->setAdminUser();

        // Call external function.
        $result = \enrol_wallet\external\instance::get_cost($instance->id, $user->id);
        $result = external_api::clean_returnvalue(\enrol_wallet\external\instance::get_cost_returns(), $result);

        $this->assertEquals(0, $result['cost']);
    }

    /**
     * Test get_cost with discount.
     * @covers ::get_cost()
     */
    public function test_get_cost_with_discount(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $instance             = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance->cost       = 100;
        $instance->customint6 = 1;
        $DB->update_record('enrol', $instance);

        // Give user some balance.
        $op = new balance_op($user->id);
        $op->credit(200);

        $this->setAdminUser();

        // Call external function.
        $result = \enrol_wallet\external\instance::get_cost($instance->id, $user->id);
        $result = external_api::clean_returnvalue(\enrol_wallet\external\instance::get_cost_returns(), $result);

        // Cost should be returned (with or without discount).
        $this->assertIsNumeric($result['cost']);
    }

    /**
     * Test get_cost with invalid instance.
     * @covers ::get_cost()
     */
    public function test_get_cost_invalid_instance(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $this->setAdminUser();

        // Call external function with invalid instance.
        $this->expectException(\moodle_exception::class);
        \enrol_wallet\external\instance::get_cost(99999, $user->id);
    }

    /**
     * Test get_cost parameters validation.
     * @covers ::get_cost_parameters()
     */
    public function test_get_cost_parameters(): void {
        $params = \enrol_wallet\external\instance::get_cost_parameters();

        $this->assertInstanceOf('core_external\external_function_parameters', $params);
    }

    /**
     * Test get_cost returns structure.
     * @covers ::get_cost_returns()
     */
    public function test_get_cost_returns(): void {
        $returns = \enrol_wallet\external\instance::get_cost_returns();

        $this->assertInstanceOf('core_external\external_single_structure', $returns);
    }
}
