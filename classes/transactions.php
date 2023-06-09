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
     * setup the notification.
     * @return notifications
     */
    private static function notify() {
        return new notifications();
    }
    /**
     * Function needed to topup the wallet in the corresponding wordpress website.
     * @param float $amount
     * @param int $userid
     * @param string $description the description of this transaction.
     * @param string|int $charger the user id who charged this amount.
     * @param bool $refundable If this transaction is refundable or not.
     * @return int|string the id of transaction record or error string response from the wordpress website.
     */
    public static function payment_topup($amount, $userid, $description = '', $charger = '', $refundable = true) {
        global $DB;

        if (empty($charger)) {
            $charger = $userid;
        }

        // Turn all credit operations to nonrefundable if refund settings not enabled.
        $refundenabled = get_config('enrol_wallet', 'enablerefund');

        if (empty($refundenabled)) {
            $refundable = false;
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
        // Check if a valid operation done.
        if ($newbalance <= $before) {
            return false;
        }

        $oldnotrefund = self::get_nonrefund_balance($userid);

        $recorddata = [
            'userid'      => $userid,
            'type'        => 'credit',
            'amount'      => $amount,
            'balbefore'   => $before,
            'balance'     => $newbalance,
            'norefund'    => $refundable ? $oldnotrefund : $amount + $oldnotrefund,
            'descripe'    => $description,
            'timecreated' => time()
        ];

        $id = $DB->insert_record('enrol_wallet_transactions', $recorddata);

        if ($refundable) {
            self::queue_transaction_transformation($id);
        }

        self::notify()->transaction_notify($recorddata);

        self::trigger_transaction_event($amount, 'credit', $charger, $userid, $description, $id, $refundable);

        return $id;
    }

    /** Function to deduct the credit from wallet balance.
     * @param int $userid
     * @param float $amount
     * @param string $coursename the name of the course.
     * @param int $charger the id of the charger user.
     * @param string $other another description.
     * @return mixed
     */
    public static function debit($userid, float $amount, $coursename = '', $charger = '', $other = '') {
        global $DB;
        if (empty($charger)) {
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
                return false;
            }

            $response = 'done';
        }

        // No debit occurs.
        if ($newbalance >= $before) {
            return false;
        }

        // Inserting a record in the transaction table.
        $a = (object)[
            'amount'     => $amount,
            'charger'    => $charger,
            'coursename' => $coursename,
        ];

        if (!empty($coursename)) {
            $description = get_string('debitdesc_course', 'enrol_wallet', $a);
        } else if (!empty($other)) {
            $description = $other;
        } else {
            $description = get_string('debitdesc_user', 'enrol_wallet', $a);
        }

        $oldnotrefund = self::get_nonrefund_balance($userid);

        $recorddata = [
            'userid'      => $userid,
            'type'        => 'debit',
            'amount'      => $amount,
            'balbefore'   => $before,
            'balance'     => $newbalance,
            'norefund'    => ($newbalance >= $oldnotrefund) ? $oldnotrefund : $newbalance,
            'descripe'    => $description,
            'timecreated' => time()
        ];

        $id = $DB->insert_record('enrol_wallet_transactions', $recorddata);

        self::notify()->transaction_notify($recorddata);

        self::trigger_transaction_event($amount, 'debit', $charger, $userid, $description, $id, false);

        return $id;
    }

    /**
     * Get the balance available to user from wp-site.
     * return the user balance or false or string in case of error.
     *
     * @param int $userid
     * @return float|false|string
     */
    public static function get_user_balance($userid) {
        global $DB;

        $source = get_config('enrol_wallet', 'walletsource');

        if ($source == self::SOURCE_WORDPRESS) {
            $wordpress = new \enrol_wallet\wordpress;
            $response = $wordpress->get_user_balance($userid);

            if (!is_numeric($response)) {
                // This mean error or user not exist yet.
                return 0;
            }

            return $response;

        } else if ($source == self::SOURCE_MOODLE) {

            // Get the balance from the last transaction.
            $sort = 'timecreated DESC,id DESC';
            $record = $DB->get_records('enrol_wallet_transactions', ['userid' => $userid], $sort, 'balance', 0, 1);

            // Getting the balance from last transaction.
            $key = array_key_first($record);
            // User with no records of any transactions means no balance yet.
            $balance = (!empty($record)) ? $record[$key]->balance : 0;

            return (float)$balance;

        } else {

            // Not likely, because this means settings error.
            return false;
        }
    }

    /**
     * Get the nonrefundable balance.
     *
     * @param int $userid
     * @return float
     */
    public static function get_nonrefund_balance($userid) {
        global $DB;
        $balance = self::get_user_balance($userid);
        $record = $DB->get_records('enrol_wallet_transactions', ['userid' => $userid], 'id DESC', 'norefund', 0, 1);

        // Getting the non refundable balance from last transaction.
        if (!empty($record)) {
            $key = array_key_first($record);
            $norefund = $record[$key]->norefund;
        } else {
            $norefund = 0;
        }

        if ($balance < $norefund) {
            $norefund = $balance;
        }

        return (float)$norefund;
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
            if (!empty($couponrecord->maxusage) && $couponrecord->maxusage <= $couponrecord->usetimes) {
                return get_string('coupon_exceedusage', 'enrol_wallet');
            }

            // Make sure that this coupon is within validation time (0 mean any time).
            if (!empty($couponrecord->validfrom) && $couponrecord->validfrom > time()) {
                $date = userdate($couponrecord->validfrom);
                return get_string('coupon_notvalidyet', 'enrol_wallet', $date);
            }

            if (!empty($couponrecord->validto) && $couponrecord->validto < time()) {
                return get_string('coupon_expired', 'enrol_wallet');
            }

            // Set the returning coupon data.
            $coupondata = [
                'value' => $couponrecord->value,
                'type'  => $couponrecord->type,
            ];
        }

        // Check if the coupon type is enabled in this site.
        if (
            $coupondata['type'] == 'percent' &&
            (
                $couponsetting == enrol_wallet_plugin::WALLET_COUPONSFIXED ||
                $couponsetting == enrol_wallet_plugin::WALLET_NOCOUPONS
            )
            ) {

            return get_string('discountcoupondisabled', 'enrol_wallet');
        }

        if (
            $coupondata['type'] == 'fixed' &&
            (
                $couponsetting == enrol_wallet_plugin::WALLET_COUPONSDISCOUNT ||
                $couponsetting == enrol_wallet_plugin::WALLET_NOCOUPONS
            )
            ) {

            return get_string('fixedcoupondisabled', 'enrol_wallet');
        }

        // Check if we applying the coupon (fixed value coupons) charge the wallet directly.
        if (
            $apply
            && $coupondata['type'] == 'fixed'
            && $couponsetting != enrol_wallet_plugin::WALLET_COUPONSDISCOUNT
            ) {

            $desc = get_string('topupcoupon_desc', 'enrol_wallet', $coupon);
            self::payment_topup($coupondata['value'], $userid, $desc, $userid);

            // Mark the coupon as used.
            self::mark_coupon_used($coupon, $userid, $instanceid, $coupondata['type'], $coupondata['value']);
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
                // And the coupon will be marked as used in enrol_self() function.
            }
        }

        return $coupondata;
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
        global $DB;

        // Unset the session coupon to make sure not used again.
        if (isset($_SESSION['coupon'])) {
            $_SESSION['coupon'] = '';
            unset($_SESSION['coupon']);
        }

        $source = get_config('enrol_wallet', 'walletsource');

        if ($source == self::SOURCE_WORDPRESS && $type == 'percent') {
            // It is already included in the wordpress plugin code.
            $couponrecord = (object)self::get_coupon_value($coupon, $userid, $instanceid, true);

        } else if ($source == self::SOURCE_WORDPRESS) {
            $couponrecord = (object)self::get_coupon_value($coupon, $userid, $instanceid, false);

        } else {

            $couponrecord = $DB->get_record('enrol_wallet_coupons', ['code' => $coupon]);
            $usage = $couponrecord->usetimes + 1;
            $data = (object)[
                'id'       => $couponrecord->id,
                'lastuse'  => time(),
                'usetimes' => $usage,
            ];
            $DB->update_record('enrol_wallet_coupons', $data);
        }

        // Logging the usage in the coupon usage table.
        $logdata = (object)[
            'code'       => $coupon,
            'type'       => !empty($type) ? $type : $couponrecord->type,
            'value'      => !empty($value) ? $value : $couponrecord->value,
            'userid'     => $userid,
            'instanceid' => $instanceid,
            'timeused'   => time(),
        ];
        $id = $DB->insert_record('enrol_wallet_coupons_usage', $logdata);

        $eventdata = [
            'userid'        => $userid,
            'relateduserid' => $userid,
            'objectid'      => !empty($id) ? $id : null,
            'other'         => [
                                'code' => $coupon,
                                ]
        ];

        if (!empty($instanceid)) {
            $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $instanceid], '*', MUST_EXIST);

            $eventdata['courseid'] = $instance->courseid;
            $eventdata['context'] = \context_course::instance($instance->courseid);

        } else {

            $eventdata['context'] = \context_system::instance();
        }

        $event = \enrol_wallet\event\coupon_used::create($eventdata);
        $event->trigger();
    }

    /**
     * Apply cashback after course purchace.
     * @param int $userid the user id
     * @param float $costafter the cost of the course after discounts
     * @param string $coursename the full name of the course
     * @param int $courseid the course id
     * @return void
     */
    public static function apply_cashback($userid, $costafter, $coursename, $courseid) {
        // Now apply the cashback if enabled.
        $cashbackenabled = get_config('enrol_wallet', 'cashback');

        if ($cashbackenabled) {
            $percent = get_config('enrol_wallet', 'cashbackpercent');
            $desc = get_string('cashbackdesc', 'enrol_wallet', $coursename);
            $value = $costafter * $percent / 100;
            $id = self::payment_topup($value, $userid, $desc, $userid, false);
            // Trigger cashback event.
            $eventdata = [
                'context'       => \context_course::instance($courseid),
                'courseid'      => $courseid,
                'objectid'      => $id,
                'userid'        => $userid,
                'relateduserid' => $userid,
                'other'         => [
                        'amount'   => $value,
                        'original' => $costafter,
                ],
            ];
            $event = \enrol_wallet\event\cashback_applied::create($eventdata);
            $event->trigger();
        }
    }
    /**
     * Triggering transactions event.
     * @param float $amount amount of the transaction.
     * @param string $type credit or debit
     * @param int $charger id of the charger user
     * @param int $userid id of the user related to the transaction
     * @param string $desc reason of the transaction
     * @param int $id id of the record in the transaction table
     * @param bool $refundable is the transaction is refundable
     * @return void
     */
    private static function trigger_transaction_event($amount, $type, $charger, $userid, $desc, $id, $refundable) {
        require_once(__DIR__.'/event/transactions_triggered.php');
        $context = \context_system::instance();

        $eventarray = [
                        'context'       => $context,
                        'objectid'      => $id,
                        'userid'        => $charger,
                        'relateduserid' => $userid,
                        'other' => [
                                    'type'       => $type,
                                    'amount'     => $amount,
                                    'refundable' => $refundable,
                                    'desc'       => $desc,
                                    ],
                    ];

        $event = \enrol_wallet\event\transactions_triggered::create($eventarray);
        $event->trigger();
    }

    /**
     * Quene the tast to Transform the amount of a certain credit transaction to be nonrefundable
     * after the grace period is over.
     * @param int $id the id of the transaction record.
     * @return void
     */
    private static function queue_transaction_transformation($id) {
        global $DB;
        $record = $DB->get_record('enrol_wallet_transactions', ['id' => $id]);
        $period = get_config('enrol_wallet', 'refundperiod');

        if (empty($period)) {
            return;
        }

        $runtime = time() + $period;

        $task = new \enrol_wallet\task\turn_non_refundable;
        $task->set_custom_data(
                [
                    'id'     => $id,
                    'userid' => $record->userid,
                    'amount' => $record->amount,
                ]
            );

        $task->set_next_run_time($runtime);

        \core\task\manager::queue_adhoc_task($task);
    }
}

