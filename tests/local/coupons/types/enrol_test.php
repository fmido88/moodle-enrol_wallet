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
use enrol_wallet\local\coupons\types\enrol as type_enrol;
use stdClass;

/**
 * Tests for the enrol coupon type.
 *
 * @package    enrol_wallet
 * @category   test
 * @coversDefaultClass \enrol_wallet\local\coupons\types\enrol
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class enrol_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test enrol coupon type behavior and validation.
     *
     * @covers \enrol_wallet\local\coupons\types\enrol::has_value
     * @covers \enrol_wallet\local\coupons\types\enrol::validate_coupon
     * @covers \enrol_wallet\local\coupons\types\enrol::can_specify_courses
     */
    public function test_enrol_coupon_behaviour(): void {
        $record = new stdClass();
        $record->code = 'ENROL1';
        $record->type = 'enrol';
        $record->value = 0;
        $record->courses = '1';
        $record->category = 0;
        $record->maxusage = 0;
        $record->maxperuser = 0;
        $record->usetimes = 0;
        $record->validfrom = 0;
        $record->validto = 0;
        $record->timecreated = time();
        $record->lastuse = 0;

        $type = type_enrol::make($record);
        $this->assertFalse($type::has_value());
        $this->assertTrue($type::can_specify_courses());

        $this->assertTrue($type->validate_coupon(area_base::make('topup'), 0));

        $invalidrecord = clone $record;
        $invalidrecord->courses = '';
        $this->assertFalse(type_enrol::make($invalidrecord)->validate_coupon(area_base::make('topup'), 0));
    }
}
