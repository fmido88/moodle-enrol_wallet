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
 * Config class tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet;

use enrol_wallet\local\config;

/**
 * Config class tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class config_test extends \advanced_testcase {

    /**
     * Test singleton pattern.
     * @covers ::make()
     * @covers ::instance()
     */
    public function test_singleton(): void {
        $this->resetAfterTest();

        $config1 = config::make();
        $config2 = config::instance();

        $this->assertSame($config1, $config2);
    }

    /**
     * Test __get method.
     * @covers ::__get()
     */
    public function test_get(): void {
        $this->resetAfterTest();

        $config = config::make();

        // Test getting a config value.
        $value = $config->walletsource;
        $this->assertNotNull($value);
    }

    /**
     * Test __set method.
     * @covers ::__set()
     */
    public function test_set(): void {
        $this->resetAfterTest();

        $config = config::make();

        // Set a config value.
        $config->cashback = 1;
        $this->assertEquals(1, $config->cashback);

        // Set another value.
        $config->cashbackpercent = 10;
        $this->assertEquals(10, $config->cashbackpercent);
    }

    /**
     * Test static get method.
     * @covers ::get()
     */
    public function test_static_get(): void {
        $this->resetAfterTest();

        $value = config::get('walletsource');
        $this->assertNotNull($value);
    }

    /**
     * Test static set method.
     * @covers ::set()
     */
    public function test_static_set(): void {
        $this->resetAfterTest();

        config::set('cashback', 1);
        $this->assertEquals(1, config::get('cashback'));
    }

    /**
     * Test __invoke method.
     * @covers ::__invoke()
     */
    public function test_invoke(): void {
        $this->resetAfterTest();

        $config = config::make();

        // Get value using invoke.
        $value = $config('walletsource');
        $this->assertNotNull($value);

        // Set value using invoke.
        $config('cashback', 1);
        $this->assertEquals(1, $config('cashback'));
    }

    /**
     * Test __isset method.
     * @covers ::__isset()
     */
    public function test_isset(): void {
        $this->resetAfterTest();

        $config = config::make();

        // Check if config value is set.
        $this->assertTrue(isset($config->walletsource));
        $this->assertFalse(isset($config->randomconfig));
    }

    /**
     * Test __unset method.
     * @covers ::__unset()
     */
    public function test_unset(): void {
        $this->resetAfterTest();

        $config = config::make();

        // Set a value first.
        $config->testvalue = 'test';

        $this->assertEquals('test', $config->testvalue);
        // Unset it.
        unset($config->testvalue);

        // Value should be null after unset.
        $this->assertNull($config->testvalue);
    }
}
