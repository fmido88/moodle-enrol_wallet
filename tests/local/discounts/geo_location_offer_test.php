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

use MoodleQuickForm;

/**
 * Unit tests for geo_location_offer class functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\geo_location_offer
 */
class geo_location_offer_test extends \advanced_testcase {
    /**
     * Test geo_location_offer::key() returns correct constant.
     *
     * @covers ::key
     */
    public function test_key(): void {
        $this->assertEquals('geo', geo_location_offer::key());
    }

    /**
     * Test geo_location_offer::get_visible_name() contains "Geo".
     *
     * @covers ::get_visible_name
     */
    public function test_get_visible_name(): void {
        $this->assertMatchesRegularExpression('/Geo/i', geo_location_offer::get_visible_name());
    }

    /**
     * Test geo_location_offer constructor creates valid instance.
     *
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $offer = geo_location_offer::mock_offer($this->getDataGenerator());
        $instance = new geo_location_offer($offer, 1, 2);
        $this->assertInstanceOf(geo_location_offer::class, $instance);
    }

    /**
     * Test geo_location_offer::get_description() returns null.
     *
     * @covers ::get_description
     */
    public function test_get_description(): void {
        $offer = geo_location_offer::mock_offer($this->getDataGenerator());
        $instance = new geo_location_offer($offer, 1, 2);
        $desc = $instance->get_description();
        $this->assertNull($desc);
    }

    /**
     * Test geo_location_offer::validate_offer() always returns true.
     *
     * @covers ::validate_offer
     */
    public function test_validate_offer(): void {
        $this->resetAfterTest();

        $offer = geo_location_offer::mock_offer($this->getDataGenerator());
        $instance = new geo_location_offer($offer, 1, 2);
        $this->assertTrue($instance->validate_offer());
    }

    /**
     * Test geo_location_offer::add_form_element() adds no elements (disabled feature).
     *
     * @covers ::add_form_element
     */
    public function test_add_form_element(): void {
        $mform = new MoodleQuickForm('test', 'get', '/');
        $count1 = count($mform->_elements);
        geo_location_offer::add_form_element($mform, 0, 1);
        $count2 = count($mform->_elements);
        $this->assertEquals($count1, $count2);
    }

    /**
     * Test geo_location_offer::is_available() returns false (disabled).
     *
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $this->assertFalse(geo_location_offer::is_available());
    }
}
