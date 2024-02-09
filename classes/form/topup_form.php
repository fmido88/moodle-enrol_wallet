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

use enrol_wallet\category\options;
use enrol_wallet\util\balance;

/**
 * The form that able the user to topup their wallet using payment gateways.
 * @package enrol_wallet
 */
class topup_form extends \moodleform {
    /**
     * The unique id of the form.
     * @var string
     */
    protected $formid;

    /**
     * Override the original constructor to set the from id.
     *
     * The constructor function calls the abstract function definition() and it will then
     * process and clean and attempt to validate incoming data.
     *
     * It will call your custom validate method to validate data and will also check any rules
     * you have specified in definition using addRule
     *
     * The name of the form (id attribute of the form) is automatically generated depending on
     * the name you gave the class extending moodleform. You should call your class something
     * like
     *
     * @param mixed $action the action attribute for the form. If empty defaults to auto detect the
     *              current url. If a moodle_url object then outputs params as hidden variables.
     * @param mixed $customdata if your form defintion method needs access to data such as $course
     *              $cm, etc. to construct the form definition then pass it in this array. You can
     *              use globals for somethings.
     * @param string $method if you set this to anything other than 'post' then _GET and _POST will
     *               be merged and used as incoming data to the form.
     * @param string $target target frame for form submission. You will rarely use this. Don't use
     *               it if you don't need to as the target attribute is deprecated in xhtml strict.
     * @param mixed $attributes you can pass a string of html attributes here or an array.
     *               Special attribute 'data-random-ids' will randomise generated elements ids. This
     *               is necessary when there are several forms on the same page.
     *               Special attribute 'data-double-submit-protection' set to 'off' will turn off
     *               double-submit protection JavaScript - this may be necessary if your form sends
     *               downloadable files in response to a submit button, and can't call
     *               \core_form\util::form_download_complete();
     * @param bool $editable
     * @param array $ajaxformdata Forms submitted via ajax, must pass their data here, instead of relying on _GET and _POST.
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '',
                                $attributes = null, $editable = true, $ajaxformdata = null) {
        if (empty($attributes)) {
            $attributes = ['id' => $this->get_form_id()];
        } else if (is_array($attributes)) {
            $attributes['id'] = $this->get_form_id();
        } else {
            $attributes .= ' id="'.$this->get_form_id().'"';
        }
        return parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Create and return the id of the form to be used in js module.
     * @return string
     */
    protected function get_form_id() {
        if (isset($this->formid)) {
            return $this->formid;
        } else {
            $this->formid = $this->get_form_identifier() . '_' . random_string();
            return $this->formid;
        }
    }
    /**
     * Form definition. Abstract method - always override!
     * @return void
     */
    public function definition() {
        global $DB, $PAGE;

        $instance = ((object)$this->_customdata)->instance;

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
                // This element only used to pass the values to js code.
                $discountrule = (object)[
                    'discount' => $record->percent / 100,
                    'condition' => $record->cond,
                    'category' => $record->category ?? 0,
                ];
                $mform->addElement('hidden', 'discount_rule_'.$i);
                $mform->setType('discount_rule_'.$i, PARAM_TEXT);
                $mform->setConstant('discount_rule_'.$i, json_encode($discountrule));
            }
        }
        $balance = new balance;

        $catoptions = [];
        if ($balance->catenabled) {
            $categorytitle = get_string('category');
            if (empty($instance->id)) {
                $catoptions = options::get_all_options_with_discount();
            } else {
                $helper = options::create_from_instance_id($instance->id);
                $catoptions = $helper->get_local_options_with_discounts();
            }
        }
        if (count($catoptions) > 1) {
            $mform->addElement('select', 'category', $categorytitle, $catoptions);
        } else {
            $mform->addElement('hidden', 'category');
            $mform->setType('category', PARAM_INT);
            if (!empty($catoptions)) {
                $mform->setDefault('category', array_key_first($catoptions));
            } else {
                $mform->setDefault('category', 0);
            }
        }

        $mform->addElement('text', 'value', get_string('topupvalue', 'enrol_wallet'));
        $mform->setType('value', PARAM_FLOAT);
        $mform->addHelpButton('value', 'topupvalue', 'enrol_wallet');
        $mform->addRule('value', get_string('invalidvalue', 'enrol_wallet'), 'numeric', null, 'client');
        $mform->addRule('value', get_string('charger_novalue', 'enrol_wallet'), 'required', null, 'client');
        $mform->addRule('value', get_string('charger_novalue', 'enrol_wallet'), 'nonzero', null, 'client');

        if (!empty($i)) {
            $mform->addElement('text', 'value-after', get_string('topupafterdiscount', 'enrol_wallet'));
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

        if (empty($instance->courseid) || $instance->courseid == SITEID) {
            $mform->addElement('hidden', 'return');
            $mform->setType('return', PARAM_LOCALURL);
            $mform->setDefault('return', $PAGE->url);
        }

        if (!empty($i)) {
            // Add some js code to display the actual value to charge the wallet with.
            $args = ['formid' => $this->get_form_id(), 'formType' => 'topup'];
            $PAGE->requires->js_call_amd('enrol_wallet/cdiscount', 'init', $args);
        }

        $this->add_action_buttons(false, get_string('topup', 'enrol_wallet'));
        $this->set_display_vertical();
    }
}
