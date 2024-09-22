<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/** The topup wallet page Display confirmation about payment to charge wallet.
 *
 * @package     enrol_wallet
 * @copyright   2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once(__DIR__.'/../lib.php');
global $DB, $USER;

debugging("Entering topup.php", DEBUG_DEVELOPER);

$instanceid = required_param('instanceid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);
$account    = required_param('account', PARAM_INT);
$currency   = required_param('currency', PARAM_TEXT);
$val        = optional_param('value', 0, PARAM_FLOAT);
$category   = optional_param('category', 0, PARAM_INT);
$return     = optional_param('return', '', PARAM_LOCALURL);
$success    = optional_param('success', 0, PARAM_BOOL);

debugging("Parameters: instanceid=$instanceid, courseid=$courseid, confirm=$confirm, account=$account, currency=$currency, val=$val, category=$category, success=$success", DEBUG_DEVELOPER);

$urlparams = [
    'instanceid' => $instanceid,
    'courseid'   => $courseid,
    'account'    => $account,
    'currency'   => $currency,
    'value'      => $val,
    'category'   => $category,
    'return'     => $return,
];
$baseurl = new moodle_url('/enrol/wallet/extra/topup.php', $urlparams);
// Check the conditional discount.
$enabled   = get_config('enrol_wallet', 'conditionaldiscount_apply');
$discount  = 0;

if (!empty($enabled)) {
    $value = enrol_wallet\util\discount_rules::get_the_before($val, $category);
} else {
    $value = $val;
}

debugging("Final value after discount: $value", DEBUG_DEVELOPER);

$context = context_course::instance($courseid);

if (!confirm_sesskey()) {
    debugging("Invalid sesskey", DEBUG_DEVELOPER);
    throw new moodle_exception('invalidsesskey');
}

if (!empty($return)) {
    $url = new moodle_url($return);
} else {
    $url = ($courseid == SITEID) ? new \moodle_url('/') : new \moodle_url('/course/view.php', ['id' => $courseid]);
}

if ($value <= 0) {
    debugging("Invalid value: $value", DEBUG_DEVELOPER);
    redirect($url, get_string('invalidvalue', 'enrol_wallet'), null, 'error');
}

require_login();

debugging("Checking hold gift record for user {$USER->id}", DEBUG_DEVELOPER);
$hold = $DB->get_record('enrol_wallet_hold_gift', ['referred' => $USER->id, 'released' => 0]);
if ($hold) {
    debugging("Found hold gift record: " . var_export($hold, true), DEBUG_DEVELOPER);
} else {
    debugging("No hold gift record found for user {$USER->id}", DEBUG_DEVELOPER);
}

$referralenabled = get_config('enrol_wallet', 'referral_on_topup');
$referralamount = (float)get_config('enrol_wallet', 'referral_amount');
$minimumtopup = (float)get_config('enrol_wallet', 'referral_topup_minimum');
debugging("Referral config: enabled={$referralenabled}, amount={$referralamount}, minimum={$minimumtopup}", DEBUG_DEVELOPER);

if ($success) {
    debugging("Payment successful, checking referral eligibility", DEBUG_DEVELOPER);
    
    // Check if referral on top-up is enabled
    $referralenabled = get_config('enrol_wallet', 'referral_on_topup');
    debugging("Referral on top-up enabled: " . ($referralenabled ? 'Yes' : 'No'), DEBUG_DEVELOPER);

    if (!$referralenabled) {
        debugging("Referral on top-up is not enabled. Skipping referral bonus application.", DEBUG_DEVELOPER);
    } else {
        // Check if the user is eligible for a referral bonus
        $hold = $DB->get_record('enrol_wallet_hold_gift', ['referred' => $USER->id, 'released' => 0]);
        
        if ($hold) {
            debugging("User is eligible for referral bonus. Referrer ID: {$hold->referrer}", DEBUG_DEVELOPER);
            
            // Payment has been successful, apply the referral bonus if applicable
            $balance_op = new \enrol_wallet\util\balance_op($USER->id);
            $result = $balance_op->apply_referral_on_topup($value);
            
            if ($result) {
                debugging("Referral bonus applied successfully", DEBUG_DEVELOPER);
                \core\notification::success(get_string('referral_success', 'enrol_wallet'));
            } else {
                debugging("Referral bonus not applied", DEBUG_DEVELOPER);
                \core\notification::info(get_string('referral_not_applied', 'enrol_wallet'));
            }
        } else {
            debugging("User is not eligible for referral bonus. No unreleased hold gift found.", DEBUG_DEVELOPER);
        }
    }
    debugging("Redirecting to: " . $url, DEBUG_DEVELOPER);
    redirect($url);
} else {
    debugging("Displaying payment confirmation page", DEBUG_DEVELOPER);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url($baseurl);
    $PAGE->set_title(new lang_string('confirm'));

    echo $OUTPUT->header();

    $desc = get_string('paymenttopup_desc', 'enrol_wallet');

    // Set a fake item for payment.
    $itemdata = [
        'cost'        => $value,
        'currency'    => $currency,
        'userid'      => $USER->id,
        'category'    => $category,
        'timecreated' => time(),
    ];
    $id = $DB->insert_record('enrol_wallet_items', $itemdata);
    debugging("Created payment item with ID: $id", DEBUG_DEVELOPER);
    // Prepare the payment button.
    $attributes = [
        'class'            => "btn btn-primary",
        'type'             => "button",
        'id'               => "gateways-modal-trigger-$account",
        'data-action'      => "core_payment/triggerPayment",
        'data-component'   => "enrol_wallet",
        'data-paymentarea' => "wallettopup",
        'data-itemid'      => "$id",
        'data-cost'        => "$value",
        'data-successurl'  => $baseurl->out(false, ['success' => 1, 'sesskey' => sesskey()]),
        'data-description' => "$desc",
    ];

    $buttoncontinue = new single_button($baseurl, get_string('yes'), 'get');
    foreach ($attributes as $name => $v) {
        $buttoncontinue->set_attribute($name, $v);
    }

    $buttoncancel = new single_button($url, get_string('no'), 'get');

    $a = (object) [
        'value'    => $value,
        'before'   => $val,
        'currency' => $currency,
    ];

    $policy = get_config('enrol_wallet', 'refundpolicy');
    if (!empty($policy)) {
        $a->policy = $policy;
    }
    if ($val == $value) {
        $message = get_string('confirmpayment', 'enrol_wallet', $a);
    } else {
        $message = get_string('confirmpayment_discounted', 'enrol_wallet', $a);
    }

    // This code is required for payment button.
    $PAGE->requires->js_call_amd('core_payment/gateways_modal', 'init');

    echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
    echo $OUTPUT->footer();
}

debugging("Exiting topup.php", DEBUG_DEVELOPER);
