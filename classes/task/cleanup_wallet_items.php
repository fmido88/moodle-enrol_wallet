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
 * cleanup wallet items task.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\task;

/**
 * Sync enrollments task.
 */
class cleanup_wallet_items extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanupwalletitemstask', 'enrol_wallet');
    }

    /**
     * Run task for cleaning up wallet items.
     */
    public function execute() {
        global $DB;
        mtrace('Task started...');

        $paymentexist = $DB->get_manager()->table_exists('payments');

        $params = ['timetocheck' => time() - DAYSECS];

        $sql = "SELECT it.*
        FROM {enrol_wallet_items} it
        WHERE (it.timecreated IS NULL OR it.timecreated < :timetocheck)";

        $records = $DB->get_records_sql($sql, $params);
        mtrace(count($records)." records found to be deleted...");

        foreach ($records as $record) {
            if (!$paymentexist
            || !$DB->record_exists('payments', ['itemid' => $record->id, 'component' => 'enrol_wallet'])) {
                $DB->delete_records('enrol_wallet_items', ['id' => $record->id]);
            }
        }

        mtrace("Task ended.");
    }
}
