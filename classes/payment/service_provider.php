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
 * Payment subsystem callback implementation for enrol_wallet.
 *
 * @package    enrol_wallet
 * @category   payment
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\payment;

/**
 * Payment subsystem callback implementation for enrol_wallet.
 *
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {

    /**
     * Callback function that returns the enrolment cost and the accountid
     * for the course that $instanceid enrolment instance belongs to or itemid
     * for fake item to topup wallet.
     *
     * @param string $paymentarea Payment area
     * @param int $itemid The enrolment instance id or fake item id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $itemid): \core_payment\local\entities\payable {
        global $DB, $USER;
        // Check if the payment is for enrolment or topup the wallet.
        if ($paymentarea == 'walletenrol') {
            $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $itemid], '*', MUST_EXIST);
            require_once(__DIR__.'/../../lib.php');
            $coupon = isset($_SESSION['coupon']) ? $_SESSION['coupon'] : null;
            $fee = (float)\enrol_wallet_plugin::get_cost_after_discount($USER->id, $instance, $coupon);
            $balance = (float)\enrol_wallet\transactions::get_user_balance($USER->id);
            $cost = $fee - $balance;
            return new \core_payment\local\entities\payable((float)$cost, $instance->currency, (int)$instance->customint1);
        } else {
            global $DB;
            // Get the fake item in case of topup the wallet.
            $item = $DB->get_record('enrol_wallet_items', ['id' => $itemid], '*', MUST_EXIST);
            // In this case we get the default settings.
            $account = get_config('enrol_wallet', 'paymentaccount');
            return new \core_payment\local\entities\payable($item->cost, $item->currency, $account);
        }
    }

    /**
     * Callback function that returns the URL of the page the user should be redirected to in the case of a successful payment.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrolment instance id
     * @return \moodle_url
     */
    public static function get_success_url(string $paymentarea, int $instanceid): \moodle_url {
        global $DB;
        // Check if the payment is for enrolment or topup the wallet.
        if ($paymentarea == 'walletenrol') {
            $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'wallet', 'id' => $instanceid], MUST_EXIST);

            return new \moodle_url('/course/view.php', ['id' => $courseid]);
        } else {
            return new \moodle_url('/');
        }
    }

    /**
     * Callback function that delivers what the user paid for to them.
     *
     * @param string $paymentarea
     * @param int $itemid The item id
     * @param int $paymentid payment id as inserted into the 'payments' table, if needed for reference
     * @param int $userid The userid the order is going to deliver to
     * @return bool Whether successful or not
     */
    public static function deliver_order(string $paymentarea, int $itemid, int $paymentid, int $userid): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/enrol/wallet/lib.php');
        // Check if the payment is for enrolment or topup the wallet.
        if ($paymentarea == 'walletenrol') {
            $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $itemid], '*', MUST_EXIST);

            $plugin = enrol_get_plugin('wallet');
            $user = \core_user::get_user($userid);
            // Now enrol the user after successful payment.
            $plugin->enrol_self($instance, $user);

            $course = $DB->get_record('course', ['id' => $instance->courseid], '*', IGNORE_MISSING);
            $course ? $coursename = $course->fullname : $coursename = '';
            // Check the balance because we only get paied for the difference.
            $balance = (float)\enrol_wallet\transactions::get_user_balance($userid);
            $coupon = isset($_SESSION['coupon']) ? $_SESSION['coupon'] : null;
            $_SESSION['coupon'] = ''; // Unset the coupon.
            $fee = (float)$plugin->get_cost_after_discount($userid, $instance, $coupon);
            $cost = $fee - $balance;
            // Deduct the user's balance.
            \enrol_wallet\transactions::debit($userid, $balance, '('.$coursename.') And '.$cost.' from payment');
            return true;
        } else {
            // Get the fake item in case of topup the wallet.
            $item = $DB->get_record('enrol_wallet_items', ['id' => $itemid], '*', MUST_EXIST);
            \enrol_wallet\transactions::payment_topup($item->cost, $userid);
            // Deleting the fake item record for privacy.
            $DB->delete_records('enrol_wallet_items', ['id' => $itemid]);
            return true;
        }
    }
}
