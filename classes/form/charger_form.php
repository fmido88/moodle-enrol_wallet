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
use enrol_wallet\local\config;
use enrol_wallet\local\discounts\discount_rules;
use enrol_wallet\local\utils\catoptions;
use enrol_wallet\local\utils\form;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use stdClass;

/**
 * The form by which managers could charge others manually.
 * @package enrol_wallet
 */
class charger_form extends topup_form {
    #[\Override()]
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $hook = new \enrol_wallet\hook\before_charger_form_definition($mform, $this->_customdata);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        // Check the conditional discount.
        $cdenabled = config::instance()->conditionaldiscount_apply;

        if (!empty($cdenabled)) {
            $i = discount_rules::add_discounts_to_form($mform);
        }

        $mform->addElement('header', 'main', get_string('chargingoptions', 'enrol_wallet'));

        $operations = [
            'credit'  => get_string('credit', 'enrol_wallet'),
            'debit'   => get_string('debit', 'enrol_wallet'),
            'reset'   => get_string('reset', 'enrol_wallet'),
        ];
        $oplabel = get_string('chargingoperation', 'enrol_wallet');
        $mform->addElement('select', 'op', $oplabel, $operations);

        $valuetitle = get_string('chargingvalue', 'enrol_wallet');
        $mform->addElement('text', 'value', $valuetitle);
        $mform->setType('value', PARAM_FLOAT);
        $mform->hideIf('value', 'op', 'eq', 'balance');
        $mform->hideIf('value', 'op', 'eq', 'reset');

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

        if (!empty($cdenabled)) {
            // Empty div used by js to display the calculated final value.
            $enter = get_string('entervalue', 'enrol_wallet');
            $attributes = ['data-holder' => 'calculated-value', 'style' => 'font-weight: 700;'];
            $html = \html_writer::div($enter, 'alert alert-warning', $attributes);
            $mform->addElement('html', $html);
        }

        form::add_user_auto_complete_selection($mform, 'userlist', get_string('selectusers', 'enrol_manual'), 'charger-userlist');

        $mform->addElement('text', 'reason', get_string('transactionreason', 'enrol_wallet'));
        $mform->setType('reason', PARAM_TEXT);

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

    #[\Override()]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        if (!empty($data['submit'])) {
            if (empty($data['userlist'])) {
                $errors['userlist'] = get_string('selectuser', 'enrol_wallet');
            }

            $op = $data['op'];

            if (!\in_array($op, ['credit', 'debit', 'balance', 'reset'])) {
                $errors['op'] = get_string('charger_invalid_operation', 'enrol_wallet');

                return $errors;
            }

            $value = $data['value'] ?? '';
            $userid = $data['userlist'] ?? 0;
            $catid = $data['category'] ?? 0;

            // No value.
            if (empty($value) && !\in_array($op, ['balance', 'reset'], true)) {
                $errors['value'] = get_string('charger_novalue', 'enrol_wallet');
            }

            // No user.
            if (empty($errors['userlist']) && (empty($userid) || !$DB->record_exists('user', ['id' => $userid]))) {
                $errors['userlist'] = get_string('charger_nouser', 'enrol_wallet');
            }

            if (empty($data['neg']) && empty($errors)) {
                $balance = new balance($userid, $catid);
                $before = $balance->get_valid_balance();

                if ($op === 'debit' && $value > $before) {
                    // Cannot deduct more than the user's balance.
                    $a = ['value' => $value, 'before' => $before];
                    $errors['value'] = get_string('charger_debit_err', 'enrol_wallet', $a);
                }
            }
        }

        return $errors;
    }

    /**
     * Process the submission of the form.
     * @param  array|stdClass $data
     * @return bool|null
     */
    public function process_form_submission($data = null) {
        global $USER;

        if (!$data) {
            $data = $this->get_data();
        }

        if (empty($data)) {
            return null;
        }

        $data = (array)$data;
        $op = $data['op'] ?? '';

        if (!empty($op) && $op != 'result') {
            $value = $data['value'] ?? '';
            $userid = $data['userlist'];
            $catid = $data['category'] ?? 0;
            $reason = $data['reason'] ?? '';

            $charger = $USER->id;

            $operations = new balance_op($userid, $catid);
            $before = $operations->get_total_balance();

            switch ($op) {
                case 'reset':
                    $operations->reset_balance($reason);
                    break;

                case 'credit':
                    $desc = get_string('charger_credit_desc', 'enrol_wallet', fullname($USER));

                    if (!empty($reason)) {
                        $desc .= ": $reason";
                    }

                    // Process the transaction.
                    $operations->credit($value, $operations::USER, $charger, $desc);
                    break;

                case 'debit':
                    $neg = $data['neg'] ?? optional_param('neg', false, PARAM_BOOL);
                    // Process the payment.
                    $operations->debit($value, $operations::USER, $charger, $reason, $neg);
                    break;

                default:
                    break;
            }

            $after = $operations->get_total_balance();
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
     * @param  array $params
     * @return bool
     */
    public function notify_result(array $params = []) {
        if (!has_capability('enrol/wallet:viewotherbalance', system::instance())) {
            return false;
        }

        $result = $params['result'] ?? $this->optional_param('result', false, PARAM_TEXT);
        $before = $params['before'] ?? $this->optional_param('before', '', PARAM_FLOAT);
        $after = $params['after'] ?? $this->optional_param('after', '', PARAM_FLOAT);
        $userid = $params['userid'] ?? $this->optional_param('userid', '', PARAM_INT);
        $err = $params['err'] ?? $this->optional_param('error', '', PARAM_TEXT);

        $info = '';

        if (!empty($err)) {
            $info .= get_string('ch_result_error', 'enrol_wallet', $err);
            $type = 'error';
        } else {
            $user = \core_user::get_user($userid);
            $userfull = $user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')';
            // Display the result to the user.
            $info .= get_string('ch_result_before', 'enrol_wallet', $before);
            $type = 'success';

            if (!empty($result) && is_numeric($result)) {
                $success = true;
            } else {
                $success = false;

                if (\is_string($result)) {
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
