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

namespace enrol_wallet\local\restriction;

use context;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\options;
use MoodleQuickForm;
use stdClass;

/**
 * Class cohorts
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohorts {
    /**
     * Check if the instance is restricted by cohort member.
     * @param stdClass $instance
     * @return bool|string
     */
    public static function is_restricted(stdClass $instance) {
        global $CFG, $USER, $DB;
        if ($instance->customint5) {
            require_once("$CFG->dirroot/cohort/lib.php");
            if (!cohort_is_member($instance->customint5, $USER->id)) {
                $cohort = $DB->get_record('cohort', ['id' => $instance->customint5]);
                if ($cohort) {
                    $a = format_string($cohort->name, true, ['context' => context::instance_by_id($cohort->contextid)]);
                    return markdown_to_html(get_string('cohortnonmemberinfo', 'enrol_wallet', $a));
                }
            }
        }
        return false;
    }
    /**
     * Adding another course restriction options to enrolment edit form.
     * @param \MoodleQuickForm $mform
     * @param instance|stdClass $instance
     * @param context $context
     * @return void
     */
    public static function add_to_edit_form(MoodleQuickForm $mform, stdClass $instance, context $context) {
        // Cohort restriction.
        $cohorts = options::get_cohorts_options($instance, $context);
        if (\count($cohorts) > 1) {
            $mform->addElement('select', 'customint5', get_string('cohortonly', 'enrol_wallet'), $cohorts);
            $mform->addHelpButton('customint5', 'cohortonly', 'enrol_wallet');
        } else {
            $mform->addElement('hidden', 'customint5');
            $mform->setType('customint5', PARAM_INT);
            $mform->setConstant('customint5', 0);
        }
    }
}
