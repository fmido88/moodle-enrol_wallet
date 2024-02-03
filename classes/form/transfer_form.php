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

use enrol_wallet\util\balance;
use core_course_category;
use core_user;

defined('MOODLE_INTERNAL') || die();

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
        $transferenabled = get_config('enrol_wallet', 'transfer_enabled');
        if (empty($transferenabled)) {
            return;
        }
        $this->config = (object)[
            'transfer_enabled' => $transferenabled,
            'transferpercent'  => get_config('enrol_wallet', 'transferpercent'),
            'transferfee_from' => get_config('enrol_wallet', 'transferfee_from'),
            'mintransfer'      => get_config('enrol_wallet', 'mintransfer'),
        ];
        global $CFG, $USER;
        require_once($CFG->libdir.'/formslib.php');
        $isparent = false;
        if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
            require_once("$CFG->dirroot/auth/parent/lib.php");
            $isparent = auth_parent_is_parent($USER);
        }
        $this->parent = $isparent;

        $mform = $this->_form;

        $balance = new balance();
        $total = $balance->get_total_balance();
        $currency = get_config('enrol_wallet', 'currency');

        $mform->addElement('header', 'transferformhead', get_string('transfer', 'enrol_wallet'));

        $displaybalance = format_string(format_float($total, 2) . ' ' . $currency);
        $mform->addElement('static', 'displaybalance', get_string('availablebalance', 'enrol_wallet'), $displaybalance);

        if (!empty($balance->details['catbalance'])) {
            $options = [0 => get_string('site')];
            foreach ($balance->details['catbalance'] as $id => $obj) {
                $category = core_course_category::get($id, IGNORE_MISSING);
                if (!empty($category)) {
                    $name = $category->get_nested_name(false);
                    $mform->addElement('static', 'cat'.$id, $name, number_format($obj->balance, 2));
                    $options[$id] = $name;
                }
            }
            $mform->addElement('select', 'category', get_string('category'), $options);
        }

        if ($isparent) {
            $options = [];
            foreach (auth_parent_get_children($USER) as $childid) {
                $child = core_user::get_user($childid);
                $options[$child->email] = fullname($child);
            }
            $mform->addElement('select',  'email',  get_string('user'),  $options);

            $mform->addElement('hidden', 'parent');
            $mform->setType('parent', PARAM_BOOL);
            $mform->setDefault('parent', true);
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

        // No email or invalid email format.
        if (empty($data['email'])) {
            $errors['email'] = get_string('wrongemailformat', 'enrol_wallet');
        }

        // No amount or invalid amount.
        if (empty($data['amount']) || $data['amount'] < 0) {
            $errors['amount'] = get_string('charger_novalue', 'enrol_wallet');
        }
        $condition = get_config('enrol_wallet', 'mintransfer');
        if ($data['amount'] < (int)$condition) {
            $errors['amount'] = get_string('mintransfer', 'enrol_wallet', $condition);
        }

        if (!empty($data['category'])) {
            $balance = $this->balance->get_cat_balance($data['category']);
        } else {
            $balance = $this->balance->get_main_balance();
        }

        $parent = ($data['parent'] ?? false) && $this->parent;

        if ($parent) {
            $fee = 0;
        } else {
            // Check the transfer fees.
            $percentfee = $this->config->transferpercent;
            $percentfee = (!empty($percentfee)) ? $percentfee : 0;
            $fee = $data['amount'] * $percentfee / 100;
        }

        $feefrom = $this->config->transferfee_from;
        if ($feefrom == 'sender') {
            $debit = $data['amount'] + $fee;
            $credit = $data['amount'];
        } else if ($feefrom == 'receiver') {
            $credit = $data['amount'] - $fee;
            $debit = $data['amount'];
        }
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
}
