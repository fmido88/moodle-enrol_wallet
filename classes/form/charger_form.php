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
 * Charger form.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

use core\context\system;
use enrol_wallet\local\wallet\balance_op;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

use enrol_wallet\local\utils\catoptions;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\discounts\discount_rules;
use enrol_wallet\local\utils\form;

/**
 * The form by which managers could charge others manually.
 * @package enrol_wallet
 */
class charger_form extends \moodleform {
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
        global $CFG, $DB, $USER, $PAGE;

        $mform = $this->_form;
        // Check the conditional discount.
        $enabled = get_config('enrol_wallet', 'conditionaldiscount_apply');
        if (!empty($enabled)) {
            $i = discount_rules::add_discounts_to_form($mform);
        }

        if (file_exists($CFG->dirroot.'/blocks/vc/lib.php')
            && !empty($this->_customdata['vc'])
            && function_exists('block_vc_extend_credit_form')) {

            require_once($CFG->dirroot.'/blocks/vc/lib.php');
            block_vc_extend_credit_form($mform, $this->get_data());
        }

        $mform->addElement('header', 'main', get_string('chargingoptions', 'enrol_wallet'));

        $operations = [
            'credit'  => get_string('credit', 'enrol_wallet'),
            'debit'   => get_string('debit', 'enrol_wallet'),
        ];
        $oplabel = get_string('chargingoperation', 'enrol_wallet');
        $mform->addElement('select', 'op', $oplabel, $operations);

        $valuetitle = get_string('chargingvalue', 'enrol_wallet');
        $mform->addElement('text', 'value', $valuetitle);
        $mform->setType('value', PARAM_FLOAT);
        $mform->hideIf('value', 'op', 'eq', 'balance');

        $balance = new balance();
        if ($balance->catenabled) {
            $categorytitle = get_string('category');
            $catoptions = catoptions::get_all_categories_options();
            $mform->addElement('select', 'category', $categorytitle, $catoptions);
        } else {
            $mform->addElement('hidden', 'category');
            $mform->setType('category', PARAM_INT);
            $mform->setDefault('category', 0);
        }

        $mform->addElement('checkbox', 'neg', get_string('debitnegative', 'enrol_wallet'));
        $mform->hideIf('neg', 'op', 'neq', 'debit');

        if (!empty($enabled)) {
            // Empty div used by js to display the calculated final value.
            $enter = get_string('entervalue', 'enrol_wallet');
            $attributes = ['data-holder' => 'calculated-value', 'style' => 'font-weight: 700;'];
            $html = \html_writer::div($enter, 'alert alert-warning', $attributes);
            $mform->addElement('html', $html);
        }

        form::add_user_auto_complete_selection($mform, 'userlist', get_string('selectusers', 'enrol_manual'), 'charger-userlist');

        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'submit', get_string('submit'));
        $buttons[] = $mform->createElement('button', 'displaybalance', get_string('showbalance', 'enrol_wallet'));
        $mform->addGroup($buttons);
        $PAGE->requires->js_call_amd('enrol_wallet/balance', 'init', ['formid' => $this->get_form_id()]);

        $mform->addElement('html', '<div data-purpose="balance-holder"></div>');
        if (!empty($i)) {
            // Add some js code to display the actual value to charge the wallet with.
            $args = ['formid' => $this->get_form_id(), 'formType' => 'charge'];
            $PAGE->requires->js_call_amd('enrol_wallet/cdiscount', 'init', $args);
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

        if (!empty($data['submit'])) {
            if (empty($data['userlist'])) {
                $errors['userlist'] = get_string('selectuser', 'enrol_wallet');
            }

            $op = $data['op'];
            if (!in_array($op, ['credit', 'debit', 'balance'])) {
                $errors['op'] = get_string('charger_invalid_operation', 'enrol_wallet');
                return $errors;
            }

            $value  = $data['value'] ?? '';
            $userid = $data['userlist'];
            $catid = $data['category'] ?? 0;
            // No value.
            if (empty($value) && ($op !== 'balance')) {
                $errors['value'] = get_string('charger_novalue', 'enrol_wallet');
            }

            // No user.
            if (empty($userid) || !$DB->record_exists('user', ['id' => $userid])) {
                $errors['userlist'] = get_string('charger_nouser', 'enrol_wallet');
            }

            if (empty($data['neg'])) {
                $balance = new balance($userid, $catid);
                $before = $balance->get_valid_balance();
                if ($op === 'debit' && $value > $before) {
                    // Cannot deduct more than the user's balance.
                    $a = ['value' => $value, 'before' => $before];
                    $errors['value'] = get_string('charger_debit_err', 'enrol_wallet', $a);
                }
            }

        } else if (!empty($data['submitvc'])) {

            return $errors;
        }

        return $errors;
    }

    /**
     * Process the submission of the form.
     * @param array|stdClass $data
     * @return bool|null
     */
    public function process_form_submission($data = null) {
        global $USER, $DB;
        if (!$data) {
            $data = $this->get_data();
        }

        if (empty($data)) {
            return null;
        }

        $data = (array)$data;
        $op = $data['op'] ?? '';

        if (!empty($op) && $op != 'result') {

            $value  = $data['value'] ?? '';
            $userid = $data['userlist'];
            $catid  = $data['category'] ?? 0;

            $charger = $USER->id;

            $operations = new balance_op($userid, $catid);
            $before = $operations->get_total_balance();
            if ($op === 'credit') {

                $desc = get_string('charger_credit_desc', 'enrol_wallet', fullname($USER));
                // Process the transaction.
                $operations->credit($value, $operations::USER, $charger, $desc);
                $after = $operations->get_total_balance();

            } else if ($op === 'debit') {
                $neg = $data['neg'] ?? optional_param('neg', false, PARAM_BOOL);
                // Process the payment.
                $operations->debit($value, $operations::USER, $charger, '', $neg);
                $after = $operations->get_total_balance();

            }

            $params = [
                'before' => $before,
                'after'  => ($op == 'balance') ? $before : $after,
                'userid' => $userid,
                'op'     => 'result',
            ];

            return $this->notify_result($params);
        }

        return false;
    }

    /**
     * Add notifications about charge result.
     * @param array $params
     * @return bool
     */
    public function notify_result(array $params = []) {
        if (!has_capability('enrol/wallet:viewotherbalance', system::instance())) {
            return false;
        }

        $result = $params['result'] ?? optional_param('result', false, PARAM_TEXT);
        $before = $params['before'] ?? optional_param('before', '', PARAM_FLOAT);
        $after  = $params['after'] ?? optional_param('after', '', PARAM_FLOAT);
        $userid = $params['userid'] ?? optional_param('userid', '', PARAM_INT);
        $err    = $params['err'] ?? optional_param('error', '', PARAM_TEXT);

        $info = '';
        if (!empty($err)) {

            $info .= get_string('ch_result_error', 'enrol_wallet', $err);
            $type = 'error';

        } else {

            $user = \core_user::get_user($userid);
            $userfull = $user->firstname.' '.$user->lastname.' ('.$user->email.')';
            // Display the result to the user.
            $info .= get_string('ch_result_before', 'enrol_wallet', $before);
            $type = 'success';
            if (!empty($result) && is_numeric($result)) {
                $success = true;
            } else {
                $success = false;
                if (is_string($result)) {
                    $info .= $result;
                }
            }
            $a = [
                'userfull'     => $userfull,
                'after'        => $after,
                'after_before' => ($after - $before),
                'before'       => $before,
            ];
            if ($after !== $before) {

                if ($after !== '') {
                    $info .= get_string('ch_result_after', 'enrol_wallet', $after);
                }
                if ($after < 0) {
                    $info .= get_string('ch_result_negative', 'enrol_wallet');
                    $type = 'warning';
                }

                $info .= get_string('ch_result_info_charge', 'enrol_wallet', $a);

            } else {

                $info .= get_string('ch_result_info_balance', 'enrol_wallet', $a);
                $type = $success ? 'info' : 'error';

            }
        }
        // Display the results.
        \core\notification::add($info, $type);

        return true;
    }
}
