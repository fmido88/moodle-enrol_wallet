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

use enrol_wallet\local\config;
use enrol_wallet\local\coupons\areas\base as area_base;
use stdClass;

/**
 * Tests for the fixed coupon type.
 *
 * @package    enrol_wallet
 * @category   test
 * @coversDefaultClass \enrol_wallet\local\coupons\types\fixed
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class fixed_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test fixed coupon type behavior and submission messaging.
     *
     * @covers \enrol_wallet\local\coupons\types\fixed::get_type
     * @covers \enrol_wallet\local\coupons\types\fixed::is_topup_coupon
     * @covers \enrol_wallet\local\coupons\types\fixed::is_enrol_coupon
     * @covers \enrol_wallet\local\coupons\types\fixed::get_discounted_value
     * @covers \enrol_wallet\local\coupons\types\fixed::get_submission_message
     */
    public function test_fixed_type_behaviour(): void {
        config::make()->currency = 'EUR';

        $record = new stdClass();
        $record->code = 'FIXED1';
        $record->type = 'fixed';
        $record->value = 25;
        $record->courses = '';
        $record->category = 0;
        $record->maxusage = 0;
        $record->maxperuser = 0;
        $record->usetimes = 0;
        $record->validfrom = 0;
        $record->validto = 0;
        $record->timecreated = time();
        $record->lastuse = 0;

        $type = fixed::make($record);

        $this->assertSame('fixed', fixed::get_type());
        $this->assertTrue(fixed::is_enrol_coupon());
        $this->assertTrue(fixed::is_topup_coupon());
        $this->assertFalse(fixed::is_discount_coupon());
        $this->assertTrue(fixed::has_value());
        $this->assertSame(5.0, $type->get_discounted_value(30.0));

        $message = $type->get_submission_message(area_base::make('topup'));
        $this->assertSame('success', $message['type']);
        $this->assertStringContainsString('25', $message['message']);
    }
}
