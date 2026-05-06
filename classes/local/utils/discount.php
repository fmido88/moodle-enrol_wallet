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

use core\exception\invalid_parameter_exception;
use core_collator;

/**
 * Class discount
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait discount {
    /**
     * Calculate the cost after discount sequentially.
     * @var int
     */
    public const B_SEQ = 1;

    /**
     * Apply the sum of discounts.
     * @var int
     */
    public const B_SUM = 2;

    /**
     * Apply max discount.
     * @var int
     */
    public const B_MAX = 0;
    /**
     * sequentially calculate discount.
     * @param  array $discounts
     * @param  bool  $percentage
     * @return float
     */
    final protected static function calculate_sequential_discount(array $discounts, bool $percentage = false): float {
        self::check_float_array($discounts);

        core_collator::asort($discounts, core_collator::SORT_NUMERIC);
        $discounts = array_reverse($discounts);

        $discount = 0;

        foreach ($discounts as $d) {
            $d /= 100;
            $discount = 1 - (1 - $discount) * (1 - $d);
        }
        $discount = max(min(1, $discount), 0);

        if ($percentage) {
            return $discount * 100;
        }

        return $discount;
    }

    /**
     * Calculate the sum of the discounts.
     * @param float[] $discounts
     * @param bool $percentage
     * @return float
     */
    final protected static function calculate_sum_discount(array $discounts, bool $percentage = false): float {
        self::check_float_array($discounts);

        $discount = array_sum(array_values($discounts));
        $max = 100;
        if (!$percentage) {
            $discount /= 100;
            $max = 1;
        }
        return max(min($discount, $max), 0);
    }

    /**
     * Get the max of given discounts.
     * @param float[] $discounts
     * @param bool $percentage
     * @return float
     */
    final protected static function calculate_max_discount(array $discounts, bool $percentage = false): float {
        if (\count($discounts) === 0) {
            return 0;
        }
        self::check_float_array($discounts);
        $discount = max($discounts);
        $max = 100;
        if (!$percentage) {
            $discount /= 100;
            $max = 1;
        }
        return max(min($discount, $max), 0);
    }

    /**
     * Confirm that all values in an array is of type float.
     * @param array $array
     * @throws invalid_parameter_exception
     * @return void
     */
    private static function check_float_array(array &$array): void {
        foreach ($array as &$value) {
            $value = validate_param($value, PARAM_FLOAT);
        }
    }
}
