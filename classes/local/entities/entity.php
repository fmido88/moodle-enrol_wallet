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

namespace enrol_wallet\local\entities;

use context;
use core_course_category;
use enrol_wallet\local\config;
use enrol_wallet\local\coupons\coupons;
use stdClass;

/**
 * Class entity.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class entity extends stdClass {
    /**
     * The course which the instance belong to.
     * @var int
     */
    protected int $courseid;

    /**
     * The cost after discount.
     * @var float
     */
    protected float $costafter;

    /**
     * The coupon helper class object.
     * @var coupons
     */
    protected coupons $couponutil;

    /**
     * The id of the user.
     * @var int
     */
    protected int $userid;

    /**
     * The id of the entity.
     * @var int
     */
    protected int $id;

    /**
     * Create a new enrol wallet instance helper class.
     * store the cost after discount.
     *
     * @param int $courseid the course id that the entity belongs to.
     * @param int $entityid the id of the entity.
     * @param int $userid the id of the user, 0 means the current user.
     */
    public function __construct(int $courseid, int $entityid, int $userid = 0) {
        global $USER;
        $this->userid   = empty($userid) ? $USER->id : $userid;
        $this->courseid = $courseid;
        $this->id       = $entityid;
    }

    /**
     * Set the userid to calculate the discount for.
     * @param int|stdClass $user
     * @return void
     */
    public function set_user(int|stdClass $user = 0) {
        global $USER;
        $this->userid = match(true) {
            empty($user)      => $USER->id,
            \is_object($user) => $user->id,
            default           => $user,
        };
    }
    /**
     * Get the course that the instance belongs to.
     * @return \stdClass
     */
    public function get_course(): stdClass {
        return get_course($this->courseid);
    }

    /**
     * Get the id of the course that the entity belongs to.
     * @return int
     */
    public function get_course_id(): int {
        return $this->courseid;
    }

    /**
     * Get course category object at which the instance belongs to.
     * @return core_course_category|null
     */
    public function get_course_category(): ?core_course_category {
        $catid = $this->get_category_id();

        if (!empty($catid)) {
            return core_course_category::get($catid, IGNORE_MISSING, true, $this->userid);
        }

        return null;
    }

    /**
     * Return a visible formatted name of the entity.
     * @return void
     */
    abstract public function get_name(): string;

    /**
     * Return the context that the entity belongs to.
     * @return void
     */
    abstract public function get_context(): context;

    /**
     * Return only the id of the category.
     * @return int
     */
    public function get_category_id(): int {
        if ($course = $this->get_course()) {
            return $course->category;
        }

        return 0;
    }

    /**
     * Get the cost of the entity after calculate the discount.
     * @param  ?float $cost
     * @return ?float
     */
    public function get_cost_after_discount(?float $cost = null): ?float {
        return $this->calculate_discount($cost);
    }

    /**
     * Get the coupons area describe this entity, one of constants coupons::AREA_.
     * @return void
     */
    abstract protected static function get_coupon_area(): int;

    /**
     * Calculate and return the discount due to profile field.
     * @return float from 0 to 1
     */
    protected function get_profile_field_discount(): float {
        global $DB;
        $discount = 0;

        // Check if the discount according to custom profile field in enabled.
        if (!$fieldid = config::make()->discount_field) {
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

            if (isset($matches[0]) && \intval($matches[0]) <= 100) {
                // Cannot allow discount more than 100%.
                $discount = \intval($matches[0]) / 100;
            }
        }

        return min(1, $discount);
    }

    /**
     * Calculate and return discount due to discount coupon.
     * @param float  $originalcost
     * @return float from 0 to 1
     */
    protected function get_coupon_discount(float $originalcost): float {
        // Check if there is a discount coupon first.
        $couponutil = $this->get_coupon_helper();

        $discount = 0;

        if (!empty($couponutil)) {
            if ($couponutil->is_valid()) {
                $discount = match($couponutil->get_type(false)) {
                    coupons::DISCOUNT      => min($couponutil->get_value() / 100, 1),
                    coupons::FIXEDDISCOUNT => ($originalcost > 0)
                                                ? max(0, min($couponutil->get_value() / $originalcost, 1)) : 0,
                    default => 0,
                };
            } else if ($error = $couponutil->get_error()) {
                static $warned = false;

                if (!$warned) {
                    $warned = true;
                    \core\notification::error($error);
                }
            }
        }

        return $discount;
    }

    /**
     * Calculate percentage discount for a user from custom profile field and coupon code.
     * and then return the cost of the entity after discount.
     * @param  float $cost The cost passed in the availability_wallet process
     *                     We check this cost against all costs in availability tree
     * @return float
     */
    protected function calculate_discount(float $cost): float {
        $coupondiscount  = $this->get_coupon_discount($cost);
        $profilediscount = $this->get_profile_field_discount();
        $discount        = max(1, $coupondiscount + $profilediscount);
        $this->costafter = $cost * (1 - $discount);

        return $this->costafter;
    }

    /**
     * Get the coupon helper class for he code used for discount if existed.
     * @return coupons|null
     */
    public function get_coupon_helper(): ?coupons {
        $coupon = coupons::check_discount_coupon();

        if ($coupon) {
            $couponutil = new coupons($coupon, $this->userid);
            $couponutil->validate_coupon(static::get_coupon_area(), $this->id);
            $this->couponutil = $couponutil;

            return $this->couponutil;
        }

        return null;
    }
}
