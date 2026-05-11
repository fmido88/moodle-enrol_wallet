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
use enrol_wallet\local\coupons\areas\enrol as area_enrol;
use enrol_wallet\local\coupons\areas\section as area_section;
use enrol_wallet\local\coupons\areas\topup as area_topup;
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\coupons\types\category;
use enrol_wallet\local\utils\testing;

/**
 * Tests for coupon area base utilities.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class base_test extends \advanced_testcase {
    /** @var object Category record */
    private object $category;

    /** @var object Course record */
    private object $course;

    /** @var object Course section record */
    private object $section;

    /** @var object Course module record */
    private object $cm;

    /** @var object Enrolment instance record */
    private object $instance;

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
        $this->instance = testing::get_generator()->create_instance($this->course->id);
    }

    /**
     * Test base area factory creates the correct area instances.
     *
     * @covers \enrol_wallet\local\coupons\areas\base::make
     * @covers \enrol_wallet\local\coupons\areas\base::get_classes
     */
    public function test_make_area_instances(): void {
        $topup = area_base::make('topup');
        $this->assertInstanceOf(area_topup::class, $topup);
        $this->assertEquals(0, $topup->areaid);

        $enrol = area_base::make(area_enrol::AREA, $this->instance->id);
        $this->assertInstanceOf(area_enrol::class, $enrol);
        $this->assertEquals($this->instance->id, $enrol->areaid);

        $cm = area_base::make(area_cm::AREA, $this->cm->id);
        $this->assertInstanceOf(area_cm::class, $cm);
        $this->assertEquals($this->cm->id, $cm->areaid);

        $section = area_base::make(area_section::AREA, $this->section->id);
        $this->assertInstanceOf(area_section::class, $section);
        $this->assertEquals($this->section->id, $section->areaid);
    }

    /**
     * Test available area codes and area class lookups.
     *
     * @covers \enrol_wallet\local\coupons\areas\base::get_areas
     * @covers \enrol_wallet\local\coupons\areas\base::get_class_from_area_code
     */
    public function test_get_areas_and_class_from_code(): void {
        $areas = area_base::get_areas();
        $this->assertArrayHasKey('topup', $areas);
        $this->assertArrayHasKey('enrol', $areas);
        $this->assertArrayHasKey('cm', $areas);
        $this->assertArrayHasKey('section', $areas);

        $this->assertSame(area_enrol::class, area_base::get_class_from_area_code(area_enrol::AREA));
        $this->assertSame(area_cm::class, area_base::get_class_from_area_code(area_cm::AREA));
        $this->assertNull(area_base::get_class_from_area_code(999));
    }

    /**
     * Test area class resolution from data payloads.
     *
     * @covers \enrol_wallet\local\coupons\areas\base::get_class_from_data
     */
    public function test_get_class_from_data(): void {
        $enrolclass = area_base::get_class_from_data(['instanceid' => $this->instance->id]);
        $this->assertSame(area_enrol::class, $enrolclass);

        $cmclass = area_base::get_class_from_data(['cmid' => $this->cm->id]);
        $this->assertSame(area_cm::class, $cmclass);

        $sectionclass = area_base::get_class_from_data(['sectionid' => $this->section->id]);
        $this->assertSame(area_section::class, $sectionclass);

        $topupclass = area_base::get_class_from_data([]);
        $this->assertSame(area_topup::class, $topupclass);
    }

    /**
     * Test area context resolution returns expected contexts.
     *
     * @covers \enrol_wallet\local\coupons\areas\base::get_context
     */
    public function test_get_context_for_areas(): void {
        $topup = area_base::make('topup');
        $this->assertInstanceOf(\context_system::class, $topup->get_context());

        $enrol = area_base::make(area_enrol::AREA, $this->instance->id);
        $this->assertInstanceOf(\context_course::class, $enrol->get_context());
        $this->assertSame($this->course->id, $enrol->get_context()->instanceid);
    }
}
