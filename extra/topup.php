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
global $DB;
$instanceid = required_param('instanceid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);
$account    = required_param('account', PARAM_INT);
$currency   = required_param('currency', PARAM_TEXT);
$val        = optional_param('value', 0, PARAM_FLOAT);
$return     = optional_param('return', '', PARAM_LOCALURL);

$urlparams = [
    'instanceid' => $instanceid,
    'courseid'   => $courseid,
    'account'    => $account,
    'currency'   => $currency,
    'value'      => $val,
    'return'     => $return,
];
$baseurl = new moodle_url('/enrol/wallet/extra/topup.php', $urlparams);
// Check the conditional discount.
$enabled   = get_config('enrol_wallet', 'conditionaldiscount_apply');
$discount  = 0;

if (!empty($enabled)) {
    $params = [
        'time1' => time(),
        'time2' => time(),
    ];
    $select = '(timefrom <= :time1 OR timefrom = 0 ) AND (timeto >= :time2 OR timeto = 0)';
    $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params);

    foreach ($records as $record) {
        if ($val >= $record->cond && $record->percent > $discount) {
            $discount = $record->percent;
        }
    }

    $value = (float)($val * (1 - $discount / 100));
} else {
    $value = $val;
}

$context = context_course::instance($courseid);

if (!confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey');
}

if (!empty($return)) {
    $url = new moodle_url($return);
} else {
    $url = ($courseid == SITEID) ? new \moodle_url('/') : new \moodle_url('/course/view.php', ['id' => $courseid]);
}

if ($value <= 0) {
    redirect($url, get_string('invalidvalue', 'enrol_wallet'), null, 'error');
}

if ($confirm) {
    // No need for this condition as the payment button use its own success url.
    // Just in case.
    redirect($url);
} else {
    global $DB, $USER;

    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url($baseurl);
    $PAGE->set_title(new lang_string('confirm'));

    require_login();

    echo $OUTPUT->header();

    $desc = get_string('paymenttopup_desc', 'enrol_wallet');

    // Set a fake item form payment.
    $id = $DB->insert_record('enrol_wallet_items', ['cost' => $value, 'currency' => $currency, 'userid' => $USER->id]);
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
        'data-successurl'  => "$url",
        'data-description' => "$desc",
    ];

    // Again there is no need for this $yesurl as clicking the button trigger the payment.
    // Just in case.
    $baseurl->param('confirm', true);
    $buttoncontinue = new single_button($baseurl, get_string('yes'), 'get', true, $attributes);

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
    $code = 'require([\'core_payment/gateways_modal\'], function(modal) {
        modal.init();
    });';
    $PAGE->requires->js_init_code($code);

    echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
    echo $OUTPUT->footer();
}
