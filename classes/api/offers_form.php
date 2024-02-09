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
use external_description;
use external_single_structure;
use external_value;
use enrol_wallet\util\offers;

/**
 * Class balance_op
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offers_form extends external_api {
    /**
     * Returns description of get_balance_details() parameters
     *
     * @return external_function_parameters
     */
    public static function get_form_fragment_parameters() {
        return new external_function_parameters([
            'type'      => new external_value(PARAM_TEXT, 'The type of the offers rules'),
            'increment' => new external_value(PARAM_INT, 'order of the added fragment'),
            'course'    => new external_value(PARAM_INT, 'the course id'),
        ]);
    }

    /**
     * Returns the balance details for a single user.
     *
     * @param string $type
     * @param int $increment
     * @param int $courseid
     * @return array
     */
    public static function get_form_fragment($type, $increment, $courseid) {
        global $PAGE;
        require_login();
        $params = ['type' => $type, 'increment' => $increment, 'course' => $courseid];
        $params = self::validate_parameters(self::get_form_fragment_parameters(), $params);
        $type = $params['type'];
        $i = $params['increment'];
        $courseid = $params['course'];
        $PAGE->set_context(\context_course::instance($courseid));
        return ['data' => offers::render_form_fragment($type, $i, $courseid)];
    }

    /**
     * Returns description of get_balance_details() result value.
     *
     * @return external_description
     */
    public static function get_form_fragment_returns() {
        return new external_single_structure([
            'data' => new external_value(PARAM_RAW, 'part of a form for offer rules'),
        ]);
    }
}
