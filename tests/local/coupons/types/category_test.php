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
 * Tests for the category coupon type.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class category_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test category coupon type behavior and validation.
     *
     * @covers \enrol_wallet\local\coupons\types\category::can_specify_category
     * @covers \enrol_wallet\local\coupons\types\category::validate_coupon
     * @covers \enrol_wallet\local\coupons\types\category::get_discounted_value
     */
    public function test_category_coupon_behaviour(): void {
        config::make()->catbalance = true;

        $record = new stdClass();
        $record->code = 'CATEGORY1';
        $record->type = 'category';
        $record->value = 20;
        $record->courses = '';
        $record->category = $this->getDataGenerator()->create_category()->id;
        $record->maxusage = 0;
        $record->maxperuser = 0;
        $record->usetimes = 0;
        $record->validfrom = 0;
        $record->validto = 0;
        $record->timecreated = time();
        $record->lastuse = 0;

        $type = category::make($record);

        $this->assertTrue($type::can_specify_category());
        $this->assertTrue($type->validate_coupon(area_base::make('topup'), 0));
        $this->assertSame(30.0, $type->get_discounted_value(50.0));

        $invalidrecord = clone $record;
        $invalidrecord->category = 0;
        $this->assertFalse(category::make($invalidrecord)->validate_coupon(area_base::make('topup'), 0));
    }
}
