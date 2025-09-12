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
 * Unit tests for the enrol_wallet's payment subsystem callback implementation.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\payment;
use enrol_wallet\local\config;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;

/**
 * Unit tests for the enrol_wallet's payment subsystem callback implementation.
 *
 */
final class service_provider_test extends \advanced_testcase {

    /**
     * Test for service_provider::get_payable().
     * For payment area walletenrol, which enrol user into the course after payment.
     *
     * @covers ::get_payable()
     * @return void
     */
    public function test_get_payable_walletenrol(): void {
        global $DB;
        $this->resetAfterTest();
        if (!class_exists('\core_payment\helper')) {
            return;
        }
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $walletplugin = enrol_get_plugin('wallet');
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $course = $generator->create_course();
        $user = $generator->create_user();
        $data = [
            'courseid' => $course->id,
            'customint1' => $account->get('id'),
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => $studentrole->id,
        ];
        $id = $walletplugin->add_instance($course, $data);
        $payrecord = [
            'cost'       => 250,
            'currency'   => 'USD',
            'userid'     => $user->id,
            'instanceid' => $id,
        ];
        if (!$itemid = $DB->get_field('enrol_wallet_items', 'id', $payrecord, IGNORE_MULTIPLE)) {
            $itemid = $DB->insert_record('enrol_wallet_items', $payrecord);
        }
        $payable = service_provider::get_payable('walletenrol', $itemid);

        $this->assertEquals($account->get('id'), $payable->get_account_id());
        $this->assertEquals(250, $payable->get_amount());
        $this->assertEquals('USD', $payable->get_currency());
    }
    /**
     * Test for service_provider::get_payable().
     * For payment area wallettopup, which topping up the wallet after payment.
     *
     * @covers ::get_payable()
     * @return void
     */
    public function test_get_payable_wallettopup(): void {
        global $DB;
        $this->resetAfterTest();
        if (!class_exists('\core_payment\helper')) {
            return;
        }
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $user = $generator->create_user();
        config::make()->paymentaccount = $account->get('id');
        // Set a fake item form payment.
        $id = $DB->insert_record('enrol_wallet_items', ['cost' => 250, 'currency' => 'USD', 'userid' => $user->id]);

        $payable = service_provider::get_payable('wallettopup', $id);

        $this->assertEquals($account->get('id'), $payable->get_account_id());
        $this->assertEquals(250, $payable->get_amount());
        $this->assertEquals('USD', $payable->get_currency());
    }
    /**
     * Test for service_provider::get_success_url().
     * For payment area walletenrol, which enrol user into the course after payment.
     *
     * @covers ::get_success_url()
     */
    public function test_get_success_url_walletenrol(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        if (!class_exists('\core_payment\helper')) {
            return;
        }
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $walletplugin = enrol_get_plugin('wallet');
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $course = $generator->create_course();
        $user = $generator->create_user();

        $data = [
            'courseid' => $course->id,
            'customint1' => $account->get('id'),
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => $studentrole->id,
        ];
        $id = $walletplugin->add_instance($course, $data);
        $payrecord = [
            'cost'       => 250,
            'currency'   => 'USD',
            'userid'     => $user->id,
            'instanceid' => $id,
        ];
        if (!$itemid = $DB->get_field('enrol_wallet_items', 'id', $payrecord, IGNORE_MULTIPLE)) {
            $itemid = $DB->insert_record('enrol_wallet_items', $payrecord);
        }
        $successurl = service_provider::get_success_url('walletenrol', $itemid);
        $this->assertEquals(
            $CFG->wwwroot . '/course/view.php?id=' . $course->id,
            $successurl->out(false)
        );
    }

    /**
     * Test for service_provider::get_success_url().
     * For payment area wallettopup, which topping up the wallet after payment.
     *
     * @covers ::get_success_url()
     */
    public function test_get_success_url_wallettopup(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        if (!class_exists('\core_payment\helper')) {
            return;
        }
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $user = $generator->create_user();

        config::make()->paymentaccount = $account->get('id');
        // Set a fake item form payment.
        $id = $DB->insert_record('enrol_wallet_items', ['cost' => 250, 'currency' => 'USD', 'userid' => $user->id]);

        $successurl = service_provider::get_success_url('wallettopup', $id);
        $this->assertEquals(
            $CFG->wwwroot . '/',
            $successurl->out(false)
        );
    }
    /**
     * Test for service_provider::deliver_order().
     * For payment area walletenrol, which enrol user into the course after payment.
     *
     * @covers ::deliver_order()
     * @return void
     */
    public function test_deliver_order_walletenrol(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        if (!class_exists('\core_payment\helper')) {
            return;
        }
        $this->assertTrue(enrol_is_enabled('wallet'));

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $walletplugin = enrol_get_plugin('wallet');
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $course = $generator->create_course();
        $context = \context_course::instance($course->id);
        $user = $generator->create_user();
        $this->setUser($user);
        global $USER;
        $data = [
            'courseid' => $course->id,
            'customint1' => $account->get('id'),
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => $studentrole->id,
        ];
        $id = $walletplugin->add_instance($course, $data);
        $payrecord = [
            'cost'       => 250,
            'currency'   => 'USD',
            'userid'     => $USER->id,
            'instanceid' => $id,
        ];
        if (!$itemid = $DB->get_field('enrol_wallet_items', 'id', $payrecord, IGNORE_MULTIPLE)) {
            $itemid = $DB->insert_record('enrol_wallet_items', $payrecord);
        }

        $paymentgen = $generator->get_plugin_generator('core_payment');
        $paymentid = $paymentgen->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 10,
            'userid' => $user->id,
        ]);

        $this->assertFalse(is_enrolled($context, $user));

        $balance = new balance();
        $this->assertEquals(0, $balance->get_total_balance());

        $delivered = service_provider::deliver_order('walletenrol', $itemid, $paymentid, $user->id);
        $balance = new balance();
        $this->assertTrue($delivered);
        $this->assertEquals(0, $balance->get_total_balance());
        $this->assertTrue(is_enrolled($context, $user));
        $this->assertTrue(user_has_role_assignment($user->id, $studentrole->id, $context->id));

        $user2 = $generator->create_user();
        $this->setUser($user2);
        $op = new balance_op();
        $op->credit(100);
        $payrecord = [
            'cost'       => 150,
            'currency'   => 'USD',
            'userid'     => $USER->id,
            'instanceid' => $id,
        ];

        $itemid = $DB->insert_record('enrol_wallet_items', $payrecord);

        $this->assertFalse(is_enrolled($context));

        $balance = new balance();
        $this->assertEquals(100, $balance->get_total_balance());

        $paymentid = $paymentgen->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 10,
            'userid' => $user2->id,
        ]);
        $delivered = service_provider::deliver_order('walletenrol', $itemid, $paymentid, $user2->id);
        $balance = new balance();
        $this->assertTrue($delivered);
        $this->assertEquals(0, $balance->get_total_balance());
        $this->assertTrue(is_enrolled($context, $user2));
        $this->assertTrue(user_has_role_assignment($user2->id, $studentrole->id, $context->id));

        $user3 = $generator->create_user();
        $this->setUser($user3);
        $op = new balance_op();
        $op->credit(100);
        $payrecord = [
            'cost'       => 100,
            'currency'   => 'USD',
            'userid'     => $USER->id,
            'instanceid' => $id,
        ];
        if (!$itemid = $DB->get_field('enrol_wallet_items', 'id', $payrecord, IGNORE_MULTIPLE)) {
            $itemid = $DB->insert_record('enrol_wallet_items', $payrecord);
        }
        $this->assertFalse(is_enrolled($context));

        $balance = new balance();
        $this->assertEquals(100, $balance->get_total_balance());

        $paymentid = $paymentgen->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 10,
            'userid' => $user3->id,
        ]);

        $delivered = service_provider::deliver_order('walletenrol', $itemid, $paymentid, $user3->id);

        $balance = new balance();
        $this->assertFalse($delivered);
        $this->assertEquals(200, $balance->get_total_balance());
        $this->assertEquals(100, $balance->get_main_balance());
        $this->assertFalse(is_enrolled($context, $user3));
    }

    /**
     * Test for service_provider::deliver_order().
     * For payment area wallettopup, which topping up the wallet after payment.
     *
     * @covers ::deliver_order()
     * @return void
     */
    public function test_deliver_order_wallettopup(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        if (!class_exists('\core_payment\helper')) {
            return;
        }
        $this->assertTrue(enrol_is_enabled('wallet'));

        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $user = $generator->create_user();

        config::make()->paymentaccount = $account->get('id');
        // Set a fake item form payment.
        $id = $DB->insert_record('enrol_wallet_items', ['cost' => 250, 'currency' => 'USD', 'userid' => $user->id]);

        $paymentid = $generator->get_plugin_generator('core_payment')->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 250,
            'userid' => $user->id,
        ]);

        service_provider::deliver_order('wallettopup', $id, $paymentid, $user->id);
        $bal = new balance($user->id);
        $balance = $bal->get_main_balance();

        $this->assertEquals(250, $balance);

        // Set a fake item form payment.
        $category = $generator->create_category();
        $recorddata = [
            'cost' => 100,
            'currency' => 'USD',
            'userid' => $user->id,
            'category' => $category->id,
        ];
        $id = $DB->insert_record('enrol_wallet_items', $recorddata);

        $paymentid = $generator->get_plugin_generator('core_payment')->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 100,
            'userid' => $user->id,
        ]);

        service_provider::deliver_order('wallettopup', $id, $paymentid, $user->id);
        $bal = new balance($user->id, $category);

        $this->assertEquals(250, $bal->get_main_balance());
        $this->assertEquals(350, $bal->get_valid_balance());
        $this->assertEquals(350, $bal->get_total_balance());
        $this->assertEquals(100, $bal->get_cat_balance($category->id));
    }

}
