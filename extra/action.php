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

/**
 * Enrol wallet action after submit the coupon code.
 *
 * @package     enrol_wallet
 * @copyright   2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once(__DIR__.'/../lib.php');
global $DB, $USER;

require_login();

$cancel = optional_param('cancel', '', PARAM_TEXT);
$url = optional_param('url', '', PARAM_URL);
$redirecturl = !empty($url) ? new moodle_url('/'.$url) : new moodle_url('/');

if ($cancel) {
    // Important to unset the session coupon.
    if (isset($_SESSION['coupon'])) {
        $_SESSION['coupon'] = '';
        unset($_SESSION['coupon']);
    }
    redirect($redirecturl);
}

$userid = $USER->id;

$coupon     = required_param('coupon', PARAM_TEXT);
$instanceid = optional_param('instanceid' , '', PARAM_INT);
$courseid   = optional_param('courseid', 0, PARAM_INT);
$cmid       = optional_param('cmid', 0, PARAM_INT);
$sectionid  = optional_param('sectionid', 0, PARAM_INT);

$couponsetting = get_config('enrol_wallet', 'coupons');

if (!confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey');
}

// Get the coupon data.
$coupondata = enrol_wallet\transactions::get_coupon_value($coupon, $userid, $instanceid, false);
if (empty($coupondata) || is_string($coupondata)) {
    $msg = get_string('coupon_applyerror', 'enrol_wallet', $coupondata);
    $msgtype = 'error';
    // This mean that the function return error.
} else {

    $value = $coupondata['value'];
    $type = $coupondata['type'];
    // Check the type to determine what to do.
    if ($type == 'fixed' &&
        ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSFIXED
        || $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL)) {

        // Apply the coupon code to add its value to the user's wallet.
        enrol_wallet\transactions::get_coupon_value($coupon, $userid, $instanceid, true);
        $currency = get_config('enrol_wallet', 'currency');
        $a = [
            'value'    => $value,
            'currency' => $currency,
        ];
        $msg = get_string('coupon_applyfixed', 'enrol_wallet', $a);
        $msgtype = 'success';

    } else if ($type == 'percent' &&
            ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSDISCOUNT
            || $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL)
            && !empty($instanceid)) {
        // Percentage discount coupons applied in enrolment.
        $id = $DB->get_field('enrol', 'courseid', ['id' => $instanceid, 'enrol' => 'wallet'], IGNORE_MISSING);

        if ($id) {

            $redirecturl = new moodle_url('/enrol/index.php', ['id' => $id, 'coupon' => $coupon]);
            $msg = get_string('coupon_applydiscount', 'enrol_wallet', $value);
            $msgtype = 'success';

        } else {

            $msg = get_string('coupon_applynocourse', 'enrol_wallet');
            $msgtype = 'error';

        }

    } else if ($type == 'percent' &&
            ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSDISCOUNT
            || $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL)
            && (!empty($cmid) || !empty($sectionid))) {
            // This is the case when the coupon applied by availability wallet.
            $_SESSION['coupon'] = $coupon;

            $redirecturl = new moodle_url('/'.$url, ['coupon' => $coupon]);
            $msg = get_string('coupon_applydiscount', 'enrol_wallet', $value);
            $msgtype = 'success';

    } else if ($type == 'percent' && empty($instanceid)) {

        $msg = get_string('coupon_applynothere', 'enrol_wallet');
        $msgtype = 'error';

    } else {

        $msg = get_string('invalidcoupon_operation', 'enrol_wallet');
        $msgtype = 'error';
    }
}

redirect($redirecturl, $msg, null, $msgtype);
