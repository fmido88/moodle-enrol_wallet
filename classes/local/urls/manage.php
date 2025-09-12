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
 * Wallet manage pages urls.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum manage: string {
    use base;
    // Bulk wallet enrolments edit page.
    case BULKENROLMENTS = 'manage/bulkedit.php';
    // Bulk wallet instances edit page.
    case BULKINSTANCES = 'manage/bulkinstances.php';
    // Charge another user form and action.
    case CHARGE = 'manage/charger.php';
    // Manage conditional discount page.
    case CONDITIONAL_DISCOUNT = 'manage/conditionaldiscount.php';

    // Create coupons page.
    case GENERATE_COUPON = 'manage/coupon.php';
    // Edit coupon page.
    case EDIT_COUPON = 'manage/couponedit.php';
    // Upload csv coupons page.
    case UPLOAD_COUPONS = 'manage/couponupload.php';
}
