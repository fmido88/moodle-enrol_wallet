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
 * Notifications class.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;
use enrol_wallet_plugin;

/**
 * Notifications class
 *
 */
class notifications {
    /**
     * Sending notification after a wallet transaction.
     * @param array $data
     * @return int the id of the message.
     */
    public static function transaction_notify($data) {
        $userid = $data['userid'];
        $type = $data['type'];
        $amount = $data['amount'];
        $before = $data['balbefore'];
        $balance = $data['balance'];
        $desc = $data['descripe'];
        $time = userdate($data['timecreated']);

        $a = (object)[
            'type' => $type,
            'amount' => $amount,
            'before' => $before,
            'balance' => $balance,
            'desc' => $desc,
            'time' => $time,
        ];
        $user = \core_user::get_user($userid);
        $message = new \core\message\message();
        $message->component = 'enrol_wallet';
        $message->name = 'wallet_transaction'; // The notification name from message.php.
        $message->userfrom = \core_user::get_noreply_user(); // If the message is 'from' a specific user you can set them here.
        $message->userto = $user;
        $message->subject = get_string('messagesubject', 'enrol_wallet', $type);
        if ($type == 'credit') {
            $messagebody = get_string('messagebody_credit', 'enrol_wallet', $a);
        } else if ($type == 'debit') {
            $messagebody = get_string('messagebody_debit', 'enrol_wallet', $a);
        } else {
            $messagebody = '';
        }

        $message->fullmessage = $messagebody;
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = "<p>$messagebody</p>";
        $message->smallmessage = $desc;
        $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message.
        $message->contexturl = ''; // A relevant URL for the notification.
        $message->contexturlname = ''; // Link title explaining where users get to for the contexturl.
        $content = array('*' => array('header' => ' Wallet Transaction ', 'footer' => '')); // Extra content for specific processor.
        $message->set_additional_content('email', $content);

        // Actually send the message.
        $messageid = message_send($message);

        // BUG: there is an error with message processor airnotifier.
        // I don't think that this error is from this plugin but this long comment to remind me to lookup for it.
        // The main problem is with format_string() function and cannot determine the reason exactly.
        /* Coding problem: $PAGE->context was not set.
        You may have forgotten to call require_login() or $PAGE->set_context().
        The page may not display correctly as a result
        line 567 of /lib/pagelib.php: call to debugging()
        line 962 of /lib/pagelib.php: call to moodle_page->magic_get_context()
        line 1466 of /lib/weblib.php: call to moodle_page->__get()
        line 91 of /message/output/airnotifier/message_output_airnotifier.php: call to format_string()
        line 506 of /lib/classes/message/manager.php: call to message_output_airnotifier->send_message()
        line 382 of /lib/classes/message/manager.php: call to core\message\manager::call_processors()
        line 349 of /lib/classes/message/manager.php: call to core\message\manager::send_message_to_processors()
        line 341 of /lib/messagelib.php: call to core\message\manager::send_message()
        line 80 of /enrol/wallet/classes/notifications.php: call to message_send()
        line 112 of /enrol/wallet/classes/transactions.php: call to enrol_wallet\notifications::transaction_notify()
        line 63 of /enrol/wallet/extra/charger.php: call to enrol_wallet\transactions::payment_topup() */

        return $messageid;
    }

}

