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

namespace enrol_wallet\task;

use enrol_wallet\util\balance_op;
use enrol_wallet\util\balance;
/**
 * Class queue_trasaction
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_trasaction  extends \core\task\adhoc_task {
    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return 'queue transactions';
    }
    /**
     * Perform the transaction.
     * @return void
     */
    public function execute() {
        $data = $this->get_custom_data();
        $op = new balance_op($data['userid'], $data['catid'] ?? 0);
        if ($data['method'] == 'credit') {
            $op->credit($data['amount'], $data['by'], $data['thingid'], $data['desc'], $data['refundable'], $data['trigger']);
        } else if ($data['method'] == 'debit') {
            $op->debit($data['amount'], $data['by'], $data['thingid'], $data['desc'], $data['neg']);
        }
    }
    /**
     * Queue credit
     * @param \enrol_wallet\util\balance $op
     * @param array $args
     * @return void
     */
    public static function queue_credit(balance $op, array $args) {
        $args['method'] = 'credit';
        $args['userid'] = $op->get_user_id();
        $args['catid'] = $op->get_catid();
        return self::queue_transaction($args);
    }
    /**
     * Queue debit
     * @param \enrol_wallet\util\balance $op
     * @param array $args
     * @return void
     */
    public static function queue_debit(balance $op, array $args) {
        $args['method'] = 'debit';
        $args['userid'] = $op->get_user_id();
        $args['catid'] = $op->get_catid();
        return self::queue_transaction($args);
    }
    /**
     * Queue transaction
     * @param array $args
     * @return void
     */
    protected static function queue_transaction(array $args) {
        $task = new self();
        $task->set_custom_data($args);
        if (!PHPUNIT_TEST) {
            $task->set_next_run_time(time() - 1);
            \core\task\manager::queue_adhoc_task($task);
        } else {
            $task->execute();
        }
    }
}
