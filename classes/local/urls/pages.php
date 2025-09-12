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

namespace enrol_wallet\local\urls;

// phpcs:ignore moodle.Commenting.InlineComment.DocBlock
/**
 * General pages available to users.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum pages: string {
    use base;
    // Wallet offers page.
    case OFFERS = 'pages/offers.php';
    // Referral page.
    case REFERRAL = 'pages/referral.php';
    // Confirm payment for topup page.
    case TOPUP = 'pages/topup.php';
    // Confirm enrolment and deduction page.
    case CONFIRM_ENROL = 'confirm.php';
    // Transfer balance to another user page.
    case TRANSFER = 'pages/transfer.php';
    // Wallet home page.
    case WALLET = 'wallet.php';
}
