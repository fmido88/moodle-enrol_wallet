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

/**
 * Class generate_coupons
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_coupons extends \core\task\adhoc_task {
    /**
     * Execute coupons generation task.
     * @return void
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

        \core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $trace = PHPUNIT_TEST ? new \null_progress_trace : new \text_progress_trace;
        $options = $this->get_custom_data();
        $trace->output('Starting task...');
        $trace->output('Data: ' . $this->get_custom_data_as_string());

        $ids = enrol_wallet_generate_coupons($options, $trace);
        $trace->output('Finished generating coupons with codes: ' . implode(',', $ids));
    }
}
