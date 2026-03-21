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

/**
 * Helper.
 *
 * @package   enrol_wallet
 * @copyright 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\entities;

use core_course_list_element;
use enrol_wallet\local\coupons\coupons;
use stdClass;

/**
 * Helper class for wallet enrolment instance
 * @package enrol_wallet
 */
class section extends entity {
    /**
     * course module record.
     * @var \stdClass
     */
    public stdClass $section;

    /**
     * The costs before discount.
     * @var array
     */
    public array $costs = [];

    /**
     * Create a new enrol wallet instance helper class.
     * store the cost after discount.
     *
     * @param int $sectionid The enrol wallet instance or its id.
     * @param int $userid the id of the user, 0 means the current user.
     */
    public function __construct($sectionid, $userid = 0) {
        global $DB;
        $this->section = $DB->get_record('course_sections', ['id' => $sectionid]);
        parent::__construct($this->section->course, $sectionid, $userid);

        if (!empty($this->section->availability)) {
            $conditions = json_decode($this->section->availability);
            $this->set_costs($conditions);
        }
    }
    /**
     * Get the course context that the section belongs to.
     * @return bool|\core\context\course
     */
    public function get_context(): \context {
        return \context_course::instance($this->courseid);
    }
    /**
     * Return the coupon area.
     * @return int
     */
    protected static function get_coupon_area(): int {
        return coupons::AREA_SECTION;
    }
    /**
     * Set all available costs for this cm, considering multiple conditions may be applied.
     * @param \stdClass $conditions the availability tree.
     */
    private function set_costs($conditions) {
        foreach ($conditions->c as $child) {
            if (!empty($child->c) && !empty($child->op)) {
                $this->set_costs($child);
            } else if ($child->type === 'wallet') {
                $this->costs[] = $child->cost;
            }
        }
    }

    /**
     * Get the visible name of the section.
     * @return string
     */
    public function get_name(): string {
        global $CFG;

        if (!empty($this->section->name)) {
            return format_string($this->section->name);
        }

        $course = new core_course_list_element($this->get_course());
        $coursename = $course->get_formatted_fullname();
        require_once("{$CFG->dirroot}/course/lib.php");

        $sectionname = get_section_name($this->section->course, $this->section->section);

        return "$sectionname ($coursename)";
    }

    /**
     * Calculate percentage discount for a user from custom profile field and coupon code.
     * and then return the cost of the cm after discount.
     * @param ?float $cost The cost passed in the availability_wallet process
     *                    We check this cost against all costs in availability tree
     * @return float|null
     */
    public function get_cost_after_discount(?float $cost = null): ?float {
        if (!\in_array($cost, $this->costs)) {
            debugging("The cost passes to get_cost_after_discount() is not in the cost list.", DEBUG_DEVELOPER);
            return null;
        }
        return $this->calculate_discount($cost);
    }
}
