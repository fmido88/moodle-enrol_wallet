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

use enrol_wallet\local\config;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op as op;

/**
 * Send expiry notifications task.
 */
class turn_non_refundable extends \core\task\adhoc_task {

    /**
     * Category id corresponding to transformation.
     * @var int
     */
    private $catid;
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
        if (!PHPUNIT_TEST) {
            $trace = new \text_progress_trace;
        } else {
            $trace = new \null_progress_trace;
        }

        $trace->output('Starting the task...');

        $data = $this->get_custom_data();

        $transform = $this->check_transform_validation($data, $trace);

        if ($transform && is_numeric($transform)) {
            $trace->output('Transform validation success...');

            $userid = $data->userid;
            $this->apply_transformation($userid, $transform, $trace);

            $trace->output('Transformation done...');

        } else {

            $trace->output('Transformation validation failed...');
        }

        $trace->output('Task Completed.');
        $trace->finished();
    }

    /**
     * Check if transformation is valid or not.
     * @param object $data custom data of the task
     * @param \progress_trace $trace
     * @return false|float|string false if not valid, float for the amount to be transform and string in test.
     */
    public function check_transform_validation($data, $trace) {
        global $DB;
        $userid = $data->userid;
        $amount = $data->amount;
        $this->catid = $data->catid ?? 0;

        if (!empty($this->catid)) {
            $category = \core_course_category::get($this->catid, IGNORE_MISSING, true);
            if (!$category) {
                $output = 'Category not found...';
                $trace->output($output);
                if (!PHPUNIT_TEST) {
                    return false;
                }
                return $output;
            }
        }

        $user = \core_user::get_user($userid, 'id, deleted');
        if (!$user || !empty($user->deleted)) {
            $output = 'User not found...';
            $trace->output($output);
            if (!PHPUNIT_TEST) {
                return false;
            }
            return $output;
        }

        $balancehelper = new balance($userid, $category ?? $this->catid);

        $mainbalance = $balancehelper->get_main_balance();
        $mainnorefund = $balancehelper->get_main_nonrefundable();

        if (empty($this->catid)) {
            $balance = $mainbalance;
            $norefund = $mainnorefund;
        } else {
            $balance = $balancehelper->get_valid_balance() - $mainbalance;
            $norefund = $balancehelper->get_valid_nonrefundable() - $mainnorefund;
        }

        $period = config::make()->refundperiod;

        if ($norefund >= $balance) {
            $output = 'Non refundable amount grater than or equal user\'s balance'."\n";
            $trace->output($output);
            if (!PHPUNIT_TEST) {
                return false;
            }
            return $output;
        }

        // Get all transactions in this time.
        $where = "userid = :userid AND type = :type AND timecreated >= :checktime";
        $params = [
            'userid'    => $userid,
            'type'      => 'debit',
            'checktime' => timedate::time() - $period,
        ];
        if (!empty($this->catid)) {
            $where .= " AND category = :catid";
            $params['catid'] = $this->catid;
        } else {
            $where .= " AND (category IS NULL OR category = 0)";
        }

        $records = $DB->get_records_select('enrol_wallet_transactions', $where, $params, 'id DESC', 'id, amount');

        if (empty($records)) {
            return $amount;
        }

        $debit = 0;
        // Check how much the user spent in this period.
        foreach ($records as $record) {
            $debit += $record->amount;
        }

        // Check if the user spent more than the amount of the transform transaction.
        if ($amount <= $debit) {
            $output = 'user spent this amount in the grace period already...'."\n";
            $trace->output($output);
            if (!PHPUNIT_TEST) {
                return false;
            } else {
                return $output;
            }
        } else {

            $transform = $amount - $debit;
            return $transform;
        }
    }

    /**
     * Apply transformation
     * @param int $userid user's id
     * @param float $transform the amount that should be transformed to nonrefundable
     * @param \progress_trace $trace
     * @return void
     */
    public function apply_transformation($userid, $transform, $trace) {
        global $DB;
        $op = new op($userid, $this->catid);

        $balance = $op->get_valid_balance();
        $norefund = $op->get_valid_nonrefundable();

        // If refunding is disable, transform all balance to non-refundable.
        $refundenabled = config::make()->enablerefund;
        if (empty($refundenabled)) {
            $transform = $balance;
            $trace->output('Refunding is disabled in this website, all of user\'s balance will transform...'."\n");
        }

        $op->turn_to_nonrefundable($transform);
        $recorddata = [
            'userid'      => $userid,
            'amount'      => 0,
            'type'        => 'credit',
            'balbefore'   => $balance,
            'balance'     => $balance,
            'norefund'    => min($norefund + $transform, $balance),
            'category'    => $this->catid,
            'descripe'    => get_string('nonrefundable_transform_desc', 'enrol_wallet'),
            'timecreated' => timedate::time(),
        ];
        $DB->insert_record('enrol_wallet_transactions', $recorddata);

        $trace->output("User with id $userid now has a ".
                    $recorddata['norefund']." nonrefundabel balance in his\her wallet out of $balance total balance...\n");
    }
}
