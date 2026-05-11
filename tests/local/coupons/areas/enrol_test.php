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
use enrol_wallet\local\coupons\areas\enrol as area_enrol;
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\coupons\types\fixed;
use enrol_wallet\local\utils\testing;

/**
 * Tests for the coupon enrol area.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class enrol_test extends \advanced_testcase {
    /** @var object Category record */
    private object $category;

    /** @var object Course record */
    private object $course;

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
        $this->instance = testing::get_generator()->create_instance($this->course->id);
    }

    /**
     * Test enrol coupon area behavior.
     *
     * @covers \enrol_wallet\local\coupons\areas\enrol::record_exists
     * @covers \enrol_wallet\local\coupons\areas\enrol::is_valid_for_type
     * @covers \enrol_wallet\local\coupons\areas\enrol::get_redirect_url
     * @covers \enrol_wallet\local\coupons\areas\enrol::get_name
     */
    public function test_enrol_area_behaviour(): void {
        $enrol = area_base::make(area_enrol::AREA, $this->instance->id);

        $this->assertTrue($enrol->record_exists());
        $this->assertSame(get_string('couponarea_enrol', 'enrol_wallet'), area_enrol::get_visible_name());
        $this->assertStringContainsString($this->course->fullname, $enrol->get_name(false));

        $coupon = generator::create_coupon_record(type: 'fixed', value: 25);
        $type = fixed::make($coupon);
        $this->assertTrue($enrol->is_valid_for_type($type));
        $this->assertTrue($enrol->is_same_category($this->category->id));

        $redirect = $enrol->get_redirect_url(new \core\url('/cancel'), $type);
        $this->assertStringContainsString('/enrol/index.php', $redirect->out(false));
        $this->assertSame((string)$this->course->id, $redirect->params()['id']);
        $this->assertSame($type->code, $redirect->params()['coupon']);
    }
}
