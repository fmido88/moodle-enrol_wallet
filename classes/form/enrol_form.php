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
class enrol_form extends \moodleform {
    /**
     * instance
     * @var object
     */
    protected $instance;
    /**
     * toomany
     * @var
     */
    protected $toomany = false;

    /**
     * Overriding this function to get unique form id for multiple wallet enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    /**
     * definition
     * @return void
     */
    public function definition() {
        global $USER;
        $instance = (object)$this->_customdata;
        $this->instance = $instance;

        $mform = $this->_form;
        $costbefore = $instance->cost;
        $currency = $instance->currency;
        $plugin = enrol_get_plugin('wallet');

        $coupon = $plugin->check_discount_coupon();
        $costafter = $plugin->get_cost_after_discount($USER->id, $instance, $coupon);

        $balance = \enrol_wallet\transactions::get_user_balance($USER->id);

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'walletheader', $heading);

        $a = [
            'credit_cost'   => $costbefore,
            'user_balance'   => $balance,
            'after_discount' => $costafter,
            'currency'       => $currency,
        ];
        // Display cost and balance.
        if ($balance >= $costafter) {
            if ($costafter == $costbefore) {
                $mform->addElement('html', get_string('checkout', 'enrol_wallet', $a));
            } else {
                $mform->addElement('html', get_string('checkout_discounted', 'enrol_wallet', $a));
            }
        } else {
            $a['borrow'] = $costafter - $balance;
            if ($costafter == $costbefore) {
                $mform->addElement('html', get_string('checkout_borrow', 'enrol_wallet', $a));
            } else {
                $mform->addElement('html', get_string('checkout_borrow_discounted', 'enrol_wallet', $a));
            }
        }

        // Display refund policy if enabled.
        $refund = get_config('enrol_wallet', 'unenrolrefund');
        $policy = get_config('enrol_wallet', 'unenrolrefundpolicy');
        if (!empty($refund) && !empty($policy)) {
            $period = get_config('enrol_wallet', 'unenrolrefundperiod');
            $period = (!empty($period)) ? $period / DAYSECS : '('.get_string('unlimited').')';

            $fee = get_config('enrol_wallet', 'unenrolrefundfee');
            $fee = !(empty($fee)) ? $fee : 0;

            $policy = str_replace('{fee}', $fee, $policy);
            $policy = str_replace('{period}', $period, $policy);

            $mform->addElement('html', $policy);
        }

        $this->add_action_buttons(false, get_string('purchase', 'enrol_wallet'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);

        if (!empty($coupon)) {
            $mform->addElement('hidden', 'coupon');
            $mform->setType('coupon', PARAM_TEXT);
            $mform->setDefault('coupon', $coupon);
        }
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
        $errors = parent::validation($data, $files);

        if ($this->toomany) {
            $errors['notice'] = get_string('error');
        }

        return $errors;
    }
}
