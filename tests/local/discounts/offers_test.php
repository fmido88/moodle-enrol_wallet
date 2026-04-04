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
use stdClass;

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
     * Test get max discount - returns highest discount.
     * @covers ::get_max_discount()
     */
    public function test_get_max_discount(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Test 1: Instance with no offers.
        $instance = new \stdClass();
        $instance->courseid = 1;
        $instance->customtext3 = null;
        $user = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);
        $maxdiscount = $offerhelper->get_max_discount();
        $this->assertEquals(0, $maxdiscount);

        config::make()->discount_behavior = instance::B_MAX;

        // Test 2: Multiple offers instance.
        $instance = new \stdClass();
        $instance->courseid = 1;
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
        $user = $this->getDataGenerator()->create_user();
        $offerhelper = new offers($instance, $user->id);
        $maxdiscount2 = $offerhelper->get_max_discount();
        $this->assertEquals(25, $maxdiscount2);

        config::make()->discount_behavior = instance::B_SUM;
        $maxdiscount3 = $offerhelper->get_max_discount();
        $this->assertEquals(50, $maxdiscount3);

        config::make()->discount_behavior = instance::B_SEQ;
        $maxdiscount4 = $offerhelper->get_max_discount();
        // First discount 25, remain 75%. Second 15 on 75% = 11.25, total 36.25, remain 63.75.
        // Third 10 on 63.75% = 6.375, total 42.625.
        $this->assertEquals(42.625, $maxdiscount4);

        $offersraw = [
            time_offer::mock_offer($this->getDataGenerator(), 20),
            time_offer::mock_offer($this->getDataGenerator(), 30),
            time_offer::mock_offer($this->getDataGenerator(), 10),
        ];
        $instance->customtext3 = json_encode($offersraw);
        $offersobj = new offers($instance, $user->id);

        // B_MAX: highest discount.
        config::make()->discount_behavior = instance::B_MAX;
        $this->assertEquals(30, $offersobj->get_max_discount());

        // B_SUM: total, capped 100.
        config::make()->discount_behavior = instance::B_SUM;
        $this->assertEquals(60, $offersobj->get_max_discount());

        // B_SEQ: sequential application.
        config::make()->discount_behavior = instance::B_SEQ;
        $expectedseq = 1 - (1 - 0.30) * (1 - 0.20) * (1 - 0.10); // 49.4%
        $this->assertEqualsWithDelta($expectedseq * 100, $offersobj->get_max_discount(), 0.1);
    }

    /**
     * Test get sum discounts.
     * @covers ::get_sum_discounts()
     */
    public function test_get_sum_discounts(): void {
        global $DB;
        $this->resetAfterTest();

        $now = timedate::time();

        // Test 1: Empty offers array.
        $instance1 = new \stdClass();
        $instance1->courseid = 1;
        $instance1->customtext3 = json_encode([]);
        $user1 = $this->getDataGenerator()->create_user();
        $offerhelper1 = new offers($instance1, $user1->id);
        $discounts1 = $offerhelper1->get_available_discounts();
        $maxdiscount1 = $offerhelper1->get_max_discount();
        $sum1 = $offerhelper1->get_sum_discounts();
        $this->assertEmpty($discounts1);
        $this->assertEquals(0, $maxdiscount1);
        $this->assertEquals(0, $sum1);

        // Test 2: Expired offers only.
        $instance2 = new \stdClass();
        $instance2->courseid = 1;
        $instance2->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 50,
            ],
        ]);
        $user2 = $this->getDataGenerator()->create_user();
        $offerhelper2 = new offers($instance2, $user2->id);
        $sum2 = $offerhelper2->get_sum_discounts();
        $this->assertEquals(0, $sum2);

        // Test 3: Multiple valid time offers (20+30=50).
        $instance3 = new \stdClass();
        $instance3->courseid = 1;
        $instance3->customtext3 = json_encode([
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
        $user3 = $this->getDataGenerator()->create_user();
        $offerhelper3 = new offers($instance3, $user3->id);
        $sum3 = $offerhelper3->get_sum_discounts();
        $this->assertEquals(50, $sum3);

        // Test 4: High discounts (capped at 100).
        $instance4 = new \stdClass();
        $instance4->courseid = 1;
        $instance4->customtext3 = json_encode([
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
        $user4 = $this->getDataGenerator()->create_user();
        $offerhelper4 = new offers($instance4, $user4->id);
        $sum4 = $offerhelper4->get_sum_discounts();
        $this->assertEquals(100, $sum4);
    }

    /**
     * Test get max valid discount.
     * @covers ::get_max_valid_discount()
     */
    public function test_get_max_valid_discount(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Test 1: Only expired offers.
        $instance1 = new \stdClass();
        $instance1->courseid = 1;
        $instance1->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 50,
            ],
        ]);
        $user1 = $this->getDataGenerator()->create_user();
        $offerhelper1 = new offers($instance1, $user1->id);
        $maxvalid1 = $offerhelper1->get_max_valid_discount();
        $this->assertEquals(0, $maxvalid1);

        // Test 2: Mixed valid/expired offers (max valid = 40).
        $instance2 = new \stdClass();
        $instance2->courseid = 1;
        $instance2->customtext3 = json_encode([
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
        $user2 = $this->getDataGenerator()->create_user();
        $offerhelper2 = new offers($instance2, $user2->id);
        $maxvalid2 = $offerhelper2->get_max_valid_discount();
        $this->assertEquals(40, $maxvalid2);
    }

    /**
     * Test offers with no offers configured.
     * @covers ::get_raw_offers()
     */
    public function test_get_raw_offers(): void {
        global $PAGE;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        // Test no offers case.
        $instance1 = new stdClass();
        $instance1->courseid = $course1->id;
        $instance1->customtext3 = null;
        $offerhelper1 = new offers($instance1, $user1->id);

        $rawoffers1 = $offerhelper1->get_raw_offers();
        $this->assertEmpty($rawoffers1);

        $maxdiscount1 = $offerhelper1->get_max_discount();
        $this->assertEquals(0, $maxdiscount1);

        $instance = testing::get_generator()->create_instance($course2->id, false, 500);
        $rawoffers = [];
        $classes = offers::get_offer_classes();
        $discount = 0;
        $sum = 0;
        $seq = 0;

        $PAGE->set_course($course2);

        foreach ($classes as $class) {
            if (!method_exists($class, 'mock_offer')) {
                continue;
            }
            $discount += 5;
            $sum += $discount;
            $y = 0;

            do {
                $mocked = $class::mock_offer($this->getDataGenerator(), $discount);
                $y++;

                if ($y > 5) {
                    var_dump($mocked);
                    continue 2;
                }
            } while ((new $class($mocked, $course2->id, $user1->id))->is_hidden());
            $rawoffers[] = $mocked;
        }
        $instance->offersrules = json_encode($rawoffers);

        $offers = new offers($instance, $user1->id);
        $rawoffers2 = $offers->get_raw_offers();
        $this->assertCount(\count($rawoffers), $rawoffers2);
        $this->assertNotEmpty($rawoffers2);

        config::make()->discount_behavior = instance::B_MAX;
        $maxdiscount2 = $offers->get_max_discount();
        $this->assertEquals($discount, $maxdiscount2);

        config::make()->discount_behavior = instance::B_SUM;
        $maxdiscount2 = $offers->get_max_discount();
        $this->assertEquals(min($sum, 100), $maxdiscount2);

        config::make()->discount_behavior = instance::B_SEQ;
        $maxdiscount2 = $offers->get_max_discount();
        $seq = $discount;

        for ($i = 0; $discount > 0; $i++) {
            $discount -= 5;
            $seq += $discount - $discount * $seq / 100;
        }
        $this->assertEquals($seq, $maxdiscount2);
    }

    /**
     * Test course enrollment count offer validation - valid.
     * @covers ::get_available_discounts()
     */
    public function test_get_available_discounts(): void {
        global $DB;
        $this->resetAfterTest();

        $now = timedate::time();
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype'  => 'text',
            'name'      => 'Test Custom Field',
            'shortname' => 'testcustomfield',
        ]);

        // Test 1: Time-based offers (valid + expired).
        $instance1 = testing::get_generator()->create_instance(cost: 80);
        $instance1->customtext3 = json_encode([
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
        $instance1->update();
        $user1 = $this->getDataGenerator()->create_user();
        $offerhelper1 = new offers($instance1, $user1->id);
        $discounts1 = $offerhelper1->get_available_discounts();
        $this->assertCount(1, $discounts1);
        $this->assertContains(20.0, $discounts1);

        // Test 2: COURSE_ENROL_COUNT offer (valid).
        $cat1 = $this->getDataGenerator()->create_category();
        $course19 = $this->getDataGenerator()->create_course(['category' => $cat1->id]);
        $course20 = $this->getDataGenerator()->create_course(['category' => $cat1->id]);
        $user19 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user19->id, $course19->id);

        $this->setUser($user19);
        $instance19 = new \stdClass();
        $instance19->courseid = $course20->id;
        $instance19->customtext3 = json_encode([
            (object)[
                'type'     => offers::COURSE_ENROL_COUNT,
                'number'   => 1,
                'discount' => 15,
            ],
        ]);
        $offerhelper19 = new offers($instance19, $user19->id);
        $discounts19 = $offerhelper19->get_available_discounts();
        $this->assertNotEmpty($discounts19);

        // Test 3: COURSE_ENROL_COUNT requiring 2 (invalid).
        $cat2 = $this->getDataGenerator()->create_category();
        $course21 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);
        $course22 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);
        $user20 = $this->getDataGenerator()->create_user();
        $instance20 = new \stdClass();
        $instance20->courseid = $course22->id;
        $instance20->customtext3 = json_encode([
            (object)[
                'type'     => offers::COURSE_ENROL_COUNT,
                'number'   => 2,
                'discount' => 15,
            ],
        ]);
        $offerhelper20 = new offers($instance20, $user20->id);
        $discounts20 = $offerhelper20->get_available_discounts();
        $this->assertEmpty($discounts20);

        // Test 4: OTHER_CATEGORY_COURSES (valid).
        $cat23 = $this->getDataGenerator()->create_category();
        $cat24 = $this->getDataGenerator()->create_category();
        $course23 = $this->getDataGenerator()->create_course(['category' => $cat24->id]);
        $course24 = $this->getDataGenerator()->create_course(['category' => $cat24->id]);
        $targetcourse23 = $this->getDataGenerator()->create_course(['category' => $cat23->id]);
        $user21 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user21->id, $course23->id);
        $this->getDataGenerator()->enrol_user($user21->id, $course24->id);
        $instance21 = new stdClass();
        $instance21->courseid = $targetcourse23->id;
        $instance21->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat24->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);
        $offerhelper21 = new offers($instance21, $user21->id);
        $discounts21 = $offerhelper21->get_available_discounts();
        $this->assertNotEmpty($discounts21);

        // Test 5: OTHER_CATEGORY_COURSES insufficient courses (invalid).
        $cat25 = $this->getDataGenerator()->create_category();
        $cat26 = $this->getDataGenerator()->create_category();
        $course25 = $this->getDataGenerator()->create_course(['category' => $cat26->id]);
        $targetcourse25 = $this->getDataGenerator()->create_course(['category' => $cat25->id]);
        $user22 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user22->id, $course25->id);
        $instance22 = new \stdClass();
        $instance22->courseid = $targetcourse25->id;
        $instance22->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat26->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);
        $offerhelper22 = new offers($instance22, $user22->id);
        $discounts22 = $offerhelper22->get_available_discounts();
        $this->assertEmpty($discounts22);

        // Test 6: Non-existent category.
        $course26 = $this->getDataGenerator()->create_course();
        $user23 = $this->getDataGenerator()->create_user();
        $instance23 = new \stdClass();
        $instance23->courseid = $course26->id;
        $instance23->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => 99999,
                'courses'  => 1,
                'discount' => 20,
            ],
        ]);
        $offerhelper23 = new offers($instance23, $user23->id);
        $discounts23 = $offerhelper23->get_available_discounts();
        $this->assertEmpty($discounts23);

        // Test 7: COURSES_ENROL_SAME_CAT ALL condition (initially invalid).
        $cat27 = $this->getDataGenerator()->create_category();
        $course27 = $this->getDataGenerator()->create_course(['category' => $cat27->id]);
        $course28 = $this->getDataGenerator()->create_course(['category' => $cat27->id]);
        $course29 = $this->getDataGenerator()->create_course(['category' => $cat27->id]);
        $targetcourse27 = $this->getDataGenerator()->create_course(['category' => $cat27->id]);
        $user24 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user24->id, $course27->id);
        $this->getDataGenerator()->enrol_user($user24->id, $course28->id);
        $instance24 = testing::get_generator()->create_instance($targetcourse27->id, false);
        $instance24->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course27->id, $course28->id, $course29->id],
                'condition' => 'all',
                'discount'  => 25,
            ],
        ]);
        $instance24->update();
        $offerhelper24 = new offers($instance24, $user24->id);
        $discounts24 = $offerhelper24->get_available_discounts();
        $this->assertEmpty($discounts24);

        // Now enroll in course29 (now valid).
        $this->getDataGenerator()->enrol_user($user24->id, $course29->id);
        $instance26 = new instance($instance24, $user24->id);
        $offerhelper26 = new offers($instance26, $user24->id);
        $discounts26 = $offerhelper26->get_available_discounts();
        $this->assertTrue(count($discounts26) > 0);

        // Test 8: COURSES_ENROL_SAME_CAT ANY condition (valid).
        $cat30 = $this->getDataGenerator()->create_category();
        $course30 = $this->getDataGenerator()->create_course(['category' => $cat30->id]);
        $course31 = $this->getDataGenerator()->create_course(['category' => $cat30->id]);
        $course32 = $this->getDataGenerator()->create_course(['category' => $cat30->id]);
        $targetcourse30 = $this->getDataGenerator()->create_course(['category' => $cat30->id]);
        $user25 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user25->id, $course30->id);
        $this->setUser($user25);
        $instance25 = testing::get_generator()->create_instance($targetcourse30->id, false, 100);
        $instance25->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course30->id, $course31->id, $course32->id],
                'condition' => 'any',
                'discount'  => 25,
            ],
        ]);
        $instance25->update();
        $offerhelper25 = new offers($instance25, $user25->id);
        $discounts25 = $offerhelper25->get_available_discounts();
        $this->assertTrue(count($discounts25) > 0);

        // Test 9: COURSES_ENROL_SAME_CAT ANY no enrollment (invalid).
        $cat33 = $this->getDataGenerator()->create_category();
        $course33 = $this->getDataGenerator()->create_course(['category' => $cat33->id]);
        $course34 = $this->getDataGenerator()->create_course(['category' => $cat33->id]);
        $targetcourse33 = $this->getDataGenerator()->create_course(['category' => $cat33->id]);
        $user26 = $this->getDataGenerator()->create_user();
        $instance26 = new \stdClass();
        $instance26->courseid = $targetcourse33->id;
        $instance26->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course33->id, $course34->id],
                'condition' => courses_enrol_same_cat_offer::COND_ANY,
                'discount'  => 25,
            ],
        ]);
        $offerhelper26 = new offers($instance26, $user26->id);
        $discounts26 = $offerhelper26->get_available_discounts();
        $this->assertEmpty($discounts26);

        // Test 10: COURSE_ENROL_COUNT with categorized course.
        $cat9 = $this->getDataGenerator()->create_category();
        $course9 = $this->getDataGenerator()->create_course(['category' => $cat9->id]);
        $user9 = $this->getDataGenerator()->create_user();
        $instance9 = new \stdClass();
        $instance9->courseid = $course9->id;
        $instance9->customtext3 = json_encode([
            (object)[
                'type'     => offers::COURSE_ENROL_COUNT,
                'number'   => 1,
                'discount' => 15,
            ],
        ]);
        $offerhelper9 = new offers($instance9, $user9->id);
        $discounts9 = $offerhelper9->get_available_discounts();
        $this->assertEmpty($discounts9);

        // Test 11: Mixed offer types.
        $course10 = $this->getDataGenerator()->create_course();
        $instance10 = new \stdClass();
        $instance10->courseid = $course10->id;
        $instance10->customtext3 = json_encode([
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
        $user10 = $this->getDataGenerator()->create_user();
        $offerhelper10 = new offers($instance10, $user10->id);
        $discounts10 = $offerhelper10->get_available_discounts();
        $this->assertCount(1, $discounts10);
        $this->assertContains(10.0, $discounts10);

        // Test 12: Profile field firstname equals (valid).
        $course11 = $this->getDataGenerator()->create_course();
        $user11 = $this->getDataGenerator()->create_user(['firstname' => 'John']);
        $instance11 = new \stdClass();
        $instance11->courseid = $course11->id;
        $instance11->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'John',
                'discount' => 15,
            ],
        ]);
        $offerhelper11 = new offers($instance11, $user11->id);
        $discounts11 = $offerhelper11->get_available_discounts();
        $this->assertNotEmpty($discounts11);

        // Test 13: Profile field firstname mismatch (invalid).
        $course12 = $this->getDataGenerator()->create_course();
        $user12 = $this->getDataGenerator()->create_user(['firstname' => 'Jane']);
        $instance12 = new \stdClass();
        $instance12->courseid = $course12->id;
        $instance12->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'John',
                'discount' => 15,
            ],
        ]);
        $offerhelper12 = new offers($instance12, $user12->id);
        $discounts12 = $offerhelper12->get_available_discounts();
        $this->assertEmpty($discounts12);

        // Test 14: Profile field contains (valid).
        $course13 = $this->getDataGenerator()->create_course();
        $user13 = $this->getDataGenerator()->create_user(['firstname' => 'John']);
        $instance13 = new \stdClass();
        $instance13->courseid = $course13->id;
        $instance13->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_CONTAINS,
                'value'    => 'ohn',
                'discount' => 15,
            ],
        ]);
        $offerhelper13 = new offers($instance13, $user13->id);
        $discounts13 = $offerhelper13->get_available_discounts();
        $this->assertNotEmpty($discounts13);

        // Test 15: Profile field starts with (valid).
        $course14 = $this->getDataGenerator()->create_course();
        $user14 = $this->getDataGenerator()->create_user(['firstname' => 'John']);
        $instance14 = new \stdClass();
        $instance14->courseid = $course14->id;
        $instance14->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_STARTS_WITH,
                'value'    => 'Jo',
                'discount' => 15,
            ],
        ]);
        $offerhelper14 = new offers($instance14, $user14->id);
        $discounts14 = $offerhelper14->get_available_discounts();
        $this->assertNotEmpty($discounts14);

        // Test 16: Profile field ends with (valid).
        $course15 = $this->getDataGenerator()->create_course();
        $user15 = $this->getDataGenerator()->create_user(['firstname' => 'John']);
        $instance15 = new \stdClass();
        $instance15->courseid = $course15->id;
        $instance15->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_ENDS_WITH,
                'value'    => 'hn',
                'discount' => 15,
            ],
        ]);
        $offerhelper15 = new offers($instance15, $user15->id);
        $discounts15 = $offerhelper15->get_available_discounts();
        $this->assertNotEmpty($discounts15);

        // Test 17: Profile field idnumber empty (valid).
        $course16 = $this->getDataGenerator()->create_course();
        $user16 = $this->getDataGenerator()->create_user(['idnumber' => '']);
        $instance16 = new \stdClass();
        $instance16->courseid = $course16->id;
        $instance16->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'idnumber',
                'op'       => profile_field_offer::PFOP_IS_EMPTY,
                'discount' => 15,
            ],
        ]);
        $offerhelper16 = new offers($instance16, $user16->id);
        $discounts16 = $offerhelper16->get_available_discounts();
        $this->assertNotEmpty($discounts16);

        // Test 18: Profile field idnumber not empty (valid).
        $course17 = $this->getDataGenerator()->create_course();
        $user17 = $this->getDataGenerator()->create_user(['idnumber' => '12345']);
        $instance17 = new \stdClass();
        $instance17->courseid = $course17->id;
        $instance17->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'idnumber',
                'op'       => profile_field_offer::PFOP_IS_NOT_EMPTY,
                'discount' => 15,
            ],
        ]);
        $offerhelper17 = new offers($instance17, $user17->id);
        $discounts17 = $offerhelper17->get_available_discounts();
        $this->assertNotEmpty($discounts17);

        // Test 19: Custom profile field equals (valid).
        $course37 = $this->getDataGenerator()->create_course();
        $course38 = $this->getDataGenerator()->create_course();
        $course39 = $this->getDataGenerator()->create_course();
        $course40 = $this->getDataGenerator()->create_course();
        $course41 = $this->getDataGenerator()->create_course();
        $course42 = $this->getDataGenerator()->create_course();
        $course43 = $this->getDataGenerator()->create_course();

        \availability_profile\condition::wipe_static_cache();
        $user28 = $this->getDataGenerator()->create_user(['profile_field_testcustomfield' => 'SpecialValue']);
        $user29 = $this->getDataGenerator()->create_user(['profile_field_testcustomfield' => 'NotSpecialValue']);
        $instance28 = testing::get_generator()->create_instance($course37->id, false, 100);
        $instance28->customtext3 = json_encode([profile_field_offer::mock_offer(
            $this->getDataGenerator(),
            15,
            'testcustomfield',
            null,
            profile_field_offer::PFOP_IS_EQUAL_TO,
            'SpecialValue'
        ),
        ]);
        $instance28->update();
        $instance28->set_user($user28);
        $offerhelper28 = new offers($instance28, $user28->id);
        $discounts28 = $offerhelper28->get_available_discounts();
        $details28 = $offerhelper28->get_detailed_offers();
        $raw28 = $offerhelper28->get_raw_offers();
        $this->assertNotEmpty($raw28);
        $this->assertIsArray($discounts28);
        $this->assertStringContainsStringIgnoringCase(
            '15% DISCOUNT if your profile field "Test Custom Field" Is equal to "SpecialValue"',
            strip_tags(reset($details28)['description']),
        );
        $this->assertGreaterThan(0, \count($discounts28));
        $this->assertContainsOnly('float', $discounts28);
        $this->assertContains(15.0, $discounts28);
        $this->assertEquals(85, $instance28->get_cost_after_discount());

        $instance28->set_user($user29);
        $offerhelper29 = new offers($instance28, $user29->id);
        $discounts29 = $offerhelper29->get_available_discounts();
        $this->assertEmpty($discounts29);
        $this->assertEquals(100, $instance28->get_cost_after_discount());

        // Test 20: Profile field does not contain (valid).
        $user30 = $this->getDataGenerator()->create_user(['firstname' => 'John']);
        $instance30 = testing::get_generator()->create_instance($course38->id, false, 200);
        $instance30->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_DOES_NOT_CONTAIN,
                'value'    => 'xxx',
                'discount' => 40,
            ],
        ]);
        $instance30->update();
        $offerhelper30 = new offers($instance30, $user30->id);
        $discounts30 = $offerhelper30->get_available_discounts();
        $this->assertNotEmpty($discounts30);

        // Test 21: Profile field email equals (valid).
        $user31 = $this->getDataGenerator()->create_user(['email' => 'john@example.com']);
        $instance31 = testing::get_generator()->create_instance($course39->id, false, 300);
        $instance31->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'email',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'john@example.com',
                'discount' => 15,
            ],
        ]);
        $offerhelper31 = new offers($instance31, $user31->id);
        $discounts31 = $offerhelper31->get_available_discounts();
        $this->assertNotEmpty($discounts31);

        // Test 22: Custom profile field empty (valid).
        $user32 = $this->getDataGenerator()->create_user();
        $instance32 = testing::get_generator()->create_instance($course40->id, false, 50);
        $instance32->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'cf'       => 'testcustomfield',
                'op'       => profile_field_offer::PFOP_IS_EMPTY,
                'discount' => 15,
            ],
        ]);
        $instance32->update();
        $offerhelper32 = new offers($instance32, $user32->id);
        $discounts32 = $offerhelper32->get_available_discounts();
        $this->assertNotEmpty($discounts32);

        // Test 23: Custom profile field not empty (valid).
        $user33 = $this->getDataGenerator()->create_user(['profile_field_testcustomfield' => 'HasValue']);
        $instance33 = testing::get_generator()->create_instance($course41->id, false, 150);
        $instance33->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'cf'       => 'testcustomfield',
                'op'       => profile_field_offer::PFOP_IS_NOT_EMPTY,
                'discount' => 15,
            ],
        ]);
        $offerhelper33 = new offers($instance33, $user33->id);
        $discounts33 = $offerhelper33->get_available_discounts();
        $this->assertNotEmpty($discounts33);

        // Test 24: Profile field detailed offers.
        $instance34 = testing::get_generator()->create_instance($course42->id);
        $instance34->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'firstname',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'John',
                'discount' => 15,
            ],
        ]);
        $instance34->update();
        $user34 = $this->getDataGenerator()->create_user();
        $offerhelper34 = new offers($instance34, $user34->id);
        $detailed34 = $offerhelper34->get_detailed_offers();
        $this->assertCount(1, $detailed34);

        // Test 25: Profile field institution (valid).
        $user35 = $this->getDataGenerator()->create_user(['institution' => 'Moodle University']);
        $instance35 = testing::get_generator()->create_instance($course43->id);
        $instance35->customtext3 = json_encode([
            (object)[
                'type'     => offers::PROFILE_FIELD,
                'sf'       => 'institution',
                'op'       => profile_field_offer::PFOP_IS_EQUAL_TO,
                'value'    => 'Moodle University',
                'discount' => 15,
            ],
        ]);
        $instance35->update();
        $offerhelper35 = new offers($instance35, $user35->id);
        $discounts35 = $offerhelper35->get_available_discounts();
        $this->assertNotEmpty($discounts35);
    }

    /**
     * Test detailed offers for OTHER_CATEGORY_COURSES.
     * @covers ::get_detailed_offers()
     */
    public function test_get_detailed_offers(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        // Test 1: Time-based offer.
        $course1 = $this->getDataGenerator()->create_course();
        $instance1 = new \stdClass();
        $instance1->courseid = $course1->id;
        $instance1->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 25,
            ],
        ]);
        $user1 = $this->getDataGenerator()->create_user();
        $offerhelper1 = new offers($instance1, $user1->id);
        $detailed1 = $offerhelper1->get_detailed_offers();
        $this->assertNotEmpty($detailed1);

        // Test 2: OTHER_CATEGORY_COURSES offer.
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat1->id]);
        $instance2 = new \stdClass();
        $instance2->courseid = $course2->id;
        $instance2->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat2->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);
        $user2 = $this->getDataGenerator()->create_user();
        $offerhelper2 = new offers($instance2, $user2->id);
        $detailed2 = $offerhelper2->get_detailed_offers();
        $this->assertCount(1, $detailed2);

        // Test 3: COURSES_ENROL_SAME_CAT offer.
        $cat48 = $this->getDataGenerator()->create_category();
        $course48 = $this->getDataGenerator()->create_course(['category' => $cat48->id]);
        $course49 = $this->getDataGenerator()->create_course(['category' => $cat48->id]);
        $targetcourse48 = $this->getDataGenerator()->create_course(['category' => $cat48->id]);
        $instance37 = new \stdClass();
        $instance37->courseid = $targetcourse48->id;
        $instance37->customtext3 = json_encode([
            (object)[
                'type'      => offers::COURSES_ENROL_SAME_CAT,
                'courses'   => [$course48->id, $course49->id],
                'condition' => courses_enrol_same_cat_offer::COND_ALL,
                'discount'  => 15,
            ],
        ]);
        $user36 = $this->getDataGenerator()->create_user();
        $offerhelper36 = new offers($instance37, $user36->id);
        $detailed36 = $offerhelper36->get_detailed_offers();
        $this->assertCount(1, $detailed36);

        // Test 4: Same category exclusion.
        $cat49 = $this->getDataGenerator()->create_category();
        $course50 = $this->getDataGenerator()->create_course(['category' => $cat49->id]);
        $course51 = $this->getDataGenerator()->create_course(['category' => $cat49->id]);
        $user37 = $this->getDataGenerator()->create_user();
        $this->setUser($user37);
        $instance38 = testing::get_generator()->create_instance($course51->id, false, 100);
        $instance38->customtext3 = json_encode([
            (object)[
                'type'     => offers::OTHER_CATEGORY_COURSES,
                'cat'      => $cat49->id,
                'courses'  => 2,
                'discount' => 20,
            ],
        ]);
        $instance38->update();
        $this->getDataGenerator()->enrol_user($user37->id, $course50->id);
        $this->getDataGenerator()->enrol_user($user37->id, $course51->id);
        $offerhelper38 = new offers($instance38, $user37->id);
        $discounts38 = $offerhelper38->get_available_discounts();
        $this->assertTrue(\count($discounts38) === 0);

        // Test 5: Available only (mixed valid/expired).
        $course5 = $this->getDataGenerator()->create_course();
        $instance5 = new \stdClass();
        $instance5->courseid = $course5->id;
        $instance5->customtext3 = json_encode([
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
        $user5 = $this->getDataGenerator()->create_user();
        $offerhelper5 = new offers($instance5, $user5->id);
        $detailed53 = $offerhelper5->get_detailed_offers(true);
        $this->assertCount(1, $detailed53);

        // Test 6: Available only expired (empty).
        $course53 = $this->getDataGenerator()->create_course();
        $instance53 = new \stdClass();
        $instance53->courseid = $course53->id;
        $instance53->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - 3 * DAYSECS,
                'to'       => $now - DAYSECS,
                'discount' => 50,
            ],
        ]);
        $user53 = $this->getDataGenerator()->create_user();
        $offerhelper53 = new offers($instance53, $user53->id);
        $detailed54 = $offerhelper53->get_detailed_offers(true);
        $this->assertEmpty($detailed54);
        $detailed55 = $offerhelper53->get_detailed_offers(false);
        $this->assertNotEmpty($detailed55);
    }

    /**
     * Test format offers descriptions.
     * @covers ::format_offers_descriptions()
     */
    public function test_format_offers_descriptions(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        $course1 = $this->getDataGenerator()->create_course();
        $instance1 = new \stdClass();
        $instance1->courseid = $course1->id;
        $instance1->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 25,
            ],
        ]);
        $user1 = $this->getDataGenerator()->create_user();
        $offerhelper1 = new offers($instance1, $user1->id);

        $formatted1 = $offerhelper1->format_offers_descriptions();
        $this->assertNotEmpty($formatted1);
    }

    /**
     * Test get_offer_options static method.
     * @covers ::get_offer_options()
     */
    public function test_get_offer_options(): void {
        $this->resetAfterTest();

        $reflection = new \ReflectionClass(offers::class);
        $method = $reflection->getMethod('get_offer_options');

        if ((float)(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) < 8.1) {
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

    public function test_get_offer_classes(): void {
        $this->resetAfterTest();
        $classes = offers::get_offer_classes();
        $options = offers::get_offer_options();

        foreach ($classes as $type => $class) {
            $this->assertArrayHasKey($type, $options);
            $this->assertTrue(class_exists($class));
            $this->assertTrue(is_subclass_of($class, offer_item::class));
        }
    }

    /**
     * Test render_form_fragment for time-based offer.
     * @covers ::render_form_fragment()
     */
    public function test_render_form_fragment(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $html = offers::render_form_fragment(offers::TIME, 1, $course->id);
        $this->assertIsString($html);

        $htmlinvalid = offers::render_form_fragment('doesnotexist', 1, $course->id);
        $this->assertEquals('', $htmlinvalid);

        $classes1 = offers::get_offer_classes();

        foreach ($classes1 as $class1) {
            $type1 = $class1::key();
            $i1 = random_int(0, 25);
            $html2 = offers::render_form_fragment($type1, $i1, $course->id);
            $this->assertIsString($html2);
            $name1 = $class1::get_visible_name();

            $this->assertStringContainsString($name1, $html2);
            $this->assertStringContainsString("offer_{$type1}_discount_{$i1}", $html2);
            $this->assertMatchesRegularExpression("/offer_{$type1}\w+_{$i1}/", $html2);
        }
    }

    /**
     * Test fname method.
     * @covers ::fname()
     */
    public function test_fname(): void {
        $this->resetAfterTest();

        $reflection = new \ReflectionClass(offers::class);
        $method = $reflection->getMethod('fname');

        if ((float)(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) < 8.1) {
            $method->setAccessible(true);
        }

        $result1 = $method->invoke(null, 'time', 'discount', 1);
        $this->assertEquals('offer_time_discount_1', $result1);

        $result2 = $method->invoke(null, 'time', 'from', 2);
        $this->assertEquals('offer_time_from_2', $result2);

        $result3 = $method->invoke(null, 'pf', 'value', 3);
        $this->assertEquals('offer_pf_value_3', $result3);

        $result4 = $method->invoke(null, 'time', '', 4);
        $this->assertEquals('offer_time_4', $result4);
    }

    /**
     * Test add_form_fragment adds elements to form.
     * @covers ::add_form_fragment()
     */
    public function test_add_form_fragment(): void {
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $mform = new MoodleQuickForm('TestingForm', 'post', qualified_me());

        $elementscount1 = count($mform->_elements);
        $offer1 = new stdClass();
        $offer1->type = offers::TIME;
        offers::add_form_fragment($offer1, 1, $course1->id, $mform);
        $elementscount2 = count($mform->_elements);

        $this->assertTrue($elementscount2 - $elementscount1 > 4);
    }

    /**
     * Test get_courses_with_offers static method.
     * @covers ::get_courses_with_offers()
     */
    public function test_get_courses_with_offers(): void {
        $this->resetAfterTest();

        $now = timedate::time();

        global $DB;
        $course1 = $this->getDataGenerator()->create_course();
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance1->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);
        $instance1->cost = 0;
        $DB->update_record('enrol', $instance1);

        $courses1 = offers::get_courses_with_offers();
        $this->assertIsArray($courses1);

        $coursewithoffer = $this->getDataGenerator()->create_course();
        $instanceoffer = testing::get_generator()->create_instance($coursewithoffer->id, false, 100);
        $instanceoffer->customtext3 = json_encode([time_offer::mock_offer(null, 20)]);
        $instanceoffer->update();
        $courses = offers::get_courses_with_offers();
        $this->assertArrayHasKey($coursewithoffer->id, $courses);
        $this->assertObjectHasProperty('hasoffer', $courses[$coursewithoffer->id]);
    }

    /**
     * Test offers constructor with default user.
     * @covers ::__construct()
     */
    public function test_constructor(): void {
        global $DB;
        $this->resetAfterTest();

        $now = timedate::time();

        // Test 1: Default user constructor.
        $instance1 = new \stdClass();
        $instance1->courseid = 1;
        $instance1->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $offerhelper1 = new offers($instance1);
        $rawoffers1 = $offerhelper1->get_raw_offers();
        $this->assertCount(1, $rawoffers1);

        // Test 2: Explicit user constructor with DB instance.
        $course2 = $this->getDataGenerator()->create_course();
        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance2->customtext3 = json_encode([
            (object)[
                'type'     => offers::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);
        $DB->update_record('enrol', $instance2);
        $instance50 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $user50 = $this->getDataGenerator()->create_user();
        $offerhelper50 = new offers($instance50, $user50->id);
        $rawoffers50 = $offerhelper50->get_raw_offers();
        $this->assertCount(1, $rawoffers50);
        $this->assertEquals(20, $rawoffers50[0]->discount);
    }

    public function test_parse_data(): void {
        $this->resetAfterTest();
        $_POST = [
            'offer_time_from_0'     => time() - DAYSECS,
            'offer_time_to_0'       => time() + DAYSECS,
            'offer_time_discount_0' => 10,
        ];

        $data = new stdClass();
        offers::parse_data($data);
        $this->assertObjectHasProperty('customtext3', $data);

        [$k, $i, $t] = offers::analyze_element_key('offer_time_discount_0');
        $this->assertEquals('discount', $k);
        $this->assertEquals(0, $i);
        $this->assertEquals('time', $t);

        $_POST = [
            'add_offer'             => offers::TIME,
            'offer_time_from_0'     => time() - DAYSECS,
            'offer_time_to_0'       => time() + DAYSECS,
            'offer_time_discount_0' => 25,
        ];

        $data = new stdClass();
        offers::parse_data($data);
        $this->assertObjectHasProperty('customtext3', $data);
        $offersparsed = json_decode($data->customtext3);
        $this->assertCount(1, $offersparsed);
        $this->assertEquals(25, $offersparsed[0]->discount);

        // Validation.
        $errors = offers::validate((array)$data);
        $this->assertEmpty($errors);

        // Invalid discount.
        $_POST['offer_time_discount_0'] = 150;
        $errors = offers::validate((array)$data);
        $this->assertArrayHasKey('offer_time_discount_0', $errors);

        // Depth 1: set containing a time sub-offer.
        $_POST = [
            'add_offer'                         => offers_set::key(),
            'offer_set_op_0'                    => offers_set::OP_AND,
            'offer_set_discount_0'              => 20,
            'offer_set_offer_time_from_0_0'     => time() - DAYSECS,
            'offer_set_offer_time_to_0_0'       => time() + DAYSECS,
            'offer_set_offer_time_discount_0_0' => 15,
        ];

        $data2 = new stdClass();
        offers::parse_data($data2);
        $decoded2 = json_decode($data2->customtext3);
        $this->assertCount(1, $decoded2);
        $this->assertEquals(offers_set::key(), $decoded2[0]->type);
        $this->assertCount(1, $decoded2[0]->sub);
        $this->assertEquals(time_offer::key(), $decoded2[0]->sub[0]->type);

        // Depth 2: nested set inside a set.
        $_POST = [
            'add_offer'                                     => offers_set::key(),
            'offer_set_op_0'                                => offers_set::OP_OR,
            'offer_set_discount_0'                          => 40,
            'offer_set_offer_set_op_0_0'                    => offers_set::OP_AND,
            'offer_set_offer_set_discount_0_0'              => 10,
            'offer_set_offer_set_offer_time_from_0_0_0'     => time() - DAYSECS,
            'offer_set_offer_set_offer_time_to_0_0_0'       => time() + DAYSECS,
            'offer_set_offer_set_offer_time_discount_0_0_0' => 8,
        ];

        $data3 = new stdClass();
        offers::parse_data($data3);
        $decoded3 = json_decode($data3->customtext3);
        $this->assertCount(1, $decoded3);
        $this->assertEquals(offers_set::key(), $decoded3[0]->type);
        $this->assertCount(1, $decoded3[0]->sub);
        $this->assertEquals(offers_set::key(), $decoded3[0]->sub[0]->type);
        $this->assertEquals(1, count($decoded3[0]->sub[0]->sub));
        $this->assertEquals(time_offer::key(), $decoded3[0]->sub[0]->sub[0]->type);

        $_POST = [];
    }

    public function test_get_offer_class_name(): void {
        $this->assertNull(offers::get_offer_class_name('invalid_type'));
    }
}
