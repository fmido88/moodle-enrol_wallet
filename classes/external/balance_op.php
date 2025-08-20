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

namespace enrol_wallet\external;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("$CFG->dirroot/enrol/wallet/externalclasses.php");
use external_api;
use external_function_parameters;
use external_description;
use external_single_structure;
use external_value;
/**
 * Class balance_op
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class balance_op extends external_api {
    /**
     * Returns description of get_balance_details() parameters
     *
     * @return external_function_parameters
     */
    public static function get_balance_details_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'The id of the user', VALUE_DEFAULT, 0),
        ]);
    }
    /**
     * Returns the balance details for a single user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_balance_details($userid) {
        global $CFG, $PAGE;
        $params = self::validate_parameters(self::get_balance_details_parameters(), ['userid' => $userid]);
        $userid = $params['userid'];
        require_once("$CFG->dirroot/enrol/wallet/locallib.php");
        require_login();
        $context = \context_user::instance($userid);
        require_capability('enrol/wallet:viewotherbalance', $context);
        $PAGE->set_context($context);
        return ['details' => enrol_wallet_display_current_user_balance($userid)];
    }

    /**
     * Returns description of get_balance_details() result value.
     *
     * @return external_description
     */
    public static function get_balance_details_returns() {
        return new external_single_structure([
            'details' => new external_value(PARAM_RAW, 'Balance details'),
        ]);
    }
}
