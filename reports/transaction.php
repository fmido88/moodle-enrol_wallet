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
 * wallet enrolment plugin transaction page.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\local\filters\user as user_filter;
use core_reportbuilder\system_report_factory;
use enrol_wallet\local\urls\reports;
use enrol_wallet\output\transaction_chart;
use enrol_wallet\reportbuilder\local\systemreports\transactions;

require_once('../../../config.php');

// Adding some security.
require_login(null, false);

$pagesize = optional_param('pagesize', 50, PARAM_INT);

$systemcontext = context_system::instance();

// Setup the page.
$title = get_string('transactions', 'enrol_wallet');
$PAGE->set_context($systemcontext);
$PAGE->set_title($title);
$PAGE->set_heading($title);

reports::TRANSACTIONS->set_page_url_to_me(['pagesize' => $pagesize]);

$report = system_report_factory::create(transactions::class, $systemcontext);

$report->set_default_per_page($pagesize);

$canviewall = has_capability('enrol/wallet:transaction', \core\context\system::instance());
$conditionvalues = $canviewall ? [] : ['user:userselect_operator' => user_filter::USER_CURRENT];
$report->set_condition_values($conditionvalues);

if ($canviewall && ($userid = optional_param('userid', null, PARAM_INT))) {
    $filtervalues = [
        'user:userselect_operator' => user_filter::USER_SELECT,
        'user:userselect_value'    => [$userid],
    ];
    $report->set_filter_values($filtervalues);
}

$charts = (new transaction_chart($report))->get_output($PAGE->get_renderer('core'));

echo $OUTPUT->header();

echo $charts;

// Transaction per page.
$limits = [];

for ($i = 50; $i <= 2000; $i += 50) {
    $limits[$i] = $i;
}

echo $OUTPUT->heading($title, 3);

echo html_writer::tag('span', get_string('transaction_perpage', 'enrol_wallet'));
echo $OUTPUT->single_select(reports::TRANSACTIONS->url(), 'pagesize', $limits, $pagesize, []);

echo $report->output();

echo $OUTPUT->footer();
