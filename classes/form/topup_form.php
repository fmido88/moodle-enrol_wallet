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
class topup_form extends \moodleform {

    /**
     * Form definition. Abstract method - always override!
     * @return void
     */
    public function definition() {
        global $DB, $PAGE;

        $instance = $this->_customdata->instance;

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

        $attr = !empty($i) ? ['id' => 'topup-value', 'onkeyup' => 'calculateCharge()', 'onchange' => 'calculateCharge()'] : [];
        $mform->addElement('text', 'value', get_string('topupvalue', 'enrol_wallet'), $attr);
        $mform->setType('value', PARAM_FLOAT);
        $mform->addHelpButton('value', 'topupvalue', 'enrol_wallet');
        $mform->addRule('value', get_string('invalidvalue', 'enrol_wallet'), 'numeric', null, 'client');
        $mform->addRule('value', get_string('charger_novalue', 'enrol_wallet'), 'required', null, 'client');
        $mform->addRule('value', get_string('charger_novalue', 'enrol_wallet'), 'nonzero', null, 'client');

        if (!empty($i)) {
            $attr = ['id' => 'topup-value-discount', 'onkeyup' => 'calculateBefore()', 'onchange' => 'calculateBefore()'];
            $mform->addElement('text', 'value-after', get_string('topupafterdiscount', 'enrol_wallet'), $attr);
            $mform->setType('value-after', PARAM_FLOAT);
            $mform->addHelpButton('value-after', 'topupafterdiscount', 'enrol_wallet');
        }

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $instance->courseid);

        $mform->addElement('hidden', 'currency');
        $mform->setType('currency', PARAM_INT);
        $mform->setDefault('currency', $instance->currency);

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setDefault('instanceid', $instance->id);

        $mform->addElement('hidden', 'account');
        $mform->setType('account', PARAM_INT);
        $mform->setDefault('account', $instance->customint1);

        $mform->addElement('hidden', 'sesskey');
        $mform->setType('sesskey', PARAM_TEXT);
        $mform->setDefault('sesskey', sesskey());

        if (empty($instance->courseid) || $instance->courseid == SITEID) {
            $mform->addElement('hidden', 'return');
            $mform->setType('return', PARAM_LOCALURL);
            $mform->setDefault('return', $PAGE->url);
        }

        if (!empty($i)) {
            // Add some js code to display the actual value to charge the wallet with.
            $js = <<<JS
                function calculateCharge() {
                    var value = parseFloat(document.getElementById("topup-value").value);

                    var maxDiscount = 0;
                    for (var i = 1; i <= '$i'; i++) {
                        var discount = parseFloat(document.getElementById("discounted-value["+ i +"]").value);
                        var condition = parseFloat(document.getElementById("discount-condition["+ i +"]").value);

                        if (value >= condition && discount > maxDiscount) {
                            maxDiscount = discount;
                        }
                    }

                    var calculatedValue = value - (value * maxDiscount);
                    document.getElementById("topup-value-discount").value = calculatedValue;
                }

                function calculateBefore() {
                    var value = parseFloat(document.getElementById("topup-value-discount").value);

                    var maxDiscount = 0;
                    for (var i = 1; i <= '$i'; i++) {
                        var discount = parseFloat(document.getElementById("discounted-value["+ i +"]").value);
                        var condition = parseFloat(document.getElementById("discount-condition["+ i +"]").value);

                        var valueBefore = value / (1 - discount);
                        if (valueBefore >= condition && discount > maxDiscount) {
                            maxDiscount = discount;
                        }
                    }

                    var realValueBefore = value / (1 - maxDiscount);
                    document.getElementById("topup-value").value = realValueBefore;
                }
            JS;
            $mform->addElement('html', '<script>'.$js.'</script>');
        }

        $this->add_action_buttons(false, get_string('topup', 'enrol_wallet'));
        $this->set_display_vertical();
    }
}
