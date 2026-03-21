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

namespace enrol_wallet\payment;

use enrol_wallet\local\utils\testing;

/**
 * Tests for Wallet enrolment
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_test extends \advanced_testcase {
    /**
     * Test create item.
     *
     * @covers ::create_item()
     * @return void
     */
    public function test_create_item(): void {
        global $DB;
        $g = $this->getDataGenerator();
        $this->resetAfterTest();
        $user1 = $g->create_user();
        $user2 = $g->create_user();

        $category = $g->create_category();
        $course = $g->create_course(['category' => $category->id]);
        $instance = testing::get_generator()->create_instance($course->id);

        $item1 = item::create_item(100, 'USD', $user1->id, $instance->id);
        $item2 = item::create_item(100, 'USD', $user1->id, $instance->id);
        $item3 = item::create_item(100, 'USD', $user2->id, $instance->id);
        $item4 = item::create_item(50, 'USD', $user1->id);

        $this->assertEquals($item1->id, $item2->id);
        $this->assertNotEquals($item1->id, $item3->id);
        $this->assertNotEquals($item1->id, $item4->id);

        $item1record = $DB->get_record('enrol_wallet_items', ['id' => $item1->id]);
        $item1called = item::get_record(['id' => $item1->id]);

        $this->assertEquals($item1record->cost, $item1called->cost);
        $this->assertEquals($item1record->currency, $item1called->currency);
        $this->assertEquals($item1record->instanceid, $item1called->instanceid);
        $this->assertEquals($item1record->userid, $item1called->userid);
    }
}
