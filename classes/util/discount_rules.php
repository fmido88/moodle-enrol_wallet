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
 * Helper.
 *
 * @package   enrol_wallet
 * @copyright 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\util;

use core_course_category;

/**
 * Helper class for wallet enrolment discount rules
 * @package enrol_wallet
 */
class discount_rules {
    /**
     * Get all valid discount rules for the specified category
     * If no category passed in constructor, it returns the rules in site level
     * @param int $catid the course category id.
     * @return array[object]
     */
    public static function get_current_discount_rules($catid = 0) {
        global $DB;
        $now = time();
        $params = [
            'time1' => $now,
            'time2' => $now,
        ];
        $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        if (!empty($catid)) {
            $all = core_course_category::get($catid)->get_parents();
            $all[] = $catid;
            list($catselect, $catparams) = $DB->get_in_or_equal($all, SQL_PARAMS_NAMED);
            $select .= $catselect;
            $params += $catparams;
        } else {
            $select .= ' AND (IS NULL category OR category = 0)';
        }
        return $DB->get_records_select('enrol_wallet_cond_discount', $select, $params);
    }

    /**
     * Get all categories with a discount rules
     *
     * @param bool $current if set true it will return the valid rules only in this time.
     * @return array[int] of categories id, 0 for site level.
     */
    public static function get_all_categories_with_discounts($current = true) {
        global $DB;
        $select = '';
        $params = [];
        if ($current) {
            $now = time();
            $params = [
                'time1' => $now,
                'time2' => $now,
            ];
            $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        }
        $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params, 'category ASC', 'id, category');
        $all = [];
        foreach ($records as $record) {
            if (empty($record->category)) {
                $record->category = 0;
            }
            $all[$record->category] = $record->category;
        }
        return $all;
    }

    /**
     * Get the rest of the amount that should be added by conditional discount rules
     *
     * @param float $amount
     * @param int $catid the id of the category, 0 means site level.
     * @return array with two elements the rest of the amout and the condition applied.
     */
    public static function get_the_rest($amount, $catid = 0) {
        global $DB;
        $enabled = get_config('enrol_wallet', 'conditionaldiscount_apply');
        $percentdiscount = 0;
        if (!empty($enabled)) {
            $params = [
                'time1' => time(),
                'time2' => time(),
            ];
            $select = '(timefrom <= :time1 OR timefrom = 0 ) AND (timeto >= :time2 OR timeto = 0)';
            if (!empty($catid)) {
                $select .= " AND category = :catid";
                $params['catid'] = $catid;
            } else {
                $select .= " AND (category IS NULL OR category = 0)";
            }
            $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params);

            foreach ($records as $record) {
                if ($record->percent >= 100) {
                    continue;
                }
                $beforediscount = $amount + ($amount * $record->percent / (100 - $record->percent));
                if ($beforediscount >= $record->cond && $record->percent > $percentdiscount) {

                    $percentdiscount = $record->percent;
                    $condition = $record->cond;
                }
            }
        }

        // If there is a value for discount.
        if (empty($percentdiscount)) {
            return [null, null];
        }

        // Discount more than 100 is not acceptable.
        $percentdiscount = min(100, $percentdiscount);
        $discount = $percentdiscount / 100;

        // The rest of the amount after subtract the part the user paid.
        $rest = $amount * $discount / (1 - $discount);
        return [$rest, $condition];
    }
}
