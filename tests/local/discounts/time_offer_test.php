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

namespace enrol_wallet\local\discounts;

use enrol_wallet\local\utils\timedate;
use MoodleQuickForm;

/**
 * Unit tests for time_offer class functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\time_offer
 */
class time_offer_test extends \advanced_testcase {
    /**
     * Test time_offer::key() returns correct constant.
     *
     * @covers ::key
     */
    public function test_key(): void {
        $this->assertEquals('time', time_offer::key());
    }

    /**
     * Test time_offer::get_visible_name() returns name containing "Time".
     *
     * @covers ::get_visible_name
     */
    public function test_get_visible_name(): void {
        $this->assertMatchesRegularExpression('/Time/i', time_offer::get_visible_name());
    }

    /**
     * Test time_offer constructor creates valid instance with discount.
     *
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $offer = time_offer::mock_offer($this->getDataGenerator(), 15.6);
        $instance = new time_offer($offer, 1, 2);
        $this->assertInstanceOf(time_offer::class, $instance);
        $this->assertEquals(15.6, $instance->get_discount());
    }

    /**
     * Test time_offer::get_description() for available/expired offers.
     *
     * @covers ::get_description
     */
    public function test_get_description(): void {
        $offer = time_offer::mock_offer($this->getDataGenerator(), 10);
        $instance = new time_offer($offer, 1, 2);

        // Available offer.
        $desc = $instance->get_description(false);
        $this->assertStringContainsString('10%', $desc);

        // Available only - currently valid.
        $desc = $instance->get_description(true);
        $this->assertNotNull($desc);
        $this->assertStringContainsString(format_float(10, 2, true, true), $desc);

        // Expired offer.
        $expired = clone $offer;
        $expired->to = time() - DAYSECS;
        $expiredinstance = new time_offer($expired, 1, 2);
        $this->assertNull($expiredinstance->get_description(true));
    }

    /**
     * Test time_offer::validate_offer() for valid/invalid time ranges.
     *
     * @covers ::validate_offer
     */
    public function test_validate_offer(): void {
        $offer = time_offer::mock_offer($this->getDataGenerator());
        $instance = new time_offer($offer, 1, 2);

        // Valid time range.
        $this->assertTrue($instance->validate_offer());

        // Not started yet.
        $notstarted = clone $offer;
        $notstarted->from = time() + DAYSECS;
        $nsinstance = new time_offer($notstarted, 1, 2);
        $this->assertFalse($nsinstance->validate_offer());

        // Expired.
        $expired = clone $offer;
        $expired->to = time() - HOURSECS;
        $expinstance = new time_offer($expired, 1, 2);
        $this->assertFalse($expinstance->validate_offer());
    }

    /**
     * Test time_offer::add_form_element() creates form elements correctly.
     *
     * @covers ::add_form_element
     */
    public function test_add_form_element(): void {
        $mform = new MoodleQuickForm('test', 'get', '/');
        time_offer::add_form_element($mform, 0, 1);

        $elements = $mform->_elements;
        $fromnames = [];
        $tonames = [];

        foreach ($elements as $e) {
            $name = $e->getName();

            if (strpos($name, 'offer_time_from_0') === 0) {
                $fromnames[] = $name;
            }

            if (strpos($name, 'offer_time_to_0') === 0) {
                $tonames[] = $name;
            }
        }
        $this->assertCount(1, $fromnames);
        $this->assertCount(1, $tonames);
    }

    /**
     * Test time_offer::validate_submitted_offer() validates form data.
     *
     * @covers ::validate_submitted_offer
     */
    public function test_validate_submitted_offer(): void {
        // Valid dates.
        $offer = time_offer::mock_offer($this->getDataGenerator(), 20, timedate::time() + DAYSECS, timedate::time());
        $errors = [];
        time_offer::validate_submitted_offer($offer, 0, $errors);
        $this->assertEmpty($errors);

        // To date too old.
        $offer->to = timedate::time() - 3 * DAYSECS;
        $errors = [];
        time_offer::validate_submitted_offer($offer, 1, $errors);
        $this->assertArrayHasKey('offer_time_to_1', $errors);

        // From > To.
        $offer->from = time() + 2 * DAYSECS;
        $offer->to = time() + DAYSECS;
        $errors = [];
        time_offer::validate_submitted_offer($offer, 2, $errors);
        $this->assertArrayHasKey('offer_time_from_2', $errors);
    }

    /**
     * Test time_offer::clean_submitted_value() processes form values.
     *
     * @covers ::clean_submitted_value
     */
    public function test_clean_submitted_value(): void {
        // Array date_time_selector data.
        $daydata = ['day' => 15, 'month' => 6, 'year' => 2024, 'hour' => 10, 'minute' => 30];
        time_offer::clean_submitted_value('from', $daydata);
        $this->assertIsInt($daydata);
        $this->assertGreaterThan($daydata, time());

        // Simple text.
        $discount = '15.6';
        time_offer::clean_submitted_value('discount', $discount);
        $this->assertEquals(15.6, $discount);
        $this->assertIsFloat($discount);
    }

    /**
     * Test time_offer::is_available() returns true.
     *
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $this->assertTrue(time_offer::is_available());
    }
}
