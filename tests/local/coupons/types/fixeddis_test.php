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

namespace enrol_wallet\local\coupons\types;

use enrol_wallet\local\coupons\areas\base as area_base;
use stdClass;

/**
 * Tests for the fixed discount coupon type.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class fixeddis_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test fixed discount coupon behavior and validation.
     *
     * @covers \enrol_wallet\local\coupons\types\fixeddis::is_discount_coupon
     * @covers \enrol_wallet\local\coupons\types\fixeddis::validate_coupon
     * @covers \enrol_wallet\local\coupons\types\fixeddis::get_discounted_value
     */
    public function test_fixed_discount_coupon_behaviour(): void {
        $record = new stdClass();
        $record->code = 'FIXEDDIS1';
        $record->type = 'fixeddis';
        $record->value = 15;
        $record->courses = '';
        $record->category = 0;
        $record->maxusage = 0;
        $record->maxperuser = 0;
        $record->usetimes = 0;
        $record->validfrom = 0;
        $record->validto = 0;
        $record->timecreated = time();
        $record->lastuse = 0;

        $type = fixeddis::make($record);

        $this->assertTrue(fixeddis::is_discount_coupon());
        $this->assertTrue($type->validate_coupon(area_base::make('topup'), 0));
        $this->assertSame(0.0, $type->get_discounted_value(10.0));

        $invalidrecord = clone $record;
        $invalidrecord->value = 0;
        $this->assertFalse(fixeddis::make($invalidrecord)->validate_coupon(area_base::make('topup'), 0));
    }
}
