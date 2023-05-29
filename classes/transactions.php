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
use enrol_wallet\notifications;
use enrol_wallet_plugin;

/**
 * Functions to handle all wallet transactions and coupons operations.
 *
 */
class transactions {

    /**
     * If the wallet source is from wordpress site.
     */
    public const SOURCE_WORDPRESS = 0;
    /**
     * If the wallet source is from this moodle site.
     */
    public const SOURCE_MOODLE = 1;
    /**
     * Mocking the notification class, useful for phpunit test.
     * @var
     */
    public $notify = null;
    /**
     * setup the notification.
     * @return notifications
     */
    private static function notify() {
        if (empty($notify)) {
            $notify = new notifications();
        }
        return $notify;
    }
    /**
     * Function needed to topup the wallet in the corresponding wordpress website.
     * @param float $amount
     * @param int $userid
     * @param string $description the description of this transaction.
     * @param string|int $charger the user id who charged this amount.
     * @return array|string the response from the wordpress website.
     */
    public static function payment_topup($amount, $userid, $description = '', $charger = '') {
        global $DB;
        if ($charger === '') {
            $charger = $userid;
        }
        $before = self::get_user_balance($userid);
        $source = get_config('enrol_wallet', 'walletsource');
        if ($source == self::SOURCE_WORDPRESS) {

            $wordpress = new \enrol_wallet\wordpress;
            $responsedata = $wordpress->credit($amount, $userid, $description, $charger);

            if (is_string($responsedata)) {
                return $responsedata;
            }

            $newbalance = self::get_user_balance($userid);
        } else {
            $newbalance = $before + $amount;
        }

        $recorddata = [
            'userid' => $userid,
            'type' => 'credit',
            'amount' => $amount,
            'balbefore' => $before,
            'balance' => $newbalance,
            'descripe' => $description.' by user with id '.$charger,
            'timecreated' => time()
        ];

        $DB->insert_record('enrol_wallet_transactions', $recorddata);
        $responsedata['success'] = true;
        self::notify()->transaction_notify($recorddata);

        return $responsedata['success'];
    }

    /** Function to deduct the credit from wallet balance.
     * @param int $userid
     * @param float $amount
     * @param string $coursename the name of the course.
     * @param int $charger the id of the charger user.
     * @return mixed
     */
    public static function debit($userid, float $amount, $coursename = '', $charger = '') {
        if ($charger === '') {
            $charger = $userid;
        }
        $before = self::get_user_balance($userid);
        $source = get_config('enrol_wallet', 'walletsource');
        if ($source == self::SOURCE_WORDPRESS) {
            $wordpress = new \enrol_wallet\wordpress;

            $response = $wordpress->debit($userid, $amount, $coursename, $charger);

            $newbalance = self::get_user_balance($userid);
        } else if ($source == self::SOURCE_MOODLE) {
            $newbalance = $before - $amount;

            if ($newbalance < 0) {
                // This is mean that value to debit is greater than the balance and the new balance is negative.
                // TODO throw error.
                return null;
            }
            $response = 'done';
        }

        // Inserting a record in the transaction table.
        global $DB;

        $a = (object)[
            'amount' => $amount,
            'charger' => $charger,
            'coursename' => $coursename,
        ];

        if ($coursename !== '') {
            $description = get_string('debitdesc_course', 'enrol_wallet', $a);
        } else {
            $description = get_string('debitdesc_user', 'enrol_wallet', $a);
        }

        $recorddata = [
            'userid' => $userid,
            'type' => 'debit',
            'amount' => $amount,
            'balbefore' => $before,
            'balance' => $newbalance,
            'descripe' => $description,
            'timecreated' => time()
        ];
        $DB->insert_record('enrol_wallet_transactions', $recorddata);
        self::notify()->transaction_notify($recorddata);

        return $response;
    }

    /**
     * Get the balance available to user from wp-site.
     * return the user balance or false or string in case of error.
     *
     * @param int $userid
     * @return float|false|string
     */
    public static function get_user_balance($userid) {
        $source = get_config('enrol_wallet', 'walletsource');
        if ($source == self::SOURCE_WORDPRESS) {
            $wordpress = new \enrol_wallet\wordpress;
            $response = $wordpress->get_user_balance($userid);
            if (!is_numeric($response)) {
                return 0;
            }
            return $response;
        } else if ($source == self::SOURCE_MOODLE) {
            global $DB;

            $record = $DB->get_records('enrol_wallet_transactions', ['userid' => $userid], 'id DESC', 'balance', 0, 1);

            // Getting the balance form last transaction.
            $key = array_key_first($record);
            $balance = (!empty($record)) ? $record[$key]->balance : 0;

            return (float)$balance;
        } else {
            return false;
        }

    }

    /** Getting the value of the coupon.
     *  Apply the coupon value when using it.
     *  We apply the code automatic if it is fixed value coupon.
     * @param string $coupon the coupon code to check.
     * @param int $userid
     * @param int $instanceid
     * @param bool $apply Apply for fixed values only.
     * @return array|string the value of the coupon and its type in array or string represent the error if the code is not valid
     */
    public static function get_coupon_value($coupon, $userid, $instanceid = 0, $apply = false) {
        global $DB;
        $couponsetting = get_config('enrol_wallet', 'coupons');

        if ($couponsetting == enrol_wallet_plugin::WALLET_NOCOUPONS) {
            // This means that coupons is disabled in the site.
            return false;
        }

        $source = get_config('enrol_wallet', 'walletsource');
        if ($source == self::SOURCE_WORDPRESS) {
            $wordpress = new \enrol_wallet\wordpress;
            $coupondata = $wordpress->get_coupon($coupon, $userid, $instanceid = 0, $apply);

            if (!is_array($coupondata)) {
                return $coupondata;
            }

        } else {
            // If it is on moodle website.
            // Get the coupon data from the database.
            $couponrecord = $DB->get_record('enrol_wallet_coupons', ['code' => $coupon]);
            if (!$couponrecord) {
                return get_string('coupon_notexist', 'enrol_wallet');
            }
            // Make sure that the coupon didn't exceed the max usage (0 mean unlimited).
            if ($couponrecord->maxusage <= $couponrecord->usetimes && $couponrecord->maxusage != 0) {
                return get_string('coupon_exceedusage', 'enrol_wallet');
            }
            // Make sure that this coupon is within validation time (0 mean any time).
            if ($couponrecord->validfrom > time() && $couponrecord->validfrom != 0) {
                $date = userdate($couponrecord->validfrom);
                return get_string('coupon_notvalidyet', 'enrol_wallet', $date);
            }
            if ($couponrecord->validto < time() && $couponrecord->validto != 0) {
                return get_string('coupon_expired', 'enrol_wallet');
            }
            // Set the returning coupon data.
            $coupondata = [
                'value' => $couponrecord->value,
                'type' => $couponrecord->type,
            ];

            if ($apply) {
                // Mark the coupon as used.
                self::mark_coupon_used($coupon, $userid, $instanceid);
            }
        }

        // Check if we applying the coupon.
        if ($apply) {
            if ($coupondata['type'] == 'fixed' && $couponsetting != enrol_wallet_plugin::WALLET_COUPONSDISCOUNT) {
                $desc = get_string('topupcoupon_desc', 'enrol_wallet', $coupon);
                self::payment_topup($coupondata['value'], $userid, $desc, $userid);
            }
        }

        // Check if the coupon type is enabled in this site.
        if ($coupondata['type'] == 'percent' &&
            ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSFIXED ||
            $couponsetting == enrol_wallet_plugin::WALLET_NOCOUPONS)) {
            return get_string('discountcoupondisabled', 'enrol_wallet');
        }

        if ($coupondata['type'] == 'fixed' &&
            ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSDISCOUNT ||
            $couponsetting == enrol_wallet_plugin::WALLET_NOCOUPONS)) {
            return get_string('fixedcoupondisabled', 'enrol_wallet');
        }

        // After we get the coupon data now we check if this coupon used from enrolment page.
        // If true and the value >= the fee, save time for student and enrol directly.
        if (
            $apply &&
            0 != $instanceid &&
            $coupondata['type'] == 'fixed' &&
            $couponsetting != enrol_wallet_plugin::WALLET_COUPONSDISCOUNT
            ) {
            $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $instanceid], '*', MUST_EXIST);
            $user = \core_user::get_user($userid);

            $plugin = enrol_get_plugin('wallet');
            $fee = (float)$plugin->get_cost_after_discount($userid, $instance);
            // Check if the coupon value is grater than or equal the fee.
            // Enrol the user in the course.
            if ($coupondata['value'] >= $fee) {
                $plugin->enrol_self($instance, $user);
                $coursename = get_course($instance->courseid)->fullname;
                self::debit($userid, $fee, '('.$coursename.') by coupon');
            }
        }
        return $coupondata;
    }

    /**
     * Called when the coupon get used and mark it as used.
     * @param string $coupon the coupon code.
     * @param int $userid
     * @param int $instanceid
     * @return void
     */
    public static function mark_coupon_used($coupon, $userid, $instanceid) {

        $source = get_config('enrol_wallet', 'walletsource');

        if ($source == self::SOURCE_WORDPRESS) {
            // It is already included in the wordpress plugin code.
            self::get_coupon_value($coupon, $userid, $instanceid, true);
        } else {
            global $DB;
            $couponrecord = $DB->get_record('enrol_wallet_coupons', ['code' => $coupon]);
            $usage = $couponrecord->usetimes + 1;
            $data = (object)[
                'id' => $couponrecord->id,
                'lastuse' => time(),
                'usetimes' => $usage,
            ];
            $DB->update_record('enrol_wallet_coupons', $data);
            // Logging the usage in the coupon usage table.
            $logdata = (object)[
                'code' => $coupon,
                'type' => $couponrecord->type,
                'value' => $couponrecord->value,
                'userid' => $userid,
                'instanceid' => $instanceid,
                'timeused' => time(),
            ];
            $DB->insert_record('enrol_wallet_coupons_usage', $logdata);
        }

    }
}

