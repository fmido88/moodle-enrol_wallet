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
            list($catin, $catparams) = $DB->get_in_or_equal($all, SQL_PARAMS_NAMED);
            $select .= " AND category $catin";
            $params += $catparams;
        } else {
            $select .= ' AND (category IS NULL OR category = 0)';
        }
        return $DB->get_records_select('enrol_wallet_cond_discount', $select, $params, 'cond DESC, percent DESC');
    }
    /**
     * Get all site and categories discount rules.
     * @return array[\stdClass]
     */
    public static function get_all_available_discount_rules() {
        $enabled = (bool)get_config('enrol_wallet', 'conditionaldiscount_apply');
        if (!$enabled) {
            return [];
        }
        global $DB;
        $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        $params = ['time1' => time(), 'time2' => time()];

        return $DB->get_records_select('enrol_wallet_cond_discount', $select, $params, 'category ASC, cond DESC, percent DESC');
    }

    /**
     * Add hidden elements for calculating discounts to a form
     * @param \MoodleQuickForm $mform
     * @return int the number of rules
     */
    public static function add_discounts_to_form(\MoodleQuickForm $mform) {
        $records = self::get_all_available_discount_rules();

        $i = 0;
        foreach ($records as $record) {
            $i++;
            // This element only used to pass the values to js code.
            $discountrule = (object)[
                'discount'  => $record->percent / 100,
                'condition' => $record->cond,
                'category'  => $record->category ?? 0,
            ];
            $mform->addElement('hidden', 'discount_rule_'.$i);
            $mform->setType('discount_rule_'.$i, PARAM_TEXT);
            $mform->setConstant('discount_rule_'.$i, json_encode($discountrule));
        }
        return $i;
    }

    /**
     * Get all categories ids with a discount rules
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
     * @return array with two elements the rest of the amount and the condition applied.
     */
    public static function get_the_rest($amount, $catid = 0) {
        global $DB;
        $enabled = get_config('enrol_wallet', 'conditionaldiscount_apply');
        $percentdiscount = 0;
        if (!empty($enabled)) {
            $records = self::get_current_discount_rules($catid);

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

    /**
     * Get the value before applying the conditional discount.
     * (i.e. the min value)
     * @param float $amount
     * @param int $catid
     * @param float $discount
     * @return float
     */
    public static function get_the_before($amount, $catid = 0, $discount = 0) {
        if (empty($discount)) {
            $discount = self::get_applied_discount($amount, $catid);
        }
        return (float)($amount * (1 - $discount / 100));
    }

    /**
     * Get the value that will be added to the wallet balance after applying the discount
     * rule.
     * (i.e. the max value)
     * @param float $amount
     * @param int $catid
     * @param float $discount
     */
    public static function get_the_after($amount, $catid = 0, $discount = 0) {
        if (empty($discount)) {
            $discount = self::get_applied_discount($amount, $catid);
        }
        return (float)($amount / (1 - $discount / 100));
    }

    /**
     * Get the discount that could be applied for a given amount and category.
     * @param float $amount
     * @param int $catid
     * @return float
     */
    private static function get_applied_discount($amount, $catid) {
        $discount = 0;
        $records = self::get_current_discount_rules($catid);
        foreach ($records as $record) {
            if ($amount >= $record->cond && $record->percent > $discount) {
                $discount = $record->percent;
            }
        }
        return $discount;
    }
    /**
     * Render the discount rules as a form of line divided by the rules.
     * @param int $catid -1 for all rules, 0 site rules only or category id for the rules
     * in specific category
     * @return string
     */
    public static function get_the_discount_line($catid = 0) {
        $enabled = (bool)get_config('enrol_wallet', 'conditionaldiscount_apply');
        if (!$enabled) {
            return '';
        }

        if ($catid < 0) {
            $records = self::get_all_available_discount_rules();
        } else {
            $records = self::get_current_discount_rules($catid);
        }

        if (empty($records)) {
            return '';
        }

        global $OUTPUT;

        $currency = get_config('enrol_wallet', 'currency');
        $data = new \stdClass;
        $data->data = [];
        $maxconditions = [];
        foreach ($records as $record) {
            if ($record->cond > ($maxconditions[$record->category ?? 0] ?? 0)) {
                $maxconditions[$record->category] = $record->cond;
            }
        }

        $discounts = [];
        $catid = -1;
        foreach ($records as $id => $record) {
            if ($catid != $record->category) {
                if (isset($data->data[$catid])) {
                    $data->data[$catid]->discounts = array_values($discounts);
                    $discounts = [];
                }
                $catid = $record->category;
                $data->data[$catid] = new \stdClass;
                $data->data[$catid]->catid = $catid;
                $data->data[$catid]->count = 0;

                if (empty($catid)) {
                    $name = get_string('site');
                } else {
                    $category = core_course_category::get($catid);
                    $name = $category->get_nested_name(false);
                }
                $data->data[$catid]->heading = $OUTPUT->heading($name, 4);
                $prevwidth = 0;
            }
            $maxcondition = $maxconditions[$catid] * 1.2;
            $data->data[$catid]->count++;
            $discounts[$id] = new \stdClass;
            $discounts[$id]->percent = (100 - ($record->cond / $maxcondition * 100)) - $prevwidth;
            $discounts[$id]->order = (int)round((float)$record->cond / $maxcondition * 10);
            $discounts[$id]->color = (int)round((1 - ((float)$record->cond / $maxcondition)) * 255);
            $discounts[$id]->condition = '> ' . format_float($record->cond, 2) . " $currency";
            $discounts[$id]->discount = format_float($record->percent, 2) . '%';
            $prevwidth = $discounts[$id]->percent;
        }
        $data->data[$catid]->discounts = array_values($discounts);
        $data->data = array_values($data->data);
        return $OUTPUT->render_from_template('enrol_wallet/discount-line', $data);
    }

    /**
     * Get the conditional discount records with bundles.
     * @return array
     */
    private static function get_bundles_records() {
        global $DB;$now = time();
        $params = [
            'time1' => $now,
            'time2' => $now,
        ];
        $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        $select .= ' AND bundle IS NOT NULL';
        return $DB->get_records_select('enrol_wallet_cond_discount', $select, $params);
    }

    /**
     * Return a single bundle top up button.
     * @param \stdClass $record
     * @return string
     */
    private static function format_single_bundle_button($record) {
        global $OUTPUT;

        $before = $record->bundle;
        $discount = self::get_applied_discount($before, $record->category ?? 0);
        $after = self::get_the_before($before, $record->category ?? 0, $discount);
        $desc = !empty($record->bundledesc) ? format_text($record->bundledesc, $record->descformat) : '';
        $data = new \stdClass;
        $data->category = $record->category ?? 0;
        $data->value = $before;
        $data->instanceid = 0;
        $data->courseid = SITEID;
        $data->account = get_config('enrol_wallet', 'account');
        $data->currency = get_config('enrol_wallet', 'currency');
        $topupurl = new \moodle_url('/enrol/wallet/extra/topup.php', (array)$data);

        $context = [
            'discount'    => $discount,
            'after'       => $after,
            'before'      => $before,
            'description' => $desc,
            'currency'    => $data->currency,
        ];
        if (empty($data->category)) {
            $context['category'] = get_string('site');
        } else {
            $category = core_course_category::get($data->category, IGNORE_MISSING);
            if (!$category) {
                return '';
            }
            $context['category'] = $category->get_nested_name();
        }
        $button = new \single_button($topupurl, '');
        $buttoncontext = (array)$button->export_for_template($OUTPUT);
        $context = $context + $buttoncontext;
        return $OUTPUT->render_from_template('enrol_wallet/single-bundle', $context);
    }

    /**
     * Render all bundle buttons available for topping up.
     * @return string
     */
    public static function bundles_buttons() {
        $out = '';
        $records = self::get_bundles_records();
        if (empty($records)) {
            return $out;
        }
        $out .= \html_writer::start_div('enrol-wallet-bundles');
        foreach ($records as $record) {
            $out .= self::format_single_bundle_button($record);
        }
        $out .= \html_writer::end_div();
        return $out;
    }
}
