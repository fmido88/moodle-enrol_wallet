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
use enrol_wallet\local\coupons\areas\cm as area_cm;
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\coupons\types\enrol as type_enrol;
use enrol_wallet\local\coupons\types\fixed;

/**
 * Tests for the coupon course module area.
 *
 * @package    enrol_wallet
 * @category   test
 * @coversDefaultClass \enrol_wallet\local\coupons\areas\cm
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cm_test extends \advanced_testcase {
    /** @var object Category record */
    private object $category;

    /** @var object Course record */
    private object $course;

    /** @var object Course section record */
    private object $section;

    /** @var object Course module record */
    private object $cm;

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
        $page = $this->gen->create_module('page', (object)[
            'course'  => $this->course->id,
            'section' => $this->section->id,
        ]);
        global $DB;
        $this->cm = $DB->get_record('course_modules', ['id' => $page->cmid], '*', MUST_EXIST);
    }

    /**
     * Test course module coupon area behavior.
     *
     * @covers \enrol_wallet\local\coupons\areas\cm::record_exists
     * @covers \enrol_wallet\local\coupons\areas\cm::is_valid_for_type
     * @covers \enrol_wallet\local\coupons\areas\cm::get_redirect_url
     * @covers \enrol_wallet\local\coupons\areas\cm::get_name
     */
    public function test_cm_area_behaviour(): void {
        $cm = area_base::make(area_cm::AREA, $this->cm->id);

        $this->assertTrue($cm->record_exists());
        $this->assertSame(get_string('couponarea_cm', 'enrol_wallet'), area_cm::get_visible_name());

        $this->assertTrue($cm->is_valid_for_type(fixed::make(generator::create_coupon_record(type: 'fixed', value: 25))));

        $this->assertFalse($cm->is_valid_for_type(type_enrol::make((object)[
            'code'        => 'ENROL0',
            'type'        => 'enrol',
            'value'       => 0,
            'courses'     => 1,
            'category'    => 0,
            'maxusage'    => 0,
            'maxperuser'  => 0,
            'usetimes'    => 0,
            'validfrom'   => 0,
            'validto'     => 0,
            'timecreated' => time(),
            'lastuse'     => 0,
        ])));

        $url = new \core\url('/test');
        $coupon = generator::create_coupon_record(type: 'fixed', value: 25);
        $redirect = $cm->get_redirect_url($url, fixed::make($coupon));
        $this->assertSame($url->out(false), $redirect->out(false));
        $this->assertSame($coupon->code, $redirect->params()['coupon']);
    }
}
