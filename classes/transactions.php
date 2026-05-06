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

/**
 * Functions to handle all wallet transactions and coupons operations.
 * @deprecated use balance_op class for operations, balance class for retrieving balance details.
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
}

