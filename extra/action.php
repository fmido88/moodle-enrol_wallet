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
require(__DIR__.'/../lib.php');
global $DB, $USER;

// Call require_login() to ensure that the user is authenticated.
require_login();

$cancel = optional_param('cancel', '', PARAM_TEXT);
$url = optional_param('url', '', PARAM_URL);
$redirecturl = !empty($url) ? new moodle_url('/'.$url) : new moodle_url('/');

if ($cancel) {
    redirect($redirecturl);
    exit;
}

$userid = required_param('userid', PARAM_INT);
$coupon = required_param('coupon', PARAM_TEXT);
$instanceid = optional_param('instanceid' , '', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$couponsetting = get_config('enrol_wallet', 'coupons');

if (confirm_sesskey()) {
    // Get the coupon data.
    $coupondata = enrol_wallet\transactions::get_coupon_value($coupon, $userid, $instanceid, false);
    if (is_string($coupondata) || $coupondata === false) {
        $errormessage = 'ERROR invalid coupon code';
        $errormessage .= '<br>'.$coupondata;
        // This mean that the function return error.
        redirect($redirecturl, $errormessage);
        exit;
    } else {
        $value = $coupondata['value'];
        $type = $coupondata['type'];
        // Check the type to determine what to do.
        if ($type == 'fixed' &&
            ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSFIXED
            || $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL)) {

            // Apply the coupon code to add his value to the user's wallet.
            enrol_wallet\transactions::get_coupon_value($coupon, $userid, $instanceid, true);
            $currency = get_config('enrol_wallet', 'currency');
            $msg = 'Coupon code applied successfully with value of '.$value.' '.$currency.'.';

            redirect($redirecturl, $msg, null, 'success');
            exit;
        } else if ($type == 'percent' &&
                ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSDISCOUNT
                || $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL)
                && !empty($instanceid)) {

            $id = $DB->get_field('enrol', 'courseid', ['id' => $instanceid, 'enrol' => 'wallet'], IGNORE_MISSING);

            if ($id) {
                $redirecturl = new moodle_url('/enrol/index.php', ['id' => $id, 'coupon' => $coupon]);
                $msg = 'You now have discounted by '. $value.'%';
                redirect($redirecturl, $msg, null, 'success');
                exit;
            } else {
                $msg = 'Error during applying coupon, course not found.';
                redirect($redirecturl, $msg);
                exit;
            }
        } else if ($type == 'percent' && empty($instanceid)) {
            $msg = 'Cannot apply discount coupon here.';
            redirect($redirecturl, $msg);
            exit;
        } else {
            $msg = 'Invalid Action.';
            redirect($redirecturl, $msg);
            exit;
        }
    }
}
