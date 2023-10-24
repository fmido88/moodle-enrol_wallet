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

/**
 * Unit tests for the enrol_wallet's payment subsystem callback implementation.
 *
 */
class service_provider_test extends \advanced_testcase {

    /**
     * Test for service_provider::get_payable().
     * For payment area walletenrol, which enrol user into the course after payment.
     *
     * @covers ::get_payable()
     */
    public function test_get_payable_walletenrol() {
        global $DB;
        $this->resetAfterTest();
        if (class_exists('\core_payment\helper')) {
            return;
        }
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $walletplugin = enrol_get_plugin('wallet');
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $course = $generator->create_course();

        $data = [
            'courseid' => $course->id,
            'customint1' => $account->get('id'),
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => $studentrole->id,
        ];
        $id = $walletplugin->add_instance($course, $data);

        $payable = service_provider::get_payable('walletenrol', $id);

        $this->assertEquals($account->get('id'), $payable->get_account_id());
        $this->assertEquals(250, $payable->get_amount());
        $this->assertEquals('USD', $payable->get_currency());
    }
    /**
     * Test for service_provider::get_payable().
     * For payment area wallettopup, which topping up the wallet after payment.
     *
     * @covers ::get_payable()
     */
    public function test_get_payable_wallettopup() {
        global $DB;
        $this->resetAfterTest();
        if (class_exists('\core_payment\helper')) {
            return;
        }
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $user = $generator->create_user();
        set_config('paymentaccount', $account->get('id'), 'enrol_wallet');
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
    public function test_get_success_url_walletenrol() {
        global $CFG, $DB;
        $this->resetAfterTest();
        if (class_exists('\core_payment\helper')) {
            return;
        }
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $walletplugin = enrol_get_plugin('wallet');
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $course = $generator->create_course();

        $data = [
            'courseid' => $course->id,
            'customint1' => $account->get('id'),
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => $studentrole->id,
        ];
        $id = $walletplugin->add_instance($course, $data);

        $successurl = service_provider::get_success_url('walletenrol', $id);
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
    public function test_get_success_url_wallettopup() {
        global $CFG, $DB;
        $this->resetAfterTest();
        if (class_exists('\core_payment\helper')) {
            return;
        }
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $user = $generator->create_user();

        set_config('paymentaccount', $account->get('id'), 'enrol_wallet');
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
     */
    public function test_deliver_order_walletenrol() {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        if (class_exists('\core_payment\helper')) {
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

        $data = [
            'courseid' => $course->id,
            'customint1' => $account->get('id'),
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => $studentrole->id,
        ];
        $id = $walletplugin->add_instance($course, $data);

        $paymentid = $generator->get_plugin_generator('core_payment')->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 10,
            'userid' => $user->id
        ]);

        service_provider::deliver_order('walletenrol', $id, $paymentid, $user->id);
        $this->assertTrue(is_enrolled($context, $user));
        $this->assertTrue(user_has_role_assignment($user->id, $studentrole->id, $context->id));
    }

    /**
     * Test for service_provider::deliver_order().
     * For payment area wallettopup, which topping up the wallet after payment.
     *
     * @covers ::deliver_order()
     */
    public function test_deliver_order_wallettopup() {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        if (class_exists('\core_payment\helper')) {
            return;
        }
        $this->assertTrue(enrol_is_enabled('wallet'));

        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'paypal']);
        $user = $generator->create_user();

        set_config('paymentaccount', $account->get('id'), 'enrol_wallet');
        // Set a fake item form payment.
        $id = $DB->insert_record('enrol_wallet_items', ['cost' => 250, 'currency' => 'USD', 'userid' => $user->id]);

        $paymentid = $generator->get_plugin_generator('core_payment')->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 250,
            'userid' => $user->id
        ]);

        service_provider::deliver_order('wallettopup', $id, $paymentid, $user->id);
        $balance = \enrol_wallet\transactions::get_user_balance($user->id);

        $this->assertEquals(250, $balance);
    }

}
