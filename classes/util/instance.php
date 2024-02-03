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
    private $couponutil;
    /**
     * The id of the user we need to calculate the discount for.
     * @var int
     */
    public $userid;

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
        $this->calculate_cost_after_discount();
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
        return core_course_category::get($catid);
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
     * Get percentage discount for a user from custom profile field and coupon code.
     * and then calculate the cost of the course after discount.
     * @return void
     */
    private function calculate_cost_after_discount() {
        global $DB;
        $userid = $this->userid;

        $instance = $this->instance;
        $cost = $instance->cost;
        if ($ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid])) {
            if (!empty($ue->timeend) && get_config('enrol_wallet', 'repurchase')) {
                if ($first = get_config('enrol_wallet', 'repurchase_firstdis')) {
                    $cost = (100 - $first) * $instance->cost / 100;
                    $second = get_config('enrol_wallet', 'repurchase_seconddis');
                    $timepassed = $ue->timemodified > $ue->timecreated + $ue->timeend - $ue->timestart;
                    if ($second && $ue->modifierid == $userid && $timepassed) {
                        $cost = (100 - $second) * $instance->cost / 100;
                    }
                }
            }
        }

        // Check if there is a discount coupon first.
        $coupon = coupons::check_discount_coupon();

        $costaftercoupon = $cost;

        if (!empty($coupon)) {
            $couponutil = new coupons($coupon, $userid);

            $validation = $couponutil->validate_coupon(coupons::AREA_ENROL, $instance->id);

            if (true === $validation) {
                $this->couponutil = $couponutil;
                if ($couponutil->type == coupons::DISCOUNT && $couponutil->valid) {
                    $costaftercoupon = $instance->cost * (1 - $couponutil->value / 100);
                }
            } else if (is_string($validation)) {
                static $warned = false;
                if (!$warned) {
                    $warned = true;
                    \core\notification::error($validation);
                }
            }
        }

        // Check if the discount according to custom profile field in enabled.
        if (!$fieldid = get_config('enrol_wallet', 'discount_field')) {
            $this->costafter = $costaftercoupon;
            return;
        }

        // Check the data in the discount field.
        $data = $DB->get_field('user_info_data', 'data', ['userid' => $userid, 'fieldid' => $fieldid]);

        if (empty($data)) {
            $this->costafter = $costaftercoupon;
            return;
        }
        // If the user has free access to courses return 0 cost.
        if (stripos(strtolower($data), 'free') !== false) {
            $this->costafter = 0;
            // If there is a word no in the data means no discount.
        } else if (stripos(strtolower($data), 'no') !== false) {
            $this->costafter = $costaftercoupon;
        } else {
            // Get the integer from the data.
            preg_match('/\d+/', $data, $matches);
            if (isset($matches[0]) && intval($matches[0]) <= 100) {
                // Cannot allow discount more than 100%.
                $discount = intval($matches[0]);
                $this->costafter = $costaftercoupon * (100 - $discount) / 100;
            } else {
                $this->costafter = $costaftercoupon;
            }
        }
    }

    /**
     * Get the cost of the enrol instance after discount.
     * @param bool $recalculate
     * @return float the cost after discount.
     */
    public function get_cost_after_discount($recalculate = false) {
        if ($recalculate) {
            $this->calculate_cost_after_discount();
        }
        return $this->costafter;
    }

    /**
     * Get the coupon code used for discount if existed.
     * @return coupons|null
     */
    public function get_coupon_helper() {
        return $this->couponutil ?? null;
    }
}
