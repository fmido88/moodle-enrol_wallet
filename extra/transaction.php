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

require_once('../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once(__DIR__.'/../locallib.php');
require_once($CFG->libdir.'/formslib.php');

global $DB, $USER;
// Adding some security.
require_login();

$systemcontext = context_system::instance();
$viewall = has_capability('enrol/wallet:transaction', $systemcontext);

$sort      = optional_param('tsort', '', PARAM_ALPHA);
$userid    = (!$viewall) ? $USER->id : optional_param('userid', '', PARAM_INT);
$datefrom  = optional_param_array('datefrom', [], PARAM_INT);
$dateto    = optional_param_array('dateto', [], PARAM_INT);
$ttype     = optional_param('ttype', '', PARAM_TEXT);
$value     = optional_param('value', '', PARAM_FLOAT);
$pagesize  = optional_param('pagesize', 50, PARAM_INT);
$limitfrom = optional_param('page', 0, PARAM_INT);

// Page parameters.
$urlparams = [
    'tsort'   => $sort,
    'pagesize' => $pagesize,
    'page'    => $limitfrom,
    'userid'  => $userid,
    'ttype'   => $ttype,
    'value'   => $value,
];
if (!empty($datefrom)) {
    foreach ($datefrom as $key => $v) {
        $urlparam["datafrom[$key]"] = $v;
    }
}

if (!empty($dateto)) {
    foreach ($dateto as $key => $v) {
        $urlparam["dateto[$key]"] = $v;
    }
}

// Unset empty params.
foreach ($urlparams as $key => $val) {
    if (empty($val)) {
        unset($urlparams[$key]);
    }
}

// Setup the page.
$title = get_string('transactions', 'enrol_wallet');
$PAGE->set_context($systemcontext);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$baseurl = new moodle_url('/enrol/wallet/extra/transaction.php');
$thisurl = new moodle_url('/enrol/wallet/extra/transaction.php', $urlparams);
$PAGE->set_url($thisurl);

echo $OUTPUT->header();

// -------------------------------------------------------------------------------------------------------

// Create the filtration form.

$customdata = ['viewall' => $viewall, 'context' => $systemcontext];
$mform = new enrol_wallet\form\transactions_filter(null, $customdata, 'get');
$filterdata = $mform->get_data();
// -------------------------------------------------------------------------------------------------

// Setup the transactions table.
$table = new enrol_wallet\table\transactions($USER->id, $filterdata);
$table->define_baseurl($thisurl);

// -------------------------------------------------------------------------------------------

// Display the filtration form.
$mform->display();

echo $OUTPUT->single_button($baseurl, get_string('clear_filter', 'enrol_wallet'));

echo $OUTPUT->heading($title, 3);

// Display the table.
$table->out($pagesize, true);

echo $OUTPUT->footer();
