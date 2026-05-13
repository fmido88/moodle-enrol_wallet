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

use core\exception\coding_exception;
use core\exception\invalid_parameter_exception;
use core_collator;
use core_text;

/**
 * Calculate the cost after discount sequentially.
 * @var int
 */
const ENROL_WALLET_DISCOUNT_SEQ = 1;

/**
 * Apply the sum of discounts.
 * @var int
 */
const ENROL_WALLET_DISCOUNT_SUM = 2;

/**
 * Apply max discount.
 * @var int
 */
const ENROL_WALLET_DISCOUNT_MAX = 0;

/**
 * Discount trait to calculate discounts.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait discount {
    /**
     * Get the calculation discount constant by it's shortname.
     * @param  string $type
     * @return ?int
     */
    final public static function const(string $type): ?int {
        return match (core_text::strtolower($type)) {
            'seq', 'b_seq', 'sequential' => ENROL_WALLET_DISCOUNT_SEQ,
            'max', 'b_max', 'maximum'    => ENROL_WALLET_DISCOUNT_MAX,
            'sum', 'b_sum', 'summation'  => ENROL_WALLET_DISCOUNT_SUM,
            default => throw new coding_exception("Non-recognized constant $type"),
        };
    }

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
     * @param  float[] $discounts
     * @param  bool    $percentage
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
     * @param  float[] $discounts
     * @param  bool    $percentage
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
     * @param  array                       $array
     * @throws invalid_parameter_exception
     * @return void
     */
    private static function check_float_array(array &$array): void {
        foreach ($array as &$value) {
            $value = validate_param($value, PARAM_FLOAT);
        }
    }
}
