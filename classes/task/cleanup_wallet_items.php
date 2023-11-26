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
        $select = "timecreated IS NULL OR timecreated < :timetocheck";
        $params = ['timetocheck' => time() - 6 * HOURSECS];
        $count = $DB->count_records_select('enrol_wallet_items', $select, $params);
        mtrace("$count records found to be deleted...");
        $DB->delete_records_select("enrol_wallet_items", $select, $params);
        mtrace("Task ended.");
    }

}
