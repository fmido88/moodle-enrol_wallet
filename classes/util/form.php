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

namespace enrol_wallet\util;

/**
 * Class form.
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form {
    /**
     * Add a user selection autocomplete element to a form used by enrol_manual.
     * @param  \MoodleQuickForm $mform
     * @param  string           $elementname element name
     * @param  string           $visiblename form element visible name
     * @param  string|null      $elementid   html element id
     * @param  bool             $multi       multiple selection
     * @return void
     */
    public static function add_user_auto_complete_selection(
        \MoodleQuickForm &$mform,
        $elementname,
        $visiblename = '',
        $elementid = null,
        $multi = false
    ) {
        global $USER, $CFG;

        if (empty($visiblename)) {
            $visiblename = get_string('selectusers', 'enrol_manual');
        }

        $courses  = enrol_get_users_courses($USER->id, false);
        $courseid = SITEID;

        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);

            if (has_capability('moodle/course:enrolreview', $context)) {
                $courseid = $course->id;
                break;
            }
        }
        $context = \context_system::instance();
        $options = [
            'ajax'       => 'enrol_manual/form-potential-user-selector',
            'multiple'   => $multi,
            'courseid'   => $courseid,
            'enrolid'    => 0,
            'perpage'    => $CFG->maxusersperpage,
        ];

        if (!empty($elementid)) {
            $options['id'] = $elementid;
        }

        if (class_exists('core_user\fields')) {
            $options['userfields'] = implode(',', \core_user\fields::get_identity_fields($context, true));
        } else {
            require_once($CFG->dirroot . '/enrol/wallet/locallib.php');
            $options['userfields'] = implode(',', enrol_wallet_get_identity_fields($context, true));
        }

        $mform->addElement('autocomplete', $elementname, $visiblename, [], $options);
    }
}
