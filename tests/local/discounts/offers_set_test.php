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
use ReflectionProperty;
use stdClass;

/**
 * Unit tests for offers_set class functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_wallet\local\discounts\offers_set
 */
class offers_set_test extends \advanced_testcase {
    /**
     * Test offers_set::key() returns correct constant.
     *
     * @covers ::key
     */
    public function test_key(): void {
        $this->assertEquals('set', offers_set::key());
    }

    /**
     * Test offers_set::get_visible_name() contains "set" and "offers".
     *
     * @covers ::get_visible_name
     */
    public function test_get_visible_name(): void {
        $this->assertStringContainsStringIgnoringCase('set', offers_set::get_visible_name());
        $this->assertStringContainsStringIgnoringCase('offers', offers_set::get_visible_name());
    }

    /**
     * Test offers_set constructor with valid/invalid operation.
     *
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $this->resetAfterTest();

        // Valid AND.
        $offer = offers_set::mock_offer($this->getDataGenerator(), op: offers_set::OP_AND);

        $set = new offers_set($offer, 1, 2);
        $this->assertInstanceOf(offers_set::class, $set);

        $op = (new ReflectionProperty(offers_set::class, 'op'))->getValue($set);
        $this->assertEquals(offers_set::OP_AND, $op);
        $suboffers = (new ReflectionProperty(offers_set::class, 'suboffers'))->getValue($set);
        $this->assertCount(3, $suboffers);

        // Invalid op.
        $invalid = clone $offer;
        $invalid->op = 'INVALID';
        $this->expectException(\coding_exception::class);
        new offers_set($invalid, 1, 2);
    }

    /**
     * Test offers_set::get_description() for AND/OR operations.
     *
     * @covers ::get_description
     */
    public function test_get_description(): void {
        $this->resetAfterTest();

        // Empty.
        $offer = new stdClass();
        $offer->type = 'set';
        $offer->op = offers_set::OP_AND;
        $offer->discount = 25.0;
        $offer->sub = [];
        $set = new offers_set($offer, 1, 2);
        $this->assertNull($set->get_description());

        // AND operation.
        $offer = offers_set::mock_offer($this->getDataGenerator(), op: offers_set::OP_AND);
        $set = new offers_set($offer, 1, 2);
        $desc = $set->get_description();
        $this->assertStringContainsString('all', strtolower($desc));

        // OR operation.
        $offer->op = offers_set::OP_OR;
        $set = new offers_set($offer, 1, 2);
        $desc = $set->get_description();
        $this->assertStringContainsString('any', strtolower($desc));
    }

    /**
     * Test offers_set::validate_offer() for AND/OR logic with suboffers.
     *
     * @covers ::validate_offer
     */
    public function test_validate_offer(): void {
        $this->resetAfterTest();
        $now = time();

        // AND - all valid.
        $valid = (object)['type' => 'time', 'discount' => 10, 'from' => $now - DAYSECS, 'to' => $now + DAYSECS];
        $offer = new stdClass();
        $offer->type = 'set';
        $offer->op = offers_set::OP_AND;
        $offer->discount = 15.0;
        $offer->sub = [$valid];
        $set = new offers_set($offer, 1, 2);
        $this->assertTrue($set->validate_offer());

        // AND - one invalid.
        $invalid = clone $valid;
        $invalid->to = $now - HOURSECS;
        $offer->sub = [$invalid];
        $set = new offers_set($offer, 1, 2);
        $this->assertFalse($set->validate_offer());

        // OR - all valid.
        $offer->op = offers_set::OP_OR;
        $offer->sub = [$valid];
        $set = new offers_set($offer, 1, 2);
        $this->assertTrue($set->validate_offer());

        // OR - all invalid.
        $offer->sub = [$invalid];
        $set = new offers_set($offer, 1, 2);
        $this->assertFalse($set->validate_offer());

        // OR - one valid.
        $offer->sub = [$valid, $invalid];
        $set = new offers_set($offer, 1, 2);
        $this->assertTrue($set->validate_offer());
        // Todo: use *_offer::mock_offer() not (object)[] and make more than one sub offer.
        // With minimum two sub-offers the validation should be in three scenarios:
        // the two is valid so it is validated for both AND and OR ops.
        // the two is invalid so it is not validated for both AND and OR ops.
        // one is valid and the other not, so it is validated on OR but not for AND.
    }

    /**
     * Test offers_set::fname() generates correct form field names.
     *
     * @covers ::fname
     */
    public function test_fname(): void {
        $this->assertEquals('offer_set_op_0', offers_set::fname('op', 0));
        $this->assertEquals('offer_set_discount_1', offers_set::fname('discount', 1));

        $wrapper = fn ($n) => "w_$n"; // Try with our own wrapper.
        $this->assertEquals('w_offer_set_sub_2', offers_set::fname('sub', 2, $wrapper));
    }

    /**
     * Test offers_set::add_form_element() creates operation selector and add button.
     *
     * @covers ::add_form_element
     */
    public function test_add_form_element(): void {
        $mform = new MoodleQuickForm('test', 'post', '/');
        $offer = offers_set::mock_offer($this->getDataGenerator());
        offers_set::add_form_element($mform, 0, 1, $offer);
        $this->assertTrue($mform->elementExists('offer_set_0'));

        $group = $mform->getElement('offer_set_0');
        $names = array_map(fn ($el) => $el->getName(), $group->_elements);
        $this->assertTrue(\in_array('offer_set_op_0', $names));
        $this->assertTrue(\in_array('add_sub_offer_0', $names));

        // Todo: redo again with nested offers with depth 1 and 2.
    }

    /**
     * Test offers_set::after_edit_form_definition() sets default operation values.
     *
     * @covers ::after_edit_form_definition
     */
    public function test_after_edit_form_definition(): void {
        $mform = new MoodleQuickForm('test', 'post', '/');
        $offer = offers_set::mock_offer($this->getDataGenerator(), 10, [], offers_set::OP_AND);

        // Todo: Add sub offers and make sure no errors and each got the correct element name
        // and default value.
        offers_set::after_edit_form_definition($mform, $offer, 0);
        $this->assertEquals(offers_set::OP_AND, $mform->_defaultValues['offer_set_op_0']);

        $offer->op = offers_set::OP_OR;
        offers_set::after_edit_form_definition($mform, $offer, 1);
        $this->assertEquals(offers_set::OP_OR, $mform->_defaultValues['offer_set_op_1']);
    }

    /**
     * Test offers_set::validate_submitted_offer() validates operation field.
     *
     * @covers ::validate_submitted_offer
     */
    public function test_validate_submitted_offer(): void {
        $offer = new stdClass();
        $offer->type = 'set';
        $offer->op = offers_set::OP_AND;
        $offer->sub = [];
        $errors = [];

        offers_set::validate_submitted_offer($offer, 0, $errors);
        $this->assertEmpty($errors);

        $offer->op = 'INVALID';
        offers_set::validate_submitted_offer($offer, 1, $errors);
        $this->assertArrayHasKey('offer_set_op_1', $errors);
        // Todo: Test with sub offers.
    }

    /**
     * Test offers_set::pre_save_submitted_data() processes nested form data.
     *
     * @covers ::pre_save_submitted_data
     */
    public function test_pre_save_submitted_data(): void {
        $offers = [];
        offers_set::pre_save_submitted_data($offers, 0, 'op', offers_set::OP_OR);
        $this->assertEquals(offers_set::OP_OR, $offers[0]->op);

        // Nested: offer_set_sub0_time_from_0.
        offers_set::pre_save_submitted_data($offers, 0, 'offer_time_from_0', 12345);
        $this->assertObjectHasProperty('sub', $offers[0]);
        $this->assertArrayHasKey(0, $offers[0]->sub);
        $this->assertEquals(12345, $offers[0]->sub[0]->from);
        $this->assertEquals('time', $offers[0]->sub[0]->type);
        // Todo: Check a double nested (Depth 2).
    }

    /**
     * Test offers_set nested set logic (depth 2) for validate_offer and is_hidden.
     *
     * @covers ::validate_offer
     * @covers ::is_hidden
     */
    public function test_nested_offer_set(): void {
        $this->resetAfterTest();
        $now = time();

        $valid = (object)[
            'type'     => 'time',
            'discount' => 10,
            'from'     => $now - DAYSECS,
            'to'       => $now + DAYSECS,
        ];

        $invalid = (object)[
            'type'     => 'time',
            'discount' => 5,
            'from'     => $now + DAYSECS,
            'to'       => $now + 2 * DAYSECS,
        ];

        $inner = (object)[
            'type'     => 'set',
            'op'       => offers_set::OP_AND,
            'discount' => 5,
            'sub'      => [$valid],
        ];

        $outer = (object)[
            'type'     => 'set',
            'op'       => offers_set::OP_OR,
            'discount' => 20,
            'sub'      => [$inner, $invalid],
        ];

        $set = new offers_set($outer, 1, 2);
        $this->assertTrue($set->validate_offer());
        $this->assertFalse($set->is_hidden());
    }

    /**
     * Test offers_set::is_available() returns true.
     *
     * @covers ::is_available
     */
    public function test_is_available(): void {
        $this->assertTrue(offers_set::is_available());
    }
}
