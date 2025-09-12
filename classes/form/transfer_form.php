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

namespace enrol_wallet\form;

use enrol_wallet\local\wallet\balance;
use core_course_category;
use core_user;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * Class transfer_form
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transfer_form extends \moodleform {
    /**
     * If the user is parent or not.
     * @var bool
     */
    public $parent;
    /**
     * The balance helper class.
     * @var balance
     */
    protected $balance;
    /**
     * The min condition for transfer.
     * @var float
     */
    public $condition;
    /**
     * If the transfer configurations.
     * @var \stdClass
     */
    public $config;
    /**
     * Form definition.
     */
    protected function definition() {
        global $CFG, $USER;

        $transferenabled = (bool)get_config('enrol_wallet', 'transfer_enabled');
        if (empty($transferenabled)) {
            return;
        }
        $this->config = (object)[
            'transfer_enabled' => $transferenabled,
            'transferpercent'  => (float)get_config('enrol_wallet', 'transferpercent'),
            'transferfee_from' => get_config('enrol_wallet', 'transferfee_from'),
            'mintransfer'      => (float)get_config('enrol_wallet', 'mintransfer'),
        ];

        $isparent = false;
        if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
            require_once("$CFG->dirroot/auth/parent/lib.php");
            $isparent = auth_parent_is_parent($USER);
        }
        $this->parent = $isparent;

        $mform = $this->_form;

        $this->balance = new balance();
        $total = $this->balance->get_total_balance();
        $currency = get_config('enrol_wallet', 'currency');

        $mform->addElement('header', 'transferformhead', get_string('transfer', 'enrol_wallet'));

        $displaybalance = format_string(format_float($total, 2) . ' ' . $currency);
        $mform->addElement('static', 'displaybalance', get_string('availablebalance', 'enrol_wallet'), $displaybalance);

        if ($this->balance->catenabled) {
            $main = $this->balance->get_main_balance();
            $mainbalance = format_string(format_float($main, 2) . ' ' . $currency);
            $mform->addElement('static', 'mainbalance', get_string('mainbalance', 'enrol_wallet'), $mainbalance);

            $details = $this->balance->get_balance_details();
            if (!empty($details->catbalance)) {
                $options = [0 => get_string('site')];
                foreach ($details->catbalance as $id => $obj) {
                    $category = core_course_category::get($id, IGNORE_MISSING, true);
                    if (!empty($category)) {
                        $name = $category->get_nested_name(false);
                        $catbalance = $obj->balance ?? $obj->refundable + $obj->nonrefundable;
                        $mform->addElement('static', 'cat'.$id, $name, number_format($catbalance, 2));
                        $options[$id] = $name;
                    }
                }
                $mform->addElement('select', 'category', get_string('category'), $options);
            }
        } else {
            $mform->addElement('hidden', 'category');
            $mform->setType('category', PARAM_INT);
            $mform->setDefault('category', 0);
        }

        if ($isparent) {
            $options = [];
            foreach (auth_parent_get_children($USER) as $childid) {
                $child = core_user::get_user($childid);
                $options[$child->email] = fullname($child);
            }
            $mform->addElement('select',  'email',  get_string('user'),  $options);

        } else {
            $mform->addElement('text', 'email', get_string('email'));
            $mform->setType('email', PARAM_EMAIL);
        }

        $mform->addElement('text', 'amount', get_string('amount', 'enrol_wallet'));
        $mform->setType('amount', PARAM_FLOAT);

        $percentfee = $this->config->transferpercent;
        if (!empty($percentfee) && !$isparent) {
            $a = ['fee' => $percentfee];
            $a['from'] = get_string($this->config->transferfee_from, 'enrol_wallet');

            $mform->addElement('static', 'feedesc',
                                get_string('transferpercent', 'enrol_wallet'),
                                get_string('transferfee_desc', 'enrol_wallet', $a));
        }

        $mform->addElement('submit', 'confirm', get_string('confirm'));
    }

    /**
     * Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     * Server side rules do not work for uploaded files, implement server side rules here if needed.
     * returns of "element_name"=>"error_description" if there are errors,
     * or an empty array if everything is OK (true allowed for backwards compatibility too).
     *
     * @param array $data array of data
     * @param array $files array of files
     * @return array array of errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!$this->config->transfer_enabled) {
            $elements = array_keys($this->_form->_elements);
            foreach ($elements as $element) {
                $errors[$element] = get_string('transfer_notenabled', 'enrol_wallet');
            }
        }

        // No email or invalid email format.
        if (empty($data['email'])) {
            $errors['email'] = get_string('wrongemailformat', 'enrol_wallet');
        }

        $condition = $this->config->mintransfer;
        // No amount or invalid amount.
        if (empty($data['amount']) || $data['amount'] < 0) {
            $errors['amount'] = get_string('charger_novalue', 'enrol_wallet');
        } else if ($data['amount'] < $condition) {
            $errors['amount'] = get_string('mintransfer', 'enrol_wallet', $condition);
        }

        if (!empty($data['category'])) {
            $balance = $this->balance->get_cat_balance($data['category']);
        } else {
            $balance = $this->balance->get_main_balance();
        }

        [$debit, $credit] = $this->get_debit_credit($data['amount']);

        // No sufficient balance.
        if ($debit > $balance || $credit < 0 || $debit < 0) {
            $errors['amount'] = get_string('insufficientbalance', 'enrol_wallet', ['amount' => $debit, 'balance' => $balance]);
        }

        $receiver = \core_user::get_user_by_email($data['email'], 'id, deleted, suspended');
        // No active user found with this email.
        if (empty($receiver) || !empty($receiver->deleted) || !empty($receiver->suspended)) {
            $errors['email'] = get_string('usernotfound', 'enrol_wallet', $data['email']);
        }
        return $errors;
    }

    /**
     * Get the debit amount from sender and the credit amount for the reciever.
     * @param float $amount
     * @return array[float]
     */
    public function get_debit_credit($amount) {
        $amount = (float)$amount;

        $parent = $this->parent;

        if ($parent) {
            $fee = 0;
        } else {
            // Check the transfer fees.
            $percentfee = $this->config->transferpercent ?? 0;
            $fee = $amount * $percentfee / 100;
        }

        $feefrom = $this->config->transferfee_from;
        if ($feefrom == 'sender') {
            $debit = $amount + $fee;
            $credit = $amount;
        } else if ($feefrom == 'receiver') {
            $credit = $amount - $fee;
            $debit = $amount;
        }

        return [$debit, $credit];
    }
}
