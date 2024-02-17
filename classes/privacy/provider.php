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
 * Privacy Subsystem implementation for enrol_wallet.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\metadata\collection;
use enrol_wallet\util\balance;

/**
 * Privacy Subsystem for enrol_wallet implementing null_provider.
 *
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider,
    \core_payment\privacy\consumer_provider {

    /**
     * Returns meta data about this system.
     * @param collection $collection The initialized collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('enrol_wallet_items', [
            'userid'     => "privacy:metadata:enrol_wallet_items:userid",
            'cost'       => "privacy:metadata:enrol_wallet_items:cost",
            'currency'   => "privacy:metadata:enrol_wallet_items:currency",
            'instanceid' => "privacy:metadata:enrol_wallet_items:instanceid",
            ], "privacy:metadata:enrol_wallet_items");

        $collection->add_database_table('enrol_wallet_awards', [
            'userid'   => "privacy:metadata:enrol_wallet_awards:userid",
            'courseid' => "privacy:metadata:enrol_wallet_awards:courseid",
            'grade'    => "privacy:metadata:enrol_wallet_awards:grade",
            'amount'   => "privacy:metadata:enrol_wallet_awards:amount",
            ], "privacy:metadata:enrol_wallet_awards");

        $collection->add_database_table('enrol_wallet_transactions', [
            'userid'    => "privacy:metadata:enrol_wallet_transactions:userid",
            'type'      => "privacy:metadata:enrol_wallet_transactions:type",
            'amount'    => "privacy:metadata:enrol_wallet_transactions:amount",
            'balance'   => "privacy:metadata:enrol_wallet_transactions:balance",
            'balbefore' => "privacy:metadata:enrol_wallet_transactions:balbefore",
            'norefund'  => "privacy:metadata:enrol_wallet_transactions:norefund",
            'descripe'  => "privacy:metadata:enrol_wallet_transactions:description",
        ], "privacy:metadata:enrol_wallet_transactions");

        $collection->add_database_table('enrol_wallet_coupons_usage', [
            'userid'     => "privacy:metadata:enrol_wallet_coupons_usage:userid",
            'instanceid' => "privacy:metadata:enrol_wallet_coupons_usage:instanceid",
        ], "privacy:metadata:enrol_wallet_coupons_usage");

        $collection->add_database_table('enrol_wallet_referral', [
            'userid'   => "privacy:metadata:enrol_wallet_referral:userid",
            'code'     => "privacy:metadata:enrol_wallet_referral:code",
            'usetimes' => "privacy:metadata:enrol_wallet_referral:usetimes",
            'users'    => "privacy:metadata:enrol_wallet_referral:users",
        ], "privacy:metadata:enrol_wallet_referral");

        $collection->add_database_table('enrol_wallet_hold_gift', [
            'referred' => "privacy:metadata:enrol_wallet_hold_gift:referred",
            'courseid' => "privacy:metadata:enrol_wallet_hold_gift:courseid",
            'amount'   => "privacy:metadata:enrol_wallet_hold_gift:amount",
            'referrer' => "privacy:metadata:enrol_wallet_hold_gift:referrer",
        ], "privacy:metadata:enrol_wallet_hold_gift");

        $collection->add_database_table('enrol_wallet_balance', [
            'userid'      => "privacy:metadata:enrol_wallet_balance:userid",
            'refundable'     => "privacy:metadata:enrol_wallet_balance:refundable",
            "nonrefundable"   => "privacy:metadata:enrol_wallet_balance:nonrefundable",
            'cat_balance' => "privacy:metadata:enrol_wallet_balance:catbalance",
            'freegift'    => "privacy:metadata:enrol_wallet_balance:freegift",
        ], "privacy:metadata:enrol_wallet_balance");

        $collection->add_database_table('enrol_wallet_cond_discount', [
            'usermodified' => "privacy:metadata:enrol_wallet_cond_discount:usermodified",
        ], "privacy:metadata:enrol_wallet_cond_discount");

        $source = get_config('enrol_wallet', 'walletsource');
        if ($source == balance::WP) {
            $collection->add_external_location_link('wordpress', [
                'userid'   => "privacy:metadata:wordpress:userid",
                'email'    => "privacy:metadata:wordpress:email",
                'username' => "privacy:metadata:wordpress:username",
                'password' => "privacy:metadata:wordpress:password",
            ], "privacy:metadata:wordpress");
        }

        return $collection;
    }

    /**
     * Return contextid for the provided payment data
     * @param string $paymentarea
     * @param int $itemid
     * @return int|null
     */
    public static function get_contextid_for_payment(string $paymentarea, int $itemid): ?int {
        global $DB;
        if ($paymentarea == 'walletenrol') {
            $sql = "SELECT ctx.id
                    FROM {enrol} e
                    JOIN {context} ctx ON (e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse)
                    JOIN {enrol_wallet_items} it ON it.instanceid = e.id
                    WHERE it.id = :itemid AND e.enrol = :enrolname";
            $params = [
                'contextcourse' => CONTEXT_COURSE,
                'itemid'       => $itemid,
                'enrolname'     => 'wallet',
            ];
            $contextid = $DB->get_field_sql($sql, $params);
        } else if ($paymentarea == 'wallettopup') {
            $contextid = \context_system::instance()->id;
        }
        return $contextid ?: null;
    }

    /**
     * Get the list of users who have data within a context.
     * @param \core_privacy\local\request\userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context instanceof \context_course) {
            $sql = "SELECT p.userid
                      FROM {payments} p
                      JOIN {enrol_wallet_items} it ON p.itemid = it.id
                      JOIN {enrol} e ON (p.component = :component AND  it.instanceid = e.id)
                     WHERE e.courseid = :courseid";
            $params = [
                'component' => 'enrol_wallet',
                'courseid'  => $context->instanceid,
            ];
            $userlist->add_from_sql('userid', $sql, $params);

        } else if ($context instanceof \context_system) {
            // If context is system, then the enrolment belongs to a deleted enrolment.
            $sql = "SELECT p.userid
                      FROM {payments} p
                 LEFT JOIN {enrol} e ON p.itemid = e.id
                     WHERE p.component = :component AND e.id IS NULL";
            $params = [
                'component' => 'enrol_wallet',
            ];
            $userlist->add_from_sql('userid', $sql, $params);

            // Also there if fake items for topping up the wallet.
            $sql = "SELECT p.userid
                      FROM {payments} p
                 LEFT JOIN {enrol_wallet_items} it ON (p.itemid = it.id AND p.userid = it.userid)
                     WHERE p.component = :component
                     AND p.paymentarea = :paymentarea
                     AND (it.instanceid IS NULL OR it.instanceid = 0)
                     GROUP BY p.userid";
            $params = [
                'component'   => 'enrol_wallet',
                'paymentarea' => 'wallettopup',
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $subcontext = [
            get_string('pluginname', 'enrol_wallet'),
        ];
        foreach ($contextlist as $context) {
            if ($context instanceof \context_course) {
                $paymentarea = 'walletenrol';
                $sql = "SELECT it.*
                        FROM {enrol} e
                        JOIN {enrol_wallet_items} it ON it.instanceid = e.id
                        WHERE e.courseid = :courseid
                            AND enrol = :enrol";
                $items = $DB->get_records_sql($sql, ['courseid' => $context->instanceid, 'enrol' => 'wallet']);

                foreach ($items as $item) {
                    if (!class_exists('\core_payment\privacy\provider')) {
                        break;
                    }
                    \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                        $context,
                        $subcontext,
                        $contextlist->get_user()->id,
                        'enrol_wallet',
                        $paymentarea,
                        $item->id
                    );
                }
            } else {
                $paymentarea = 'wallettopup';
                $items = $DB->get_records('enrol', ['courseid' => $context->instanceid, 'enrol' => 'wallet']);

                foreach ($items as $item) {
                    if (!class_exists('\core_payment\privacy\provider')) {
                        break;
                    }
                    \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                        $context,
                        $subcontext,
                        $contextlist->get_user()->id,
                        'enrol_wallet',
                        $paymentarea,
                        $item->id
                    );
                }
            }

        }

        if (in_array(SYSCONTEXTID, $contextlist->get_contextids())) {
            $dbman = $DB->get_manager();
            if (!$dbman->table_exists('payments')) {
                return;
            }
            // Orphaned payments for deleted enrollments.
            $sql = "SELECT p.*
                      FROM {payments} p
                 LEFT JOIN {enrol_wallet_items} it ON p.itemid = it.id
                 LEFT JOIN {enrol} e ON e.id = it.instanceid
                     WHERE p.userid = :userid
                     AND p.component = :component
                     AND (
                        (e.id IS NULL AND it.instanceid IS NOT NULL)
                        OR it.instanceid IS NULL
                        OR it.instanceid = 0
                        )";
            $params = [
                'component'   => 'enrol_wallet',
                'userid'      => $contextlist->get_user()->id,
            ];

            $orphanedpayments = $DB->get_recordset_sql($sql, $params);
            foreach ($orphanedpayments as $payment) {
                if (!class_exists('\core_payment\privacy\provider')) {
                    break;
                }
                \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                    \context_system::instance(),
                    $subcontext,
                    $payment->userid,
                    $payment->component,
                    $payment->paymentarea,
                    $payment->itemid
                );
            }
            $orphanedpayments->close();
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('payments')) {
            return;
        }
        if ($context instanceof \context_course) {
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {enrol} e ON (p.component = :component AND p.itemid = e.id)
                     WHERE e.courseid = :courseid";
            $params = [
                'component' => 'enrol_wallet',
                'courseid'  => $context->instanceid,
            ];
            if (class_exists('\core_payment\privacy\provider')) {
                \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            }

        } else if ($context instanceof \context_system) {
            // If context is system, then the enrolment belongs to a deleted enrolment.
            $sql = "SELECT p.id
                      FROM {payments} p
                 LEFT JOIN {enrol} e ON p.itemid = e.id
                     WHERE p.component = :component AND e.id IS NULL";
            $params = [
                'component' => 'enrol_wallet',
            ];
            if (class_exists('\core_payment\privacy\provider')) {
                \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            }
            // Also there if fake items for topping up the wallet.
            $sql = "SELECT p.userid
                      FROM {payments} p
                 LEFT JOIN {enrol_wallet_items} it ON (p.itemid = it.id AND p.userid = it.userid)
                     WHERE p.component = :component AND p.paymentarea = :paymentarea";
            $params = [
                'component'   => 'enrol_wallet',
                'paymentarea' => 'wallettopup',
            ];

            if (class_exists('\core_payment\privacy\provider')) {
                \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            }
            // Delete fake items.
            $ids = $DB->get_records('payments', ['component' => 'enrol_wallet', 'paymentarea' => 'wallettopup']);
            foreach ($ids as $payment) {
                $DB->delete_records('enrol_wallet_items', ['id' => $payment->itemid, 'userid' => $payment->userid]);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('payments')) {
            return;
        }
        $contexts = $contextlist->get_contexts();

        $courseids = [];
        foreach ($contexts as $context) {
            if ($context instanceof \context_course) {
                $courseids[] = $context->instanceid;
            }
        }

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $sql = "SELECT p.id
                  FROM {payments} p
                  JOIN {enrol_wallet_items} it ON p.itemid = it.id
                  JOIN {enrol} e ON (p.component = :component AND it.instanceid = e.id)
                 WHERE p.userid = :userid AND e.courseid $insql";
        $params = $inparams + [
            'component' => 'enrol_wallet',
            'userid'    => $contextlist->get_user()->id,
        ];
        if (class_exists('\core_payment\privacy\provider')) {
            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        }

        if (in_array(SYSCONTEXTID, $contextlist->get_contextids())) {
            // Orphaned payments.
            // First deleted enrollments.
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {enrol_wallet_items} it ON p.itemid = it.id
                 LEFT JOIN {enrol} e ON it.instanceid = e.id
                     WHERE p.component = :component
                        AND p.userid = :userid
                        AND e.id IS NULL";
            $params = [
                'component' => 'enrol_wallet',
                'userid' => $contextlist->get_user()->id,
            ];
            if (class_exists('\core_payment\privacy\provider')) {
                \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            }
            // Also check for wallet topup.
            $sql = "SELECT p.id
                      FROM {payments} p
                 LEFT JOIN {enrol_wallet_items} it ON p.itemid = it.id
                     WHERE p.component = :component
                        AND p.paymentarea = :paymentarea
                        AND p.userid = :userid";
            $params = [
                'component'   => 'enrol_wallet',
                'userid'      => $contextlist->get_user()->id,
                'paymentarea' => 'wallettopup',
            ];
            if (class_exists('\core_payment\privacy\provider')) {
                \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            }
            // Delete fake items.
            $ids = $DB->get_records('payments', [
                                                    'component'   => 'enrol_wallet',
                                                    'paymentarea' => 'wallettopup',
                                                    'userid'      => $contextlist->get_user()->id,
                                                ]);
            foreach ($ids as $payment) {
                $DB->delete_records('enrol_wallet_items', ['id' => $payment->itemid, 'userid' => $payment->userid]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context instanceof \context_course) {
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {enrol_wallet_items} it ON p.itemid = it.id
                      JOIN {enrol} e ON (p.component = :component AND it.instanceid = e.id)
                     WHERE e.courseid = :courseid AND p.userid $usersql";
            $params = $userparams + [
                'component' => 'enrol_wallet',
                'courseid'  => $context->instanceid,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        } else if ($context instanceof \context_system) {
            // Orphaned payments.
            // First deleted enrollments.
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {enrol_wallet_items} it ON p.itemid = it.id
                 LEFT JOIN {enrol} e ON it.instanceid = e.id
                     WHERE p.component = :component
                        AND p.userid $usersql
                        AND (e.id IS NULL OR it.id IS NULL)";
            $params = $userparams + [
                'component' => 'enrol_wallet',
            ];
            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            // Also check for wallet topup.
            $sql = "SELECT p.id
                      FROM {payments} p
                 LEFT JOIN {enrol_wallet_items} it ON p.itemid = it.id
                     WHERE p.component = :component
                       AND p.paymentarea = :paymentarea
                       AND p.userid $usersql
                       AND it.userid = p.userid";
            $params = $userparams + [
                'component'   => 'enrol_wallet',
                'paymentarea' => 'wallettopup',
            ];
            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            // Delete fake items.
            $sql = "SELECT p.itemid
                      FROM {payments} p
                 LEFT JOIN {enrol_wallet_items} it ON p.itemid = it.id
                     WHERE p.component = :component
                       AND p.paymentarea = :paymentarea
                       AND p.userid $usersql
                       AND it.userid = p.userid";
            $ids = $DB->get_records_sql($sql, $params);
            foreach ($ids as $payment) {
                $DB->delete_records('enrol_wallet_items', ['id' => $payment->itemid]);
            }
        }
    }
}
