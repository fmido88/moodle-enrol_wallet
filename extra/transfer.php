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
 * The page to transfer wallet ballance to other user.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

$context = context_system::instance();

require_login();
require_capability('enrol/wallet:transfer', $context);

// Check if transfer isn't enabled in this website.
$transferenabled = get_config('enrol_wallet', 'transfer_enabled');
if (empty($transferenabled)) {
    redirect(new moodle_url('/'), get_string('transfer_notenabled', 'enrol_wallet'), null, 'error');
}

$url = new moodle_url('/enrol/wallet/extra/transfer.php');
$confirm = optional_param('confirm', '', PARAM_BOOL);

if ($confirm) {
    // Don't do any action until confirming sesskey.
    if (!confirm_sesskey()) {
        throw new moodle_exception('invalidsesskey');
    }

    global $USER;
    $email  = required_param('email', PARAM_EMAIL);
    $amount = required_param('amount', PARAM_FLOAT);
    $condition = get_config('enrol_wallet', 'mintransfer');
    // No email or invalid email format.
    if (empty($email)) {
        $msg = get_string('wrongemailformat', 'enrol_wallet');
        redirect($url, $msg, null, 'error');
    }

    // No amount or invalid amount.
    if (empty($amount) || $amount < 0) {
        $msg = get_string('charger_novalue', 'enrol_wallet');
        redirect($url, $msg, null, 'error');
    }

    if ($amount < $condition) {
        $msg = get_string('mintransfer', 'enrol_wallet', $condition);
        redirect($url, $msg, null, 'error');
    }

    // Check the transfer fees.
    $percentfee = get_config('enrol_wallet', 'transferpercent');
    $percentfee = (!empty($percentfee)) ? $percentfee : 0;
    $fee = $amount * $percentfee / 100;

    $feefrom = get_config('enrol_wallet', 'transferfee_from');
    if ($feefrom == 'sender') {
        $debit = $amount + $fee;
        $credit = $amount;
    } else if ($feefrom == 'receiver') {
        $credit = $amount - $fee;
        $debit = $amount;
    }

    $balance = enrol_wallet\transactions::get_user_balance($USER->id);
    // No sufficient balance.
    if ($debit > $balance) {
        $msg = get_string('insufficientbalance', 'enrol_wallet', ['amount' => $debit, 'balance' => $balance]);
        redirect($url, $msg, null, 'error');
    }

    $receiver = core_user::get_user_by_email($email);
    // No active user found with this email.
    if (empty($receiver) || !empty($receiver->deleted) || !empty($receiver->suspended)) {
        $msg = get_string('usernotfound', 'enrol_wallet', $email);
        redirect($url, $msg, null, 'error');
    }

    // Debit the sender.
    enrol_wallet\transactions::debit($USER->id, $debit, '', $receiver->id);

    // Credit the receiver.
    $a = [
        'fee' => $fee,
        'amount' => $credit,
        'receiver' => fullname($receiver),
    ];
    $desc = get_string('transferop_desc', 'enrol_wallet', $a);
    enrol_wallet\transactions::payment_topup($credit, $receiver->id, $desc, $USER->id, false);

    // All done.
    redirect($url, $desc, null, 'success');

} else {

    $PAGE->set_context($context);
    $PAGE->set_title(get_string('transferpage', 'enrol_wallet'));
    $PAGE->set_heading(get_string('transferpage', 'enrol_wallet'));
    $PAGE->set_url($url);

    // Transfer form.
    $mformoutput = enrol_wallet_get_transfer_form();

    echo $OUTPUT->header();

    echo $mformoutput;

    echo $OUTPUT->footer();
}

