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
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\coupons\types\base as type_base;
use enrol_wallet\local\coupons\types\enrol as type_enrol;

/**
 * Tests for coupon type base utilities.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class base_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test base type get_type throws a coding exception.
     *
     * @covers \enrol_wallet\local\coupons\types\base::get_type
     */
    public function test_get_type_throws_on_base(): void {
        $this->expectException(\core\exception\coding_exception::class);
        type_base::get_type();
    }

    /**
     * Test type-to-class mapping returns expected types.
     *
     * @covers \enrol_wallet\local\coupons\types\base::get_class_name_from_type
     * @covers \enrol_wallet\local\coupons\types\base::get_classes
     */
    public function test_get_class_name_from_type(): void {
        $this->assertSame(fixed::class, type_base::get_class_name_from_type('fixed'));
        $this->assertSame(percent::class, type_base::get_class_name_from_type('percent'));
        $this->assertSame(type_enrol::class, type_base::get_class_name_from_type('enrol'));
        $this->assertSame(category::class, type_base::get_class_name_from_type('category'));
        $this->assertNull(type_base::get_class_name_from_type('missingtype'));
    }

    /**
     * Test coupon type options include expected values.
     *
     * @covers \enrol_wallet\local\coupons\types\base::get_coupons_options
     */
    public function test_get_coupons_options(): void {
        $options = type_base::get_coupons_options(true);
        $this->assertArrayHasKey('fixed', $options);
        $this->assertArrayHasKey('percent', $options);
        $this->assertArrayHasKey('enrol', $options);
        $this->assertArrayHasKey('category', $options);
    }

    /**
     * Test enabled type options honor configured coupon types.
     *
     * @covers \enrol_wallet\local\coupons\types\base::get_enabled_types
     * @covers \enrol_wallet\local\coupons\types\base::get_enabled_options
     */
    public function test_enabled_options(): void {
        config::make()->coupons = implode(',', [fixed::TYPE, category::TYPE]);
        $enabled = type_base::get_enabled_options(true);
        $this->assertArrayHasKey('fixed', $enabled);
        $this->assertArrayHasKey('category', $enabled);
        $this->assertArrayNotHasKey('percent', $enabled);
    }

    /**
     * Test generator form validation reports errors for invalid type data.
     *
     * @covers \enrol_wallet\local\coupons\types\base::validate_generator_form
     */
    public function test_validate_generator_form_errors(): void {
        global $DB;
        $this->assertEmpty($DB->get_records('enrol_wallet_coupons'));

        $errors = [];
        $data = [
            'method'     => 'single',
            'type'       => 'fixed',
            'value'      => 25,
            'maxusage'   => 1,
            'maxperuser' => 2,
            'courses'    => '',
            'category'   => '',
            'code'       => '',
        ];
        type_base::validate_generator_form($data, $errors);
        $this->assertArrayHasKey('code', $errors);

        $errors = [];
        $data['method'] = 'random';
        $data['number'] = 0;
        $data['code'] = '';
        type_base::validate_generator_form($data, $errors);
        $this->assertArrayHasKey('number', $errors);

        $errors = [];
        $data['method'] = 'random';
        $data['number'] = 1;
        $data['maxusage'] = 1;
        $data['maxperuser'] = 2;
        type_base::validate_generator_form($data, $errors);
        $this->assertArrayHasKey('maxperuser', $errors);
        $this->assertArrayHasKey('maxusage', $errors);

        $errors = [];
        $data['type'] = 'enrol';
        $data['courses'] = '';
        type_base::validate_generator_form($data, $errors);
        $this->assertArrayHasKey('courses', $errors);

        $errors = [];
        $data['type'] = 'category';
        $data['category'] = '';
        type_base::validate_generator_form($data, $errors);
        $this->assertArrayHasKey('category', $errors);
    }

    /**
     * Test coupon type flags and values for all types.
     *
     * @covers \enrol_wallet\local\coupons\types\base::get_type
     */
    public function test_type_values_and_flags(): void {
        $this->assertSame('fixed', fixed::get_type());
        $this->assertTrue(fixed::is_enrol_coupon());
        $this->assertTrue(fixed::is_topup_coupon());
        $this->assertFalse(fixed::is_discount_coupon());
        $this->assertTrue(fixed::has_value());

        $this->assertSame('enrol', type_enrol::get_type());
        $this->assertTrue(type_enrol::is_enrol_coupon());
        $this->assertFalse(type_enrol::has_value());

        $this->assertSame('percent', percent::get_type());
        $this->assertTrue(percent::is_discount_coupon());

        $this->assertSame('category', category::get_type());
        $this->assertTrue(category::can_specify_category());

        $this->assertSame('fixeddis', fixeddis::get_type());
        $this->assertTrue(fixeddis::is_discount_coupon());
    }
}
