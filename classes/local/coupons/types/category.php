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

namespace enrol_wallet\local\coupons\types;

use enrol_wallet\local\config;
use enrol_wallet\local\coupons\areas\base as area_base;

/**
 * Class category.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category extends base {
    /**
     * The type of the coupon.
     * @var int
     */
    public const TYPE = 4;

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('categorycoupon', 'enrol_wallet');
    }

    #[\Override()]
    public function validate_coupon(area_base $area, int $userid, ?string &$error = null): bool {
        $catenabled = (bool)config::make()->catbalance && !$this->wordpress;

        if (empty($this->category) || (!$catenabled && $area->get_area() === 'topup')) {
            $error = get_string('invalidcouponcategory', 'enrol_wallet');

            return false;
        }

        $catid = $this->category;

        // This type of coupons is restricted to be used in certain category and its children.
        if (!$area->is_same_category($catid)) {
            $categoryname = $area->get_cat_entity($catid)->get_category()->get_nested_name(false);
            $error = get_string('coupon_categoryfail', 'enrol_wallet', $categoryname);

            return false;
        }

        return true;
    }

    #[\Override()]
    public static function is_topup_coupon(): bool {
        return true;
    }

    #[\Override()]
    public static function is_enrol_coupon(): bool {
        return true;
    }

    #[\Override()]
    public function get_discounted_value(float $cost): float {
        return $cost - $this->value;
    }
    #[\Override()]
    protected static function validate_type_generator_form(array $data, array &$errors): void {
        if (empty($data['category'])) {
            $errors['category'] = get_string('coupons_category_error', 'enrol_wallet');
        }
    }
    #[\Override()]
    public static function can_specify_category(): bool {
        return true;
    }
    #[\Override()]
    public function get_submission_message(area_base $area): array {
        return [
            'message' => get_string('coupon_categoryapplied', 'enrol_wallet'),
            'type'    => 'success',
        ];
    }
}
