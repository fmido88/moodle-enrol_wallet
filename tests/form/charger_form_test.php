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
 * Charger form tests with actual form submission and validation.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

use enrol_wallet\local\config;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/enrol/wallet/lib.php');

// This file is made with AI.
// Todo: Merge these separated tests on minimal tests as possible with
// more testing scenarios.

/**
 * Charger form tests with actual form submission and validation.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class charger_form_test extends \advanced_testcase {
    /**
     * Test form definition creates valid form structure.
     * @covers ::definition()
     */
    public function test_form_definition(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the form - just verify it can be instantiated without errors.
        $form = new charger_form();

        // If we get here, the form was defined successfully.
        $this->assertInstanceOf(charger_form::class, $form);
    }

    /**
     * Test form validation with valid credit data.
     * @covers ::validation()
     */
    public function test_validation_valid_credit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user to charge.
        $user = $this->getDataGenerator()->create_user();

        // Create the form.
        $form = new charger_form();

        // Mock form submission data for credit operation.
        $data = [
            'op'       => 'credit',
            'value'    => 100,
            'userlist' => $user->id,
            'reason'   => 'Test credit',
            'category' => 0,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have no errors.
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation with valid debit data.
     * @covers ::validation()
     */
    public function test_validation_valid_debit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user with balance.
        $user = $this->getDataGenerator()->create_user();
        $op   = new balance_op($user->id);
        $op->credit(200);

        // Create the form.
        $form = new charger_form();

        // Mock form submission data for debit operation.
        $data = [
            'op'       => 'debit',
            'value'    => 50,
            'userlist' => $user->id,
            'reason'   => 'Test debit',
            'category' => 0,
            'neg'      => false,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have no errors (user has enough balance).
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation fails when debit exceeds balance.
     * @covers ::validation()
     */
    public function test_validation_debit_exceeds_balance(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user with low balance.
        $user = $this->getDataGenerator()->create_user();
        $op   = new balance_op($user->id);
        $op->credit(50);

        // Create the form.
        $form = new charger_form();

        // Mock form submission data for debit operation exceeding balance.
        $data = [
            'op'       => 'debit',
            'value'    => 100,
            'userlist' => $user->id,
            'reason'   => 'Test debit too much',
            'category' => 0,
            'neg'      => false,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have error for value field.
        $this->assertArrayHasKey('value', $errors);
        $this->assertStringContainsString('balance', strtolower($errors['value']));
    }

    /**
     * Test form validation allows negative debit with neg flag.
     * @covers ::validation()
     */
    public function test_validation_debit_with_negative_allowed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user with low balance.
        $user = $this->getDataGenerator()->create_user();
        $op   = new balance_op($user->id);
        $op->credit(50);

        // Create the form.
        $form = new charger_form();

        // Mock form submission data for debit operation with neg flag.
        $data = [
            'op'       => 'debit',
            'value'    => 100,
            'userlist' => $user->id,
            'reason'   => 'Test debit with negative',
            'category' => 0,
            'neg'      => true,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have no errors because neg flag is set.
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation fails with missing user.
     * @covers ::validation()
     */
    public function test_validation_missing_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the form.
        $form = new charger_form();

        // Mock form submission data without user.
        $data = [
            'op'       => 'credit',
            'value'    => 100,
            'userlist' => '', // No user selected.
            'reason'   => 'Test credit',
            'category' => 0,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have error for userlist field.
        $this->assertArrayHasKey('userlist', $errors);
    }

    /**
     * Test form validation fails with invalid user.
     * @covers ::validation()
     */
    public function test_validation_invalid_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the form.
        $form = new charger_form();

        // Mock form submission data with non-existent user.
        $data = [
            'op'       => 'credit',
            'value'    => 100,
            'userlist' => 999999, // Non-existent user.
            'reason'   => 'Test credit',
            'category' => 0,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have error for userlist field.
        $this->assertArrayHasKey('userlist', $errors);
    }

    /**
     * Test form validation fails with missing value.
     * @covers ::validation()
     */
    public function test_validation_missing_value(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the form.
        $form = new charger_form();

        // Mock form submission data without value.
        $data = [
            'op'       => 'credit',
            'value'    => '', // No value.
            'userlist' => $user->id,
            'reason'   => 'Test credit',
            'category' => 0,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have error for value field.
        $this->assertArrayHasKey('value', $errors);
    }

    /**
     * Test form validation fails with invalid operation.
     * @covers ::validation()
     */
    public function test_validation_invalid_operation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the form.
        $form = new charger_form();

        // Mock form submission data with invalid operation.
        $data = [
            'op'       => 'invalid_op',
            'value'    => 100,
            'userlist' => $user->id,
            'reason'   => 'Test',
            'category' => 0,
            'submit'   => 'Submit',
        ];

        // Validate the data.
        $errors = $form->validation($data, []);

        // Should have error for op field.
        $this->assertArrayHasKey('op', $errors);
    }

    /**
     * Test form processing for credit operation.
     * @covers ::process_form_submission()
     */
    public function test_process_credit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the form.
        $form = new charger_form();

        // Set up form data for credit.
        $data = (object)[
            'op'       => 'credit',
            'value'    => 100,
            'userlist' => $user->id,
            'reason'   => 'Test credit operation',
            'category' => 0,
        ];

        // Process the form submission.
        $result = $form->process_form_submission($data);

        // Check that balance was updated.
        $balance = new balance($user->id);
        $this->assertEquals(100, $balance->get_total_balance());

        // Result should be true (notification displayed).
        $this->assertTrue($result);
    }

    /**
     * Test form processing for debit operation.
     * @covers ::process_form_submission()
     */
    public function test_process_debit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user with balance.
        $user = $this->getDataGenerator()->create_user();
        $op   = new balance_op($user->id);
        $op->credit(200);

        // Create the form.
        $form = new charger_form();

        // Set up form data for debit.
        $data = (object)[
            'op'       => 'debit',
            'value'    => 50,
            'userlist' => $user->id,
            'reason'   => 'Test debit operation',
            'category' => 0,
            'neg'      => false,
        ];

        // Process the form submission.
        $result = $form->process_form_submission($data);

        // Check that balance was updated.
        $balance = new balance($user->id);
        $this->assertEquals(150, $balance->get_total_balance());

        // Result should be true (notification displayed).
        $this->assertTrue($result);
    }

    /**
     * Test form processing for reset operation.
     * @covers ::process_form_submission()
     */
    public function test_process_reset(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user with balance.
        $user = $this->getDataGenerator()->create_user();
        $op   = new balance_op($user->id);
        $op->credit(200);

        // Verify initial balance.
        $balance = new balance($user->id);
        $this->assertEquals(200, $balance->get_total_balance());

        // Create the form.
        $form = new charger_form();

        // Set up form data for reset.
        $data = (object)[
            'op'       => 'reset',
            'value'    => '', // Value not needed for reset.
            'userlist' => $user->id,
            'reason'   => 'Test reset operation',
            'category' => 0,
        ];

        // Process the form submission.
        $result = $form->process_form_submission($data);

        // Check that balance was reset to 0.
        $balance = new balance($user->id);
        $this->assertEquals(0, $balance->get_total_balance());

        // Result should be true (notification displayed).
        $this->assertTrue($result);
    }

    /**
     * Test form processing with category balance.
     * @covers ::process_form_submission()
     */
    public function test_process_credit_with_category(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Enable category balance.
        config::make()->catbalance = 1;

        // Create a category.
        $category = $this->getDataGenerator()->create_category();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the form.
        $form = new charger_form();

        // Set up form data for credit with category.
        $data = (object)[
            'op'       => 'credit',
            'value'    => 100,
            'userlist' => $user->id,
            'reason'   => 'Test category credit',
            'category' => $category->id,
        ];

        // Process the form submission.
        $result = $form->process_form_submission($data);

        // Check that category balance was updated.
        $balance = new balance($user->id, $category->id);
        $this->assertEquals(100, $balance->get_valid_balance());

        // Result should be true.
        $this->assertTrue($result);
    }

    /**
     * Test notify_result method.
     * @covers ::notify_result()
     */
    public function test_notify_result(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // Create the form.
        $form = new charger_form();

        // Test successful result notification.
        $params = [
            'before' => 0,
            'after'  => 100,
            'userid' => $user->id,
            'op'     => 'result',
        ];

        $result = $form->notify_result($params);
        $this->assertTrue($result);

        // Test error result notification.
        $params = [
            'err' => 'Test error message',
        ];

        $result = $form->notify_result($params);
        $this->assertTrue($result);
    }

    /**
     * Test form processing returns null with empty data.
     * @covers ::process_form_submission()
     */
    public function test_process_empty_data(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the form.
        $form = new charger_form();

        // Process with null data.
        $result = $form->process_form_submission(null);

        // Should return null.
        $this->assertNull($result);
    }

    /**
     * Test form processing returns false for result operation.
     * @covers ::process_form_submission()
     */
    public function test_process_result_operation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the form.
        $form = new charger_form();

        // Set up form data with op = result.
        $formdata = (object)[
            'op' => 'result',
        ];

        // Process the form submission.
        $result = $form->process_form_submission($formdata);

        // Should return false.
        $this->assertFalse($result);
    }
}
