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

namespace enrol_wallet\form;

use enrol_wallet\local\coupons\generator;

/**
 * Tests for the coupons generator form.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class coupons_generator_form_test extends \advanced_testcase {
    #[\Override()]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that the coupons generator form can be instantiated.
     *
     * @covers \enrol_wallet\form\coupons_generator::definition
     */
    public function test_form_definition(): void {
        $this->setAdminUser();
        $form = new coupons_generator(null);
        $this->assertInstanceOf(coupons_generator::class, $form);
    }

    /**
     * Test validation rejects single coupon generation without a code.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_single_requires_code(): void {
        $this->setAdminUser();
        $form = new coupons_generator(null);

        $errors = $form->validation([
            'method'     => 'single',
            'type'       => 'fixed',
            'value'      => 10,
            'maxusage'   => 1,
            'maxperuser' => 0,
            'sesskey'    => sesskey(),
            'code'       => '',
        ], []);

        $this->assertArrayHasKey('code', $errors);
    }

    /**
     * Test validation rejects a duplicate coupon code in single generation mode.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_single_duplicate_code(): void {
        global $DB;
        $this->setAdminUser();
        generator::create_coupon_record(code: 'DUPLICATE', type: 'fixed', value: 10);

        $form = new coupons_generator(null);
        $errors = $form->validation([
            'method'     => 'single',
            'type'       => 'fixed',
            'value'      => 10,
            'maxusage'   => 1,
            'maxperuser' => 0,
            'sesskey'    => sesskey(),
            'code'       => 'DUPLICATE',
        ], []);

        $this->assertArrayHasKey('code', $errors);
    }

    /**
     * Test validation rejects random coupon generation without a number count.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_random_requires_number(): void {
        $this->setAdminUser();
        $form = new coupons_generator(null);

        $errors = $form->validation([
            'method'     => 'random',
            'type'       => 'fixed',
            'value'      => 10,
            'maxusage'   => 1,
            'maxperuser' => 0,
            'sesskey'    => sesskey(),
            'number'     => 0,
            'length'     => 8,
            'lower'      => 1,
            'upper'      => 1,
            'digits'     => 1,
        ], []);

        $this->assertArrayHasKey('number', $errors);
    }

    /**
     * Test validation rejects when max per user is greater than max usage.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_maxperuser_gt_maxusage(): void {
        $this->setAdminUser();
        $form = new coupons_generator(null);

        $errors = $form->validation([
            'method'     => 'random',
            'type'       => 'fixed',
            'value'      => 10,
            'maxusage'   => 1,
            'maxperuser' => 2,
            'sesskey'    => sesskey(),
            'number'     => 1,
            'length'     => 8,
            'lower'      => 1,
            'upper'      => 1,
            'digits'     => 1,
        ], []);

        $this->assertArrayHasKey('maxperuser', $errors);
        $this->assertArrayHasKey('maxusage', $errors);
    }

    /**
     * Test validation rejects enrol coupon type when courses are missing.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_enrol_type_requires_courses(): void {
        $this->setAdminUser();
        $form = new coupons_generator(null);

        $errors = $form->validation([
            'method'     => 'single',
            'type'       => 'enrol',
            'value'      => 0,
            'maxusage'   => 1,
            'maxperuser' => 0,
            'sesskey'    => sesskey(),
            'code'       => 'ENROLTEST',
            'courses'    => '',
        ], []);

        $this->assertArrayHasKey('courses', $errors);
    }

    /**
     * Test validation rejects category coupon type when category is missing.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_category_type_requires_category(): void {
        $this->setAdminUser();
        $form = new coupons_generator(null);

        $errors = $form->validation([
            'method'     => 'single',
            'type'       => 'category',
            'value'      => 10,
            'maxusage'   => 1,
            'maxperuser' => 0,
            'sesskey'    => sesskey(),
            'code'       => 'CATEGORYTEST',
            'category'   => '',
        ], []);

        $this->assertArrayHasKey('category', $errors);
    }

    /**
     * Test validation succeeds for a valid random coupon generation request.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_successful_random_coupon(): void {
        $this->setAdminUser();
        $categories = \core_course_category::get_all();
        $firstcategory = reset($categories);

        $form = new coupons_generator(null);
        $errors = $form->validation([
            'method'     => 'random',
            'type'       => 'fixed',
            'value'      => 10,
            'maxusage'   => 1,
            'maxperuser' => 0,
            'sesskey'    => sesskey(),
            'number'     => 2,
            'length'     => 8,
            'lower'      => 1,
            'upper'      => 1,
            'digits'     => 1,
            'category'   => $firstcategory->id,
            'courses'    => [],
        ], []);

        $this->assertEmpty($errors);
    }

    /**
     * Test validation rejects percent type coupon values outside the allowed range.
     *
     * @covers \enrol_wallet\form\coupons_generator::validation
     */
    public function test_validation_percent_type_rejects_out_of_range_values(): void {
        $this->setAdminUser();
        $form = new coupons_generator(null);

        $errors = $form->validation([
            'method'     => 'single',
            'type'       => 'percent',
            'value'      => 150,
            'maxusage'   => 1,
            'maxperuser' => 0,
            'sesskey'    => sesskey(),
            'code'       => 'PERCENTTEST',
        ], []);

        $this->assertArrayHasKey('value', $errors);
    }
}
