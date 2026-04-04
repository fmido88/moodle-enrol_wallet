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

use core_course_category;
use MoodleQuickForm;
use stdClass;

/**
 * Unit tests for other_category_courses_offer class functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\other_category_courses_offer
 */
class other_category_courses_offer_test extends \advanced_testcase {
    /**
     * Test other_category_courses_offer::key() returns correct constant.
     *
     * @covers ::key
     */
    public function test_key(): void {
        $this->assertEquals('otherc', other_category_courses_offer::key());
    }

    /**
     * Test other_category_courses_offer::get_visible_name() contains "Other category".
     *
     * @covers ::get_visible_name
     */
    public function test_get_visible_name(): void {
        $this->assertMatchesRegularExpression('/Other.*category/i', other_category_courses_offer::get_visible_name());
    }

    /**
     * Test other_category_courses_offer constructor creates valid instance.
     *
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $offer = other_category_courses_offer::mock_offer($this->getDataGenerator());
        $instance = new other_category_courses_offer($offer, 1, 2);
        $this->assertInstanceOf(other_category_courses_offer::class, $instance);
    }

    /**
     * Test other_category_courses_offer::get_description() includes category name and course count.
     *
     * @covers ::get_description
     */
    public function test_get_description(): void {
        $this->resetAfterTest();
        $offer = other_category_courses_offer::mock_offer($this->getDataGenerator(), 20, null, 2);
        $instance = new other_category_courses_offer($offer, 1, 2);
        $desc = $instance->get_description();
        $category = core_course_category::get($offer->cat);
        $this->assertStringContainsString($category->get_formatted_name(), $desc);
        $this->assertStringContainsString('2', $desc);
        $this->assertStringContainsString('20%', $desc);
    }

    /**
     * Test other_category_courses_offer::validate_offer() with sufficient/insufficient enrollments.
     *
     * @covers ::validate_offer
     */
    public function test_validate_offer(): void {
        $this->resetAfterTest();

        $cat1 = $this->getDataGenerator()->create_category(['idnumber' => 'cat1']);
        $cat2 = $this->getDataGenerator()->create_category(['idnumber' => 'cat2']);

        $course1 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);
        $target = $this->getDataGenerator()->create_course(['category' => $cat1->id]);

        $user = $this->getDataGenerator()->create_user();

        // Enroll in 2 courses from other category.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        // Valid - cat2 has 2 enrollments.
        $offer = other_category_courses_offer::mock_offer($this->getDataGenerator(), 25.0, $cat2->id, 2);
        $instance = new other_category_courses_offer($offer, $target->id, $user->id);
        $this->assertTrue($instance->validate_offer());

        // Invalid - only 1 enrollment.
        $offer = other_category_courses_offer::mock_offer($this->getDataGenerator(), 25.0, $cat2->id, 3);
        $instance = new other_category_courses_offer($offer, $target->id, $user->id);
        $this->assertFalse($instance->validate_offer());
    }

    /**
     * Test other_category_courses_offer::add_form_element() creates category/courses group.
     *
     * @covers ::add_form_element
     */
    public function test_add_form_element(): void {
        $mform = new MoodleQuickForm('test', 'get', '/');
        other_category_courses_offer::add_form_element($mform, 0, 1);

        $this->assertTrue($mform->elementExists('offer_otherc_0'));

        $group = $mform->getElement('offer_otherc_0');
        $names = array_map(fn ($e) => $e->getName(), $group->_elements);
        $this->assertContains('offer_otherc_cat_0', $names);
        $this->assertContains('offer_otherc_courses_0', $names);
    }

    /**
     * Test other_category_courses_offer::validate_submitted_offer() validates category/courses fields.
     *
     * @covers ::validate_submitted_offer
     */
    public function test_validate_submitted_offer(): void {
        // Invalid category.
        $offer = new stdClass();
        $offer->cat = 99999;
        $errors = [];
        other_category_courses_offer::validate_submitted_offer($offer, 0, $errors);
        $this->assertArrayHasKey('offer_otherc_0', $errors);

        // Invalid courses number.
        $offer->cat = 'cat1';
        $offer->courses = 0;
        $errors = [];
        other_category_courses_offer::validate_submitted_offer($offer, 1, $errors);
        $this->assertArrayHasKey('offer_otherc_1', $errors);
    }

    /**
     * Test other_category_courses_offer::is_available() returns true.
     *
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $this->assertTrue(other_category_courses_offer::is_available());
    }
}
