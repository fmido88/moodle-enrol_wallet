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
 * wallet enrol plugin external functions
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/enrol/wallet/lib.php");

/**
 * wallet enrolment external functions.
 *
 * @package   enrol_wallet
 * @copyright 2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_wallet_external extends external_api {

    /**
     * Returns description of get_instance_info() parameters.
     *
     * @return external_function_parameters
     */
    public static function get_instance_info_parameters() {
        return new external_function_parameters(
                [
                    'instanceid' => new external_value(PARAM_INT, 'instance id of wallet enrolment plugin.')
                ]
            );
    }

    /**
     * Return wallet-enrolment instance information.
     *
     * @param int $instanceid instance id of wallet enrolment plugin.
     * @return array instance information.
     * @throws moodle_exception
     */
    public static function get_instance_info($instanceid) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::get_instance_info_parameters(), ['instanceid' => $instanceid]);

        // Retrieve wallet enrolment plugin.
        $enrolplugin = enrol_get_plugin('wallet');
        if (empty($enrolplugin)) {
            throw new moodle_exception('invaliddata', 'error');
        }

        self::validate_context(context_system::instance());

        $enrolinstance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $enrolinstance->courseid], '*', MUST_EXIST);
        if (!core_course_category::can_view_course_info($course) && !can_access_course($course)) {
            throw new moodle_exception('coursehidden');
        }

        $instanceinfo = (array) $enrolplugin->get_enrol_info($enrolinstance);

        unset($instanceinfo['requiredparam']);

        return $instanceinfo;
    }

    /**
     * Returns description of get_instance_info() result value.
     *
     * @return external_description
     */
    public static function get_instance_info_returns() {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'id of course enrolment instance'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'type' => new external_value(PARAM_PLUGIN, 'type of enrolment plugin'),
                'name' => new external_value(PARAM_RAW, 'name of enrolment plugin'),
                'status' => new external_value(PARAM_RAW, 'status of enrolment plugin'),
                'cost' => new external_value(PARAM_NUMBER, 'The cost of the course'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function enrol_user_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Id of the course'),
                'instanceid' => new external_value(PARAM_INT, 'Instance id of wallet enrolment plugin.', VALUE_DEFAULT, 0)
            ]
        );
    }

    /**
     * wallet enrol the current user in the given course.
     *
     * @param int $courseid id of course
     * @param int $instanceid instance id of wallet enrolment plugin
     * @return array of warnings and status result
     * @throws moodle_exception
     */
    public static function enrol_user($courseid, $instanceid = 0) {
        global $CFG, $USER;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::enrol_user_parameters(),
                                                    [
                                                        'courseid' => $courseid,
                                                        'instanceid' => $instanceid
                                                    ]);

        $warnings = [];

        $course = get_course($params['courseid']);
        $context = context_course::instance($course->id);
        self::validate_context(context_system::instance());

        if (!core_course_category::can_view_course_info($course)) {
            throw new moodle_exception('coursehidden');
        }

        // Retrieve the wallet enrolment plugin.
        $enrol = enrol_get_plugin('wallet');
        if (empty($enrol)) {
            throw new moodle_exception('canntenrol', 'enrol_wallet');
        }

        // We can expect multiple wallet-enrolment instances.
        $instances = [];
        $enrolinstances = enrol_get_instances($course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "wallet") {
                // Instance specified.
                if (!empty($params['instanceid'])) {
                    if ($courseenrolinstance->id == $params['instanceid']) {
                        $instances[] = $courseenrolinstance;
                        break;
                    }
                } else {
                    $instances[] = $courseenrolinstance;
                }
            }
        }

        if (empty($instances)) {
            throw new moodle_exception('canntenrol', 'enrol_wallet');
        }

        // Try to enrol the user in the instance/s.
        $enrolled = false;
        foreach ($instances as $instance) {
            $enrolstatus = $enrol->can_self_enrol($instance);
            if ($enrolstatus === true) {

                // Do the enrolment.
                $enrol->enrol_self($instance, $USER);
                $enrolled = true;
                break;
            } else {
                $costafter = $enrol->get_cost_after_discount($USER->id, $instance);
                $cost = $instance->cost;
                $balance = \enrol_wallet\transactions::get_user_balance($USER->id);
                $a = [
                    'cost_before' => $cost,
                    'cost_after' => $costafter,
                    'user_balance' => $balance,
                ];
                if ($enrolstatus == \enrol_wallet_plugin::INSUFFICIENT_BALANCE) {
                    $enrolstatus = get_string('insufficient_balance', 'enrol_wallet', $a);
                } else if ($enrolstatus == \enrol_wallet_plugin::INSUFFICIENT_BALANCE_DISCOUNTED) {
                    $enrolstatus = get_string('insufficient_balance_discount', 'enrol_wallet', $a);
                }

                $warnings[] = [
                    'item' => 'instance',
                    'itemid' => $instance->id,
                    'warningcode' => '1',
                    'message' => $enrolstatus
                ];
            }
        }

        $result = [];
        $result['status'] = $enrolled;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function enrol_user_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'status: true if the user is enrolled, false otherwise'),
                'warnings' => new external_warnings()
            ]
        );
    }

}
