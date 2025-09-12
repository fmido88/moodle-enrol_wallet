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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_description;
use core_external\external_single_structure;
use core_external\external_value;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\output\helper;
use enrol_wallet\output\wallet_balance;
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
        global $PAGE;

        $params = self::validate_parameters(self::get_balance_details_parameters(), ['userid' => $userid]);
        $userid = $params['userid'];

        require_login();
        $context = \context_user::instance($userid);
        $PAGE->set_context($context);

        require_capability('enrol/wallet:viewotherbalance', $context);

        $renderable = new wallet_balance($userid);
        $renderer = helper::get_wallet_renderer($PAGE);
        return ['details' => $renderer->render($renderable)];
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

    /**
     * Parameters for get_balance
     * @return external_function_parameters
     */
    public static function get_balance_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'The userid', VALUE_DEFAULT, 0),
            'catid'  => new external_value(PARAM_INT, 'The category that the payment belongs to.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get the balance details for the certain user.
     * @param mixed $userid
     * @param mixed $catid
     * @return array{
     *               main:               float,
     *               mainfree:           float,
     *               mainnonrefundable:  float,
     *               mainrefundable:     float,
     *               total:              float,
     *               totalfree:          float,
     *               totalnonrefundable: float,
     *               totalrefundable:    float,
     *               valid:              float,
     *               validfree:          float,
     *               validnonrefundable: float
     * }
     */
    public static function get_balance($userid, $catid = 0): array {
        global $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }

        [
            'userid' => $userid,
            'catid'  => $catid,
        ] = self::validate_parameters(self::get_balance_parameters(), compact('userid', 'catid'));

        if (!empty($catid)) {
            $context = \core\context\coursecat::instance($catid);
        } else {
            $context = \core\context\user::instance($userid);
        }
        self::validate_context($context);

        $balance = new balance($userid, $catid);
        return [
            'total'              => $balance->get_total_balance(),
            'totalfree'          => $balance->get_total_free(),
            'totalrefundable'    => $balance->get_total_refundable(),
            'totalnonrefundable' => $balance->get_total_nonrefundable(),
            'main'               => $balance->get_main_balance(),
            'mainrefundable'     => $balance->get_main_refundable(),
            'mainnonrefundable'  => $balance->get_main_nonrefundable(),
            'mainfree'           => $balance->get_main_free(),
            'valid'              => $balance->get_valid_balance(),
            'validfree'          => $balance->get_valid_free(),
            'validnonrefundable' => $balance->get_valid_nonrefundable(),
        ];
    }

    /**
     * Return values of get_balance
     * @return external_single_structure
     */
    public static function get_balance_returns(): external_single_structure {
        return new external_single_structure([
            'total'              => new external_value(PARAM_FLOAT, 'Total balance.'),
            'totalfree'          => new external_value(PARAM_FLOAT, 'Total free points.'),
            'totalrefundable'    => new external_value(PARAM_FLOAT, 'Total refundable balance.'),
            'totalnonrefundable' => new external_value(PARAM_FLOAT, 'Total nonrefundable balance.'),
            'main'               => new external_value(PARAM_FLOAT, 'Main balance.'),
            'mainrefundable'     => new external_value(PARAM_FLOAT, 'Main refundable balance.'),
            'mainnonrefundable'  => new external_value(PARAM_FLOAT, 'Main nonrefundable balance.'),
            'mainfree'           => new external_value(PARAM_FLOAT, 'Main free points.'),
            'valid'              => new external_value(PARAM_FLOAT, 'Valid balance'),
            'validfree'          => new external_value(PARAM_FLOAT, 'Valid free points'),
            'validnonrefundable' => new external_value(PARAM_FLOAT, 'Valid nonrefundable'),
        ]);
    }
}
