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

namespace enrol_wallet\api;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use enrol_wallet\util\instance as helper;

/**
 * Class instance
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance extends external_api {
    /**
     * Returns description of get_balance_details() parameters
     *
     * @return external_function_parameters
     */
    public static function get_cost_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The id of the enrol wallet instance'),
            'userid' => new external_value(PARAM_INT, 'The id of the user', VALUE_OPTIONAL, 0),
        ]);
    }

    /**
     * Returns the balance details for a single user.
     *
     * @param int $instanceid
     * @param int $userid
     * @return array
     */
    public static function get_cost($instanceid, $userid) {
        global $CFG, $PAGE;
        $params = ['instanceid' => $instanceid, 'userid' => $userid];
        $params = self::validate_parameters(self::get_cost_parameters(), $params);
        $userid = $params['userid'];
        $instanceid = $params['instanceid'];
        $helper = new helper($instanceid, $userid);
        return ['cost' => $helper->get_cost_after_discount()];
    }

    /**
     * Returns description of get_balance_details() result value.
     *
     * @return external_single_structure
     */
    public static function get_cost_returns() {
        return new external_single_structure([
            'cost' => new external_value(PARAM_NUMBER, 'The cost of the instance after discount'),
        ]);
    }
}
