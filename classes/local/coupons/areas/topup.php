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

namespace enrol_wallet\local\coupons\areas;

use enrol_wallet\local\coupons\types\base as type_base;
use enrol_wallet\local\wallet\balance_op;
use stdClass;

/**
 * Class topup.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topup extends base {
    /**
     * Area.
     * @var int
     */
    public const AREA = 11;

    #[\Override()]
    public function get_name(bool $withlink = false): string {
        return get_string('topupbycoupon', 'enrol_wallet');
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('couponarea_topup', 'enrol_wallet');
    }

    #[\Override()]
    public function record_exists(): bool {
        return true;
    }

    #[\Override()]
    public function is_valid_for_type(type_base $type): bool {
        return $type->is_topup_coupon();
    }

    #[\Override()]
    public function get_entity(int $userid = 0): \enrol_wallet\local\entities\entity|null {
        return null;
    }

    #[\Override()]
    public function get_balance_operation(int $userid, type_base $type): balance_op {
        if ($type::get_type() === 'category') {
            return new balance_op($userid, $type->category);
        }

        return new balance_op($userid);
    }

    #[\Override()]
    public function is_same_category(int $catid): bool {
        return true;
    }

    #[\Override()]
    public static function get_id_from_data(array|stdClass $data): ?int {
        return 0;
    }
}
