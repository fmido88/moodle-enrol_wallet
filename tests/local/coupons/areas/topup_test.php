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

namespace enrol_wallet\local\coupons\areas;

use enrol_wallet\local\coupons\areas\base as area_base;
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\coupons\types\category;
use enrol_wallet\local\coupons\types\fixed;

/**
 * Tests for the coupon topup area.
 *
 * @package    enrol_wallet
 * @category   test
 * @coversDefaultClass \enrol_wallet\local\coupons\areas\topup
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class topup_test extends \advanced_testcase {
    /** @var object Category record */
    private object $category;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->category = $this->getDataGenerator()->create_category();
    }

    /**
     * Test topup coupon area behavior.
     *
     * @covers \enrol_wallet\local\coupons\areas\topup::record_exists
     * @covers \enrol_wallet\local\coupons\areas\topup::is_valid_for_type
     * @covers \enrol_wallet\local\coupons\areas\topup::get_balance_operation
     */
    public function test_topup_area_behaviour(): void {
        $topup = area_base::make('topup');

        $this->assertTrue($topup->record_exists());
        $this->assertTrue($topup->is_valid_for_type(fixed::make(generator::create_coupon_record(type: 'fixed', value: 25))));

        $balanceop = $topup->get_balance_operation(
            $this->getDataGenerator()->create_user()->id,
            fixed::make(generator::create_coupon_record(type: 'fixed', value: 25))
        );

        $this->assertSame(0, $balanceop->get_catid());

        $categorycoupon = generator::create_coupon_record(type: 'category', value: 10, category: $this->category->id);
        $categorytype = category::make($categorycoupon);
        $balanceop = $topup->get_balance_operation($this->getDataGenerator()->create_user()->id, $categorytype);
        $this->assertEquals($this->category->id, $balanceop->get_catid());
    }
}
