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
 * The page to charge wallet for other users.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
global $USER, $DB, $CFG;

require_once($CFG->dirroot.'/enrol/wallet/lib.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

$context = context_system::instance();

require_login();
require_capability('enrol/wallet:creditdebit', $context);

$op = optional_param('op', 'none', PARAM_TEXT);

if ($op != 'none' && $op != 'result' && confirm_sesskey()) {

    $value = optional_param('value', '', PARAM_NUMBER);
    $userid = required_param("userlist", PARAM_INT);
    $err = '';

    $charger = $USER->id;
    if (empty($value) && ($op !== 'balance')) {
        $err = 'No value';
        $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('error' => $err,
                                                                                   'op' => 'result'));
        redirect($redirecturl, $err);
        exit;
    }

    if (empty($userid)) {
        $err = 'No user selected';
        $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('error' => $err,
                                                                                   'op' => 'result'));
        redirect($redirecturl, $err);
        exit;
    }
    $transactions = new enrol_wallet\transactions;
    $before = $transactions->get_user_balance($userid);
    if ($op === 'credit') {
        $desc = 'charging from wallet block by '.fullname($USER);
        // Process the transaction.
        $result = $transactions->payment_topup($value, $userid, $desc, $charger);
        $after = $transactions->get_user_balance($userid);
    } else if ($op === 'debit') {
        if ($value > $before) {
            $err = 'The value ('.$value.') is greater that the user\'s balance ('.$before.')';
            $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('error' => $err,
                                                                                       'op' => 'result'));
            redirect($redirecturl);
            exit;
        } else {
            // Process the payment.
            $result = $transactions->debit($userid, $value, '(deduct from wallet block by '.fullname($USER).')', $charger);
            $after = $transactions->get_user_balance($userid);
        }

    } else if ($op = 'balance') {
        $result = $before;
    } else {
        $result = 'invalid operation';
    }

    // Redirect to same page to show results.
    $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('result' => $result,
    'before' => $before,
    'after' => ($op == 'balance') ? $before : $after,
    'userid' => $userid,
    'op' => 'result'));

    redirect($redirecturl);

} else {

    $PAGE->set_context($context);
    $PAGE->set_url($CFG->wwwroot.'/enrol/wallet/extra/charger.php');

    echo $OUTPUT->header();
    // Display the results.
    if ($op == 'result') {
        $results = enrol_wallet_display_transaction_results();
        echo $OUTPUT->box($results);
    }
    // Display the charger form.
    $form = enrol_wallet_display_charger_form();
    echo $OUTPUT->box($form);

    echo $OUTPUT->footer();
}
