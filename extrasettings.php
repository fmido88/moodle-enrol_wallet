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
 * wallet enrolment plugin extra pages.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_wallet\local\urls\manage;
use enrol_wallet\local\urls\reports;

defined('MOODLE_INTERNAL') || die();

$context = context_system::instance();

$captransactions = has_capability('enrol/wallet:transaction', $context);
$capcredit       = has_capability('enrol/wallet:creditdebit', $context);
$capbulkedit     = has_capability('enrol/wallet:bulkedit', $context);
$capcouponview   = has_capability('enrol/wallet:viewcoupon', $context);
$capcouponcreate = has_capability('enrol/wallet:createcoupon', $context);
$capcouponedit   = has_capability('enrol/wallet:editcoupon', $context);

$ismoodle = (get_config('enrol_wallet', 'walletsource') == enrol_wallet\local\wallet\balance::MOODLE);
// Adding these pages for only users with required capability.
// These aren't appear to user's with capabilities, Only admins!.
// That is because enrolment plugins not loading the settings unless the user has the capability moodle/site:config.
// Working on solution.
if ($captransactions || $capbulkedit || $capcouponview || $capcouponcreate || $capcredit) {
    // Adding new admin category.
    $ADMIN->add('modules', new admin_category('enrol_wallet_settings',
    get_string('bulkfolder', 'enrol_wallet'), false));
}

if ($capcouponcreate && $ismoodle) {
    // Adding page to generate coupons.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_coupongenerate',
                                                get_string('coupon_generation', 'enrol_wallet'),
                                                manage::GENERATE_COUPON->url(),
                                                'enrol/wallet:createcoupon',
                                                false,
                                                $context));
}

if ($capcouponcreate && $capcouponedit && $ismoodle) {
    // Adding page to upload coupons.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_uploadcoupons',
                                                get_string('upload_coupons', 'enrol_wallet'),
                                                manage::UPLOAD_COUPONS->url(),
                                                'enrol/wallet:createcoupon',
                                                false,
                                                $context));
}

if ($capcouponview && $ismoodle) {
    // Adding page to view coupons.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_coupontable',
                                                get_string('coupon_table', 'enrol_wallet'),
                                                reports::COUPONS->url(),
                                                'enrol/wallet:viewcoupon',
                                                false,
                                                $context));
    // Adding page to view coupons usage.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_couponusage',
                                                get_string('coupon_usage', 'enrol_wallet'),
                                                reports::COUPONS_USAGE->url(),
                                                'enrol/wallet:viewcoupon',
                                                false,
                                                $context));
}

if ($capcredit) {
    // Adding page to charge wallets of other users.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_charging',
                                                get_string('chargingoptions', 'enrol_wallet'),
                                                manage::CHARGE->url(),
                                                'enrol/wallet:creditdebit',
                                                false,
                                                $context));
}

if ($captransactions) {
    // Adding page to show user's transactions.
    $url = reports::TRANSACTIONS->url();
    $pagename = get_string('transactions', 'enrol_wallet');
    $page = new admin_externalpage('wallettransactions', $pagename, $url, 'enrol/wallet:transaction', false, $context);
    $ADMIN->add('enrol_wallet_settings', $page);
}

if ($capbulkedit) {
    // Adding new page to bulk edit all user enrolments.
    $bulkeditor = get_string('bulkeditor', 'enrol_wallet');

    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_bulkedit',
                                                                $bulkeditor,
                                                                manage::BULKENROLMENTS->url(),
                                                                "enrol/wallet:bulkedit",
                                                                false,
                                                                $context));

    // Adding page to bulk edit all instances.
    $walletbulk = get_string('walletbulk', 'enrol_wallet');
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_bulkedit',
                                                                $walletbulk,
                                                                manage::BULKINSTANCES->url(),
                                                                "enrol/wallet:bulkedit",
                                                                false,
                                                                $context));
}
