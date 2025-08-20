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
 * Functions to handle all wallet transactions and coupons operations.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;

use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet\local\coupons\coupons;

/**
 * Functions to handle all wallet transactions and coupons operations.
 * @deprecated
 */
class transactions {

    /**
     * If the wallet source is from wordpress site.
     */
    public const SOURCE_WORDPRESS = balance::WP;
    /**
     * If the wallet source is from this moodle site.
     */
    public const SOURCE_MOODLE = balance::MOODLE;

    /**
     * Function needed to topup the wallet in the corresponding wordpress website or internal moodle wallet system.
     * @param float $amount
     * @param int $userid
     * @param string $description the description of this transaction.
     * @param string|int $charger the user id who charged this amount.
     * @param bool $refundable If this transaction is refundable or not.
     * @param bool $trigger Trigger the transaction event or not.
     * @return int|string the id of transaction record or error string.
     * @deprecated
     */
    public static function payment_topup($amount, $userid, $description = '', $charger = '', $refundable = true, $trigger = true) {
        $util = new balance_op($userid);
        if (!empty($cahrger)) {
            $by = balance_op::USER;
            $thingid = $charger;
        } else {
            $by = balance_op::OTHER;
            $thingid = 0;
        }
        $util->credit($amount, $by, $thingid, $description, $refundable, $trigger);
        return $util->get_transaction_id();
    }

    /** Function to deduct the credit from wallet balance.
     * @param int $userid
     * @param float $amount
     * @param string $coursename the name of the course.
     * @param int $charger the id of the charger user.
     * @param string $other another description.
     * @param int $courseid
     * @param bool $neg Allow negative balance.
     * @deprecated
     * @return mixed
     */
    public static function debit(
                                $userid,
                                float $amount,
                                $coursename = '',
                                $charger = '',
                                $other = '',
                                $courseid = 0,
                                $neg = false
                                ) {
        $util = new balance_op($userid);
        if (!empty($coursename) && !empty($courseid)) {
            $for = balance_op::D_ENROL_COURSE;
            $thingid = $courseid;
        } else if (!empty($charger)) {
            $for = balance_op::USER;
            $thingid = $charger;
        } else {
            $for = balance_op::OTHER;
            $thingid = 0;
        }

        $util->debit($amount, $for, $thingid, $other, $neg);
        return $util->get_transaction_id();
    }

    /**
     * Get the balance available to user from wp-site or moodle.
     * return the user balance, or false|string in case of error.
     *
     * @param int $userid
     * @return float|false|string
     * @deprecated
     */
    public static function get_user_balance($userid) {
        $util = new balance($userid);
        return $util->get_valid_balance();
    }

    /**
     * Get the nonrefundable balance.
     *
     * @param int $userid
     * @return float
     * @deprecated
     */
    public static function get_nonrefund_balance($userid) {
        $op = new balance_op($userid);

        return (float)$op->get_valid_nonrefundable();
    }

    /** Getting the value of the coupon.
     *  Apply the coupon value when using it.
     *  We apply the code automatic if it is fixed value coupon.
     * @param string $coupon the coupon code to check.
     * @param int $userid
     * @param int $instanceid
     * @param bool $apply Apply for fixed values only.
     * @param int $cmid
     * @param int $sectionid
     * @return array|string the value of the coupon and its type in array or string represent the error if the code is not valid
     */
    public static function get_coupon_value($coupon, $userid, $instanceid = 0, $apply = false, $cmid = 0, $sectionid = 0) {
        return coupons::get_coupon_value($coupon, $userid, $apply, $instanceid, $cmid, $sectionid);
    }

    /**
     * Apply the coupon for enrolment or topping up the wallet.
     * @param array $coupondata
     * @param int $userid
     * @param int $instanceid
     * @return void
     */
    public static function apply_coupon($coupondata, $userid, $instanceid) {
        $coupon = new coupons($coupondata['code'], $userid);
        if (!empty($instanceid)) {
            $area = coupons::AREA_ENROL;
            $areaid = $instanceid;
        } else {
            $area = coupons::AREA_TOPUP;
            $areaid = 0;
        }
        return $coupon->apply_coupon($area, $areaid);
    }

    /**
     * Check if the coupon is valid to be used in this area.
     * returns string on error and true if valid.
     * @param array $coupondata code, value, type, courses, category
     * @param array $area the area at which the coupon applied (instanceid, cmid, sectionid)
     * @return bool|string
     */
    public static function validate_coupon($coupondata, $area = []) {
        global $DB, $USER;

        if (is_string($coupondata)) {
            return $coupondata;
        }
        $coupons = new coupons($coupondata['code']);
        if (!empty($area['instanceid'])) {
            $areatype = coupons::AREA_ENROL;
            $areaid = $area['instanceid'];
        } else if (!empty($area['cmid'])) {
            $areatype = coupons::AREA_CM;
            $areaid = $area['cmid'];
        } else if (!empty($area['sectionid'])) {
            $areatype = coupons::AREA_SECTION;
            $areaid = $area['sectionid'];
        } else {
            $areatype = coupons::AREA_TOPUP;
            $areaid = 0;
        }
        return $coupons->validate_coupon($areatype, $areaid);
    }
    /**
     * Called when the coupon get used and mark it as used.
     * @param string $coupon the coupon code.
     * @param int $userid
     * @param int $instanceid
     * @param string $type percent or fixed.
     * @param float $value the value of the coupon
     * @return void
     */
    public static function mark_coupon_used($coupon, $userid, $instanceid = 0, $type = '', $value = '') {
        $coupons = new coupons($coupon, $userid);
        if (!empty($instanceid)) {
            $area = coupons::AREA_ENROL;
            $areaid = $instanceid;
        } else {
            $area = coupons::AREA_TOPUP;
            $areaid = 0;
        }
        $coupons->validate_coupon($area, $areaid);
        $coupons->mark_coupon_used();
    }
}

