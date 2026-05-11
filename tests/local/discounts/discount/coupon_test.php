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

use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\coupons\types\base as type_base;
use enrol_wallet\local\entities\instance;

/**
 * Tests for coupon discount handling.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class coupon_test extends \advanced_testcase {
    /**
     * Test coupon discounts are available when coupon types are enabled.
     */
    public function test_is_available_when_coupon_types_are_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        type_base::enable_all_types();
        $entity = new instance($record, $user->id);

        $this->assertTrue(coupon::is_available($entity));
    }

    /**
     * Test coupon session discount is applied when a valid session coupon exists.
     */
    public function test_get_percentage_discount_applies_session_discount_coupon(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        type_base::enable_all_types();
        generator::create_coupon_record(code: 'SESSION50', type: 'percent', value: 50, maxusage: 1);
        coupons::set_session_coupon('SESSION50');

        $entity = new instance($record, $user->id);
        $discount = new coupon($entity, 100.0);

        $this->assertSame(50.0, $discount->get_percentage_discount());
        $this->assertSame(50.0, $discount->get_discounted_cost());
        $this->assertSame(50.0, $discount->get_absolute_discount());

        $discount->after_process();

        $this->assertEquals(1, $DB->count_records('enrol_wallet_coupons_usage', ['code' => 'SESSION50', 'userid' => $user->id]));
    }

    /**
     * Test invalid session coupon returns zero discount.
     */
    public function test_get_percentage_discount_returns_zero_for_invalid_coupon(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        type_base::enable_all_types();
        coupons::set_session_coupon('NO_SUCH_CODE');

        $entity = new instance($record, $user->id);
        $discount = new coupon($entity, 100.0);

        $this->assertSame(0.0, $discount->get_percentage_discount());
        $this->assertSame(100.0, $discount->get_discounted_cost());
    }
}
