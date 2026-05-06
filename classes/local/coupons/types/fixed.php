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
 * Class fixed.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fixed extends base {
    /**
     * Type.
     * @var int
     */
    public const TYPE = 1;

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('fixedvaluecoupon', 'enrol_wallet');
    }

    #[\Override()]
    public function validate_coupon(area_base $area, int $userid, ?string &$error = null): bool {
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
    public function get_submission_message(area_base $area): array {
        // Apply the coupon code to add its value to the user's wallet and enrol if value is enough.
        $currency = config::make()->currency;
        $a = [
            'value'    => $this->value,
            'currency' => $currency,
        ];

        return [
            'message' => get_string('coupon_applyfixed', 'enrol_wallet', $a),
            'type'    => 'success',
        ];
    }
}
