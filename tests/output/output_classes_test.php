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

/**
 * Output classes tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\output;

use enrol_wallet\local\utils\testing;
// Todo: we must make sure that each renderable object
// is actually renderable.
/**
 * Output classes tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class output_classes_test extends \advanced_testcase {
    /**
     * Test static_renderer class.
     * @covers \enrol_wallet\output\static_renderer
     */
    public function test_static_renderer(): void {
        $this->resetAfterTest();

        // Test charger_form method.
        $form = static_renderer::charger_form();
        $this->assertIsString($form);

        // Test coupons_urls method.
        $urls = static_renderer::coupons_urls();
        $this->assertIsString($urls);
    }

    /**
     * Test wallet_tabs class.
     * @covers \enrol_wallet\output\wallet_tabs
     */
    public function test_wallet_tabs(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        $tabs = new wallet_tabs();

        // Check that the class was instantiated.
        $this->assertInstanceOf(wallet_tabs::class, $tabs);

        // Test export_for_template method.
        $renderer = helper::get_wallet_renderer();
        $data     = $tabs->export_for_template($renderer);
        $this->assertIsArray($data);

        // Todo: check that all tabs appear to admin
        // and only allowed apps appear to regular user.
        // Assert some of exported data.
    }

    /**
     * Test payment_info class.
     * @covers \enrol_wallet\output\payment_info
     */
    public function test_payment_info(): void {
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Get the wallet instance.
        $instance = testing::get_generator()->create_instance($course->id, false, 100);

        $paymentinfo = new payment_info($instance);

        // Check that the class was instantiated.
        $this->assertInstanceOf(payment_info::class, $paymentinfo);

        // Test export_for_template method.
        $renderer = helper::get_wallet_renderer();
        $data     = $paymentinfo->export_for_template($renderer);
        $this->assertIsArray($data);
        // Todo: Assert the data returned from the export
        // and the rendered html.
    }

    /**
     * Test discount_line class.
     * @covers \enrol_wallet\output\discount_line
     */
    public function test_discount_line(): void {
        $this->resetAfterTest();

        // Todo: Add some conditional discounts site and category level
        // assert that these data existed in export_for_template and
        // in the rendered string using the wallet renderer.
        $discountline = new discount_line();

        // Check that the class was instantiated.
        $this->assertInstanceOf(discount_line::class, $discountline);

        // Test export_for_template method.
        $renderer = helper::get_wallet_renderer();
        $data     = $discountline->export_for_template($renderer);
        $this->assertIsArray($data);
    }

    /**
     * Test bundles class.
     * @covers \enrol_wallet\output\bundles
     */
    public function test_bundles(): void {
        $this->resetAfterTest();

        $bundles = new bundles();

        // Check that the class was instantiated.
        $this->assertInstanceOf(bundles::class, $bundles);

        // Test export_for_template method.
        $renderer = helper::get_wallet_renderer();
        $data     = $bundles->export_for_template($renderer);
        $this->assertIsArray($data);
        // Todo: add some bundles (Site and categories) and assert that these
        // data return in export and existed in the rendered string.
    }

    /**
     * Test helper class.
     * @covers \enrol_wallet\output\helper
     */
    public function test_helper(): void {
        $this->resetAfterTest();

        // Test get_wallet_renderer method.
        $renderer = helper::get_wallet_renderer();
        $this->assertInstanceOf(\plugin_renderer_base::class, $renderer);
        $this->assertInstanceOf(renderer::class, $renderer);

        // Test get_course_renderer method.
        $courserenderer = helper::get_course_renderer();
        $this->assertInstanceOf(\core_course_renderer::class, $courserenderer);
    }
}
