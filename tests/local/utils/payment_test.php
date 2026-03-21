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
 * Tests for payment utility class.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\utils;

/**
 * Tests for payment utility class.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class payment_test extends \advanced_testcase {
    /**
     * Test get_payment_button_attributes method.
     * @covers ::get_payment_button_attributes()
     */
    public function test_get_payment_button_attributes(): void {
        $this->resetAfterTest();

        $attributes = payment::get_payment_button_attributes(
            itemid: 1,
            cost: 100.00,
            description: 'Test payment',
            paymentarea: 'wallettopup',
            classes: 'btn-primary'
        );

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('class', $attributes);
        $this->assertArrayHasKey('type', $attributes);
        $this->assertArrayHasKey('id', $attributes);
        $this->assertArrayHasKey('data-action', $attributes);
        $this->assertArrayHasKey('data-component', $attributes);
        $this->assertArrayHasKey('data-paymentarea', $attributes);
        $this->assertArrayHasKey('data-itemid', $attributes);
        $this->assertArrayHasKey('data-cost', $attributes);
        $this->assertArrayHasKey('data-successurl', $attributes);
        $this->assertArrayHasKey('data-description', $attributes);
    }

    /**
     * Test init_payment_js method.
     * @covers ::init_payment_js()
     */
    public function test_init_payment_js(): void {
        $this->resetAfterTest();

        // This should not throw any errors.
        payment::init_payment_js();

        $this->assertTrue(true); // If we get here, no exception was thrown.
    }

    /**
     * Test is_valid_account method with invalid account.
     * @covers ::is_valid_account()
     */
    public function test_is_valid_account_invalid(): void {
        $this->resetAfterTest();

        // Test with non-existent account.
        $result = payment::is_valid_account(99999);
        $this->assertFalse($result);

        // Test with negative id.
        $result = payment::is_valid_account(-1);
        $this->assertFalse($result);

        // Test with zero.
        $result = payment::is_valid_account(0);
        $this->assertFalse($result);
    }

    /**
     * Test is_valid_currency method.
     * @covers ::is_valid_currency()
     */
    public function test_is_valid_currency(): void {
        $this->resetAfterTest();

        // Test with valid currency.
        $result = payment::is_valid_currency('USD');
        $this->assertIsBool($result);

        // Test with invalid currency (too short).
        $result = payment::is_valid_currency('US');
        $this->assertFalse($result);

        // Test with invalid currency (too long).
        $result = payment::is_valid_currency('USDD');
        $this->assertFalse($result);

        // Test with empty currency.
        $result = payment::is_valid_currency('');
        $this->assertFalse($result);
    }

    /**
     * Test is_topup_available method.
     * @covers ::is_topup_available()
     */
    public function test_is_topup_available(): void {
        $this->resetAfterTest();

        $result = payment::is_topup_available();
        $this->assertIsBool($result);
    }
}
