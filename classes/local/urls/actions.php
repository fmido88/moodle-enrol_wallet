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
 *
 * Forms actions urls.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum actions: string {
    use base;
    // Bulk enrolments edit action page.
    case BULKENROLMENTS = 'actions/bulkedit_action.php';
    // Bulk edit instances actions page.
    case BULKINSTANCES = 'actions/bulkinstances_action.php';
    // Apply coupon action page.
    case APPLY_COUPON = 'actions/coupon_action.php';
    // Delete coupon action page.
    case DELETE_COUPON = 'actions/coupondelete.php';
    // Credit transformation action page.
    case TRANSFORM_ENROL_CREDIT = 'actions/credit_transformation.php';

    // Old way to add offers to navbar.
    case OFFERS_TO_NAV = 'actions/offers_nav.php';
    // Unenrol-self from a course.
    case UNENROL_SELF = 'unenrolself.php';
    // Wordpress login page.
    case WP_LOGIN = 'wplogin.php';
}
