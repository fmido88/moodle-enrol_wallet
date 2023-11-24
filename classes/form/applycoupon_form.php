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

/** Form to apply coupons in the enrolment page.
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/** Form to apply coupons.
 *
 */
class applycoupon_form extends \moodleform {
    /**
     * Overriding this function to get unique form id for multiple wallet enrollments,
     * or multiple wallet activity restriction.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $data = (object)$this->_customdata;
        if (!empty($data->id)) {
            $formid = $data->id.'_'.get_class($this);

        } else if (!empty($data->cmid)) {
            $formid = $data->cmid.'_'.get_class($this);

        } else if (!empty($data->sectionid)) {
            $formid = $data->sectionid.'_'.get_class($this);

        } else {
            $formid = parent::get_form_identifier();
        }

        return $formid;
    }

    /**
     * Form definition. Abstract method - always override!
     * @return void
     */
    public function definition() {
        global $USER;
        $mform = $this->_form;
        $instance = $this->_customdata->instance;
        $url = new \moodle_url('/course/view.php', ['id' => $instance->courseid]);

        $wallet = enrol_get_plugin('wallet');
        $coupon = $wallet->check_discount_coupon();
        $coupongroup = [];
        $cancel = optional_param('cancel', false, PARAM_BOOL);
        $coupondata = \enrol_wallet\transactions::get_coupon_value($coupon, $USER->id, $instance->id);

        $type = $coupondata['type'] ?? null;
        $validate = false; // Validation for percentage discount coupons only.
        if (!$cancel && !empty($coupon) && $type == 'percent') {
            $area = [
                'instanceid' => $instance->id ?? null,
                'cmid'       => $instance->cmid ?? null,
                'sectionid'  => $instance->sectionid ?? null,
            ];
            $validate = \enrol_wallet\transactions::validate_coupon($coupondata, $area);
        } else if ($cancel) {
            if (!empty($_SESSION['coupon'])) {
                $_SESSION['coupon'] = '';
                unset($_SESSION['coupon']);
            }
        }

        if ($validate === true) {
            $html = html_writer::span(get_string('coupon_code_applied', 'enrol_wallet', $coupon));
            $coupongroup[] = $mform->createElement('html', $html);
            $coupongroup[] = $mform->createElement('cancel');
            $coupongroup[] = $mform->createElement('hidden', 'coupon');

        } else {
            $coupongroup[] = $mform->createElement('text', 'coupon', get_string('applycoupon', 'enrol_wallet'), '"maxlength"="50"');
            $coupongroup[] = $mform->createElement('submit', 'submitcoupon', get_string('applycoupon', 'enrol_wallet'));
        }

        $mform->setType('coupon', PARAM_TEXT);

        if (empty($instance->cmid) && empty($instance->sectionid)) {
            $mform->addGroup($coupongroup, 'applycoupon', get_string('applycoupon', 'enrol_wallet'), null, false);
            $mform->addHelpButton('applycoupon', 'applycoupon', 'enrol_wallet');
        } else {
            $mform->addGroup($coupongroup, 'coupons', null, null, false);
        }

        if (!empty($instance->id)) {
            $mform->addElement('hidden', 'instanceid');
            $mform->setType('instanceid', PARAM_INT);
            $mform->setDefault('instanceid', $instance->id);
        }

        if (!empty($instance->cmid)) {
            $mform->addElement('hidden', 'cmid');
            $mform->setType('cmid', PARAM_INT);
            $mform->setDefault('cmid', $instance->cmid);
        }

        if (!empty($instance->sectionid)) {
            $mform->addElement('hidden', 'sectionid');
            $mform->setType('sectionid', PARAM_INT);
            $mform->setDefault('sectionid', $instance->sectionid);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'url');
        $mform->setType('url', PARAM_LOCALURL);
        $mform->setDefault('url', $url->out(false));

        $this->set_display_vertical();
    }

    /**
     * Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     */
    public function validation($data, $files) {

        global $DB, $USER;
        $errors = parent::validation($data, $files);

        $area = [];
        if (!empty($data['instanceid'])) {
            $area['instanceid'] = $data['instanceid'];
            $instanceid = $data['instanceid'];
        } else {
            $instanceid = 0;
        }

        if (!empty($data['cmid'])) {
            $area['cmid'] = $data['cmid'];
        }

        if (!empty($data['sectionid'])) {
            $area['sectionid'] = $data['sectionid'];
        }

        $coupon = $data['coupon'];
        $coupondata = \enrol_wallet\transactions::get_coupon_value($coupon, $USER->id, $instanceid);

        $validate = \enrol_wallet\transactions::validate_coupon($coupondata, $area);
        if ($validate !== true) {
            $errors['applycoupon'] = $validate;
            $errors['coupons'] = $validate;
        }

        return $errors;
    }
}
