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

/**
 * Tests for the null coupon type.
 *
 * @package    enrol_wallet
 * @category   test
 * @coversDefaultClass \enrol_wallet\local\coupons\types\null_type
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class null_type_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test null coupon type validation and disabled behavior.
     *
     * @covers \enrol_wallet\local\coupons\types\null_type::validate_coupon
     * @covers \enrol_wallet\local\coupons\types\null_type::is_valid_record
     * @covers \enrol_wallet\local\coupons\types\null_type::get_discounted_value
     * @covers \enrol_wallet\local\coupons\types\null_type::is_enabled
     */
    public function test_null_type_behavior(): void {
        $nulltype = new null_type('INVALID');
        $error = null;

        $this->assertFalse($nulltype->validate_coupon(area_base::make('topup'), 1, $error));
        $this->assertFalse($nulltype->is_valid_record(1, $error));
        $this->assertSame(10.0, $nulltype->get_discounted_value(10.0));
        $this->assertFalse(null_type::is_enabled());
    }
}
