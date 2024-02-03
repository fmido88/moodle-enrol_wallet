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
 * Contains tests for balance operations
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet\util;

use enrol_wallet\util\balance_op;
use enrol_wallet\util\balance;
use enrol_wallet\transactions;

/**
 * Tests for balance operations class.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class balance_op_test extends \advanced_testcase {
    /**
     * Test conditional discounts.
     * @covers ::conditional_discount_charging()
     * @return void
     */
    public function test_conditional_discount_charging(): void {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        global $USER;
        $now = time();
        set_config('conditionaldiscount_apply', 1, 'enrol_wallet');
        $params = [
            'cond' => 400,
            'percent' => 15,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ];
        $DB->insert_record('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 600,
            'percent' => 20,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ];
        $DB->insert_record('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 800,
            'percent' => 25,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ];
        $DB->insert_record('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 200,
            'percent' => 50,
            'timeto' => $now - DAYSECS, // Expired.
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ];
        $DB->insert_record('enrol_wallet_cond_discount', $params);

        $params = [
            'cond' => 400,
            'percent' => 50,
            'timefrom' => $now + DAYSECS, // Not available yet.
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ];
        $DB->insert_record('enrol_wallet_cond_discount', $params);

        transactions::payment_topup(200, $user1->id);
        // The user tries to pay 500, this is the number passes to the function.
        $extra2 = 500 * 0.15;
        transactions::payment_topup(500 * 0.85, $user2->id);

        $extra3 = 700 * 0.2;
        transactions::payment_topup(700 * 0.8, $user3->id);

        $extra4 = 1000 * 0.25;
        transactions::payment_topup(1000 * 0.75, $user4->id);

        $balance = new balance($user1->id);
        $balance1 = $balance->get_total_balance();
        $norefund1 = $balance->get_total_nonrefundable();
        $free1 = $balance->get_total_free();

        $balance = new balance($user2->id);
        $balance2 = $balance->get_total_balance();
        $norefund2 = $balance->get_total_nonrefundable();
        $free2 = $balance->get_total_free();

        $balance = new balance($user3->id);
        $balance3 = $balance->get_total_balance();
        $norefund3 = $balance->get_total_nonrefundable();
        $free3 = $balance->get_total_free();

        $balance = new balance($user4->id);
        $balance4 = $balance->get_total_balance();
        $norefund4 = $balance->get_total_nonrefundable();
        $free4 = $balance->get_total_free();

        $this->assertEquals(200, $balance1);
        $this->assertEquals(0, $norefund1);
        $this->assertEquals(0, $free1);

        $this->assertEquals(500, $balance2);
        $this->assertEquals($extra2, $norefund2);
        $this->assertEquals($extra2, $free2);

        $this->assertEquals(700, $balance3);
        $this->assertEquals($extra3, $norefund3);
        $this->assertEquals($extra3, $free3);

        $this->assertEquals(1000, $balance4);
        $this->assertEquals($extra4, $norefund4);
        $this->assertEquals($extra4, $free4);

    }

    /**
     * Test credit function.
     * @covers ::credit
     * @return void
     */
    public function test_credit():void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $cat1 = $gen->create_category();
        $cat2 = $gen->create_category();

        $op = new balance_op($user1->id);
        $op->credit(100);
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(0, $op->get_main_nonrefundable());
        $this->assertEquals(100, $op->get_total_balance());
        $this->assertEquals(100, $op->get_total_refundable());
        $this->assertEquals(0, $op->get_total_nonrefundable());
        $this->assertEquals(100, $op->get_valid_balance());
        $this->assertEquals(0, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat1);
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(0, $op->get_main_nonrefundable());
        $this->assertEquals(100, $op->get_total_balance());
        $this->assertEquals(100, $op->get_total_refundable());
        $this->assertEquals(0, $op->get_total_nonrefundable());
        $this->assertEquals(100, $op->get_valid_balance());
        $this->assertEquals(0, $op->get_valid_nonrefundable());

        $op->credit(100);
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(0, $op->get_main_nonrefundable());
        $this->assertEquals(200, $op->get_total_balance());
        $this->assertEquals(200, $op->get_total_refundable());
        $this->assertEquals(0, $op->get_total_nonrefundable());
        $this->assertEquals(200, $op->get_valid_balance());
        $this->assertEquals(0, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat2);
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(0, $op->get_main_nonrefundable());
        $this->assertEquals(200, $op->get_total_balance());
        $this->assertEquals(200, $op->get_total_refundable());
        $this->assertEquals(0, $op->get_total_nonrefundable());
        $this->assertEquals(100, $op->get_valid_balance());
        $this->assertEquals(0, $op->get_valid_nonrefundable());

        $op->credit(30);
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(0, $op->get_main_nonrefundable());
        $this->assertEquals(230, $op->get_total_balance());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(0, $op->get_total_nonrefundable());
        $this->assertEquals(130, $op->get_valid_balance());
        $this->assertEquals(0, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id);
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(0, $op->get_main_nonrefundable());
        $this->assertEquals(230, $op->get_total_balance());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(0, $op->get_total_nonrefundable());
        $this->assertEquals(100, $op->get_valid_balance());
        $this->assertEquals(0, $op->get_valid_nonrefundable());

        $op->credit(40, $op::OTHER, 0, '', false);
        $this->assertEquals(140, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(40, $op->get_main_nonrefundable());
        $this->assertEquals(270, $op->get_total_balance());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(40, $op->get_total_nonrefundable());
        $this->assertEquals(140, $op->get_valid_balance());
        $this->assertEquals(40, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat1);
        $this->assertEquals(140, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(40, $op->get_main_nonrefundable());
        $this->assertEquals(270, $op->get_total_balance());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(40, $op->get_total_nonrefundable());
        $this->assertEquals(240, $op->get_valid_balance());
        $this->assertEquals(40, $op->get_valid_nonrefundable());

        $op->credit(70, $op::OTHER, 0, '', false);
        $this->assertEquals(140, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(40, $op->get_main_nonrefundable());
        $this->assertEquals(340, $op->get_total_balance());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(110, $op->get_total_nonrefundable());
        $this->assertEquals(310, $op->get_valid_balance());
        $this->assertEquals(110, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat2);
        $this->assertEquals(140, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(40, $op->get_main_nonrefundable());
        $this->assertEquals(340, $op->get_total_balance());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(110, $op->get_total_nonrefundable());
        $this->assertEquals(170, $op->get_valid_balance());
        $this->assertEquals(40, $op->get_valid_nonrefundable());

        $op->credit(20, $op::OTHER, 0, '', false);
        $this->assertEquals(140, $op->get_main_balance());
        $this->assertEquals(100, $op->get_main_refundable());
        $this->assertEquals(40, $op->get_main_nonrefundable());
        $this->assertEquals(360, $op->get_total_balance());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(130, $op->get_total_nonrefundable());
        $this->assertEquals(190, $op->get_valid_balance());
        $this->assertEquals(60, $op->get_valid_nonrefundable());

        $count = $DB->count_records('enrol_wallet_transactions', ['userid' => $user1->id]);
        $this->assertEquals(6, $count);
        $record = [
            'userid'    => $user1->id,
            'category'  => 0,
            'balance'   => 100,
            'balbefore' => 0,
            'amount'    => 100,
            'norefund'  => 0,
        ];
        $exist1 = $DB->record_exists('enrol_wallet_transactions', $record);
        $record = [
            'userid'    => $user1->id,
            'category'  => 0,
            'balance'   => 140,
            'balbefore' => 100,
            'amount'    => 40,
            'norefund'  => 40,
        ];
        $exist2 = $DB->record_exists('enrol_wallet_transactions', $record);
        $record = [
            'userid'    => $user1->id,
            'category'  => $cat1->id,
            'balance'   => 100,
            'balbefore' => 0,
            'amount'    => 100,
            'norefund'  => 0,
        ];
        $exist3 = $DB->record_exists('enrol_wallet_transactions', $record);
        $record = [
            'userid'    => $user1->id,
            'category'  => $cat1->id,
            'balance'   => 170,
            'balbefore' => 100,
            'amount'    => 70,
            'norefund'  => 70,
        ];
        $exist4 = $DB->record_exists('enrol_wallet_transactions', $record);
        $record = [
            'userid'    => $user1->id,
            'category'  => $cat2->id,
            'balance'   => 30,
            'balbefore' => 0,
            'amount'    => 30,
            'norefund'  => 0,
        ];
        $exist5 = $DB->record_exists('enrol_wallet_transactions', $record);
        $record = [
            'userid'    => $user1->id,
            'category'  => $cat2->id,
            'balance'   => 50,
            'balbefore' => 30,
            'amount'    => 20,
            'norefund'  => 20,
        ];
        $exist6 = $DB->record_exists('enrol_wallet_transactions', $record);
        $this->assertTrue($exist1);
        $this->assertTrue($exist2);
        $this->assertTrue($exist3);
        $this->assertTrue($exist4);
        $this->assertTrue($exist5);
        $this->assertTrue($exist6);
    }

    /**
     * Test debit method
     * @covers ::debit
     * @return void
     */
    public function test_debit():void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $cat1 = $gen->create_category();
        $cat2 = $gen->create_category();
        $cat3 = $gen->create_category(['parent' => $cat2->id]);
        $catbalance = [
            $cat1->id => (object)[
                'refundable' => 50,
                'nonrefundable' => 30,
            ],
            $cat2->id => (object)[
                'refundable' => 70,
                'nonrefundable' => 100,
            ],
        ];
        $record = [
            'userid' => $user1->id,
            'refundable' => 200,
            'nonrefundable' => 120,
            'cat_balance' => json_encode($catbalance),
        ];
        $DB->insert_record('enrol_wallet_balance', $record, false);
        $op = new balance_op($user1->id);
        $this->assertEquals(320, $op->get_main_balance());
        $this->assertEquals(200, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(320, $op->get_total_refundable());
        $this->assertEquals(250, $op->get_total_nonrefundable());
        $this->assertEquals(570, $op->get_total_balance());
        $this->assertEquals(320, $op->get_valid_balance());
        $this->assertEquals(120, $op->get_valid_nonrefundable());

        $this->setUser($user1);
        $op->debit(40);
        $this->assertEquals(280, $op->get_main_balance());
        $this->assertEquals(160, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(280, $op->get_total_refundable());
        $this->assertEquals(250, $op->get_total_nonrefundable());
        $this->assertEquals(530, $op->get_total_balance());
        $this->assertEquals(280, $op->get_valid_balance());
        $this->assertEquals(120, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat1);
        $this->assertEquals(280, $op->get_main_balance());
        $this->assertEquals(160, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(280, $op->get_total_refundable());
        $this->assertEquals(250, $op->get_total_nonrefundable());
        $this->assertEquals(530, $op->get_total_balance());
        $this->assertEquals(360, $op->get_valid_balance());
        $this->assertEquals(150, $op->get_valid_nonrefundable());

        $op->debit(10);
        $this->assertEquals(280, $op->get_main_balance());
        $this->assertEquals(160, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(270, $op->get_total_refundable());
        $this->assertEquals(250, $op->get_total_nonrefundable());
        $this->assertEquals(520, $op->get_total_balance());
        $this->assertEquals(350, $op->get_valid_balance());
        $this->assertEquals(150, $op->get_valid_nonrefundable());

        $op->debit(50);
        $this->assertEquals(280, $op->get_main_balance());
        $this->assertEquals(160, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(230, $op->get_total_refundable());
        $this->assertEquals(240, $op->get_total_nonrefundable());
        $this->assertEquals(470, $op->get_total_balance());
        $this->assertEquals(300, $op->get_valid_balance());
        $this->assertEquals(140, $op->get_valid_nonrefundable());

        $op->debit(40);
        $this->assertEquals(260, $op->get_main_balance());
        $this->assertEquals(140, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(210, $op->get_total_refundable());
        $this->assertEquals(220, $op->get_total_nonrefundable());
        $this->assertEquals(430, $op->get_total_balance());
        $this->assertEquals(260, $op->get_valid_balance());
        $this->assertEquals(120, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat3);
        $this->assertEquals(260, $op->get_main_balance());
        $this->assertEquals(140, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(210, $op->get_total_refundable());
        $this->assertEquals(220, $op->get_total_nonrefundable());
        $this->assertEquals(430, $op->get_total_balance());
        $this->assertEquals(430, $op->get_valid_balance());
        $this->assertEquals(220, $op->get_valid_nonrefundable());

        $op->credit(30);
        $op->credit(40, $op::OTHER, 0, '', false);
        $this->assertEquals(260, $op->get_main_balance());
        $this->assertEquals(140, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(240, $op->get_total_refundable());
        $this->assertEquals(260, $op->get_total_nonrefundable());
        $this->assertEquals(500, $op->get_total_balance());
        $this->assertEquals(500, $op->get_valid_balance());
        $this->assertEquals(260, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat2);
        $this->assertEquals(260, $op->get_main_balance());
        $this->assertEquals(140, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(240, $op->get_total_refundable());
        $this->assertEquals(260, $op->get_total_nonrefundable());
        $this->assertEquals(500, $op->get_total_balance());
        $this->assertEquals(430, $op->get_valid_balance());
        $this->assertEquals(220, $op->get_valid_nonrefundable());

        $op = new balance_op($user1->id, $cat3);
        $op->debit(300);
        $this->assertEquals(200, $op->get_main_balance());
        $this->assertEquals(80, $op->get_main_refundable());
        $this->assertEquals(120, $op->get_main_nonrefundable());
        $this->assertEquals(80, $op->get_total_refundable());
        $this->assertEquals(120, $op->get_total_nonrefundable());
        $this->assertEquals(200, $op->get_total_balance());
        $this->assertEquals(200, $op->get_valid_balance());
        $this->assertEquals(120, $op->get_valid_nonrefundable());
    }

    /**
     * Test free balance add and deduct
     * @covers ::get_total_free
     * @return void
     */
    public function test_free_balance():void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $cat1 = $gen->create_category();
        $cat2 = $gen->create_category();
        $course1 = $gen->create_course(['category' => $cat1->id]);
        $course2 = $gen->create_course(['category' => $cat2->id]);
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $instance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'wallet'], '*', MUST_EXIST);
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();
        $user4 = $gen->create_user();
        $user5 = $gen->create_user();
        $user6 = $gen->create_user();
        $user7 = $gen->create_user();
        $user8 = $gen->create_user();
        $user9 = $gen->create_user();
        $user10 = $gen->create_user();

        $op = new balance_op($user1->id);
        $op->credit(50, $op::C_ACCOUNT_GIFT, 0, '', false);
        $op->credit(100);
        $this->assertEquals(50, $op->get_main_free());
        $this->assertEquals(150, $op->get_main_balance());
        $sink = $this->redirectEvents();
        $op->debit(30, $op::OTHER);
        $this->assertEquals(50, $op->get_main_free());
        $this->assertEquals(120, $op->get_main_balance());
        $events = $sink->get_events();
        $sink->clear();

        foreach ($events as $key => $event) {
            if ($event->eventname !== '\enrol_wallet\event\transactions_triggered') {
                unset($events[$key]);
            }
        }

        $this->assertEquals(1, count($events));
        $debitevent = reset($events);
        $this->assertEquals(0, $debitevent->other['freecut']);

        $op->debit(100, $op::OTHER);
        $this->assertEquals(20, $op->get_main_free());
        $this->assertEquals(20, $op->get_main_balance());
        $events = $sink->get_events();
        $sink->close();

        foreach ($events as $key => $event) {
            if ($event->eventname !== '\enrol_wallet\event\transactions_triggered') {
                unset($events[$key]);
            }
        }

        $this->assertEquals(1, count($events));
        $debitevent = reset($events);
        $this->assertEquals(30, $debitevent->other['freecut']);

        $op = new balance_op($user2->id);
        $op->credit(50, $op::C_REFERRAL, $user3->id, '', false);
        $this->assertEquals(50, $op->get_main_free());

        $op = new balance_op($user3->id);
        $op->credit(50, $op::C_AWARD, $course1->id, '', false);
        $this->assertEquals(50, $op->get_total_free());
        $this->assertEquals(50, $op->get_valid_free());
        $this->assertEquals(0, $op->get_main_free());

        $op = new balance_op($user4->id);
        $op->credit(50, $op::C_ROLLBACK, $instance1->id, '', false);
        $this->assertEquals(50, $op->get_total_free());
        $this->assertEquals(50, $op->get_valid_free());
        $this->assertEquals(0, $op->get_main_free());

        $op = new balance_op($user5->id);
        $op->credit(50, $op::C_CASHBACK, $course2->id, '', false);
        $this->assertEquals(50, $op->get_total_free());
        $this->assertEquals(50, $op->get_valid_free());
        $this->assertEquals(0, $op->get_main_free());
        $op = new balance_op($user5->id);
        $op->credit(50, $op::C_DISCOUNT, 0, '', false);
        $op->credit(50);
        $op = new balance_op($user5->id, $cat2);
        $this->assertEquals(150, $op->get_total_balance());
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(150, $op->get_valid_balance());
        $this->assertEquals(50, $op->get_main_nonrefundable());
        $this->assertEquals(50, $op->get_main_refundable());
        $this->assertEquals(100, $op->get_total_nonrefundable());
        $this->assertEquals(100, $op->get_total_free());
        $this->assertEquals(100, $op->get_valid_free());
        $op = new balance_op($user5->id, $cat1);
        $this->assertEquals(150, $op->get_total_balance());
        $this->assertEquals(100, $op->get_main_balance());
        $this->assertEquals(100, $op->get_valid_balance());
        $this->assertEquals(50, $op->get_main_nonrefundable());
        $this->assertEquals(50, $op->get_main_refundable());
        $this->assertEquals(100, $op->get_total_nonrefundable());
        $this->assertEquals(100, $op->get_total_free());
        $this->assertEquals(50, $op->get_valid_free());
        $op = new balance_op($user5->id, $cat2);
        $sink = $this->redirectEvents();
        $op->debit(120, $op::OTHER);
        $events = $sink->get_events();
        $sink->close();

        foreach ($events as $key => $event) {
            if ($event->eventname !== '\enrol_wallet\event\transactions_triggered') {
                unset($events[$key]);
            }
        }

        $this->assertEquals(1, count($events));
        $debitevent = reset($events);
        $this->assertEquals(70, $debitevent->other['freecut']);

        $op = new balance_op($user5->id, $cat2);
        $this->assertEquals(30, $op->get_total_balance());
        $this->assertEquals(30, $op->get_main_balance());
        $this->assertEquals(30, $op->get_valid_balance());
        $this->assertEquals(30, $op->get_main_nonrefundable());
        $this->assertEquals(0, $op->get_main_refundable());
        $this->assertEquals(30, $op->get_total_nonrefundable());
        $this->assertEquals(30, $op->get_total_free());
        $this->assertEquals(30, $op->get_valid_free());

        $op = new balance_op($user6->id);
        $op->credit(50, $op::C_DISCOUNT, 0, '', false);
        $this->assertEquals(50, $op->get_main_free());
        $this->assertEquals(50, $op->get_total_free());
        $this->assertEquals(50, $op->get_valid_free());
    }

}
