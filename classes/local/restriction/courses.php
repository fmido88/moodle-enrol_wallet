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
use context_course;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\options;
use MoodleQuickForm;
use stdClass;

/**
 * Class courses
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses {
    /**
     * Check if there is restriction according to other courses enrolment.
     * Return false if not restricted and string with required courses names in case if restricted.
     * @param instance|stdClass $instance
     * @return false|string
     */
    public static function is_restricted(instance|stdClass $instance): false|string {
        global $DB;
        if (!empty($instance->customchar3) && !empty($instance->customint7)) {
            $courses = explode(',', $instance->customchar3);
            $restrict = false;
            $count = 0;
            $total = 0;
            $notenrolled = [];
            foreach ($courses as $courseid) {
                if (!$DB->record_exists('course', ['id' => $courseid])) {
                    continue;
                }

                $total++;
                $coursectx = context_course::instance($courseid);
                if (!is_enrolled($coursectx)) {
                    $restrict = true;
                    // The user is not enrolled in the required course.
                    $notenrolled[] = get_course($courseid)->fullname;
                } else {
                    // Count the courses which the user enrolled in.
                    $count++;
                }
            }

            $coursesnames = '(' . implode(', ', $notenrolled) . ')';
            // In case that the course creator choose a higher number than the selected courses.
            $limit = min($total, $instance->customint7);
            if ($restrict && $count < $limit) {
                return $coursesnames;
            }
        }
        return false;
    }
    /**
     * Adding another course restriction options to enrolment edit form.
     * @param \MoodleQuickForm $mform
     * @param stdClass $instance
     * @return void
     */
    public static function add_to_edit_form(MoodleQuickForm $mform, stdClass $instance) {
        $coursesoptions = options::get_courses_options($instance->courseid);
        if (!empty($coursesoptions)) {
            $count = count($coursesoptions);

            $options = [];
            for ($i = 0; $i <= $count; $i++) {
                $options[$i] = $i;
            }
            $select = $mform->addElement('select', 'customint7', get_string('coursesrestriction_num', 'enrol_wallet'), $options);
            $select->setMultiple(false);
            $mform->addHelpButton('customint7', 'coursesrestriction_num', 'enrol_wallet');

            $mform->addElement('hidden', 'customchar3', '', ['id' => 'wallet_customchar3']);
            $mform->setType('customchar3', PARAM_TEXT);

            $attributes = [
                'id'       => 'wallet_courserestriction',
                'onChange' => 'restrictByCourse()',
            ];
            $restrictionlable = get_string('coursesrestriction', 'enrol_wallet');
            $select = $mform->addElement('select', 'courserestriction', $restrictionlable, $coursesoptions, $attributes);
            $select->setMultiple(true);
            $mform->addHelpButton('courserestriction', 'coursesrestriction', 'enrol_wallet');
            $mform->hideIf('courserestriction', 'customint7', 'eq', 0);
            if (!empty($instance->customchar3)) {
                $mform->setDefault('courserestriction', explode(',', $instance->customchar3));
            }
        } else {
            $mform->addElement('hidden', 'customint7');
            $mform->setType('customint7', PARAM_INT);
            $mform->setConstant('customint7', 0);

            $mform->addElement('hidden', 'customchar3');
            $mform->setType('customchar3', PARAM_TEXT);
            $mform->setConstant('customchar3', '');
        }

        if (!empty($coursesoptions)) {
            // Add some js code to set the value of customchar3 element for the restriction course enrolment.
            $js = <<<JS
                    function restrictByCourse() {
                        var textelement = document.getElementById("wallet_customchar3");
                        var courseArray = document.getElementById("wallet_courserestriction").selectedOptions;
                        var selectedValues = [];
                        for (var i = 0; i < courseArray.length; i++) {
                            selectedValues.push(courseArray[i].value);
                        }
                        // Set the value of the hidden input field to the comma-separated string of selected values.
                        textelement.value = selectedValues.join(",");
                    }
                JS;
            $mform->addElement('html', '<script>'.$js.'</script>');
        }
    }
}
