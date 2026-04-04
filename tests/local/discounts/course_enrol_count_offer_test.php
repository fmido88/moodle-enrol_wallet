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

namespace enrol_wallet\local\discounts;

use MoodleQuickForm;
use stdClass;

/**
 * Unit tests for course_enrol_count_offer class functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\course_enrol_count_offer
 */
class course_enrol_count_offer_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test course_enrol_count_offer::key() returns correct constant.
     *
     * @covers ::key
     */
    public function test_key(): void {
        $this->assertEquals('nc', course_enrol_count_offer::key());
    }

    /**
     * Test course_enrol_count_offer::get_visible_name() returns name containing "Number of courses".
     *
     * @covers ::get_visible_name
     */
    public function test_get_visible_name(): void {
        $this->assertMatchesRegularExpression('/Number\s*of\s*courses/i', course_enrol_count_offer::get_visible_name());
    }

    /**
     * Test course_enrol_count_offer constructor creates valid instance with discount.
     *
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $offer = course_enrol_count_offer::mock_offer($this->getDataGenerator(), 15);
        $instance = new course_enrol_count_offer($offer, 1, 2);
        $this->assertInstanceOf(course_enrol_count_offer::class, $instance);
        $this->assertEquals(15.0, $instance->get_discount());
    }

    /**
     * Test course_enrol_count_offer::get_description() generates course count description.
     *
     * @covers ::get_description
     */
    public function test_get_description(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $category = $this->getDataGenerator()->create_category();
        $courses = [];

        for ($i = 0; $i < 6; $i++) {
            $courses[] = $this->getDataGenerator()->create_course(['category' => $category->id]);
        }
        $offer = course_enrol_count_offer::mock_offer($this->getDataGenerator(), 15, 2, true);
        $instance = new course_enrol_count_offer($offer, $courses[0]->id, $user->id);

        // With 0 courses enrolled.
        $desc = $instance->get_description(false);
        $this->assertStringContainsString('2', $desc);
        $this->assertStringContainsString('15%', $desc);
    }

    /**
     * Test course_enrol_count_offer::validate_offer() with sufficient/insufficient course enrollments.
     *
     * @covers ::validate_offer
     */
    public function test_validate_offer(): void {
        $this->resetAfterTest();

        $cat = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $target = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Test valid (2+ courses).
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        $offer = course_enrol_count_offer::mock_offer($this->getDataGenerator(), 15.0, 2);
        $instance = new course_enrol_count_offer($offer, $target->id, $user->id);
        $this->assertTrue($instance->validate_offer());

        // Test invalid (1 course).
        $this->getDataGenerator()->enrol_user($this->getDataGenerator()->create_user()->id, $course1->id); // Different user.
        $offer = course_enrol_count_offer::mock_offer($this->getDataGenerator(), 15.0, 3);
        $instance = new course_enrol_count_offer($offer, $target->id, $user->id);
        $this->assertFalse($instance->validate_offer());
    }

    /**
     * Test course_enrol_count_offer::add_form_element() creates number input.
     *
     * @covers ::add_form_element
     */
    public function test_add_form_element(): void {
        $mform = new MoodleQuickForm('test', 'get', '/');
        course_enrol_count_offer::add_form_element($mform, 0, 1);

        $names = array_map(fn ($e) => $e->getName() ?? '', $mform->_elements);
        $this->assertContains('offer_nc_number_0', $names);
    }

    /**
     * Test course_enrol_count_offer::validate_submitted_offer() validates number field.
     *
     * @covers ::validate_submitted_offer
     */
    public function test_validate_submitted_offer(): void {
        // Invalid number (0).
        $offer = new stdClass();
        $offer->number = 0;
        $errors = [];
        course_enrol_count_offer::validate_submitted_offer($offer, 0, $errors);
        $this->assertArrayHasKey('offer_nc_number_0', $errors);

        // Valid number.
        $offer->number = 2;
        $errors = [];
        course_enrol_count_offer::validate_submitted_offer($offer, 1, $errors);
        $this->assertEmpty($errors);
    }

    /**
     * Test course_enrol_count_offer::is_available() returns true.
     *
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $this->assertTrue(course_enrol_count_offer::is_available());
    }
}
