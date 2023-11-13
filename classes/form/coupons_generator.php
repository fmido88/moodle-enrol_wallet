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
class coupons_generator extends \moodleform {

    /**
     * definition
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $method = [
            'single' => get_string('singlecoupon', 'enrol_wallet'),
            'random' => get_string('randomcoupons', 'enrol_wallet'),
        ];
        $mform->addElement('select', 'method', get_string('coupon_generation_method', 'enrol_wallet'), $method);
        $mform->addHelpButton('method', 'coupon_generation_method', 'enrol_wallet');

        $mform->addElement('text', 'code', get_string('coupon_code', 'enrol_wallet'));
        $mform->setType('code', PARAM_TEXT);
        $mform->addHelpButton('code', 'coupon_code', 'enrol_wallet');
        $mform->hideIf('code' , 'method', 'eq', 'random');

        $types = [
            'fixed'    => get_string('fixedvaluecoupon', 'enrol_wallet'),
            'percent'  => get_string('percentdiscountcoupon', 'enrol_wallet'),
            'enrol'    => get_string('enrolcoupon', 'enrol_wallet'),
            'category' => get_string('categorycoupon', 'enrol_wallet'),
        ];
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
        $mform->addElement('select', 'category',  get_string('category'),  $catoptions);
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

        $mform->addElement('text', 'number', get_string('coupons_number', 'enrol_wallet'));
        $mform->setType('number', PARAM_INT);
        $mform->addHelpButton('number', 'coupons_number', 'enrol_wallet');
        $mform->setDefault('number', 1);
        $mform->disabledIf('number' , 'method', 'eq', 'single');

        $mform->addElement('text', 'length', get_string('coupons_length', 'enrol_wallet'));
        $mform->setType('length', PARAM_INT);
        $mform->addHelpButton('length', 'coupons_length', 'enrol_wallet');
        $mform->setDefault('length', 8);
        $mform->hideIf('length' , 'method', 'eq', 'single');

        $mform->addElement('text', 'maxusage', get_string('coupons_maxusage', 'enrol_wallet'));
        $mform->setType('maxusage', PARAM_INT);
        $mform->addHelpButton('maxusage', 'coupons_maxusage', 'enrol_wallet');
        $mform->setDefault('maxusage', 1);

        $mform->addElement('text', 'maxperuser', get_string('coupons_maxperuser', 'enrol_wallet'));
        $mform->setType('maxperuser', PARAM_INT);
        $mform->addHelpButton('maxperuser', 'coupons_maxperuser', 'enrol_wallet');
        $mform->setDefault('maxperuser', 0);

        $mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), ['optional' => true]);

        $group = [];
        $group[] = $mform->createElement('checkbox', 'upper', get_string('upperletters', 'enrol_wallet'));
        $group[] = $mform->createElement('checkbox', 'lower', get_string('lowerletters', 'enrol_wallet'));
        $group[] = $mform->createElement('checkbox', 'digits', get_string('digits', 'enrol_wallet'));

        $mform->addGroup($group, 'characters', get_string('characters', 'enrol_wallet'), '-');
        $mform->addHelpButton('characters', 'characters', 'enrol_wallet');
        $mform->hideIf('characters' , 'method', 'eq', 'single');

        $mform->setDefault('characters[upper]', 1);
        $mform->setDefault('characters[lower]', 1);
        $mform->setDefault('characters[digits]', 1);

        $mform->addElement('submit', 'submit', get_string('submit_coupongenerator', 'enrol_wallet'));

        $mform->addElement('hidden', 'sesskey');
        $mform->setType('sesskey', PARAM_TEXT);
        $mform->setDefault('sesskey', sesskey());
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

        if ($data['method'] == 'single') {
            if (empty($data['code'])) {
                $errors['code'] = get_string('coupon_code_error', 'enrol_wallet');
            } else if ($DB->record_exists('enrol_wallet_coupons', ['code' => $data['code']])) {
                $errors['code'] = get_string('couponexist', 'enrol_wallet');
            }
        }

        if ($data['method'] == 'random' && empty($data['number'])) {
            $errors['number'] = get_string('coupon_generator_nonumber', 'enrol_wallet');
        }

        if (!empty($data['maxperuser']) && $data['maxperuser'] > $data['maxusage']) {
            $errors['maxperuser'] = get_string('coupon_generator_peruser_gt_max', 'enrol_wallet');
            $errors['maxusage'] = get_string('coupon_generator_peruser_gt_max', 'enrol_wallet');
        }
        return $errors;
    }
}
