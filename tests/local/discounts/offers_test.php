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
 * Tests for offers functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\discounts;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/user/profile/lib.php");

use enrol_wallet\local\config;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\testing;
use enrol_wallet\local\utils\timedate;
use MoodleQuickForm;

/**
 * Tests for offers system.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class offers_test extends \advanced_testcase {
    /**
     * Test time-based offer validation - valid offer.
     * @covers ::validate_time_offer()
     */
    public function test_validate_time_offer_valid(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create an instance with a time-based offer.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get the offers.
        $rawoffers = $offerhelper->get_raw_offers();
        $this->assertCount(1, $rawoffers);

        // Validate time offer.
        $class = $offerhelper->get_offer_item($rawoffers[0]);
        $result = $class->validate_offer();
        $this->assertTrue($result);
    }

    /**
     * Test time-based offer validation - not started yet.
     * @covers ::validate_time_offer()
     */
    public function test_validate_time_offer_not_started(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create an instance with a time-based offer that hasn't started.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now + DAYSECS,
                'to'       => $now + 2 * DAYSECS,
                'discount' => 20,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get the offers.
        $rawoffers = $offerhelper->get_raw_offers();

        // Validate time offer - should be false because not started.
        $class = $offerhelper->get_offer_item($rawoffers[0]);
        $result = $class->validate_offer();
        $this->assertFalse($result);
    }

    /**
     * Test time-based offer validation - expired.
     * @covers ::validate_time_offer()
     */
    public function test_validate_time_offer_expired(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create an instance with an expired time-based offer.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 20,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get the offers.
        $rawoffers = $offerhelper->get_raw_offers();

        // Validate time offer - should be false because expired.
        $result = $offerhelper->validate_time_offer($rawoffers[0]);
        $this->assertFalse($result);
    }

    /**
     * Test get max discount - returns highest discount.
     * @covers ::get_max_discount()
     */
    public function test_get_max_discount(): void {
        $this->resetAfterTest();
        config::make()->discount_behavior = instance::B_MAX;

        $now = timedate::time();

        // Create instance with multiple offers.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 10,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 25,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 15,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get max discount - should be 25 (highest).
        $maxdiscount = $offerhelper->get_max_discount();
        $this->assertEquals(25, $maxdiscount);

        config::make()->discount_behavior = instance::B_SUM;

        $maxdiscount = $offerhelper->get_max_discount();
        $this->assertEquals(50, $maxdiscount);

        config::make()->discount_behavior = instance::B_SEQ;
        $maxdiscount                      = $offerhelper->get_max_discount();
        // This should be First discount 25, remain value is 75%.
        // Second discount 15, Apply the discount to 75% = 11.25,
        // so total discount 25 + 11.25 = 36.25, remain value = 63.75
        // Third discount 10, Apply the discount to 63.75%.
        // so the total discount 36.25 + 6.375 = 42.625, remain value = 57.375.
        $this->assertEquals(42.625, $maxdiscount);
    }

    /**
     * Test get available discounts.
     * @covers ::get_available_discounts()
     */
    public function test_get_available_discounts(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create instance with one valid and one expired offer.
        $instance = testing::get_generator()->create_instance(cost: 80);

        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 30,
            ],
        ]);
        $instance->update();

        $user = $this->getDataGenerator()->create_user();

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts - only valid one.
        $discounts = $offerhelper->get_available_discounts();
        $this->assertCount(1, $discounts);
        $this->assertContains(20.0, $discounts);
    }

    /**
     * Test get sum discounts.
     * @covers ::get_sum_discounts()
     */
    public function test_get_sum_discounts(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create instance with multiple valid time-based offers.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 30,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get sum discounts - 20 + 30 = 50.
        $sum = $offerhelper->get_sum_discounts();
        $this->assertEquals(50, $sum);
    }

    /**
     * Test sum discounts capped at 100.
     * @covers ::get_sum_discounts()
     */
    public function test_get_sum_discounts_capped(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create instance with high discounts.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 60,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 60,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get sum discounts - should be capped at 100.
        $sum = $offerhelper->get_sum_discounts();
        $this->assertEquals(100, $sum);
    }

    /**
     * Test get max valid discount.
     * @covers ::get_max_valid_discount()
     */
    public function test_get_max_valid_discount(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create instance with multiple valid offers.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 40,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 80,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get max valid discount - should be 40 (highest valid).
        $maxvalid = $offerhelper->get_max_valid_discount();
        $this->assertEquals(40, $maxvalid);
    }

    /**
     * Test get detailed offers.
     * @covers ::get_detailed_offers()
     */
    public function test_get_detailed_offers(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create a course first.
        $course = $this->getDataGenerator()->create_course();

        // Create instance with time-based offer.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 25,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get detailed offers.
        $detailed = $offerhelper->get_detailed_offers();
        $this->assertNotEmpty($detailed);
    }

    /**
     * Test offers with no offers configured.
     * @covers ::get_raw_offers()
     */
    public function test_no_offers(): void {
        $this->resetAfterTest();

        // Create instance with no offers.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = null;

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get raw offers - should be empty.
        $rawoffers = $offerhelper->get_raw_offers();
        $this->assertEmpty($rawoffers);

        // Max discount should be 0.
        $maxdiscount = $offerhelper->get_max_discount();
        $this->assertEquals(0, $maxdiscount);
    }

    /**
     * Test course enrollment count offer validation - valid.
     * @covers ::validate_course_enrol_count()
     */
    public function test_validate_course_enrol_count_valid(): void {
        global $DB;
        $this->resetAfterTest();

        // Create courses in same category.
        $cat     = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Enroll user in course1.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        // Create instance for course2 with course enrollment count offer.
        $instance              = new \stdClass();
        $instance->courseid    = $course2->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::COURSE_ENROL_COUNT,
                'number'   => 1,
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because user is enrolled in 1 course in category.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test course enrollment count offer - not enough courses.
     * @covers ::validate_course_enrol_count()
     */
    public function test_validate_course_enrol_count_not_enough(): void {
        global $DB;
        $this->resetAfterTest();

        // Create courses in same category.
        $cat     = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Don't enroll user in any course.

        // Create instance for course2 with course enrollment count offer requiring 2 courses.
        $instance              = new \stdClass();
        $instance->courseid    = $course2->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::COURSE_ENROL_COUNT,
                'number'   => 2,
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should not have valid discount because user is not enrolled in enough courses.
        $this->assertEmpty($discounts);
    }

    /**
     * Test OTHER_CATEGORY_COURSES offer - valid with enough courses.
     * @covers ::validate_category_enrol_count()
     */
    public function test_validate_other_category_courses_valid(): void {
        global $DB;
        $this->resetAfterTest();

        // Create two categories.
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();

        // Create courses in cat2.
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);

        // Create course in cat1 (the target course).
        $targetcourse = $this->getDataGenerator()->create_course(['category' => $cat1->id]);

        $user = $this->getDataGenerator()->create_user();

        // Enroll user in both courses in cat2.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        // Create instance with OTHER_CATEGORY_COURSES offer.
        $instance              = new \stdClass();
        $instance->courseid    = $targetcourse->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat2->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because user is enrolled in 2 courses in cat2.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test OTHER_CATEGORY_COURSES offer - not enough courses.
     * @covers ::validate_category_enrol_count()
     */
    public function test_validate_other_category_courses_not_enough(): void {
        global $DB;
        $this->resetAfterTest();

        // Create two categories.
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();

        // Create only one course in cat2.
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);

        // Create course in cat1 (the target course).
        $targetcourse = $this->getDataGenerator()->create_course(['category' => $cat1->id]);

        $user = $this->getDataGenerator()->create_user();

        // Enroll user in only one course in cat2.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        // Create instance requiring 2 courses but only 1 available.
        $instance              = new \stdClass();
        $instance->courseid    = $targetcourse->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat2->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should not have valid discount.
        $this->assertEmpty($discounts);
    }

    /**
     * Test OTHER_CATEGORY_COURSES offer - category doesn't exist.
     * @covers ::validate_category_enrol_count()
     */
    public function test_validate_other_category_courses_invalid_category(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        $user = $this->getDataGenerator()->create_user();

        // Create instance with non-existent category.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => 99999, // Non-existent category.
                'courses'  => 1,
                'discount' => 20,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should not have valid discount - category doesn't exist.
        $this->assertEmpty($discounts);
    }

    /**
     * Test COURSES_ENROL_SAME_CAT (ce) offer - user enrolled in required courses.
     * @covers ::validate_courses_enrollments_same_cat()
     */
    public function test_validate_courses_enrol_same_cat_all_valid(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a category.
        $cat = $this->getDataGenerator()->create_category();

        // Create multiple courses in the category.
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        // Create target course.
        $targetcourse = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Enroll user in course1 and course2.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        // Create instance with COURSES_ENROL_SAME_CAT offer - requires ALL courses.
        $instance              = testing::get_generator()->create_instance($targetcourse->id, false);
        $instance->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course1->id, $course2->id, $course3->id],
                'condition' => 'all',
                'discount'  => 25,
            ],
        ]);

        $instance->update();

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts - should NOT have because user only has 2 of 3 courses.
        $discounts = $offerhelper->get_available_discounts();
        $this->assertEmpty($discounts);

        // Now enroll in course3.
        $this->getDataGenerator()->enrol_user($user->id, $course3->id);

        $instance = new instance($instance, $user->id);
        // Create new instance to refresh.
        $offerhelper = new offers($instance, $user->id);
        $discounts   = $offerhelper->get_available_discounts();

        // Should have valid discount now.
        $this->assertTrue(\count($discounts) > 0);
    }

    /**
     * Test COURSES_ENROL_SAME_CAT with 'any' condition.
     * @covers ::validate_courses_enrollments_same_cat()
     */
    public function test_validate_courses_enrol_same_cat_any_valid(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a category.
        $cat = $this->getDataGenerator()->create_category();

        // Create multiple courses in the category.
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        // Create target course.
        $targetcourse = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Enroll user in only course1.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        $this->setUser($user);
        // Create instance with 'any' condition.
        $instance              = testing::get_generator()->create_instance($targetcourse->id, false, 100);
        $instance->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course1->id, $course2->id, $course3->id],
                'condition' => 'any',
                'discount'  => 25,
            ],
        ]);
        $instance->update();

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts - should have because user has at least 1 course.
        $discounts = $offerhelper->get_available_discounts();
        $this->assertTrue(count($discounts) > 0);
    }

    /**
     * Test COURSES_ENROL_SAME_CAT with no enrollments.
     * @covers ::validate_courses_enrollments_same_cat()
     */
    public function test_validate_courses_enrol_same_cat_no_enrollment(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a category.
        $cat = $this->getDataGenerator()->create_category();

        // Create courses in the category.
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        // Create target course.
        $targetcourse = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Don't enroll user in any course.

        // Create instance.
        $instance              = new \stdClass();
        $instance->courseid    = $targetcourse->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course1->id, $course2->id],
                'condition' => 1,
                'discount'  => 25,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts - should NOT have.
        $discounts = $offerhelper->get_available_discounts();
        $this->assertEmpty($discounts);
    }

    /**
     * Test COURSE_ENROL_COUNT with course not in any category.
     * @covers ::validate_course_enrol_count()
     */
    public function test_validate_course_enrol_count_no_category(): void {
        global $DB;
        $this->resetAfterTest();

        $cat = $this->getDataGenerator()->create_category();
        // Create a course without category (site-level course).
        $course = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Create instance with COURSE_ENROL_COUNT offer.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::COURSE_ENROL_COUNT,
                'number'   => 1,
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should not have valid discount - course has no category.
        $this->assertEmpty($discounts);
    }

    /**
     * Test detailed offers for OTHER_CATEGORY_COURSES.
     * @covers ::get_detailed_offers()
     */
    public function test_get_detailed_offers_other_category(): void {
        $this->resetAfterTest();

        // Create categories.
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();

        // Create target course in cat1.
        $course = $this->getDataGenerator()->create_course(['category' => $cat1->id]);

        // Create instance with OTHER_CATEGORY_COURSES offer.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat2->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get detailed offers.
        $detailed = $offerhelper->get_detailed_offers();

        // Should have one offer description.
        $this->assertCount(1, $detailed);
    }

    /**
     * Test detailed offers for COURSES_ENROL_SAME_CAT.
     * @covers ::get_detailed_offers()
     */
    public function test_get_detailed_offers_courses_same_cat(): void {
        $this->resetAfterTest();

        // Create a category.
        $cat = $this->getDataGenerator()->create_category();

        // Create courses.
        $course1      = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2      = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $targetcourse = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        // Create instance with COURSES_ENROL_SAME_CAT offer.
        $instance              = new \stdClass();
        $instance->courseid    = $targetcourse->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course1->id, $course2->id],
                'condition' => 1,
                'discount'  => 15,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get detailed offers.
        $detailed = $offerhelper->get_detailed_offers();

        // Should have one offer description with courses list.
        $this->assertCount(1, $detailed);
    }

    /**
     * Test COURSE_ENROL_COUNT with zero number.
     * @covers ::validate_course_enrol_count()
     */
    public function test_validate_course_enrol_count_zero(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a category and courses.
        $cat     = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        // Enroll user.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        // Create instance with 0 required courses (invalid).
        $instance              = new \stdClass();
        $instance->courseid    = $course2->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::COURSE_ENROL_COUNT,
                'number'   => 0,
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts - should NOT have (0 is invalid).
        $discounts = $offerhelper->get_available_discounts();
        $this->assertEmpty($discounts);
    }

    /**
     * Test OTHER_CATEGORY_COURSES with same category as target course.
     * @covers ::validate_category_enrol_count()
     */
    public function test_validate_other_category_same_as_target(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a category.
        $cat = $this->getDataGenerator()->create_category();

        // Create courses in the category.
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        // Use same category as target course.
        $instance              = testing::get_generator()->create_instance($course2->id, false, 100);
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);

        $instance->update();

        // Enroll in course1 (which is in the same category as course2).
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts - should NOT count course2 itself.
        $discounts = $offerhelper->get_available_discounts();

        // Should be empty because the query excludes the target course.
        $this->assertTrue(count($discounts) === 0);
    }

    /**
     * Test with instance created from database.
     * @covers ::__construct()
     */
    public function test_instance_from_database(): void {
        global $DB;
        $this->resetAfterTest();

        $now = timedate::time();

        // Create a course and get the wallet instance.
        $course   = $this->getDataGenerator()->create_course();
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        // Add offers to instance.
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);

        $DB->update_record('enrol', $instance);

        // Reload instance.
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Verify offers loaded.
        $rawoffers = $offerhelper->get_raw_offers();
        $this->assertCount(1, $rawoffers);
        $this->assertEquals(20, $rawoffers[0]->discount);
    }

    /**
     * Test format offers descriptions.
     * @covers ::format_offers_descriptions()
     */
    public function test_format_offers_descriptions(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create instance with offers.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 25,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Format descriptions.
        $formatted = $offerhelper->format_offers_descriptions();
        $this->assertNotEmpty($formatted);
    }

    /**
     * Test get_offer_options static method.
     * @covers ::get_offer_options()
     */
    public function test_get_offer_options(): void {
        $this->resetAfterTest();

        // Test the static method returns array with expected keys.
        $reflection = new \ReflectionClass(offers::class);
        $method     = $reflection->getMethod('get_offer_options');
        if ((float)(PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION) < 8.1) {
            $method->setAccessible(true);
        }

        $options = $method->invoke(null);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('', $options);
        $this->assertArrayHasKey(offers::TIME, $options);
        $this->assertArrayHasKey(offers::COURSE_ENROL_COUNT, $options);
        $this->assertArrayHasKey(offers::OTHER_CATEGORY_COURSES, $options);
        $this->assertArrayHasKey(offers::COURSES_ENROL_SAME_CAT, $options);
    }

    /**
     * Test render_form_fragment for time-based offer.
     * @covers ::render_form_fragment()
     */
    public function test_render_form_fragment_time(): void {
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Test rendering form fragment for time offer.
        $html = offers::render_form_fragment(offers::TIME, 1, $course->id);

        $this->assertIsString($html);
        $this->assertStringContainsString('offer_group_1', $html);
        $this->assertStringContainsString('offer_time_discount_1', $html);
    }

    /**
     * Test render_form_fragment for course enrollment count offer.
     * @covers ::render_form_fragment()
     */
    public function test_render_form_fragment_nc(): void {
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Test rendering form fragment for nc offer.
        $html = offers::render_form_fragment(offers::COURSE_ENROL_COUNT, 1, $course->id);

        $this->assertIsString($html);
        $this->assertStringContainsString('offer_group_1', $html);
    }

    /**
     * Test fname method.
     * @covers ::fname()
     */
    public function test_fname(): void {
        $this->resetAfterTest();

        // Test the fname method via reflection.
        $reflection = new \ReflectionClass(offers::class);
        $method     = $reflection->getMethod('fname');
        if ((float)(PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION) < 8.1) {
            $method->setAccessible(true);
        }

        // Test different combinations.
        $result = $method->invoke(null, 'time', 'discount', 1);
        $this->assertEquals('offer_time_discount_1', $result);

        $result = $method->invoke(null, 'time', 'from', 2);
        $this->assertEquals('offer_time_from_2', $result);

        $result = $method->invoke(null, 'pf', 'value', 3);
        $this->assertEquals('offer_pf_value_3', $result);

        $result = $method->invoke(null, 'time', '', 4);
        $this->assertEquals('offer_time_4', $result);
    }

    /**
     * Test add_form_fragment adds elements to form.
     * @covers ::add_form_fragment()
     */
    public function test_add_form_fragment(): void {
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a mock MoodleQuickForm.
        $mform = new MoodleQuickForm('TestingForm', 'post', qualified_me());

        $elementscount1 = count($mform->_elements);
        // Call the static method.
        offers::add_form_fragment(offers::TIME, 1, $course->id, $mform);
        $elementscount2 = count($mform->_elements);

        // Verify the method was called.
        $this->assertTrue($elementscount2 - $elementscount1 > 4);
    }

    /**
     * Test get_courses_with_offers static method.
     * @covers ::get_courses_with_offers()
     */
    public function test_get_courses_with_offers(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Get the wallet instance and add offers.
        global $DB;
        $instance              = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);
        $instance->cost = 0; // Free course.
        $DB->update_record('enrol', $instance);

        // Get courses with offers.
        $courses = offers::get_courses_with_offers();

        // Should return courses with offers.
        $this->assertIsArray($courses);
    }

    /**
     * Test get_detailed_offers with availableonly flag.
     * @covers ::get_detailed_offers()
     */
    public function test_get_detailed_offers_available_only(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create instance with expired and valid offers.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 25,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 50,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get detailed offers - available only.
        $detailed = $offerhelper->get_detailed_offers(true);

        // Should only have one offer (the valid one).
        $this->assertCount(1, $detailed);
    }

    /**
     * Test empty offers array handling.
     * @covers ::get_available_discounts()
     */
    public function test_empty_offers(): void {
        $this->resetAfterTest();

        // Create instance with empty customtext3.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get available discounts - should be empty.
        $discounts = $offerhelper->get_available_discounts();
        $this->assertEmpty($discounts);

        // Max discount should be 0.
        $maxdiscount = $offerhelper->get_max_discount();
        $this->assertEquals(0, $maxdiscount);

        // Sum should be 0.
        $sum = $offerhelper->get_sum_discounts();
        $this->assertEquals(0, $sum);
    }

    /**
     * Test constant values exist.
     * @coversNothing
     */
    public function test_constants_exist(): void {
        $this->assertEquals('time', offers::TIME);
        $this->assertEquals('pf', offers::PROFILE_FIELD);
        $this->assertEquals('geo', offers::GEO_LOCATION);
        $this->assertEquals('otherc', offers::OTHER_CATEGORY_COURSES);
        $this->assertEquals('ce', offers::COURSES_ENROL_SAME_CAT);
        $this->assertEquals('nc', offers::COURSE_ENROL_COUNT);
    }

    /**
     * Test offer types constants.
     * @coversNothing
     */
    public function test_offer_type_constants(): void {
        // Verify all offer type constants.
        $this->assertNotEmpty(offers::TIME);
        $this->assertNotEmpty(offers::PROFILE_FIELD);
        $this->assertNotEmpty(offers::GEO_LOCATION);
        $this->assertNotEmpty(offers::OTHER_CATEGORY_COURSES);
        $this->assertNotEmpty(offers::COURSES_ENROL_SAME_CAT);
        $this->assertNotEmpty(offers::COURSE_ENROL_COUNT);
    }

    /**
     * Test multiple offers with different types.
     * @covers ::get_available_discounts()
     */
    public function test_multiple_offer_types(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create instance with multiple types of offers.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 10,
            ],
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 20,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have one valid discount (the first time offer).
        $this->assertCount(1, $discounts);
        $this->assertContains(10.0, $discounts);
    }

    /**
     * Test get_detailed_offers with unavailable offer returns empty when availableonly true.
     * @covers ::get_detailed_offers()
     */
    public function test_get_detailed_offers_all_returns_expired(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create instance with only expired offers.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 50,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Get detailed offers - available only - should be empty.
        $detailed = $offerhelper->get_detailed_offers(true);
        $this->assertEmpty($detailed);

        // Get all detailed offers - should have the expired offer.
        $detailed = $offerhelper->get_detailed_offers(false);
        $this->assertNotEmpty($detailed);
    }

    /**
     * Test offers constructor with default user.
     * @covers ::__construct()
     */
    public function test_constructor_default_user(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create an instance.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);

        // Create and set user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create offer helper without passing userid - should use current user.
        $offerhelper = new offers($instance);

        // Get raw offers.
        $rawoffers = $offerhelper->get_raw_offers();
        $this->assertCount(1, $rawoffers);
    }

    /**
     * Test get_max_discount with no offers.
     * @covers ::get_max_discount()
     */
    public function test_get_max_discount_no_offers(): void {
        $this->resetAfterTest();

        // Create instance with no offers.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = null;

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Max discount should be 0.
        $maxdiscount = $offerhelper->get_max_discount();
        $this->assertEquals(0, $maxdiscount);
    }

    /**
     * Test get_sum_discounts with no valid discounts.
     * @covers ::get_sum_discounts()
     */
    public function test_get_sum_discounts_no_valid(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create instance with only expired offers.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 50,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Sum should be 0.
        $sum = $offerhelper->get_sum_discounts();
        $this->assertEquals(0, $sum);
    }

    /**
     * Test get_max_valid_discount with no valid discounts.
     * @covers ::get_max_valid_discount()
     */
    public function test_get_max_valid_discount_no_valid(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Create instance with only expired offers.
        $instance              = new \stdClass();
        $instance->courseid    = 1;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 50,
            ],
        ]);

        $user        = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);

        // Max valid should be 0.
        $maxvalid = $offerhelper->get_max_valid_discount();
        $this->assertEquals(0, $maxvalid);
    }

    /**
     * Test PROFILE_FIELD offer - standard field with IS_EQUAL_TO operator.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_standard_isequalto(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create user with specific firstname.
        $user = $this->getDataGenerator()->create_user(['firstname' => 'John']);

        // Create instance with profile field offer using standard field.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'John',
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because firstname matches.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test PROFILE_FIELD offer - standard field with IS_EQUAL_TO - no match.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_standard_isequalto_no_match(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create user with different firstname.
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Jane']);

        // Create instance with profile field offer expecting different value.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'John',
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should NOT have valid discount because firstname doesn't match.
        $this->assertEmpty($discounts);
    }

    /**
     * Test PROFILE_FIELD offer - standard field with CONTAINS operator.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_standard_contains(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create user with firstname containing 'ohn'.
        $user = $this->getDataGenerator()->create_user(['firstname' => 'John']);

        // Create instance with profile field offer using contains.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_CONTAINS,
                'value'    => 'ohn',
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because firstname contains 'ohn'.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test PROFILE_FIELD offer - standard field with STARTS_WITH operator.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_standard_startswith(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create user with firstname starting with 'Jo'.
        $user = $this->getDataGenerator()->create_user(['firstname' => 'John']);

        // Create instance with profile field offer using startswith.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_STARTS_WITH,
                'value'    => 'Jo',
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because firstname starts with 'Jo'.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test PROFILE_FIELD offer - standard field with ENDS_WITH operator.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_standard_endswith(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create user with firstname ending with 'hn'.
        $user = $this->getDataGenerator()->create_user(['firstname' => 'John']);

        // Create instance with profile field offer using endswith.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_ENDS_WITH,
                'value'    => 'hn',
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because firstname ends with 'hn'.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test PROFILE_FIELD offer - standard field with IS_EMPTY operator.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_standard_isempty(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create user with empty idnumber.
        $user = $this->getDataGenerator()->create_user(['idnumber' => '']);

        // Create instance with profile field offer using isempty.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'idnumber',
                'op'       => profile_field_offer::PFOP_IS_EMPTY,
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because idnumber is empty.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test PROFILE_FIELD offer - standard field with IS_NOT_EMPTY operator.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_standard_isnotempty(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create user with non-empty idnumber.
        $user = $this->getDataGenerator()->create_user(['idnumber' => '12345']);

        // Create instance with profile field offer using isnotempty.
        $instance              = new \stdClass();
        $instance->courseid    = $course->id;
        $instance->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'idnumber',
                'op'       => profile_field_offer::PFOP_IS_NOT_EMPTY,
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance, $user->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because idnumber is not empty.
        $this->assertNotEmpty($discounts);
    }

    /**
     * Test PROFILE_FIELD offer - custom profile field.
     * @covers ::validate_profile_field_offer()
     */
    public function test_validate_profile_field_custom(): void {
        global $DB;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();
        $course7 = $this->getDataGenerator()->create_course();

        // Create a custom profile field (text type).
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype'  => 'text',
            'name'      => 'Test Custom Field',
            'shortname' => 'testcustomfield',
        ]);

        // Create user and set custom field value.
        $user1 = $this->getDataGenerator()->create_user(['profile_field_testcustomfield' => 'SpecialValue']);
        $user2 = $this->getDataGenerator()->create_user(['profile_field_testcustomfield' => 'NotSpecialValue']);

        // Create instance with profile field offer using custom field.
        $instance1              = testing::get_generator()->create_instance($course1->id, false, 100);
        $instance1->customtext3 = json_encode([
            [
                'type'     => offers::PROFILE_FIELD,
                'cf'       => 'testcustomfield',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'SpecialValue',
                'discount' => 15,
            ],
        ]);
        $instance1->update();
        $instance1->set_user($user1);

        $offerhelper = new offers($instance1, $user1->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because custom field matches.
        $this->assertNotEmpty($offerhelper->get_raw_offers());
        $this->assertIsArray($discounts);
        $this->assertContains(
            '15% DISCOUNT if your profile field Test Custom Field Is equal to "SpecialValue"',
            $offerhelper->get_detailed_offers()
        );
        $this->assertTrue(\count($discounts) > 0);
        $this->assertContainsOnly('float', $discounts);
        $this->assertContains(15.0, $discounts);
        $this->assertEquals(85, $instance1->get_cost_after_discount());

        $instance1->set_user($user2);
        $offerhelper = new offers($instance1, $user2->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should not have valid discount because custom field not matches.
        $this->assertEmpty($discounts);
        $this->assertEquals(100, $instance1->get_cost_after_discount());

        // Create user with firstname that does not contain 'xxx'.
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'John']);

        // Create instance with profile field offer using doesnotcontain.
        $instance2              = testing::get_generator()->create_instance($course2->id, false, 200);
        $instance2->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_DOES_NOT_CONTAIN,
                'value'    => 'xxx',
                'discount' => 40,
            ],
        ]);
        $instance2->update();

        $offerhelper = new offers($instance2, $user3->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because firstname does not contain 'xxx'.
        $this->assertNotEmpty($discounts);

        // Create user with specific email.
        $user4 = $this->getDataGenerator()->create_user(['email' => 'john@example.com']);

        // Create instance with profile field offer using email field.
        $instance3              = testing::get_generator()->create_instance($course3->id, false, 300);
        $instance3->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'email',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'john@example.com',
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance3, $user4->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because email matches.
        $this->assertNotEmpty($discounts);

        // Create user WITHOUT setting custom field value (should be empty).
        $user5 = $this->getDataGenerator()->create_user();

        // Create instance with profile field offer using isempty.
        $instance4              = testing::get_generator()->create_instance($course4->id, false, 50);
        $instance4->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'cf'       => 'testcustomfield',
                'op'       => profile_field_offer::PFOP_IS_EMPTY,
                'discount' => 15,
            ],
        ]);
        $instance4->update();

        $offerhelper = new offers($instance4, $user5->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because custom field is empty.
        $this->assertNotEmpty($discounts);

        // Create user and set custom field value (should NOT be empty).
        $user6 = $this->getDataGenerator()->create_user(['profile_field_testcustomfield' => 'HasValue']);

        // Create instance with profile field offer using isnotempty.
        $instance5              = testing::get_generator()->create_instance($course5->id, false, 150);
        $instance5->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'cf'       => 'testcustomfield',
                'op'       => profile_field_offer::PFOP_IS_NOT_EMPTY,
                'discount' => 15,
            ],
        ]);

        $offerhelper = new offers($instance5, $user6->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because custom field is not empty.
        $this->assertNotEmpty($discounts);

        // Create instance with profile field offer.
        $instance6              = testing::get_generator()->create_instance($course6->id);
        $instance6->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'John',
                'discount' => 15,
            ],
        ]);
        $instance6->update();

        $user7       = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance6, $user7->id);

        // Get detailed offers.
        $detailed = $offerhelper->get_detailed_offers();

        // Should have one offer description.
        $this->assertCount(1, $detailed);

        // Create user with specific institution.
        $user8 = $this->getDataGenerator()->create_user(['institution' => 'Moodle University']);

        // Create instance with profile field offer using institution.
        $instance7              = testing::get_generator()->create_instance($course7->id);
        $instance7->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'institution',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'Moodle University',
                'discount' => 15,
            ],
        ]);
        $instance7->update();
        $offerhelper = new offers($instance7, $user8->id);

        // Get available discounts.
        $discounts = $offerhelper->get_available_discounts();

        // Should have valid discount because institution matches.
        $this->assertNotEmpty($discounts);
    }
}
