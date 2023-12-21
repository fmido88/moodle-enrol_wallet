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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/** Enrollment form.
 *
 */
class coupons_edit extends \moodleform {

    /**
     * definition
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $defaults = $this->_customdata;

        $mform->addElement('text', 'code', get_string('coupon_code', 'enrol_wallet'));
        $mform->setType('code', PARAM_TEXT);
        $mform->addHelpButton('code', 'coupon_code', 'enrol_wallet');
        $mform->setDefault('code', $defaults['code']);

        $types = [
            'fixed'    => get_string('fixedvaluecoupon', 'enrol_wallet'),
            'percent'  => get_string('percentdiscountcoupon', 'enrol_wallet'),
            'enrol'    => get_string('enrolcoupon', 'enrol_wallet'),
            'category' => get_string('categorycoupon', 'enrol_wallet'),
        ];
        $mform->addElement('select', 'type', get_string('coupon_type', 'enrol_wallet'), $types);
        $mform->addHelpButton('type', 'coupon_type', 'enrol_wallet');
        $mform->setDefault('type', $defaults['type']);

        $mform->addElement('text', 'value', get_string('coupon_value', 'enrol_wallet'));
        $mform->setType('value', PARAM_FLOAT);
        $mform->addHelpButton('value', 'coupon_value', 'enrol_wallet');
        $mform->hideIf('value', 'type', 'eq', 'enrol');
        $mform->setDefault('value', $defaults['value']);

        $categories = \core_course_category::get_all();
        $catoptions = [];
        foreach ($categories as $category) {
            $catoptions[$category->id] = $category->get_nested_name(false);
        }
        $mform->addElement('select', 'category',  get_string('category'),  $catoptions);
        $mform->addHelpButton('category', 'category_options', 'enrol_wallet');
        $mform->hideIf('category', 'type', 'neq', 'category');
        $mform->setDefault('category', $defaults['category']);

        $courses = get_courses();
        $courseoptions = [];
        foreach ($courses as $course) {
            $courseoptions[$course->id] = $course->fullname;
        }
        $mform->addElement('autocomplete', 'courses', get_string('courses'), $courseoptions, ['multiple' => true]);
        $mform->addHelpButton('courses', 'courses_options', 'enrol_wallet');
        $mform->hideIf('courses', 'type', 'neq', 'enrol');
        if (!empty($defaults['courses'])) {
            $mform->setDefault('courses', explode(',', $defaults['courses']));
        }

        $mform->addElement('text', 'maxusage', get_string('coupons_maxusage', 'enrol_wallet'));
        $mform->setType('maxusage', PARAM_INT);
        $mform->addHelpButton('maxusage', 'coupons_maxusage', 'enrol_wallet');
        $mform->setDefault('maxusage', $defaults['maxusage']);

        $mform->addElement('static', 'usetimes', get_string('coupon_usetimes', 'enrol_wallet'), $defaults['usetimes']);

        $mform->addElement('checkbox', 'usetimesreset', get_string('coupon_resetusetime', 'enrol_wallet'));
        $mform->addHelpButton('usetimesreset', 'coupon_resetusetime', 'enrol_wallet');

        $mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), ['optional' => true]);
        $mform->setDefault('validfrom', $defaults['validfrom']);

        $mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), ['optional' => true]);
        $mform->setDefault('validto', $defaults['validto']);

        $mform->addElement('submit', 'confirm', get_string('confirm'));

        $mform->addElement('hidden', 'sesskey');
        $mform->setType('sesskey', PARAM_TEXT);
        $mform->setDefault('sesskey', sesskey());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $defaults['id']);
    }

    /**
     * Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     * returns of "element_name"=>"error_description" if there are errors,
     * or an empty array if everything is OK (true allowed for backwards compatibility too).
     *
     * @param array $data array of data
     * @param array $files array of files
     * @return array array of errors
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $type = $data['type'];

        if (empty($data['value']) && $type != 'enrol') {
            $errors['value'] = get_string('coupons_valueerror', 'enrol_wallet');
        }

        if ($type == 'enrol' && empty($data['courses'])) {
            $errors['courses'] = get_string('coupons_courseserror', 'enrol_wallet');
        }

        if ($type == 'category' && empty($data['category'])) {
            $errors['category'] = get_string('coupons_category_error', 'enrol_wallet');
        }

        if ($type == 'percent' && $data['value'] > 100) {
            $errors['value'] = get_string('invalidpercentcoupon', 'enrol_wallet');
        }

        // Check if there is another code similar to this one.
        $params = [
            'id'   => $data['id'],
            'code' => $data['code'],
        ];
        $select = 'code = :code AND id != :id';
        if (empty($data['code'])) {
            $errors['code'] = get_string('coupon_code_error', 'enrol_wallet');
        } else if ($DB->record_exists_select('enrol_wallet_coupons', $select, $params)) {
            $errors['code'] = get_string('coupon_exist', 'enrol_wallet');
        }

        return $errors;
    }
}
