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
 * Task to turn transaction to non refundable after the refund period is over.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\task;
use enrol_wallet\transactions;
/**
 * Send expiry notifications task.
 */
class turn_non_refundable extends \core\task\adhoc_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('turn_not_refundable_task', 'enrol_wallet');
    }

    /**
     * Run task turn the transaction to not refundable.
     */
    public function execute() {

        $data = $this->get_custom_data();
        if ($transform = $this->check_transform_validation($data)) {
            $userid = $data['userid'];
            $this->apply_transformation($userid, $transform);
        }

    }

    /**
     * Check if transformation is valid or not.
     * @param array $data custom data of the task
     * @return bool|float
     */
    public function check_transform_validation($data) {
        global $DB;
        $userid = $data['userid'];
        $amount = $data['amount'];
        $balance = transactions::get_user_balance($userid);
        $period = get_config('enrol_wallet', 'refundperiod');
        $norefund = transactions::get_nonrefund_balance($userid);
        if ($norefund >= $balance) {
            return false;
        }
        // Get all transactions in this time.
        $sql = "SELECT '*'
                FROM enrol_wallet_transactions
                WHERE userid = :userid
                AND timecreated >= :checktime";
        $params = [
            'userid' => $userid,
            'checktime' => time() - ($period * DAYSECS),
        ];
        $records = $DB->get_records_sql($sql, $params);
        $credit = 0;
        $debit = 0;
        // Collect all credit and debit through this time.
        foreach ($records as $record) {
            if ($record->type == 'credit') {
                $credit += $record->amount;
            } else if ($record->type == 'debit') {
                $debit += $record->amount;
            }
        }
        // Check if the user spent more than the amount of the transform transaction.
        if ($amount <= $debit) {
            return false;
        } else {
            $transform = $amount - $debit;
            return $transform;
        }
    }

    /**
     * Apply transformation
     * @param int $userid user's id
     * @param float $transform the amount that should be transformed to nonrefundable
     * @return void
     */
    public function apply_transformation($userid, $transform) {
        global $DB;
        $balance = transactions::get_user_balance($userid);
        $norefund = transactions::get_nonrefund_balance($userid);
        $recorddata = [
            'userid' => $userid,
            'amount' => 0,
            'type' => 'credit',
            'balbefore' => $balance,
            'balance' => $balance,
            'norefund' => min($norefund + $transform, $balance),
            'descripe' => 'Transform the transaction to nonrefundable due to expiring of refund period.',
            'timecreated' => time(),
        ];
        $DB->insert_record('enrol_wallet_transactions', $recorddata);
    }
}
