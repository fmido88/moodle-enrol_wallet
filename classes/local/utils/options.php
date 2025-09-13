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

namespace enrol_wallet\local\utils;

use context;
use core_course_category;
use enrol_wallet\local\config;
use enrol_wallet\local\entities\instance;
use stdClass;
use lang_string;
/**
 * Class options
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options {
    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    public static function get_status_options() {
        $options = [
                    ENROL_INSTANCE_ENABLED  => get_string('yes'),
                    ENROL_INSTANCE_DISABLED => get_string('no'),
                ];
        return $options;
    }

    /**
     * Return an array of valid options for the newenrols property.
     *
     * @return array
     */
    public static function get_newenrols_options() {
        $options = [1 => get_string('yes'), 0 => get_string('no')];
        return $options;
    }

    /**
     * Return an array of valid options for the expirynotify property.
     *
     * @return array
     */
    public static function get_expirynotify_options() {
        $options = [
                    0 => get_string('no'),
                    1 => get_string('expirynotifyenroller', 'core_enrol'),
                    2 => get_string('expirynotifyall', 'core_enrol'),
                ];
        return $options;
    }

    /**
     * Return an array of valid options for the longtimenosee property.
     *
     * @return array
     */
    public static function get_longtimenosee_options() {
        $options = [
                    0              => get_string('never'),
                    1800 * DAYSECS => get_string('numdays', '', 1800),
                    1000 * DAYSECS => get_string('numdays', '', 1000),
                    365 * DAYSECS  => get_string('numdays', '', 365),
                    180 * DAYSECS  => get_string('numdays', '', 180),
                    150 * DAYSECS  => get_string('numdays', '', 150),
                    120 * DAYSECS  => get_string('numdays', '', 120),
                    90 * DAYSECS   => get_string('numdays', '', 90),
                    60 * DAYSECS   => get_string('numdays', '', 60),
                    30 * DAYSECS   => get_string('numdays', '', 30),
                    21 * DAYSECS   => get_string('numdays', '', 21),
                    14 * DAYSECS   => get_string('numdays', '', 14),
                    7 * DAYSECS    => get_string('numdays', '', 7),
                ];
        return $options;
    }

    /**
     * Get all available courses for restriction by another course enrolment.
     * @param int $courseid Current course id of exceptions.
     * @return array<string>
     */
    public static function get_courses_options($courseid) {
        // Adding restriction upon another course enrolment.
        // Prepare the course selector.
        $courses = get_courses();
        $options = [];
        foreach ($courses as $course) {
            // We don't check enrolment in home page.
            if ($course->id == SITEID || $course->id == $courseid) {
                continue;
            }

            $category = core_course_category::get($course->category, IGNORE_MISSING, true);
            if (!$category) {
                continue;
            }
            $catname = $category->get_nested_name(false, ':') . ': ';

            $options[$course->id] = $catname . $course->fullname;
        }
        return $options;
    }

    /**
     * Return an array of valid send welcome email options.
     * @return array<string>
     */
    public static function get_send_welcome_email_option() {
        $options = [
            ENROL_DO_NOT_SEND_EMAIL                 => get_string('no'),
            ENROL_SEND_EMAIL_FROM_COURSE_CONTACT    => get_string('sendfromcoursecontact', 'enrol'),
            ENROL_SEND_EMAIL_FROM_NOREPLY           => get_string('sendfromnoreply', 'enrol'),
        ];

        return $options;
    }

    /**
     * Get available cohorts options for cohort restriction options.
     * @param stdClass $instance
     * @param context $context
     * @return array<string>
     */
    public static function get_cohorts_options($instance, $context) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/cohort/lib.php');

        $cohorts = [0 => get_string('no')];
        $allcohorts = cohort_get_available_cohorts($context, 0, 0, 0);
        if ($instance->customint5 && !isset($allcohorts[$instance->customint5])) {
            $c = $DB->get_record('cohort',
                                 ['id' => $instance->customint5],
                                 'id, name, idnumber, contextid, visible',
                                 IGNORE_MISSING);
            if ($c) {
                // Current cohort was not found because current user can not see it. Still keep it.
                $allcohorts[$instance->customint5] = $c;
            }
        }
        foreach ($allcohorts as $c) {
            $cohorts[$c->id] = format_string($c->name, true, ['context' => context::instance_by_id($c->contextid)]);
            if ($c->idnumber) {
                $cohorts[$c->id] .= ' ['.s($c->idnumber).']';
            }
        }
        if ($instance->customint5 && !isset($allcohorts[$instance->customint5])) {
            // Somebody deleted a cohort, better keep the wrong value so that random ppl can not enrol.
            $cohorts[$instance->customint5] = get_string('unknowncohort', 'cohort', $instance->customint5);
        }
        return $cohorts;
    }
    /**
     * Returns the list of currencies that the payment subsystem supports and therefore we can work with.
     *
     * @param int $account The payment account id if exist.
     * @return array[currencycode => currencyname]
     */
    public static function get_possible_currencies($account = null) {
        $codes = [];
        if (class_exists('\core_payment\helper')) {
            $codes = \core_payment\helper::get_supported_currencies();
        }

        $currencies = [];
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }

        uasort($currencies, function($a, $b) {
            return strcmp($a, $b);
        });

        // Adding custom currency in case of there is no available payment gateway or customize the wallet.
        if (empty($currencies) || empty($account)) {
            $config = config::make();
            $customcurrency = $config->customcurrency ?? get_string('MWC', 'enrol_wallet');
            $cc = $config->customcurrencycode ?? '';
            // Don't override standard currencies.
            if (!array_key_exists($cc, $currencies) || $cc === '' || $cc === 'MWC') {
                $currencies[$cc] = $customcurrency;
            }
        }
        return $currencies;
    }
    /**
     * Gets a list of roles that this user can assign for the course as the default for wallet enrolment.
     *
     * @param context $context the context.
     * @param integer $defaultrole the id of the role that is set as the default for wallet enrolment
     * @return array index is the role id, value is the role name
     */
    public static function extend_assignable_roles($context, $defaultrole) {
        global $DB;

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        if (!isset($roles[$defaultrole])) {
            if ($role = $DB->get_record('role', ['id' => $defaultrole])) {
                $roles[$defaultrole] = role_get_name($role, $context, ROLENAME_BOTH);
            }
        }

        return $roles;
    }

    /**
     * Returns the options for the action to take when a user is no longer enrolled in a course.
     *
     * @return array
     */
    public static function get_expire_actions_options() {
        return [
            ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
            ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
            ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
        ];
    }
    /**
     * Returns the options for the discount behavior.
     * This defines how the discount will be applied when multiple coupons are used.
     * The options are:
     * - Sequential: Apply each coupon one after the other, reducing the total amount.
     * - Sum: Sum the discounts of all coupons and apply the total discount to the final amount.
     * - Max: Apply the maximum discount from all coupons to the final amount.
     * @return string[]
     */
    public static function get_discount_behavior_options() {
        return [
            instance::B_SEQ => get_string('discount_behavior_sequential', 'enrol_wallet'),
            instance::B_SUM => get_string('discount_behavior_sum', 'enrol_wallet'),
            instance::B_MAX => get_string('discount_behavior_max', 'enrol_wallet'),
        ];
    }
}
