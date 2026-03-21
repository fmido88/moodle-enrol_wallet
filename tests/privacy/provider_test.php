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
 * Privacy provider tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\privacy;

use context_course;
use core_payment_generator;
use core_privacy\local\metadata\collection;
use enrol_wallet\local\config;
use enrol_wallet\local\utils\testing;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet\payment\item;
use enrol_wallet\privacy\provider;

/**
 * Privacy provider tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * Test get_metadata returns correct collection.
     * @covers ::get_metadata()
     */
    public function test_get_metadata(): void {
        $collection = new collection('enrol_wallet');
        $result = provider::get_metadata($collection);

        $this->assertInstanceOf(collection::class, $result);

        // Check that the collection has items.
        $items = $result->get_collection();
        $this->assertNotEmpty($items);
    }

    /**
     * Test export_user_data exports data correctly for wallet top-up.
     * @covers ::export_user_data()
     */
    public function test_export_user_data_wallet_topup(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();

        // Add wallet balance (top-up).
        $op = new balance_op($user->id);
        $item = item::create_item(100, 'USD', $user->id);
        $op->credit(100, $op::C_PAYMENT, $item->id, 'Wallet top-up');

        // Call export to ensure no errors.
        $contextlist = new \core_privacy\local\request\approved_contextlist($user, 'enrol_wallet', [$context->id]);
        provider::export_user_data($contextlist);

        $this->assertTrue(true); // If we get here, no exception was thrown.
    }

    /**
     * Test get_contextid_for_payment for wallet enrol payment.
     * @covers ::get_contextid_for_payment()
     */
    public function test_get_contextid_for_payment_enrol(): void {
        $this->resetAfterTest();

        global $DB;
        $course = $this->getDataGenerator()->create_course();

        $user = $this->getDataGenerator()->create_user();

        $instance = testing::get_generator()->create_instance($course->id, false, 100);

        $item = item::create_item(50, 'USD', $user->id, $instance->id);

        $contextid = provider::get_contextid_for_payment('walletenrol', $item->id);

        $this->assertNotNull($contextid);
        $this->assertIsInt($contextid);
        $this->assertEquals($contextid, (context_course::instance($course->id))->id);
    }

    /**
     * Test get_contextid_for_payment for wallet top-up.
     * @covers ::get_contextid_for_payment()
     */
    public function test_get_contextid_for_payment_topup(): void {
        $this->resetAfterTest();

        $contextid = provider::get_contextid_for_payment('wallettopup', 0);

        // Should return system context id.
        $this->assertEquals(\context_system::instance()->id, $contextid);
    }

    /**
     * Test get_users_in_context for system context.
     * @covers ::get_users_in_context()
     */
    public function test_get_users_in_context_system(): void {
        $this->resetAfterTest();

        global $DB;
        $user = $this->getDataGenerator()->create_user();

        $generator = testing::get_core_payment_generator();
        $account = $generator->create_payment_account();
        // Create wallet top-up payment (orphaned).
        $generator->create_payment([
            'accountid'   => $account->get('id'),
            'component'   => 'enrol_wallet',
            'paymentarea' => 'wallettopup',
            'itemid'      => 888888,
            'userid'      => $user->id,
            'amount'      => 100,
            'currency'    => 'USD',
            'timecreated' => time(),
        ]);

        $context = \context_system::instance();

        $userlist = new \core_privacy\local\request\userlist($context, 'enrol_wallet');
        provider::get_users_in_context($userlist);

        $this->assertInstanceOf(\core_privacy\local\request\userlist::class, $userlist);
        $count = $userlist->count();

        $this->assertEquals($count, 1);
    }

    /**
     * Test delete_data_for_all_users_in_context for system context.
     * @covers ::delete_data_for_all_users_in_context()
     */
    public function test_delete_data_for_all_users_in_context_system(): void {
        $this->resetAfterTest();

        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $generator = testing::get_core_payment_generator();
        $account = $generator->create_payment_account();
        $item = item::mock_item();
        // Create wallet top-up payment.
        $paymentid = $generator->create_payment([
            'component'   => 'enrol_wallet',
            'paymentarea' => 'wallettopup',
            'accountid'   => $account->get('id'),
            'itemid'      => $item->id,
            'userid'      => $item->userid,
            'amount'      => $item->cost,
            'currency'    => $item->currency,
            'timecreated' => time(),
        ]);

        $paycount = $DB->count_records('payments');
        $itemcount = $DB->count_records('enrol_wallet_items');
        $this->assertEquals(1, $paycount);
        $this->assertEquals(1, $itemcount);

        $context = \context_system::instance();

        // Should not throw any errors.
        provider::delete_data_for_all_users_in_context($context);

        $paycount = $DB->count_records('payments');
        $itemcount = $DB->count_records('enrol_wallet_items');
        $this->assertEquals(0, $paycount);
        $this->assertEquals(0, $itemcount);
    }

    /**
     * Test delete_data_for_user for system context with wallet top-up.
     * @covers ::delete_data_for_user()
     */
    public function test_delete_data_for_user_system(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $item = item::mock_item(['userid' => $user->id]);
        // Create wallet balance.
        $op = new balance_op($user->id);
        $op->credit($item->cost, $op::C_PAYMENT, $item->id, 'Wallet top-up');

        $context = \context_system::instance();

        // Should not throw any errors.
        $contextlist = new \core_privacy\local\request\approved_contextlist($user, 'enrol_wallet', [$context->id]);

        $count = $contextlist->count();
        $this->assertEquals(1, $count);

        $this->assertEquals(1, $DB->count_records('enrol_wallet_items'));

        provider::delete_data_for_user($contextlist);

        $this->assertEquals(0, $DB->count_records('enrol_wallet_items'));
        $this->assertTrue(true); // If we get here, no exception was thrown.
    }

    /**
     * Test delete_data_for_users for system context with wallet top-ups.
     * @covers ::delete_data_for_users()
     */
    public function test_delete_data_for_users_system(): void {
        $this->resetAfterTest();

        global $DB;
        $user = $this->getDataGenerator()->create_user();

        $item = item::mock_item(['userid' => $user->id]);
        $pgenerator = testing::get_core_payment_generator();
        $account = $pgenerator->create_payment_account();
        $paymentid = $pgenerator->create_payment([
            'component'   => 'enrol_wallet',
            'paymentarea' => 'wallettopup',
            'accountid'   => $account->get('id'),
            'itemid'      => $item->id,
            'userid'      => $user->id,
            'amount'      => $item->cost,
            'currency'    => $item->currency,
        ]);

        $this->assertEquals(1, $DB->count_records('enrol_wallet_items'));
        $this->assertEquals(1, $DB->count_records('payments'));

        $context = \context_system::instance();
        $userlist = new \core_privacy\local\request\approved_userlist($context, 'enrol_wallet', [$user->id]);

        // Should not throw any errors.
        provider::delete_data_for_users($userlist);

        $this->assertEquals(0, $DB->count_records('enrol_wallet_items'));
        $this->assertEquals(0, $DB->count_records('payments'));

        // Todo: Generate more case with multiple users ans make sure that the data deleted
        // for the required users only.
    }
}
