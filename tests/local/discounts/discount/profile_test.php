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

namespace enrol_wallet\local\discounts\discount;

use enrol_wallet\local\config;
use enrol_wallet\local\entities\instance;
/**
 * Summary of profile_test
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class profile_test extends \advanced_testcase {
    /**
     * Test profile offer only available when discount profile field is configured.
     */
    public function test_is_available_only_when_discount_field_is_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $entity = new instance($record, $user->id);

        config::make()->discount_field = 0;
        $this->assertFalse(profile::is_available($entity));

        $fielddata = (object)[
            'name' => 'discount',
            'shortname' => 'discount',
            'datatype' => 'text',
        ];
        $fieldid = $DB->insert_record('user_info_field', $fielddata, true);
        config::make()->discount_field = $fieldid;

        $this->assertTrue(profile::is_available($entity));
    }

    /**
     * Test profile discount rules apply correctly to percentage and free values.
     */
    public function test_get_percentage_discount_applies_profile_field_rules(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        $fielddata = (object)[
            'name' => 'discount',
            'shortname' => 'discount',
            'datatype' => 'text',
        ];
        $fieldid = $DB->insert_record('user_info_field', $fielddata, true);
        config::make()->discount_field = $fieldid;

        $DB->insert_record('user_info_data', (object)[
            'userid' => $user->id,
            'fieldid' => $fieldid,
            'data' => 'free',
        ]);

        $entity = new instance($record, $user->id);
        $discount = new profile($entity, 200.0);

        $this->assertSame(100.0, $discount->get_percentage_discount());
        $this->assertSame(0.0, $discount->get_discounted_cost());

        $DB->update_record('user_info_data', (object)[
            'id' => $DB->get_field('user_info_data', 'id', ['userid' => $user->id, 'fieldid' => $fieldid]),
            'data' => '25% discount',
        ]);

        $entity = new instance($record, $user->id);
        $discount = new profile($entity, 200.0);

        $this->assertSame(25.0, $discount->get_percentage_discount());
        $this->assertSame(150.0, $discount->get_discounted_cost());
    }
}
