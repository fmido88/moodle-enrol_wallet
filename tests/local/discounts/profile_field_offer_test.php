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

use HTML_QuickForm_element;
use MoodleQuickForm;
use MoodleQuickForm_group;

/**
 * Unit tests for profile_field_offer class functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\profile_field_offer
 */
class profile_field_offer_test extends \advanced_testcase {
    /**
     * Test profile_field_offer::key() returns correct constant.
     *
     * @covers ::key
     */
    public function test_key(): void {
        $this->assertEquals('pf', profile_field_offer::key());
    }

    /**
     * Test profile_field_offer::get_visible_name() returns name containing "Profile".
     *
     * @covers ::get_visible_name
     */
    public function test_get_visible_name(): void {
        $this->assertMatchesRegularExpression('/Profile/i', profile_field_offer::get_visible_name());
    }

    /**
     * Test profile_field_offer constructor creates valid instance.
     *
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $offer = profile_field_offer::mock_offer($this->getDataGenerator());
        $instance = new profile_field_offer($offer, 1, 2);
        $this->assertInstanceOf(profile_field_offer::class, $instance);
    }

    /**
     * Test profile_field_offer::get_description() generates field/operator description.
     *
     * @covers ::get_description
     */
    public function test_get_description(): void {
        $this->resetAfterTest();

        $offer = profile_field_offer::mock_offer(
            $this->getDataGenerator(),
            10,
            null,
            'firstname',
            profile_field_offer::PFOP_CONTAINS,
            'John'
        );
        $this->assertNotEmpty($offer->sf);
        $instance = new profile_field_offer($offer, 1, 2);
        $desc = $instance->get_description();
        $this->assertStringContainsString(get_string('firstname'), $desc);
        $this->assertStringContainsString('John', $desc);
        $this->assertStringContainsString('10%', $desc);
    }

    /**
     * Test profile_field_offer::validate_offer() with various operators/field values.
     *
     * @covers ::validate_offer
     */
    public function test_validate_offer(): void {
        \availability_profile\condition::wipe_static_cache();
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['firstname' => 'John']);

        // Valid match.
        $offer = profile_field_offer::mock_offer(
            $this->getDataGenerator(),
            15.0,
            null,
            'firstname',
            profile_field_offer::PFOP_IS_EQUAL_TO,
            'John'
        );
        $instance = new profile_field_offer($offer, 1, $user->id);
        $this->assertTrue($instance->validate_offer());

        // Invalid match.
        $offer = profile_field_offer::mock_offer(
            $this->getDataGenerator(),
            15.0,
            null,
            'firstname',
            profile_field_offer::PFOP_IS_EQUAL_TO,
            'Jane'
        );
        $instance = new profile_field_offer($offer, 1, $user->id);
        $this->assertFalse($instance->validate_offer());

        // Contains.
        $offer = profile_field_offer::mock_offer(
            $this->getDataGenerator(),
            20.0,
            null,
            'firstname',
            profile_field_offer::PFOP_CONTAINS,
            'oh'
        );
        $instance = new profile_field_offer($offer, 1, $user->id);
        $this->assertTrue($instance->validate_offer());

        // Empty field.
        $user2 = $this->getDataGenerator()->create_user(['idnumber' => '']);
        $offer = profile_field_offer::mock_offer(
            $this->getDataGenerator(),
            25.0,
            null,
            'idnumber',
            profile_field_offer::PFOP_IS_EMPTY
        );
        $instance = new profile_field_offer($offer, 1, $user2->id);
        $this->assertTrue($instance->validate_offer());
    }

    /**
     * Test profile_field_offer::add_form_element() creates profile field form group.
     *
     * @covers ::add_form_element
     */
    public function test_add_form_element(): void {
        $mform = new MoodleQuickForm('test', 'get', '/');
        profile_field_offer::add_form_element($mform, 0, 1);

        $this->assertTrue($mform->elementExists('offer_pf_0'));

        $group = $mform->getElement('offer_pf_0');
        $elements = array_map(fn (HTML_QuickForm_element $el) => $el->getName(), $group->_elements);
        $this->assertTrue(\in_array('offer_pf_value_0', $elements));
        $this->assertTrue(\in_array('offer_pf_field_0', $elements));
    }

    /**
     * Test profile_field_offer::validate_submitted_offer() detects missing fields.
     *
     * @covers ::validate_submitted_offer
     */
    public function test_validate_submitted_offer(): void {
        // Invalid field.
        $offer = profile_field_offer::mock_offer($this->getDataGenerator(), 10, op: profile_field_offer::PFOP_CONTAINS);
        unset($offer->sf, $offer->cf);
        $errors = [];
        profile_field_offer::validate_submitted_offer($offer, 0, $errors);
        $this->assertArrayHasKey('offer_pf_0', $errors);
    }

    /**
     * Test profile_field_offer::is_available() returns true.
     *
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $this->assertTrue(profile_field_offer::is_available());
    }
}
