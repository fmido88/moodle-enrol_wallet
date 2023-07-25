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
 * Task definition for enrol_wallet.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

// List of observers.
$observers = [
    // The observer to check the completion of a course and award the student.
    // Include the file containing the callback just in case.
    [
        'eventname'   => '\core\event\course_completed',
        'callback'    => '\enrol_wallet\observer::wallet_completion_awards',
        'includefile' => '/enrol/wallet/classes/observer.php'
    ],
    // Release the hold referral gift.
    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\enrol_wallet\observer::release_referral_gift',
    ],
    // The observer to check for new user creation and gift the student.
    // Include the file containing the callback just in case.
    [
        'eventname'   => '\core\event\user_created',
        'callback'    => '\enrol_wallet\observer::wallet_gifting_new_user',
        'includefile' => '/enrol/wallet/classes/observer.php'
    ],
    // Logout user from wordpress.
    [
        'eventname'   => '\core\event\user_loggedout',
        'callback'    => '\enrol_wallet\observer::logout_from_wordpress',
        'includefile' => '/enrol/wallet/classes/observer.php'
    ],
    // Observer to apply extra credit to fullfil the discount rule.
    [
        'eventname'   => '\enrol_wallet\event\transactions_triggered',
        'callback'    => '\enrol_wallet\observer::conditional_discount_charging',
        'includefile' => '/enrol/wallet/classes/observer.php'
    ],
];

