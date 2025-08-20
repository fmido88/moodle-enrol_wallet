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
        $userid  = $data['userid'];
        $type    = $data['type'];
        $amount  = $data['amount'];
        $before  = $data['balbefore'];
        $balance = $data['balance'];
        $desc    = $data['descripe'];
        $time    = userdate($data['timecreated']);

        $a = (object)[
            'type'    => $type,
            'amount'  => $amount,
            'before'  => $before,
            'balance' => $balance,
            'desc'    => $desc,
            'time'    => $time,
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
        $message->fullmessageformat = FORMAT_MOODLE;
        $message->fullmessagehtml = "<p>$messagebody</p>";
        $message->smallmessage = $desc;
        $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message.

        $content = ['*' => ['header' => ' Wallet Transaction ', 'footer' => '']]; // Extra content for specific processor.
        $message->set_additional_content('email', $content);

        // Set the page context to resolve the coding problem with airnotifier processor.
        global $PAGE;
        if (!is_object($PAGE->context)) {
            $PAGE->set_context(\context_course::instance(SITEID));
        }
        // Actually send the message.
        $messageid = message_send($message);

        return $messageid;
    }

}

