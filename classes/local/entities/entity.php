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
use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\discounts\discount\discount_base;
use enrol_wallet\local\utils\discount;
use stdClass;

/**
 * Class entity.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class entity extends stdClass {
    use discount;

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
     * The all discounts in this instance.
     * @var float[]
     */
    protected array $discounts;

    /**
     * The id of the user.
     * @var int
     */
    protected int $userid;

    /**
     * The id of the entity.
     * @var int
     */
    public readonly int $id;

    /**
     * If the instance class in dirty state and the cached values
     * of $costafter could be cleared.
     * @var bool
     */
    protected bool $dirty = false;

    /**
     * Discount classes.
     * @var discount_base[]
     */
    protected array $discountclasses;

    /**
     * Create a new enrol wallet instance helper class.
     * store the cost after discount.
     *
     * @param int $courseid the course id that the entity belongs to.
     * @param int $entityid the id of the entity.
     * @param int $userid   the id of the user, 0 means the current user.
     */
    public function __construct(int $courseid, int $entityid, int $userid = 0) {
        global $USER;
        $this->userid = empty($userid) ? $USER->id : $userid;
        $this->courseid = $courseid;
        $this->id = $entityid;
    }

    /**
     * Set the userid to calculate the discount for.
     * @param  int|stdClass $user
     * @return void
     */
    public function set_user(int|stdClass $user = 0): void {
        global $USER;
        $this->userid = match (true) {
            empty($user)      => $USER->id,
            \is_object($user) => $user->id,
            default           => $user,
        };
    }

    /**
     * Getter for user id.
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
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
     * Get the discount calculation get_behavior.
     * Should be one of the constants B_MAX, B_SUM, B_SEQ.
     * @return int
     */
    public function get_behavior(): int {
        return self::const('max');
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
     * @param  ?float $cost // Must be supplied in case of cm or section.
     * @return ?float
     */
    public function get_cost_after_discount(?float $cost = null): ?float {
        $this->calculate_discount($cost);

        return $this->costafter;
    }

    /**
     * Get the coupons area describe this entity, one of constants coupons::AREA_.
     * @return int
     */
    abstract public static function get_coupon_area(): int;

    /**
     * Get the available discount classes.
     * @param  float           $originalcost
     * @return discount_base[]
     */
    final protected function get_discount_classes(float $originalcost): array {
        $this->check_dirty();

        if (isset($this->discountclasses)) {
            return $this->discountclasses;
        }

        $this->discountclasses = [];
        $all = [
            'coupon',
            'offers',
            'profile',
            'repurchase',
            'hook',
        ];

        foreach ($all as $name) {
            $class = "enrol_wallet\\local\\discounts\\discount\\$name";

            if (!class_exists($class) || !$class::is_available($this)) {
                continue;
            }
            $this->discountclasses[$name] = new $class($this, $originalcost);
        }

        return $this->discountclasses;
    }

    /**
     * Calculate and return discounts of all types.
     * @param  float   $originalcost
     * @return float[]
     */
    protected function get_discounts(float $originalcost): array {
        $this->check_dirty();

        if (isset($this->discounts)) {
            return $this->discounts;
        }
        $discounts = [];
        $classes = $this->get_discount_classes($originalcost);

        foreach ($classes as $name => $class) {
            $discounts[$name] = $class->get_percentage_discount();
        }
        $this->discounts = $discounts;

        return $discounts;
    }

    /**
     * Calculate percentage discount for a user from custom profile field and coupon code.
     * and then return the cost of the entity after discount.
     * @param  float $cost The cost passed in the availability_wallet process
     *                     We check this cost against all costs in availability tree
     * @return float
     */
    final protected function calculate_discount(float $cost): float {
        $discounts = $this->get_discounts($cost);
        $discount = match ($this->get_behavior()) {
            self::const('max') => $this->calculate_max_discount($discounts),
            self::const('seq') => $this->calculate_sequential_discount($discounts),
            self::const('sum') => $this->calculate_sum_discount($discounts),
        };

        $this->costafter = $cost - $cost * $discount;

        return $discount;
    }

    /**
     * Check if the cached values of cost after discount need to be cleared first.
     * @return bool
     */
    public function is_dirty(): bool {
        return $this->dirty;
    }

    /**
     * Mark as dirty to clear the cached values of cost after discount.
     * @return void
     */
    public function mark_as_dirty(): void {
        $this->dirty = true;
    }

    /**
     * Check if the instance is dirty and hence clear
     * caches.
     * @return void
     */
    protected function check_dirty(): void {
        if ($this->is_dirty()) {
            unset($this->costafter, $this->discounts, $this->discountclasses);

            $this->dirty = false;
        }
    }

    /**
     * Get the coupon helper class for he code used for discount if existed.
     * @deprecated Please use the coupons offers class to get the coupons class
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

    /**
     * Must be called after successful purchase with discount.
     * @param  float $originalcost
     * @return void
     */
    public function post_purchase(float $originalcost): void {
        $classes = $this->get_discount_classes($originalcost);

        foreach ($classes as $class) {
            $class->after_process();
        }
    }
}
