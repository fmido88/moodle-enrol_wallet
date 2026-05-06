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

use enrol_wallet\local\coupons\areas\base as area_base;

/**
 * Class discount.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class percent extends base {
    /**
     * Type.
     * @var int
     */
    public const TYPE = 2;

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('percentdiscountcoupon', 'enrol_wallet');
    }

    #[\Override()]
    public function validate_coupon(area_base $area, int $userid, ?string &$error = null): bool {
        if ($this->value > 100 || $this->value <= 0) {
            $error = get_string('invalidpercentcoupon', 'enrol_wallet');

            return false;
        }

        return $this->validate_area_category_and_courses($area, $userid, $error);
    }

    #[\Override()]
    public static function is_discount_coupon(): bool {
        return true;
    }

    #[\Override()]
    public function get_discounted_value(float $cost): float {
        if ($cost <= 0) {
            return 0;
        }

        return $cost * $this->value / 100;
    }
    #[\Override()]
    protected static function validate_type_generator_form(array $data, array &$errors): void {
        if ($data['value'] > 100) {
            $errors['value'] = get_string('invalidpercentcoupon', 'enrol_wallet');
        }
    }
    #[\Override()]
    public function get_submission_message(area_base $area): array {
        global $DB;

        if ($instanceid = $area->get_instance_id()) {
            if (!$DB->record_exists('enrol', ['id' => $instanceid, 'enrol' => 'wallet'])) {
                return [
                    'message' => get_string('coupon_applynocourse', 'enrol_wallet'),
                    'error',
                ];
            }
        }

        return [
            'message' => get_string('coupon_applydiscount', 'enrol_wallet', $this->value),
            'type'    => 'success',
        ];
    }
}
