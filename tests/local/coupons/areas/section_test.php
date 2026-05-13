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
use enrol_wallet\local\coupons\areas\section as area_section;
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\coupons\types\fixed;

/**
 * Tests for the coupon section area.
 *
 * @package    enrol_wallet
 * @category   test
 * @coversDefaultClass \enrol_wallet\local\coupons\areas\section
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class section_test extends \advanced_testcase {
    /** @var object Category record */
    private object $category;

    /** @var object Course record */
    private object $course;

    /** @var object Course section record */
    private object $section;

    /** @var \testing_data_generator Data generator */
    private \testing_data_generator $gen;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->gen = $this->getDataGenerator();

        $this->category = $this->gen->create_category();
        $this->course = $this->gen->create_course(['category' => $this->category->id]);
        $this->section = $this->gen->create_course_section((object)[
            'course'  => $this->course->id,
            'section' => 1,
        ]);
    }

    /**
     * Test section coupon area behavior.
     *
     * @covers \enrol_wallet\local\coupons\areas\section::record_exists
     * @covers \enrol_wallet\local\coupons\areas\section::is_valid_for_type
     * @covers \enrol_wallet\local\coupons\areas\section::get_redirect_url
     * @covers \enrol_wallet\local\coupons\areas\section::get_name
     */
    public function test_section_area_behaviour(): void {
        $section = area_base::make(area_section::AREA, $this->section->id);

        $this->assertTrue($section->record_exists());
        $this->assertSame(get_string('couponarea_section', 'enrol_wallet'), area_section::get_visible_name());
        $this->assertNotEmpty($section->get_name(false));

        $coupon = generator::create_coupon_record(type: 'fixed', value: 25);
        $type = fixed::make($coupon);
        $this->assertTrue($section->is_valid_for_type($type));

        $url = new \core\url('/test');
        $redirect = $section->get_redirect_url($url, $type);
        $this->assertSame($type->code, $redirect->params()['coupon']);
    }
}
