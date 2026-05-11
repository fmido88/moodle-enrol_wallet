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
 * Tests for the percent coupon type.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class percent_test extends \advanced_testcase {
    #[\Override()]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test percent coupon type validation and discounted value.
     *
     * @covers \enrol_wallet\local\coupons\types\percent::is_discount_coupon
     * @covers \enrol_wallet\local\coupons\types\percent::validate_coupon
     * @covers \enrol_wallet\local\coupons\types\percent::get_discounted_value
     */
    public function test_percent_coupon_behaviour(): void {
        $record = new stdClass();
        $record->code = 'PERCENT1';
        $record->type = 'percent';
        $record->value = 50;
        $record->courses = '';
        $record->category = 0;
        $record->maxusage = 0;
        $record->maxperuser = 0;
        $record->usetimes = 0;
        $record->validfrom = 0;
        $record->validto = 0;
        $record->timecreated = time();
        $record->lastuse = 0;

        $type = percent::make($record);

        $this->assertTrue(percent::is_discount_coupon());
        $this->assertTrue($type->validate_coupon(area_base::make('topup'), 0));
        $this->assertSame(50.0, $type->get_discounted_value(100.0));

        $invalidrecord = clone $record;
        $invalidrecord->value = 150;
        $this->assertFalse(percent::make($invalidrecord)->validate_coupon(area_base::make('topup'), 0));
    }
}
