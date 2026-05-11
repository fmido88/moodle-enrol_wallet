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
 * Summary of repurchase_test.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class repurchase_test extends \advanced_testcase {
    /**
     * Test repurchase discount availability depends on instance and config.
     */
    public function test_is_available_only_for_instance_and_when_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $entity = new instance($record, $user->id);

        config::make()->repurchase = 0;
        $this->assertFalse(repurchase::is_available($entity));

        config::make()->repurchase = 1;
        $this->assertTrue(repurchase::is_available($entity));
    }

    /**
     * Test repurchase discount percentage is returned correctly for eligible users.
     */
    public function test_get_percentage_discount_returns_expected_repurchase_value(): void {
        global $DB;
        $this->resetAfterTest();

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        $enrolment = new \stdClass();
        $enrolment->enrolid = $record->id;
        $enrolment->userid = $user->id;
        $enrolment->modifierid = $user->id;
        $enrolment->timestart = $now - 400000;
        $enrolment->timecreated = $now - 400000;
        $enrolment->timeend = $now - 100000;
        $enrolment->timemodified = $now - 50000;
        $enrolment->status = 0;
        $DB->insert_record('user_enrolments', $enrolment);

        $config = config::make();
        $config->repurchase = 1;
        $config->repurchase_firstdis = 25;
        $config->repurchase_seconddis = 50;

        $entity = new instance($record, $user->id);
        $discount = new repurchase($entity, 120.0);

        $this->assertSame(50.0, $discount->get_percentage_discount());
        $this->assertSame(60.0, $discount->get_discounted_cost());
    }

    /**
     * Test repurchase discount returns zero when user is not eligible.
     */
    public function test_get_percentage_discount_returns_zero_when_not_eligible(): void {
        global $DB;
        $this->resetAfterTest();

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $record = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

        $enrolment = new \stdClass();
        $enrolment->enrolid = $record->id;
        $enrolment->userid = $user->id;
        $enrolment->modifierid = $user->id;
        $enrolment->timestart = $now - DAYSECS;
        $enrolment->timecreated = $now - DAYSECS;
        $enrolment->timeend = $now + DAYSECS;
        $enrolment->timemodified = $now - DAYSECS;
        $enrolment->status = 0;
        $DB->insert_record('user_enrolments', $enrolment);

        config::make()->repurchase = 1;
        config::make()->repurchase_firstdis = 0;
        config::make()->repurchase_seconddis = 60;

        $entity = new instance($record, $user->id);
        $discount = new repurchase($entity, 100.0);

        $this->assertSame(0.0, $discount->get_percentage_discount());
        $this->assertSame(100.0, $discount->get_discounted_cost());
    }
}
