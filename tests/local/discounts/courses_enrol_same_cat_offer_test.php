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
 * Unit tests for courses_enrol_same_cat_offer class functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @coversDefaultClass \enrol_wallet\local\discounts\courses_enrol_same_cat_offer
 */
class courses_enrol_same_cat_offer_test extends \advanced_testcase {
    /**
     * Test courses_enrol_same_cat_offer::key() returns correct constant.
     *
     * @covers ::key
     */
    public function test_key(): void {
        $this->assertEquals('ce', courses_enrol_same_cat_offer::key());
    }

    /**
     * Test courses_enrol_same_cat_offer::get_visible_name() contains "Another course".
     *
     * @covers ::get_visible_name
     */
    public function test_get_visible_name(): void {
        $this->assertMatchesRegularExpression('/Another\scourse/i', courses_enrol_same_cat_offer::get_visible_name());
    }

    /**
     * Test courses_enrol_same_cat_offer constructor creates valid instance.
     *
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $offer = courses_enrol_same_cat_offer::mock_offer($this->getDataGenerator());
        $instance = new courses_enrol_same_cat_offer($offer, 1, 2);
        $this->assertInstanceOf(courses_enrol_same_cat_offer::class, $instance);
    }

    /**
     * Test courses_enrol_same_cat_offer::get_description() lists course names.
     *
     * @covers ::get_description
     */
    public function test_get_description(): void {
        $this->resetAfterTest();

        $cat = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $offer = courses_enrol_same_cat_offer::mock_offer($this->getDataGenerator(), 20.0, [$course1->id, $course2->id], 'all');
        $instance = new courses_enrol_same_cat_offer($offer, 3, 2);
        $desc = $instance->get_description();
        $this->assertStringContainsString($course1->fullname, $desc);
        $this->assertStringContainsString($course2->fullname, $desc);
    }

    /**
     * Test courses_enrol_same_cat_offer::validate_offer() with ALL/ANY conditions.
     *
     * @covers ::validate_offer
     */
    public function test_validate_offer(): void {
        global $DB;
        $this->resetAfterTest();

        $cat = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $target = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // ALL condition - enrolled in both.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        $offer = courses_enrol_same_cat_offer::mock_offer($this->getDataGenerator(), 25.0, [$course1->id, $course2->id], 'all');
        $instance = new courses_enrol_same_cat_offer($offer, $target->id, $user->id);
        $this->assertTrue($instance->validate_offer());

        // ALL condition - missing one.
        $plugin = enrol_get_plugin('manual');
        $einstance = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual']);
        $plugin->unenrol_user($einstance, $user->id);
        $this->assertFalse($instance->validate_offer());

        // ANY condition - enrolled in one.
        $offer = courses_enrol_same_cat_offer::mock_offer($this->getDataGenerator(), 15.0, [$course1->id, $course2->id], 'any');
        $instance = new courses_enrol_same_cat_offer($offer, $target->id, $user->id);
        $this->assertTrue($instance->validate_offer());
    }

    /**
     * Test courses_enrol_same_cat_offer::add_form_element() creates courses/condition fields.
     *
     * @covers ::add_form_element
     */
    public function test_add_form_element(): void {
        $this->resetAfterTest();

        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $this->setUser($user);

        $mform = new MoodleQuickForm('test', 'get', '/');
        courses_enrol_same_cat_offer::add_form_element($mform, 0, $course->id);

        $names = array_map(fn ($e) => $e->getName(), $mform->_elements);
        $this->assertContains('offer_ce_courses_0', $names);
        $this->assertContains('offer_ce_condition_0', $names);
    }

    /**
     * Test courses_enrol_same_cat_offer::validate_submitted_offer() validates courses/condition.
     *
     * @covers ::validate_submitted_offer
     */
    public function test_validate_submitted_offer(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        // No courses selected.
        $offer = new stdClass();
        $offer->courses = [];
        $errors = [];
        courses_enrol_same_cat_offer::validate_submitted_offer($offer, 0, $errors);
        $this->assertArrayHasKey('offer_ce_courses_0', $errors);

        // Todo: Invalid condition.
    }

    /**
     * Test courses_enrol_same_cat_offer::is_available() returns true.
     *
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $this->assertTrue(courses_enrol_same_cat_offer::is_available());
    }
}
