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
 * Analytics page using enrolpage_viewed event data
 *
 * @package    enrol_wallet
 * @copyright  2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/enrol/wallet/extra/analytics.php');
$PAGE->set_title(get_string('enrolpage_analytics', 'enrol_wallet'));
$PAGE->set_heading(get_string('enrolpage_analytics', 'enrol_wallet'));

echo $OUTPUT->header();

// Get analytics data
$views = $DB->get_records_sql("
    SELECT COUNT(*) as count, DATE(FROM_UNIXTIME(timecreated)) as date
    FROM {logstore_standard_log}
    WHERE eventname = :eventname
    GROUP BY DATE(FROM_UNIXTIME(timecreated))
    ORDER BY date DESC
    LIMIT 30", ['eventname' => '\enrol_wallet\event\enrolpage_viewed']);

// Display data (you can use a chart library like Chart.js to visualize this data)
$table = new html_table();
$table->head = [get_string('date'), get_string('views')];
foreach ($views as $view) {
    $table->data[] = [$view->date, $view->count];
}

echo html_writer::table($table);

echo $OUTPUT->footer();