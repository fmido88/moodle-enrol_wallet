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

namespace enrol_wallet\local\discounts\discount;

use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\entities\entity;

/**
 * Class coupon
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupon extends discount_base {
    /**
     * The coupons helper class.
     * @var coupons
     */
    protected coupons $couponutil;

    /**
     * Get the coupon helper class for he code used for discount if existed.
     * @return coupons|null
     */
    public function get_coupon_helper(): ?coupons {
        $coupon = coupons::check_discount_coupon();

        if ($coupon) {
            if (isset($this->couponutil) && $this->couponutil->get_code() === $coupon) {
                return $this->couponutil;
            }
            $couponutil = new coupons($coupon, $this->get_userid());
            $couponutil->validate_coupon($this->entity::get_coupon_area(), $this->entity->id);

            return $couponutil;
        } else {
            unset($this->couponutil);
        }

        return null;
    }

    #[\Override()]
    public function get_percentage_discount(): float {
        // Check if there is a discount coupon first.
        $couponutil = $this->get_coupon_helper();
        $originalcost = $this->get_original_cost();
        $discount = 0;

        if (!empty($couponutil)) {
            if ($originalcost > 0 && $couponutil->is_valid() && $couponutil->coupon->is_discount_coupon()) {
                $discounted = $couponutil->coupon->get_discounted_value($originalcost);
                $discount = 1 - $discounted / $originalcost;
            } else if ($error = $couponutil->get_error()) {
                static $warned = false;

                if (!$warned) {
                    $warned = true;
                    \core\notification::error($error);
                }
            }
        }

        return max(min($discount * 100, 100), 0);
    }

    #[\Override()]
    public static function is_available(entity $entity): bool {
        return coupons::is_enabled();
    }

    #[\Override()]
    public function after_process(): void {
        $cost = $this->get_original_cost();
        $costafter = $this->get_discounted_cost();
        $coupon = $this->get_coupon_helper();
        if ($coupon && !empty($coupon->get_code()) && $costafter < $cost) {
            $coupon->mark_coupon_used();
        }
    }
}
