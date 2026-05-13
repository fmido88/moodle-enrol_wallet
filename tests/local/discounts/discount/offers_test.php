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

namespace enrol_wallet\local\discounts\discount;

use enrol_wallet\local\discounts\offers as offers_list;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\entities\section;

/**
 * Tests for the offers discount implementation.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\discount\offers
 */
final class offers_test extends \advanced_testcase {
    /**
     * Test instance offers produce correct percentage discount.
     * @covers ::is_available
     * @covers ::get_percentage_discount
     * @covers ::get_discounted_cost
     * @covers ::get_absolute_discount
     */
    public function test_get_percentage_discount_for_instance_offers(): void {
        global $DB;
        $this->resetAfterTest();

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        $record->customtext3 = json_encode([
            (object)[
                'type'     => offers_list::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
        ]);
        $DB->update_record('enrol', $record);

        $entity = new instance($record, $user->id);
        $discount = new offers($entity, 100.0);

        $this->assertEqualsWithDelta(20.0, $discount->get_percentage_discount(), 0.001);
        $this->assertEqualsWithDelta(80.0, $discount->get_discounted_cost(), 0.001);
        $this->assertEqualsWithDelta(20.0, $discount->get_absolute_discount(), 0.001);
        $this->assertTrue(offers::is_available($entity));

        $sections = $DB->get_records('course_sections', ['course' => $course->id]);
        $sectionrecord = reset($sections);
        $this->assertInstanceOf('\stdClass', $sectionrecord);
        $this->assertFalse(offers::is_available(new section($sectionrecord->id)));
    }

    /**
     * Test sum and sequential offer behaviors compute expected discounts.
     * @covers ::get_percentage_discount
     */
    public function test_get_percentage_discount_with_sum_and_seq_behaviors(): void {
        global $DB;
        $this->resetAfterTest();

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        $record->customtext3 = json_encode([
            (object)[
                'type'     => offers_list::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 30,
            ],
            (object)[
                'type'     => offers_list::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 20,
            ],
            (object)[
                'type'     => offers_list::TIME,
                'from'     => $now - DAYSECS,
                'to'       => $now + DAYSECS,
                'discount' => 10,
            ],
        ]);
        $DB->update_record('enrol', $record);

        $sumentity = new class ($record, $user->id) extends instance {
            #[\Override()]
            public function get_behavior(): int {
                return self::const('sum');
            }
        };
        $seqentity = new class ($record, $user->id) extends instance {
            #[\Override()]
            public function get_behavior(): int {
                return self::const('seq');
            }
        };

        $sumdiscount = new offers($sumentity, 100.0);
        $seqdiscount = new offers($seqentity, 100.0);

        $this->assertSame(60.0, $sumdiscount->get_percentage_discount());
        $this->assertEqualsWithDelta(49.6, $seqdiscount->get_percentage_discount(), 0.1);
    }
}
