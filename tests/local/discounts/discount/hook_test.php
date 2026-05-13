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

use enrol_wallet\local\entities\instance;

/**
 * Tests for the hook discount implementation.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\discount\hook
 */
final class hook_test extends \advanced_testcase {
    /**
     * Test hook discount addition and discount set behavior.
     * @covers ::add_discount
     * @covers ::get_discounts
     * @covers ::get_percentage_discount
     * @covers ::get_discounted_cost
     * @covers ::get_absolute_discount
     * @covers ::set_discounts
     */
    public function test_add_and_set_discounts(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $entity = new class ($record, $user->id) extends instance {
            #[\Override()]
            public function get_behavior(): int {
                return self::const('max');
            }
        };

        $discount = new hook($entity, 100.0);
        $discount->add_discount(10.5);
        $discount->add_discount(25);

        $this->assertSame([10.5, 25.0], $discount->get_discounts());
        $this->assertEqualsWithDelta(25.0, $discount->get_percentage_discount(), 0.001);
        $this->assertEqualsWithDelta(75.0, $discount->get_discounted_cost(), 0.001);
        $this->assertEqualsWithDelta(25.0, $discount->get_absolute_discount(), 0.001);

        $discount->set_discounts([5, 200, -10, 50]);
        $this->assertSame([5.0, 50.0], $discount->get_discounts());
    }

    /**
     * Test after_process executes callbacks and ignores exceptions.
     * @covers ::after_process
     * @covers ::add_post_purchase_callback
     */
    public function test_after_process_executes_callbacks_and_catches_exceptions(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $entity = new instance($record, $user->id);

        $called = false;
        $discount = new hook($entity, 100.0);
        $discount->add_post_purchase_callback(function () use (&$called) {
            $called = true;
        });
        $discount->add_post_purchase_callback(function () {
            throw new \Exception('callback failed');
        });

        $discount->after_process();
        $this->assertTrue($called);
    }
}
