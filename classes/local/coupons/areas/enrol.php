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

use core\output\html_writer;
use enrol_wallet\local\coupons\types\base as type_base;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\wallet\balance_op;
use stdClass;

/**
 * Class enrol.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol extends base {
    /**
     * Area.
     * @var int
     */
    public const AREA = 5;

    #[\Override()]
    public function get_name(bool $withlink = false): string {
        $instance = new instance($this->areaid);

        $name = $instance->get_name();
        $context = $instance->get_course_context();
        $coursename = $context->get_context_name(false);
        $return = "{$name} ({$coursename})";

        if (!$withlink) {
            return $return;
        }
        $url = $context->get_url();

        return html_writer::link($url, $return);
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('couponarea_enrol', 'enrol_wallet');
    }

    #[\Override()]
    public function record_exists(): bool {
        global $DB;

        return $DB->record_exists('enrol', ['id' => $this->areaid, 'enrol' => 'wallet']);
    }

    #[\Override()]
    public function is_valid_for_type(type_base $type): bool {
        return !empty($this->areaid);
    }

    #[\Override()]
    public function get_entity(int $userid = 0): \enrol_wallet\local\entities\entity|null {
        return new instance($this->areaid, $userid);
    }

    #[\Override()]
    public function get_balance_operation(int $userid, type_base $type): balance_op {
        if ($type::get_type() === 'fixed') {
            return new balance_op($userid);
        }

        return balance_op::create_from_instance($this->areaid, $userid);
    }

    #[\Override()]
    public function get_instance_id(): int {
        return $this->areaid;
    }

    #[\Override()]
    public function is_same_category(int $catid): bool {
        $catentity = $this->get_cat_entity($catid);

        return $catentity->is_child_instance($this->areaid);
    }

    #[\Override()]
    public static function get_id_from_data(array|stdClass $data): ?int {
        $data = (array)$data;

        return $data['instanceid'] ?? null;
    }

    #[\Override()]
    public function get_redirect_url(\core\url $url, ?type_base $coupon): \core\url {
        global $DB;
        $id = $DB->get_field('enrol', 'courseid', ['id' => $this->areaid, 'enrol' => 'wallet'], IGNORE_MISSING);

        if (!empty($id)) {
            $params = ['id' => $id];

            if ($coupon) {
                $params['coupon'] = $coupon->code;
            }

            return new \core\url('/enrol/index.php', $params);
        }

        return $url;
    }
}
