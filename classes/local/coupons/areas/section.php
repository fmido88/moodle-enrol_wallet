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
use enrol_wallet\local\entities\section as section_entity;
use enrol_wallet\local\wallet\balance_op;
use stdClass;

/**
 * Class section.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends base {
    /**
     * Area.
     * @var int
     */
    public const AREA = 9;

    #[\Override()]
    public function get_name(bool $withlink = false): string {
        $section = new section_entity($this->areaid);
        $name = $section->get_name();

        if (!$withlink) {
            return $name;
        }
        $url = $section->get_context()->get_url()->param('section', $section->section->section);

        return html_writer::link($url, $name);
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('couponarea_section', 'enrol_wallet');
    }

    #[\Override()]
    public function record_exists(): bool {
        global $DB;

        return $DB->record_exists('course_sections', ['id' => $this->areaid]);
    }

    #[\Override()]
    public function is_valid_for_type(type_base $type): bool {
        return !empty($this->areaid) && $type->value !== 0 && $type::get_type() !== 'enrol';
    }

    #[\Override()]
    public function get_entity(int $userid = 0): \enrol_wallet\local\entities\entity|null {
        return new section_entity($this->areaid, $userid);
    }

    #[\Override()]
    public function get_balance_operation(int $userid, type_base $type): balance_op {
        if ($type::get_type() === 'fixed') {
            return new balance_op($userid);
        }

        return balance_op::create_from_section($this->areaid, $userid);
    }

    #[\Override()]
    public function is_same_category(int $catid): bool {
        $catentity = $this->get_cat_entity($catid);

        return $catentity->is_child_section($this->areaid);
    }

    #[\Override()]
    public static function get_id_from_data(array|stdClass $data): ?int {
        $data = (array)$data;

        return $data['sectionid'] ?? null;
    }

    #[\Override()]
    public function get_redirect_url(\core\url $url, ?type_base $coupon): \core\url {
        if ($coupon) {
            $url->param('coupon', $coupon->code);
        }

        return $url;
    }
}
