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
 * Tests for CM (Course Module) entity.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\entities;

/**
 * Tests for CM entity.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cm_test extends \advanced_testcase {
    /**
     * Test CM entity instantiation with course module.
     * @covers ::__construct()
     */
    public function test_cm_instantiation(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $cm = new cm($module->cmid);

        $this->assertInstanceOf('enrol_wallet\local\entities\cm', $cm);
    }

    /**
     * Test get_context method.
     * @covers ::get_context()
     */
    public function test_get_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $cm      = new cm($module->cmid);
        $context = $cm->get_context();

        $this->assertInstanceOf('context_module', $context);
        $this->assertEquals($module->cmid, $context->instanceid);
    }

    /**
     * Test get_course method.
     * @covers ::get_course()
     */
    public function test_get_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $cm     = new cm($module->cmid);
        $result = $cm->get_course();

        $this->assertEquals($course->id, $result->id);
    }

    /**
     * Test get_name method.
     * @covers ::get_name()
     */
    public function test_get_name(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name'   => 'Test Page',
        ]);

        $cm   = new cm($module->cmid);
        $name = $cm->get_name();

        $this->assertEquals('Test Page', $name);
    }
    // Todo: Merge these tests into one test only and add a test for get_cost_after_discount().
}
