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

use enrol_wallet\local\coupons\coupons;
use stdClass;

/**
 * Helper class for wallet enrolment instance
 * @package enrol_wallet
 */
class cm extends entity {
    /**
     * course module record.
     * @var \stdClass
     */
    public readonly stdClass $cm;

    /**
     * The costs before discount.
     * @var array
     */
    protected array $costs = [];

    /**
     * Create a new enrol wallet instance helper class.
     * store the cost after discount.
     *
     * @param int $cmid The enrol wallet instance or its id.
     * @param int $userid the id of the user, 0 means the current user.
     */
    public function __construct($cmid, $userid = 0) {
        global $DB;
        $this->cm = $DB->get_record('course_modules', ['id' => $cmid]);

        parent::__construct($this->cm->course, $cmid, $userid);

        if (!empty($this->cm->availability)) {
            $conditions = json_decode($this->cm->availability);
            $this->set_costs($conditions);
        }
    }
    /**
     * Get the cm context.
     * @return \core\context\module
     */
    public function get_context(): \context {
        return \context_module::instance($this->id);
    }

    /**
     * Get the formatted name of the course module.
     * @return string
     */
    public function get_name(): string {
        return $this->get_context()->get_context_name(false);
    }
    /**
     * Get the coupon area.
     * @return int
     */
    protected static function get_coupon_area(): int {
        return coupons::AREA_CM;
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
