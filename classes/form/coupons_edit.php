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

/** Enrollment form Appear when the user's balance is sufficient for enrollment.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

use enrol_wallet\local\coupons\types\base as type_base;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Enrollment form.
 *
 */
class coupons_edit extends \moodleform {
    /**
     * definition.
     * @return void
     */
    public function definition() {
        global $DB;
        $mform = $this->_form;
        $id = $this->_customdata['id'];

        $record = $DB->get_record('enrol_wallet_coupons', ['id' => $id]);
        $coupon = type_base::make($record);

        $mform->addElement('text', 'code', get_string('coupon_code', 'enrol_wallet'));
        $mform->setType('code', PARAM_TEXT);
        $mform->addHelpButton('code', 'coupon_code', 'enrol_wallet');

        $types = type_base::get_coupons_options(true);
        $mform->addElement('select', 'type', get_string('coupon_type', 'enrol_wallet'), $types);
        $mform->addHelpButton('type', 'coupon_type', 'enrol_wallet');

        $mform->addElement('text', 'value', get_string('coupon_value', 'enrol_wallet'));
        $mform->setType('value', PARAM_FLOAT);
        $mform->addHelpButton('value', 'coupon_value', 'enrol_wallet');
        $mform->hideIf('value', 'type', 'eq', 'enrol');

        $categories = \core_course_category::get_all();
        $catoptions = [];

        foreach ($categories as $category) {
            $catoptions[$category->id] = $category->get_nested_name(false);
        }
        $mform->addElement('select', 'category', get_string('category'), $catoptions);
        $mform->addHelpButton('category', 'category_options', 'enrol_wallet');
        $mform->hideIf('category', 'type', 'neq', 'category');

        $courses = get_courses();
        $courseoptions = [];

        foreach ($courses as $course) {
            $courseoptions[$course->id] = $course->fullname;
        }
        $mform->addElement('autocomplete', 'courses', get_string('courses'), $courseoptions, ['multiple' => true]);
        $mform->addHelpButton('courses', 'courses_options', 'enrol_wallet');
        $mform->hideIf('courses', 'type', 'neq', 'enrol');

        $mform->addElement('text', 'maxusage', get_string('coupons_maxusage', 'enrol_wallet'));
        $mform->setType('maxusage', PARAM_INT);
        $mform->addHelpButton('maxusage', 'coupons_maxusage', 'enrol_wallet');

        $mform->addElement('static', 'usetimes', get_string('coupon_usetimes', 'enrol_wallet'), $coupon->get_total_use());

        $mform->addElement('checkbox', 'usetimesreset', get_string('coupon_resetusetime', 'enrol_wallet'));
        $mform->addHelpButton('usetimesreset', 'coupon_resetusetime', 'enrol_wallet');

        $mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), ['optional' => true]);

        $mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), ['optional' => true]);

        $mform->addElement('submit', 'confirm', get_string('confirm'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $id);

        $mform->setDefaults((array)$coupon->get_record());
    }

    /**
     * Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     * returns of "element_name"=>"error_description" if there are errors,
     * or an empty array if everything is OK (true allowed for backwards compatibility too).
     *
     * @param  array $data  array of data
     * @param  array $files array of files
     * @return array array of errors
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $data['method'] = 'edit';
        type_base::validate_generator_form($data, $errors);

        return $errors;
    }
}
