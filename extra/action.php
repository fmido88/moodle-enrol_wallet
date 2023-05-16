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

// Call require_login() to ensure that the user is authenticated. Also, having the ability for transactions.
require_login();

$userid = optional_param('userid', 0, PARAM_INT);
$coupon = optional_param('coupon', null, PARAM_RAW);
$instanceid = optional_param('instanceid' , '', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$couponsetting = get_config('enrol_wallet', 'coupons');
$url = optional_param('url', '', PARAM_URL);
$redirecturl = !empty($url) ? new moodle_url('/'.$url) : new moodle_url('/');

if ($userid == 0 || $coupon == null) {
    $errormessage = "Please provide both user ID and coupon code.";

    redirect($redirecturl, $errormessage);
    exit;
} else if (confirm_sesskey()) {
    // Get the coupon data.
    $coupondata = enrol_wallet_plugin::get_coupon_value($coupon, $userid, $instanceid, false);
    if (is_string($coupondata) || $coupondata === false) {
        $errormessage = 'ERROR invalid coupon code';
        $errormessage .= '<br>'.$coupondata;
        // This mean that the function return error.
        redirect($redirecturl, $errormessage);
    } else {
        $value = $coupondata['value'];
        $type = $coupondata['type'];
        // Check the type to determine what to do.
        if ($type == 'fixed' &&
            ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSFIXED
            || $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL)) {

            // Apply the coupon code to add his value to the user's wallet.
            enrol_wallet_plugin::get_coupon_value($coupon, $userid, $instanceid, true);
            $msg = 'Coupon code applied successfully with value of '.$value.' EGP.';

            redirect($redirecturl, $msg, null, 'success');
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
            }
        }
    }
}
