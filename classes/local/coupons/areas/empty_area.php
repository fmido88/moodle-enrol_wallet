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

use enrol_wallet\local\wallet\balance_op;

/**
 * Empty coupon apply area just for testing and initialize coupons class.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class empty_area extends base {
    /**
     * AREA.
     * @var int
     */
    public const AREA = -1;

    // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
    /**
     * Constructor for empty area.
     */
    public function __construct() {
        parent::__construct(0);
    }
    // phpcs:enable Generic.CodeAnalysis.UselessOverridingMethod.Found

    #[\Override()]
    public function get_balance_operation(int $userid, \enrol_wallet\local\coupons\types\base $type): balance_op {
        return new balance_op($userid);
    }

    #[\Override()]
    public function get_entity(int $userid = 0): ?\enrol_wallet\local\entities\entity {
        return null;
    }

    #[\Override()]
    public static function get_id_from_data(array|\stdClass $data): ?int {
        return null;
    }

    #[\Override()]
    public function get_name(bool $withlink = false): string {
        return '';
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return '';
    }

    #[\Override()]
    public function is_same_category(int $catid): bool {
        return false;
    }

    #[\Override()]
    public function is_valid_for_type(\enrol_wallet\local\coupons\types\base $type): bool {
        return false;
    }

    #[\Override()]
    public function record_exists(): bool {
        return false;
    }
}
