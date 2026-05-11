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
use stdClass;

/**
 * Used for non-existing coupons, invalid code or disabled.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class null_type extends base {
    /**
     * Non valid type.
     * @var int
     */
    public const TYPE = -1;

    /**
     * Override to construct a null type.
     * @param string $code
     */
    public function __construct(string $code) {
        $record = new stdClass();
        $record->code = $code;
        $record->type = 'null_type';
        $record->value = 0;
        parent::__construct($record);
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return '';
    }

    #[\Override()]
    public function validate_coupon(area_base $area, int $userid, ?string &$error = null): bool {
        return false;
    }

    #[\Override()]
    public function is_valid_record(int $userid, ?string &$error = ''): bool {
        $error = get_string('coupon_invalidrecord', 'enrol_wallet');
        return false;
    }

    #[\Override()]
    public static function is_enabled(): bool {
        return false;
    }

    #[\Override()]
    public function get_discounted_value(float $cost): float {
        return $cost;
    }
}
