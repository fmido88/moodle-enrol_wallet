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

namespace enrol_wallet\local\entities;

use enrol_wallet\local\coupons\coupons;
use core_course_category;

/**
 * Helper class for wallet enrolment instance
 * @package enrol_wallet
 */
class cm {
    /**
     * course module record.
     * @var \stdClass
     */
    public $cm;
    /**
     * The cm id
     * @var int
     */
    public $id;
    /**
     * The course which the instance belong to.
     * @var int
     */
    public $courseid;

    /**
     * The costs before discount.
     * @var array
     */
    public $costs = [];
    /**
     * The cost after discount.
     * @var float
     */
    public $costafter;
    /**
     * The coupon helper class object
     * @var coupons
     */
    public $couponutil;
    /**
     * The id of the user.
     * @var int
     */
    protected $userid;

    /**
     * Create a new enrol wallet instance helper class.
     * store the cost after discount.
     *
     * @param int $cmid The enrol wallet instance or its id.
     * @param int $userid the id of the user, 0 means the current user.
     */
    public function __construct($cmid, $userid = 0) {
        global $USER, $DB;
        $this->cm = $DB->get_record('course_modules', ['id' => $cmid]);
        $this->id = $cmid;
        $this->courseid = $this->cm->course;

        if (empty($userid)) {
            $this->userid = $USER->id;
        } else {
            $this->userid = $userid;
        }

        if (!empty($this->cm->availability)) {
            $conditions = json_decode($this->cm->availability);
            $this->set_costs($conditions);
        }
    }

    /**
     * Set all available costs for this cm, considering multiple conditions may be applied.
     * @param \stdClass $conditions the availability tree.
     */
    private function set_costs($conditions) {
        foreach ($conditions->c as $child) {
            if (!empty($child->c) && !empty($child->op)) {
                $this->set_costs($child);
            } else if ($child->type === 'wallet') {
                $this->costs[] = $child->cost;
            }
        }
    }

    /**
     * Get the course that the instance belongs to.
     * @return \stdClass
     */
    public function get_course() {
        $course = null;
        if (!empty($this->courseid)) {
            $course = get_course($this->courseid);
        }
        return $course;
    }

    /**
     * Get course category object at which the instance belongs to.
     * @return core_course_category|null
     */
    public function get_course_category() {
        $catid = $this->get_category_id();
        if (!empty($catid)) {
            return core_course_category::get($catid, IGNORE_MISSING, true);
        }
        return null;
    }

    /**
     * Return only the id of the category.
     * @return int
     */
    public function get_category_id() {
        if ($course = $this->get_course()) {
            return $course->category;
        }
        return 0;
    }

    /**
     * Calculate percentage discount for a user from custom profile field and coupon code.
     * and then return the cost of the cm after discount.
     * @param float $cost The cost passed in the availability_wallet process
     *                    We check this cost against all costs in availability tree
     * @return float|null
     */
    public function get_cost_after_discount($cost) {
        global $DB;
        if (!in_array($cost, $this->costs)) {
            return null;
        }

        // Check if there is a discount coupon first.
        if ($coupon = coupons::check_discount_coupon()) {
            $couponutil = new coupons($coupon);
            $this->couponutil = $couponutil;
            $couponutil->validate_coupon(coupons::AREA_CM, $this->id);
        }

        $this->costafter = $cost;

        if (!empty($coupon) && $couponutil->valid && $couponutil->type == coupons::DISCOUNT) {
            $this->costafter = $cost * (1 - $couponutil->value / 100);
        }

        // Check if the discount according to custom profile field in enabled.
        if (!$fieldid = get_config('enrol_wallet', 'discount_field')) {
            return $this->costafter;
        }
        // Check the data in the discount field.
        $data = $DB->get_field('user_info_data', 'data', ['userid' => $this->userid, 'fieldid' => $fieldid]);

        if (empty($data) || stripos($data, 'no') !== false) {
            return $this->costafter;
        }

        // If the user has free access to courses return 0 cost.
        if (stripos($data, 'free') !== false) {
            $this->costafter = 0;
            // If there is a word no in the data means no discount.
        } else {
            // Get the integer from the data.
            preg_match('/\d+/', $data, $matches);
            if (isset($matches[0]) && intval($matches[0]) <= 100) {
                // Cannot allow discount more than 100%.
                $discount = intval($matches[0]);
                $this->costafter = $this->costafter * (100 - $discount) / 100;
            }
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
