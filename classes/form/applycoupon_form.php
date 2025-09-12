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
 * Form to apply coupons in the enrolment page.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

use core\exception\moodle_exception;
use core\url;
use enrol_wallet\local\config;
use enrol_wallet\local\coupons\coupons;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * Form to apply coupons.
 *
 * @package enrol_wallet
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
        global $PAGE;
        $mform = $this->_form;
        $instance = ((object)$this->_customdata)->instance;
        $url = ((object)$this->_customdata)->url ?? $instance->url ?? '';
        if (empty($url) && $PAGE->has_set_url()) {
            $url = $PAGE->url;
        }
        $coupon = coupons::check_discount_coupon();
        $coupongroup = [];
        $cancel = optional_param('cancel', false, PARAM_BOOL);

        $validate = false; // Validation for percentage discount coupons only.
        if (!$cancel && !empty($coupon)) {
            $couponobj = new coupons($coupon);
            if ($couponobj->type == coupons::DISCOUNT) {
                $areaid = $instance->id ?? $instance->cmid ?? $instance->sectionid ?? 0;
                if (!empty($instance->id)) {
                    $area = coupons::AREA_ENROL;
                } else if (!empty($instance->cmid)) {
                    $area = coupons::AREA_CM;
                } else if (!empty($instance->sectionid)) {
                    $area = coupons::AREA_SECTION;
                } else {
                    $area = coupons::AREA_TOPUP;
                }
                $validate = $couponobj->validate_coupon($area, $areaid);
            }
        } else if ($cancel) {
            if (!empty($_SESSION['coupon'])) {
                $_SESSION['coupon'] = '';
                unset($_SESSION['coupon']);
            }
        }

        if (true === $validate) {
            $html = \html_writer::span(get_string('coupon_code_applied', 'enrol_wallet', $coupon));
            $coupongroup[] = $mform->createElement('html', $html);
            $coupongroup[] = $mform->createElement('cancel');
            $coupongroup[] = $mform->createElement('hidden', 'coupon');

        } else {
            $coupongroup[] = $mform->createElement('text', 'coupon', get_string('applycoupon', 'enrol_wallet'), '"maxlength"="50"');
            $coupongroup[] = $mform->createElement('submit', 'submitcoupon', get_string('applycoupon', 'enrol_wallet'));
        }

        $mform->setType('coupon', PARAM_ALPHANUMEXT);

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

        if (!empty($instance->courseid) && $instance->courseid !== SITEID) {
            $mform->addElement('hidden', 'courseid');
            $mform->setType('courseid', PARAM_INT);
            $mform->setDefault('courseid', $instance->courseid);
        }

        if (!empty($url)) {
            if (!($url instanceof \moodle_url)) {
                $url = new \moodle_url($url);
            }
            $mform->addElement('hidden', 'url');
            $mform->setType('url', PARAM_LOCALURL);
            $mform->setDefault('url', $url->out(false));
        }

        $error = optional_param('error', null, PARAM_TEXT);
        if (!empty($error)) {
            $mform->setElementError('coupon', $error);
        }
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
        $errors = parent::validation($data, $files);

        if (!empty($data['instanceid'])) {
            $area = coupons::AREA_ENROL;
            $areaid = $data['instanceid'];
        } else if (!empty($data['cmid'])) {
            $area = coupons::AREA_CM;
            $areaid = $data['cmid'];
        } else if (!empty($data['sectionid'])) {
            $area = coupons::AREA_SECTION;
            $areaid = $data['sectionid'];
        } else {
            $area = coupons::AREA_TOPUP;
            $areaid = 0;
        }

        $code = $data['coupon'];
        $coupon = new coupons($code);

        $validate = $coupon->validate_coupon($area, $areaid);
        if ($validate !== true) {
            $errors['applycoupon'] = $validate;
            $errors['coupons'] = $validate;
        }

        return $errors;
    }

    /**
     * Process submitted coupon data.
     * @param stdClass|array $data
     * @return string|url
     */
    public function process_coupon_data($data = null) {
        global $DB;

        $data ??= $this->get_data() ?? [];

        $data = (array)$data;

        $cancel = $data['cancel'] ?? $this->optional_param('cancel', false, PARAM_BOOL);
        $url = $data['url'] ?? '';
        $data['coupon'] ??= $this->optional_param('coupon', null, PARAM_ALPHANUM);

        $redirecturl = empty($url) ? new url('/') : new url($url);

        if ($cancel) {
            // Important to unset the session coupon.
            coupons::unset_session_coupon();
            $redirecturl->remove_params('coupon', 'submitcoupon');
            redirect($redirecturl);
        }

        if (empty($data['coupon'])) {
            return null;
        }

        $couponutil = new coupons($data['coupon']);

        if (!empty($data['instanceid'])) {
            $area = $couponutil::AREA_ENROL;
            $areaid = $data['instanceid'];
        } else if (!empty($data['cmid'])) {
            $area = $couponutil::AREA_CM;
            $areaid = $data['cmid'];
        } else if (!empty($data['sectionid'])) {
            $area = $couponutil::AREA_SECTION;
            $areaid = $data['sectionid'];
        } else {
            $area = $couponutil::AREA_TOPUP;
            $areaid = 0;
        }

        $couponutil->validate_coupon($area, $areaid);
        if ($error = $couponutil->has_error()) {
            $msg = get_string('coupon_applyerror', 'enrol_wallet', $error ?? '');
            $msgtype = 'error';
            // This mean that the function return error.
        } else {

            $value = $couponutil->get_value();
            $type = $couponutil->type;
            switch($area) {
                case coupons::AREA_ENROL:
                    $id = $DB->get_field('enrol', 'courseid', ['id' => $areaid, 'enrol' => 'wallet'], IGNORE_MISSING);
                    if (!empty($id)) {
                        $redirecturl = new url('/enrol/index.php', ['id' => $id, 'coupon' => $couponutil->code]);
                    }
                    break;
                case coupons::AREA_CM:
                case coupons::AREA_SECTION:
                    if (!empty($url)) {
                        $redirecturl = $url . '&' . http_build_query(['coupon' => $couponutil->code]);
                    }
                    break;
                default:
            }

            $couponutil->apply_coupon($area, $areaid);
            // Check the type to determine what to do.
            if ($type == coupons::FIXED) {
                // Apply the coupon code to add its value to the user's wallet and enrol if value is enough.
                $currency = config::instance()->currency;
                $a = [
                    'value'    => $value,
                    'currency' => $currency,
                ];
                $msg = get_string('coupon_applyfixed', 'enrol_wallet', $a);
                $msgtype = 'success';

            } else if (($type == coupons::DISCOUNT) && ($area == coupons::AREA_ENROL)) {
                // Percentage discount coupons applied in enrolment.
                if (!empty($id)) {
                    $msg = get_string('coupon_applydiscount', 'enrol_wallet', $value);
                    $msgtype = 'success';
                } else {
                    // Shouldn't happen.
                    $msg = get_string('coupon_applynocourse', 'enrol_wallet');
                    $msgtype = 'error';
                }

            } else if ($type == coupons::DISCOUNT
                        && in_array($area, [coupons::AREA_SECTION, coupons::AREA_CM])) {

                // This is the case when the coupon applied by availability wallet.

                $msg = get_string('coupon_applydiscount', 'enrol_wallet', $value);
                $msgtype = 'success';

            } else if ($type == coupons::CATEGORY) {
                // This type of coupons is restricted to be used in certain categories.
                $msg = get_string('coupon_categoryapplied', 'enrol_wallet');
                $msgtype = 'success';

            } else if ($type == coupons::AREA_ENROL) {
                // Apply the coupon and enrol the user.
                $msg = get_string('coupon_enrolapplied', 'enrol_wallet');
                $msgtype = 'success';
            }
        }

        if ($msgtype === 'error') {
            $redirecturl->param('error', $msg);
        }

        \core\notification::add($msg, $msgtype);
        return $redirecturl;
    }
}
