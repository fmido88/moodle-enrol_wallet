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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/enrol/wallet/lib.php");

use enrol_wallet_plugin as wallet;
use enrol_wallet\coupons;
use core_course_category;

/**
 * Helper class for wallet enrolment instance
 * @package enrol_wallet
 */
class instance {
    /**
     * Calculate the cost after discount sequentially
     * @var int
     */
    public const B_SEQ = 1;
    /**
     * Apply the sum of discounts
     * @var int
     */
    public const B_SUM = 2;
    /**
     * Apply max discount
     * @var int
     */
    public const B_MAX = 0;
    /**
     * The enrol wallet instance
     * @var \stdClass
     */
    public $instance;

    /**
     * The instance id
     * @var int
     */
    public $id;
    /**
     * The id of the course which the instance belong to.
     * @var int
     */
    public $courseid;

    /**
     * The cost after calculating discounts.
     * @var float $costafter
     */
    public $costafter;

    /**
     * The coupon helper class object
     * @var coupons
     */
    public $couponutil;
    /**
     * The id of the user we need to calculate the discount for.
     * @var int
     */
    public $userid;

    /**
     * The all discounts in this instance.
     * @var array
     */
    private $discounts = [0];
    /**
     * The behavior of discount calculation.
     * @var int
     */
    private $behavior;

    /**
     * Caching instances.
     * @var array
     */
    protected static $cached = [];
    /**
     * Create a new enrol wallet instance helper class.
     * store the cost after discount.
     *
     * @param int|\stdClass $instanceorid The enrol wallet instance or its id.
     * @param int $userid the id of the user, 0 means the current user.
     */
    public function __construct($instanceorid, $userid = 0) {
        global $USER;
        if (is_number($instanceorid)) {
            $this->instance = self::get_instance_by_id($instanceorid);
        } else {
            $this->instance = $instanceorid;
        }
        $this->id = $this->instance->id;
        $this->courseid = $this->instance->courseid;

        if (empty($userid)) {
            $this->userid = $USER->id;
        } else if (is_object($userid)) {
            $this->userid = $userid->id;
        } else {
            $this->userid = $userid;
        }

        $this->behavior = (int)get_config('enrol_wallet', 'discount_behavior');
        $this->calculate_cost_after_discount();
        $this->set_static_cache();
    }

    /**
     * Set static values to prevent recalculating the discounts
     * for multiple callings.
     * @return void
     */
    private function set_static_cache() {
        $cache = new \stdClass;
        $cache->costafter = $this->costafter;
        $cache->discounts = $this->discounts;
        self::$cached[$this->id . '-' . $this->userid] = $cache;
    }

    /**
     * Reset static values.
     * @return void
     */
    public static function reset_static_cache() {
        self::$cached = [];
    }
    /**
     * Get the enrol wallet instance by id.
     * @param int $instanceid
     * @return \stdClass|false
     */
    private static function get_instance_by_id($instanceid) {
        global $DB;
        $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $instanceid], '*', MUST_EXIST);

        return $instance;
    }

    /**
     * Get the enrol wallet instance object
     * @return \stdClass
     */
    public function get_instance() {
        return $this->instance;
    }

    /**
     * Get the course that the instance belongs to.
     * @return \stdClass
     */
    public function get_course() {
        return get_course($this->courseid);
    }

    /**
     * Get course category object at which the instance belongs to.
     * @return core_course_category
     */
    public function get_course_category() {
        $catid = $this->get_category_id();
        return core_course_category::get($catid, IGNORE_MISSING, true, $this->userid);
    }

    /**
     * Get course category object at which the instance belongs to.
     * @return int
     */
    public function get_category_id() {
        $catid = $this->get_course()->category;
        return $catid;
    }

    /**
     * Get instance name
     * @return string
     */
    public function get_name() {
        $wallet = new wallet;
        return $wallet->get_instance_name($this->instance);
    }

    /**
     * Calculate and return discount due to discount coupon.
     * @return float from 0 to 1
     */
    private function get_coupon_discount() {
        // Check if there is a discount coupon first.
        $coupon = coupons::check_discount_coupon();

        $discount = 0;

        if (!empty($coupon)) {
            $couponutil = new coupons($coupon, $this->userid);

            $validation = $couponutil->validate_coupon(coupons::AREA_ENROL, $this->instance->id);

            if (true === $validation) {
                $this->couponutil = $couponutil;
                if ($couponutil->type == coupons::DISCOUNT && $couponutil->valid) {
                    $discount = min($couponutil->value / 100, 1);
                }
            } else if (is_string($validation)) {
                static $warned = false;
                if (!$warned) {
                    $warned = true;
                    \core\notification::error($validation);
                }
            }
        }
        return $discount;
    }

    /**
     * Calculate and return discount due to repurchasing the course.
     * @return float from 0 to 1
     */
    private function get_repurchase_discount() {
        global $DB;
        $userid = $this->userid;
        $instanceid = $this->instance->id;
        $discount = 0;
        if ($ue = $DB->get_record('user_enrolments', ['enrolid' => $instanceid, 'userid' => $userid])) {
            if (!empty($ue->timeend) && get_config('enrol_wallet', 'repurchase')) {
                if ($first = get_config('enrol_wallet', 'repurchase_firstdis')) {
                    $discount = min($first / 100, 1);
                    $second = get_config('enrol_wallet', 'repurchase_seconddis');
                    $timepassed = $ue->timemodified > $ue->timecreated + $ue->timeend - $ue->timestart;
                    if ($second && $ue->modifierid == $userid && $timepassed) {
                        $discount = max($second / 100, $discount);
                    }
                }
            }
        }
        return min($discount, 1);
    }

    /**
     * Calculate and return the discount due to offers.
     * @return float from 0 to 1
     */
    private function get_offers_discount() {
        $offers = new offers($this->instance, $this->userid);
        $discount = 0;
        switch ($this->behavior) {
            case self::B_SUM:
                $discount = $offers->get_sum_discounts();
                break;
            case self::B_MAX:
                $discount = $offers->get_max_valid_discount();
                break;
            case self::B_SEQ;
            default:
                $discount = $this->calculate_sequential_discount($offers->get_available_discounts(), true);
        }
        return min(1, $discount / 100);
    }

    /**
     * Calculate and return the discount due to profile field.
     * @return float from 0 to 1
     */
    private function get_profile_field_discount() {
        global $DB;
        $discount = 0;
        // Check if the discount according to custom profile field in enabled.
        if (!$fieldid = get_config('enrol_wallet', 'discount_field')) {
            return $discount;
        }

        // Check the data in the discount field.
        $data = $DB->get_field('user_info_data', 'data', ['userid' => $this->userid, 'fieldid' => $fieldid]);

        if (empty($data)) {
            return $discount;
        }
        // If the user has free access to courses return 0 cost.
        if (stripos(strtolower($data), 'free') !== false) {
            $discount = 1;
            // If there is a word no in the data means no discount.
        } else if (stripos(strtolower($data), 'no') !== false) {
            $discount = 0;
        } else {
            // Get the integer from the data.
            preg_match('/\d+/', $data, $matches);
            if (isset($matches[0]) && intval($matches[0]) <= 100) {
                // Cannot allow discount more than 100%.
                $discount = intval($matches[0]) / 100;
            }
        }
        return min(1, $discount);
    }

    /**
     * Calculate, store and return all types of discounts.
     * @return array
     */
    private function calculate_discounts() {
        $this->discounts = [
            'coupons'    => $this->get_coupon_discount(),
            'profile'    => $this->get_profile_field_discount(),
            'repurchase' => $this->get_repurchase_discount(),
            'offers'     => $this->get_offers_discount(),
        ];
        return $this->discounts;
    }

    /**
     * Get percentage discount for a user from custom profile field and coupon code.
     * and then calculate the cost of the course after discount.
     * @return void
     */
    private function calculate_cost_after_discount() {
        $instance = $this->instance;
        $cost = $instance->cost;
        if (!is_numeric($cost) || $cost < 0) {
            $this->costafter = null;
            return;
        }

        $cost = (float)$cost;
        if ($cost == 0) {
            $this->costafter = $cost;
            return;
        }

        $cache = self::$cached[$this->id . '-' . $this->userid] ?? null;
        if ($cache) {
            $this->discounts = $cache->discounts;
            $this->costafter = $cache->costafter;
            return;
        }
        $discounts = $this->calculate_discounts();
        $discount = 0;

        if ($this->behavior === self::B_SUM) {
            foreach ($discounts as $d) {
                $discount += $d;
            }
        } else if ($this->behavior === self::B_MAX) {
            $discount = max($discounts);
        } else {
            $discount = $this->calculate_sequential_discount($discounts);
        }

        $discount = min(1, $discount);
        $this->costafter = $cost * (1 - $discount);
    }

    /**
     * sequentially calculate discount
     * @param array $discounts
     * @param bool $percentage
     * @return float
     */
    private function calculate_sequential_discount($discounts, $percentage = false) {
        \core_collator::asort($discounts, \core_collator::SORT_NUMERIC);
        $discounts = array_reverse($discounts);

        $discount = 0;
        foreach ($discounts as $d) {
            $d = $percentage ? $d / 100 : $d;
            $discount = 1 - (1 - $discount) * (1 - $d);
        }
        $discount = min(1, $discount);
        if ($percentage) {
            return $discount * 100;
        }
        return $discount;
    }
    /**
     * Get the cost of the enrol instance after discount.
     * @param bool $recalculate
     * @return float|null the cost after discount.
     */
    public function get_cost_after_discount($recalculate = false) {
        if ($recalculate) {
            self::reset_static_cache();
            $this->calculate_cost_after_discount();
        }

        if (!is_null($this->costafter) && is_numeric($this->costafter)) {
            return (float)$this->costafter;
        }

        return null;
    }

    /**
     * Check if there is a discount in this instance.
     * @return bool
     */
    public function has_discount() {
        if ($this->costafter < $this->instance->cost || $this->costafter === (float)0) {
            return true;
        }
        $costs = $this->get_all_costs();
        if ($this->costafter < max($costs)) {
            return true;
        }
        return false;
    }

    /**
     * get the discount in this instance in percentage
     * @return int from 0 to 100
     */
    public function get_rounded_discount() {
        if ($this->costafter === (float)0) {
            return 100;
        }

        $difference = $this->instance->cost - $this->costafter;
        if ($difference <= 0) {
            $costs = $this->get_all_costs();
            $difference = max($costs) - $this->costafter;
        }

        if ($difference > 0) {
            return (int)($difference / $this->instance->cost * 100);
        }
        return 0;
    }

    /**
     * Return all discounts in all instances.
     * @return array
     */
    public function get_all_discounts() {
        global $DB;
        $instances = $DB->get_records('enrol', ['courseid' => $this->courseid]);
        $discounts = [];
        foreach ($instances as $instance) {
            $helper = new self($instance);
            $discounts[] = $helper->get_rounded_discount();
        }
        return $discounts;
    }
    /**
     * Return an array of costs of non restricted instances keyed with the instance id;
     * @return array
     */
    public function get_all_costs() {
        global $DB;
        $instances = $DB->get_records('enrol', ['courseid' => $this->courseid]);
        $costs = [];
        foreach ($instances as $instance) {
            $wallet = new wallet($instance);
            $cost = $wallet->get_cost_after_discount($this->userid, $instance);
            $enrolstat = $wallet->can_self_enrol($instance);
            if (in_array($enrolstat, [true, wallet::INSUFFICIENT_BALANCE, wallet::INSUFFICIENT_BALANCE_DISCOUNTED], true)) {
                $costs[$instance->id] = $cost;
            }
        }
        return $costs;
    }

    /**
     * Return the id of cheapest instance in this course.
     * @return int|null
     */
    public function get_the_cheapest_instance_id() {
        $costs = $this->get_all_costs();
        $min = min($costs);
        foreach ($costs as $id => $cost) {
            if ($cost == $min) {
                return $id;
            }
        }
        return null;
    }
    /**
     * Get the coupon code used for discount if existed.
     * @return coupons|null
     */
    public function get_coupon_helper() {
        return $this->couponutil ?? null;
    }
}
