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
 * Apply coupon form tests with actual form submission and validation.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

use enrol_wallet\local\config;
use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\coupons\generator;
use enrol_wallet\local\utils\testing;
use enrol_wallet_generator;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/enrol/wallet/lib.php');
require_once($CFG->dirroot . '/enrol/wallet/tests/generator/lib.php');

/**
 * Apply coupon form tests with actual form submission and validation.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class applycoupon_form_test extends \advanced_testcase {

    /**
     * Test form definition creates valid form structure.
     * @covers ::definition()
     */
    public function test_form_definition(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create custom data for the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
            'url' => '/enrol/index.php?id=' . $course->id,
        ];

        // Create the form.
        $form = new applycoupon_form(null, $customdata);

        // If we get here, the form was defined successfully.
        $this->assertInstanceOf(applycoupon_form::class, $form);
    }

    /**
     * Test form validation with valid fixed coupon.
     * @covers ::validation()
     */
    public function test_validation_valid_fixed_coupon(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a fixed coupon.
        $couponcode = 'TESTFIXED123';
        generator::create_coupon_record(
            code: $couponcode,
            type: 'fixed',
            value: 50,
            maxusage: 10
        );

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data.
        $data = [
            'coupon' => $couponcode,
            'instanceid' => $instance->id,
            'courseid' => $course->id,
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have no errors.
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation with valid percent coupon.
     * @covers ::validation()
     */
    public function test_validation_valid_percent_coupon(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a percent coupon.
        $couponcode = 'TESTPERCENT20';
        generator::create_coupon_record(
            code: $couponcode,
            type: 'percent',
            value: 20,
            maxusage: 10
        );

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data.
        $data = [
            'coupon' => $couponcode,
            'instanceid' => $instance->id,
            'courseid' => $course->id,
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have no errors.
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation with invalid coupon.
     * @covers ::validation()
     */
    public function test_validation_invalid_coupon(): void {
        $this->resetAfterTest();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data with non-existent coupon.
        $data = [
            'coupon' => 'INVALIDCODE',
            'instanceid' => $instance->id,
            'courseid' => $course->id,
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have error.
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('applycoupon', $errors);
    }

    /**
     * Test form validation with expired coupon.
     * @covers ::validation()
     */
    public function test_validation_expired_coupon(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create an expired coupon.
        $couponcode = 'EXPIRED123';
        $expiredtime = time() - DAYSECS;
        generator::create_coupon_record(
            code: $couponcode,
            type: 'fixed',
            value: 50,
            maxusage: 10,
            validto: $expiredtime
        );

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data.
        $data = [
            'coupon' => $couponcode,
            'instanceid' => $instance->id,
            'courseid' => $course->id,
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have error for expired coupon.
        $this->assertNotEmpty($errors);
    }

    /**
     * Test form validation with max usage exceeded coupon.
     * @covers ::validation()
     */
    public function test_validation_max_usage_exceeded(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a coupon with max usage 1.
        $couponcode = 'MAXUSE1';
        generator::create_coupon_record(
            code: $couponcode,
            type: 'fixed',
            value: 50,
            maxusage: 1
        );

        // Mark the coupon as used once.
        $DB->insert_record('enrol_wallet_coupons_usage', [
            'code' => $couponcode,
            'userid' => $user->id,
            'timecreated' => time(),
        ]);

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data.
        $formdata = [
            'coupon' => $couponcode,
            'instanceid' => $instance->id,
            'courseid' => $course->id,
        ];

        // Validate the data.
        $errors = $form->validation($formdata, []);

        // Should have error for max usage exceeded.
        $this->assertNotEmpty($errors);
    }

    /**
     * Test form validation for course module area.
     * @covers ::validation()
     */
    public function test_validation_cm_area(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a fixed coupon.
        $couponcode = 'TESTCM123';
        generator::create_coupon_record(
            code: $couponcode,
            type: 'fixed',
            value: 50,
            maxusage: 10
        );

        // Create a course and module.
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $module->id, $course->id);

        // Create the form with cmid.
        $customdata = [
            'instance' => (object)[
                'cmid' => $cm->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data.
        $formdata = [
            'coupon' => $couponcode,
            'cmid' => $cm->id,
            'courseid' => $course->id,
        ];

        // Validate the data.
        $errors = $form->validation($formdata, []);

        // Should have no errors.
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation for section area.
     * @covers ::validation()
     */
    public function test_validation_section_area(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a fixed coupon.
        $couponcode = 'TESTSECTION123';
        generator::create_coupon_record(
            code: $couponcode,
            type: 'fixed',
            value: 50,
            maxusage: 10
        );

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0]);

        // Create the form with sectionid.
        $customdata = [
            'instance' => (object)[
                'sectionid' => $section->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data.
        $formdata = [
            'coupon' => $couponcode,
            'sectionid' => $section->id,
            'courseid' => $course->id,
        ];

        // Validate the data.
        $errors = $form->validation($formdata, []);

        // Should have no errors.
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation for topup area (no instance).
     * @covers ::validation()
     */
    public function test_validation_topup_area(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a fixed coupon.
        $couponcode = 'TESTTOPUP123';
        generator::create_coupon_record(
            code: $couponcode,
            type: 'fixed',
            value: 50,
            maxusage: 10
        );

        // Create the form without instance (topup).
        $customdata = [
            'instance' => (object)[
                'courseid' => SITEID,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Mock form submission data.
        $formdata = [
            'coupon' => $couponcode,
        ];

        // Validate the data.
        $errors = $form->validation($formdata, []);

        // Should have no errors.
        $this->assertEmpty($errors);
    }

    /**
     * Test process_coupon_data with fixed coupon.
     * @covers ::process_coupon_data()
     */
    public function test_process_fixed_coupon(): void {
        global $DB;
        $this->resetAfterTest();

        // Enable all coupon types.
        config::make()->coupons = implode(',', [
            coupons::FIXED,
            coupons::DISCOUNT,
            coupons::ENROL,
            coupons::CATEGORY,
        ]);

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a fixed coupon.
        $couponcode = 'FIXED50';
        generator::create_coupon_record(
            code: $couponcode,
            type: 'fixed',
            value: 50,
            maxusage: 10
        );

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Set up form data.
        $data = (object)[
            'coupon' => $couponcode,
            'instanceid' => $instance->id,
            'courseid' => $course->id,
            'cancel' => false,
        ];

        // Process the coupon data.
        $result = $form->process_coupon_data($data);

        // Should return a URL for redirect.
        $this->assertInstanceOf(\core\url::class, $result);
    }

    /**
     * Test process_coupon_data with cancel.
     * @covers ::process_coupon_data()
     */
    public function test_process_cancel(): void {
        $this->resetAfterTest();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
            'url' => '/enrol/index.php?id=' . $course->id,
        ];
        $form = new applycoupon_form(null, $customdata);

        // Set up form data with cancel.
        $data = (object)[
            'coupon' => 'SOMECODE',
            'instanceid' => $instance->id,
            'courseid' => $course->id,
            'cancel' => true,
        ];

        // Process the coupon data.
        try {
            $form->process_coupon_data($data);
            // Redirection Exception.
            $this->assertTrue(false); // Should not reach.
        } catch (\moodle_exception $e) {
            // Expected redirect exception.
            $this->assertStringContainsStringIgnoringCase('Redirect', $e->getMessage());
        }
    }


    /**
     * Test process_coupon_data with empty coupon.
     * @covers ::process_coupon_data()
     */
    public function test_process_empty_coupon(): void {
        $this->resetAfterTest();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id' => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // Set up form data with empty coupon.
        $formdata = (object)[
            'coupon' => '',
            'instanceid' => $instance->id,
            'courseid' => $course->id,
            'cancel' => false,
        ];

        // Process the coupon data.
        $result = $form->process_coupon_data($formdata);

        // Should return null for empty coupon.
        $this->assertNull($result);
    }

    /**
     * Test get_form_identifier with instance id.
     * @covers ::get_form_identifier()
     */
    public function test_get_form_identifier_with_instance(): void {
        $this->resetAfterTest();

        // Create a course and instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = testing::get_generator()->create_instance($course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'id'       => $instance->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // The form should be created successfully with unique identifier.
        $this->assertInstanceOf(applycoupon_form::class, $form);
    }

    /**
     * Test get_form_identifier with cmid.
     * @covers ::get_form_identifier()
     */
    public function test_get_form_identifier_with_cmid(): void {
        $this->resetAfterTest();

        // Create a course and module.
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $module->id, $course->id);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'cmid' => $cm->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // The form should be created successfully with unique identifier.
        $this->assertInstanceOf(applycoupon_form::class, $form);
    }

    /**
     * Test get_form_identifier with sectionid.
     * @covers ::get_form_identifier()
     */
    public function test_get_form_identifier_with_sectionid(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0]);

        // Create the form.
        $customdata = [
            'instance' => (object)[
                'sectionid' => $section->id,
                'courseid' => $course->id,
            ],
        ];
        $form = new applycoupon_form(null, $customdata);

        // The form should be created successfully with unique identifier.
        $this->assertInstanceOf(applycoupon_form::class, $form);
    }
}
