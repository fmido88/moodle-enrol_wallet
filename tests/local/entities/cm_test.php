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

use enrol_wallet\local\config;

/**
 * Tests for CM entity.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\entities\cm
 */
final class cm_test extends \advanced_testcase {
    /**
     * Test CM entity instantiation with course module.
     * @covers ::__construct
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
     * @covers ::get_context
     */
    public function test_get_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $cm = new cm($module->cmid);
        $context = $cm->get_context();

        $this->assertInstanceOf('context_module', $context);
        $this->assertEquals($module->cmid, $context->instanceid);
    }

    /**
     * Test get_course method.
     * @covers ::get_course
     */
    public function test_get_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $cm = new cm($module->cmid);
        $result = $cm->get_course();

        $this->assertEquals($course->id, $result->id);
    }

    /**
     * Test get_name method.
     * @covers ::get_name
     */
    public function test_get_name(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name'   => 'Test Page',
        ]);

        $cm = new cm($module->cmid);
        $name = $cm->get_name();

        $this->assertEquals('Test Page', $name);
    }

    /**
     * Test get_cost_after_discount behavior with available and unavailable costs.
     * @covers ::get_cost_after_discount
     */
    public function test_get_cost_after_discount_with_available_costs(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $availability = (object)[
            'c' => [
                (object)[
                    'type' => 'wallet',
                    'cost' => 50,
                ],
            ],
        ];

        $DB->update_record('course_modules', (object)[
            'id'           => $module->cmid,
            'availability' => json_encode($availability),
        ]);

        $cm = new cm($module->cmid, $user->id);

        $this->assertNull($cm->get_cost_after_discount(100.0));
        $this->assertSame(50.0, $cm->get_cost_after_discount(50.0));
    }

    /**
     * Test get_cost_after_discount with profile field discount.
     * @covers ::get_cost_after_discount
     */
    public function test_get_cost_after_discount_with_profile_discount(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $availability = (object)[
            'c' => [
                (object)[
                    'type' => 'wallet',
                    'cost' => 80,
                ],
            ],
        ];

        $DB->update_record('course_modules', (object)[
            'id'           => $module->cmid,
            'availability' => json_encode($availability),
        ]);

        $fielddata = (object)[
            'name'      => 'discount',
            'shortname' => 'discount',
            'datatype'  => 'text',
        ];
        $fieldid = $DB->insert_record('user_info_field', $fielddata, true);
        config::make()->discount_field = $fieldid;

        $DB->insert_record('user_info_data', (object)[
            'userid'  => $user->id,
            'fieldid' => $fieldid,
            'data'    => '25% discount',
        ]);

        $cm = new cm($module->cmid, $user->id);

        $this->assertSame(60.0, $cm->get_cost_after_discount(80.0));
    }
}
