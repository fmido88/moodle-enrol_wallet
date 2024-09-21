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
 * wallet enrolment plugin referral page.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once(__DIR__.'/../lib.php');

global $DB, $USER;

$isparent = false;
if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
    require_once("$CFG->dirroot/auth/parent/auth.php");
    $authparent = new auth_plugin_parent;
    $isparent = $authparent->is_parent($USER);
}

if ($isparent) {
    redirect(new moodle_url('/'), get_string('referral_noparents', 'enrol_wallet'));
}

if (empty(get_config('enrol_wallet', 'referral_enabled'))) {
    redirect(new moodle_url('/'), get_string('referral_not_enabled', 'enrol_wallet'));
}

// Adding some security.
require_login(null, false);
$thisurl = new moodle_url('/enrol/wallet/extra/referral.php');

$PAGE->set_url($thisurl);

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$PAGE->set_title(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_heading(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_pagelayout('frontpage');

echo $OUTPUT->header();

// Handle AJAX request for applying referral bonus on top-up
if (optional_param('action', '', PARAM_ALPHA) === 'apply_referral_on_topup') {
    require_sesskey();
    $amount = required_param('amount', PARAM_FLOAT);
    $result = apply_referral_on_topup($USER->id, $amount);
    echo json_encode($result);
    die();
}

enrol_wallet\pages::process_referral_page();

echo $OUTPUT->footer();

/**
 * Apply referral bonus on top-up
 *
 * @param int $userid The ID of the user who topped up their wallet
 * @param float $amount The amount of the top-up
 * @return array The result of the operation
 */
function apply_referral_on_topup($userid, $amount) {
    global $DB;

    // Check if referral on top-up is enabled
    if (!get_config('enrol_wallet', 'referral_on_topup')) {
        return array('success' => false, 'message' => 'Referral on top-up is not enabled.');
    }

    // Get the minimum top-up amount for referral
    $minimumtopup = (float)get_config('enrol_wallet', 'referral_topup_minimum');

    // Check if the top-up amount meets the minimum requirement
    if ($amount < $minimumtopup) {
        return array('success' => false, 'message' => 'Top-up amount does not meet the minimum requirement for referral bonus.');
    }

    // Check if this user was referred
    $hold = $DB->get_record('enrol_wallet_hold_gift', ['referred' => $userid]);

    if ($hold && !$hold->released) {
        $referralamount = (float)get_config('enrol_wallet', 'referral_amount');
        $referrer = \core_user::get_user($hold->referrer);

        // Credit the referrer
        $refdesc = get_string('referral_topup', 'enrol_wallet', fullname($referrer));
        $transactions = new enrol_wallet\transactions();
        $referrerResult = $transactions->payment_topup($referralamount, $hold->referrer, $refdesc, $userid, false, false);

        // Credit the referred user
        $desc = get_string('referral_gift', 'enrol_wallet', fullname($referrer));
        $referredResult = $transactions->payment_topup($referralamount, $userid, $desc, $hold->referrer, false, false);

        // Mark the referral as released
        $DB->set_field('enrol_wallet_hold_gift', 'released', 1, ['id' => $hold->id]);
        $DB->set_field('enrol_wallet_hold_gift', 'timemodified', time(), ['id' => $hold->id]);

        return array('success' => true, 'message' => 'Referral bonus applied successfully.');
    }

    return array('success' => false, 'message' => 'No unreleased hold gift found for user.');
}
