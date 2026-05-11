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
 * Tests for discount utility class.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\utils;

defined('MOODLE_INTERNAL') || die();

use core\exception\invalid_parameter_exception;

/**
 * Tests for discount utility functions.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class discount_test extends \advanced_testcase {
    /**
     * Test calculate_max_discount returns the correct maximum discount.
     */
    public function test_calculate_max_discount(): void {
        $this->resetAfterTest();

        $this->assertSame(0.5, discount_test_helper::calculate_max_discount_public([50, 30]));
        $this->assertSame(100.0, discount_test_helper::calculate_max_discount_public([50, 150], true));
        $this->assertSame(1.0, discount_test_helper::calculate_max_discount_public([120], false));
    }

    /**
     * Test calculate_sum_discount returns the correct summed discount.
     */
    public function test_calculate_sum_discount(): void {
        $this->resetAfterTest();

        $this->assertSame(0.35, discount_test_helper::calculate_sum_discount_public([15, 20]));
        $this->assertSame(35.0, discount_test_helper::calculate_sum_discount_public([15, 20], true));
        $this->assertSame(100.0, discount_test_helper::calculate_sum_discount_public([60, 50], true));
    }

    /**
     * Test calculate_sequential_discount returns the correct sequential discount.
     */
    public function test_calculate_sequential_discount(): void {
        $this->resetAfterTest();

        $this->assertSame(0.75, discount_test_helper::calculate_sequential_discount_public([50, 50]));
        $this->assertSame(75.0, discount_test_helper::calculate_sequential_discount_public([50, 50], true));
        $this->assertSame(100.0, discount_test_helper::calculate_sequential_discount_public([150], true));
    }

    /**
     * Test invalid discount values throw an exception.
     */
    public function test_invalid_values_throw_exception(): void {
        $this->resetAfterTest();

        $this->expectException(invalid_parameter_exception::class);
        discount_test_helper::calculate_sum_discount_public([10, 'invalid']);
    }
}

/**
 * Helper exposing discount trait methods for tests.
 */
class discount_test_helper {
    use discount;

    /**
     * Calculate max discount.
     *
     * @param  array $discounts
     * @param  bool  $percentage
     * @return float
     */
    public static function calculate_max_discount_public(array $discounts, bool $percentage = false): float {
        return self::calculate_max_discount($discounts, $percentage);
    }

    /**
     * Calculate sum discount.
     *
     * @param  array $discounts
     * @param  bool  $percentage
     * @return float
     */
    public static function calculate_sum_discount_public(array $discounts, bool $percentage = false): float {
        return self::calculate_sum_discount($discounts, $percentage);
    }

    /**
     * Calculate sequential discount.
     *
     * @param  array $discounts
     * @param  bool  $percentage
     * @return float
     */
    public static function calculate_sequential_discount_public(array $discounts, bool $percentage = false): float {
        return self::calculate_sequential_discount($discounts, $percentage);
    }
}
