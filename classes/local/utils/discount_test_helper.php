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

namespace enrol_wallet\local\utils;

/**
 * Helper exposing discount trait methods for tests.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
