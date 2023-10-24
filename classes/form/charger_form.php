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

/** The form that able the user to topup their wallet using payment gateways.
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * The form that able the user to topup their wallet using payment gateways.
 */
class charger_form extends \moodleform {

    /**
     * Form definition. Abstract method - always override!
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $USER;

        $mform = $this->_form;
        // Check the conditional discount.
        $enabled = get_config('enrol_wallet', 'conditionaldiscount_apply');
        if (!empty($enabled)) {
            $params = [
                'time1' => time(),
                'time2' => time(),
            ];
            $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
            $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params);

            $i = 0;
            foreach ($records as $record) {
                $i++;
                // The next two elements only used to pass the values to js code.
                $mform->addElement('hidden', 'discount'.$i, '', ['id' => "discounted-value[$i]"]);
                $mform->setType('discount'.$i, PARAM_FLOAT);
                $mform->setConstant('discount'.$i, $record->percent / 100);

                $mform->addElement('hidden', 'condition'.$i, '', ['id' => "discount-condition[$i]"]);
                $mform->setType('condition'.$i, PARAM_FLOAT);
                $mform->setConstant('condition'.$i, $record->cond);
            }
        }

        $mform->addElement('header', 'main', get_string('chargingoptions', 'enrol_wallet'));

        $operations = [
            'credit'  => 'credit',
            'debit'   => 'debit',
            'balance' => 'balance'
        ];
        $oplabel = get_string('chargingoperation', 'enrol_wallet');
        $attr = !empty($i) ? ['id' => 'charge-operation', 'onchange' => 'calculateCharge()'] : [];
        $mform->addElement('select', 'op', $oplabel, $operations, $attr);

        $valuetitle = get_string('chargingvalue', 'enrol_wallet');
        $attr = !empty($i) ? ['id' => 'charge-value', 'onkeyup' => 'calculateCharge()', 'onchange' => 'calculateCharge()'] : [];
        $mform->addElement('text', 'value', $valuetitle, $attr);
        $mform->setType('value', PARAM_FLOAT);
        $mform->hideIf('value', 'op', 'eq', 'balance');

        if (!empty($enabled)) {
            // Empty div used by js to display the calculated final value.
            $enter = get_string('entervalue', 'enrol_wallet');
            $html = '<div id="calculated-value" style="font-weight: 700;" class="alert alert-warning">'.$enter.'</div>';
            $mform->addElement('html', $html);
        }

        $courses = enrol_get_users_courses($USER->id, false);
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
            'id'         => 'charger-userlist',
            'ajax'       => 'enrol_manual/form-potential-user-selector',
            'multiple'   => false,
            'courseid'   => $courseid,
            'enrolid'    => 0,
            'perpage'    => $CFG->maxusersperpage,
            'userfields' => implode(',', \core_user\fields::get_identity_fields($context, true))
        ];
        $mform->addElement('autocomplete', 'userlist', get_string('selectusers', 'enrol_manual'), [], $options);
        $mform->addRule('userlist', 'select user', 'required', null, 'client');

        $mform->addElement('submit', 'submit', get_string('submit'));

        $mform->addElement('hidden', 'sesskey');
        $mform->setType('sesskey', PARAM_TEXT);
        $mform->setDefault('sesskey', sesskey());

        $charginglabel = get_string('charging_value', 'enrol_wallet');

        if (!empty($i)) {
            // Add some js code to display the actual value to charge the wallet with.
            $js = <<<JS
                function calculateCharge() {
                    var value = parseFloat(document.getElementById("charge-value").value);
                    var op = document.getElementById("charge-operation").value;

                    var maxDiscount = 0;
                    var calculatedValue = value;
                    for (var i = 1; i <= '$i'; i++) {
                        var discount = parseFloat(document.getElementById("discounted-value["+ i +"]").value);
                        var condition = parseFloat(document.getElementById("discount-condition["+ i +"]").value);
                        var valueBefore = value + (value * discount / (1 - discount));

                        if (valueBefore >= condition && discount > maxDiscount) {
                            maxDiscount = discount;
                            var calculatedValue = valueBefore;
                        }
                    }

                    if (op == "credit") {
                        document.getElementById("calculated-value").innerHTML = '$charginglabel' + calculatedValue;
                    } else {
                        document.getElementById("calculated-value").innerHTML = "";
                    }
                }
                JS;

            $mform->addElement('html', '<script>'.$js.'</script>');
        }
        $errors = optional_param_array('errors', null, PARAM_RAW);
        if (!empty($errors)) {
            foreach ($errors as $element => $error) {
                $mform->setElementError($element, $error);
            }
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

        global $DB;
        $errors = parent::validation($data, $files);
        $op = $data['op'];
        if (!in_array($op, ['credit', 'debit', 'balance'])) {
            $errors['op'] = get_string('charger_invalid_operation', 'enrol_wallet');
            return $errors;
        }

        $value  = $data['value'] ?? '';
        $userid = $data['userlist'];
        // No value.
        if (empty($value) && ($op !== 'balance')) {
            $errors['value'] = get_string('charger_novalue', 'enrol_wallet');
        }

        // No user.
        if (empty($userid) || !$DB->record_exists('user', ['id' => $userid])) {
            $errors['userlist'] = get_string('charger_nouser', 'enrol_wallet');
        }

        $transactions = new \enrol_wallet\transactions;
        $before = $transactions->get_user_balance($userid);
        if ($op == 'debit' && $value > $before) {
            // Cannot deduct more than the user's balance.
            $a = ['value' => $value, 'before' => $before];
            $errors['value'] = get_string('charger_debit_err', 'enrol_wallet', $a);
        }

        return $errors;
    }
}
