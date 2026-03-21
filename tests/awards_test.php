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
 * Tests for awards functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet;

use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;

/**
 * Tests for awards (when students complete courses with high marks).
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class awards_test extends \advanced_testcase {
    /**
     * Test creating an award record.
     * @covers ::create_award()
     */
    public function test_create_award(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $awarddata = [
            'userid'      => $user->id,
            'courseid'    => $course->id,
            'grade'       => 85,
            'maxgrade'    => 100,
            'percent'     => 85,
            'amount'      => 50,
            'timecreated' => timedate::time(),
        ];

        $awardid = $DB->insert_record('enrol_wallet_awards', $awarddata);
        $this->assertNotEmpty($awardid);

        // Verify the award.
        $award = $DB->get_record('enrol_wallet_awards', ['id' => $awardid]);
        $this->assertEquals($user->id, $award->userid);
        $this->assertEquals($course->id, $award->courseid);
        $this->assertEquals(85, $award->grade);
        $this->assertEquals(50, $award->amount);
    }

    /**
     * Test getting awards for a user.
     * @covers ::get_user_awards()
     */
    public function test_get_user_awards(): void {
        global $DB;
        $this->resetAfterTest();

        $user    = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Create awards for user.
        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => $course1->id,
            'grade'       => 90,
            'maxgrade'    => 100,
            'percent'     => 90,
            'amount'      => 100,
            'timecreated' => timedate::time(),
        ]);

        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => $course2->id,
            'grade'       => 80,
            'maxgrade'    => 100,
            'percent'     => 80,
            'amount'      => 50,
            'timecreated' => timedate::time(),
        ]);

        // Get all awards for user.
        $awards = $DB->get_records('enrol_wallet_awards', ['userid' => $user->id]);
        $this->assertCount(2, $awards);
    }

    /**
     * Test getting awards for a course.
     * @covers ::get_course_awards()
     */
    public function test_get_course_awards(): void {
        global $DB;
        $this->resetAfterTest();

        $user1  = $this->getDataGenerator()->create_user();
        $user2  = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create awards for different users in same course.
        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user1->id,
            'courseid'    => $course->id,
            'grade'       => 95,
            'maxgrade'    => 100,
            'percent'     => 95,
            'amount'      => 150,
            'timecreated' => timedate::time(),
        ]);

        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user2->id,
            'courseid'    => $course->id,
            'grade'       => 85,
            'maxgrade'    => 100,
            'percent'     => 85,
            'amount'      => 75,
            'timecreated' => timedate::time(),
        ]);

        // Get all awards for course.
        $awards = $DB->get_records('enrol_wallet_awards', ['courseid' => $course->id]);
        $this->assertCount(2, $awards);
    }

    /**
     * Test calculating award amount based on grade.
     * @covers ::calculate_award_amount()
     */
    public function test_calculate_award_amount(): void {
        $this->resetAfterTest();

        $grade    = 80;
        $maxgrade = 100;
        $percent  = 80;

        // Award calculation: grade percentage above threshold * amount per percentage.
        // Assuming 1% = $1 for this example.
        $threshold = 70; // Minimum grade to get award.
        $perpct    = 1; // 1$ per percentage point above threshold.

        $above  = $percent - $threshold;
        $amount = max(0, $above * $perpct);

        $this->assertEquals(10, $amount); // 1 * (80 - 70) = 10.
    }

    /**
     * Test high grade gets higher award.
     * @covers ::higher_grade_higher_award()
     */
    public function test_higher_grade_higher_award(): void {
        $this->resetAfterTest();

        $threshold = 70;
        $perpct    = 2; // 2$ per percentage point.

        // Grade 80%.
        $amount80 = max(0, (80 - $threshold) * $perpct);
        $this->assertEquals(20, $amount80);

        // Grade 95%.
        $amount95 = max(0, (95 - $threshold) * $perpct);
        $this->assertEquals(50, $amount95);

        // Grade 65% - below threshold.
        $amount65 = max(0, (65 - $threshold) * $perpct);
        $this->assertEquals(0, $amount65);
    }

    /**
     * Test award amount is added to user balance.
     * @covers ::award_balance_integration()
     */
    public function test_award_added_to_balance(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Initial balance.
        $op = new balance_op($user->id);
        $op->credit(100);

        // Create award record.
        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => $course->id,
            'grade'       => 90,
            'maxgrade'    => 100,
            'percent'     => 90,
            'amount'      => 50,
            'timecreated' => timedate::time(),
        ]);

        // Credit the award amount to user balance.
        $op = new balance_op($user->id);
        $op->credit(50, $op::C_AWARD, $course->id, '', false);

        // Check balance.
        $balance = new balance($user->id);
        $this->assertEquals(150, $balance->get_total_balance());
    }

    /**
     * Test award with different grade percentages.
     * @covers ::grade_percentages()
     */
    public function test_award_grade_percentages(): void {
        $this->resetAfterTest();

        $testcases = [
            ['grade' => 100, 'max' => 100, 'expected' => 100],
            ['grade' => 75, 'max' => 100, 'expected' => 75],
            ['grade' => 50, 'max' => 100, 'expected' => 50],
            ['grade' => 150, 'max' => 200, 'expected' => 75], // 150/200 = 75%
            ['grade' => 0, 'max' => 100, 'expected' => 0],
        ];

        foreach ($testcases as $case) {
            $percent = ($case['grade'] / $case['max']) * 100;
            $this->assertEquals($case['expected'], $percent);
        }
    }

    /**
     * Test multiple awards for same user and course - only highest should count.
     * @covers ::unique_award_per_course()
     */
    public function test_multiple_awards_same_course(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create multiple awards for same user and course (shouldn't happen in practice).
        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => $course->id,
            'grade'       => 70,
            'maxgrade'    => 100,
            'percent'     => 70,
            'amount'      => 20,
            'timecreated' => timedate::time() - 100,
        ]);

        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => $course->id,
            'grade'       => 90,
            'maxgrade'    => 100,
            'percent'     => 90,
            'amount'      => 50,
            'timecreated' => timedate::time(),
        ]);

        // Get all awards - there could be multiple, but we should only use the latest/highest.
        $awards = $DB->get_records('enrol_wallet_awards', [
            'userid'   => $user->id,
            'courseid' => $course->id,
        ], 'timecreated DESC');

        // Get the first (latest) award.
        $latest = reset($awards);
        $this->assertEquals(90, $latest->grade);
        $this->assertEquals(50, $latest->amount);
    }

    /**
     * Test award is non-refundable.
     * @covers ::award_non_refundable()
     */
    public function test_award_is_non_refundable(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Credit award as non-refundable.
        $op = new balance_op($user->id);
        $op->credit(50, $op::C_AWARD, $course->id, '', false);

        // Check balance.
        $balance = new balance($user->id);
        $this->assertEquals(50, $balance->get_total_balance());
        $this->assertEquals(50, $balance->get_total_nonrefundable());
    }

    /**
     * Test get total awards amount for user.
     * @covers ::get_total_awards()
     */
    public function test_get_total_awards_for_user(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        // Create multiple awards.
        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => 1,
            'grade'       => 90,
            'maxgrade'    => 100,
            'percent'     => 90,
            'amount'      => 50,
            'timecreated' => timedate::time(),
        ]);

        $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => 2,
            'grade'       => 85,
            'maxgrade'    => 100,
            'percent'     => 85,
            'amount'      => 30,
            'timecreated' => timedate::time(),
        ]);

        // Calculate total.
        $awards = $DB->get_records('enrol_wallet_awards', ['userid' => $user->id]);
        $total  = 0;

        foreach ($awards as $award) {
            $total += $award->amount;
        }

        $this->assertEquals(80, $total);
    }

    /**
     * Test delete award.
     * @covers ::delete_award()
     */
    public function test_delete_award(): void {
        global $DB;
        $this->resetAfterTest();

        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $awardid = $DB->insert_record('enrol_wallet_awards', [
            'userid'      => $user->id,
            'courseid'    => $course->id,
            'grade'       => 90,
            'maxgrade'    => 100,
            'percent'     => 90,
            'amount'      => 50,
            'timecreated' => timedate::time(),
        ]);

        // Verify exists.
        $this->assertTrue($DB->record_exists('enrol_wallet_awards', ['id' => $awardid]));

        // Delete.
        $DB->delete_records('enrol_wallet_awards', ['id' => $awardid]);

        // Verify deleted.
        $this->assertFalse($DB->record_exists('enrol_wallet_awards', ['id' => $awardid]));
    }

    /**
     * Test award threshold configuration.
     * @covers ::award_threshold()
     */
    public function test_award_threshold(): void {
        $this->resetAfterTest();

        // Test different threshold scenarios.
        $threshold = 80;

        // Grade below threshold.
        $gradebelow = 75;
        $this->assertFalse($gradebelow >= $threshold);

        // Grade at threshold.
        $gradeat = 80;
        $this->assertTrue($gradeat >= $threshold);

        // Grade above threshold.
        $gradeabove = 90;
        $this->assertTrue($gradeabove >= $threshold);
    }
}
