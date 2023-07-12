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
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');

global $DB, $USER;
// Adding some security.
require_login();

$systemcontext = context_system::instance();
$viewall = has_capability('enrol/wallet:transaction', $systemcontext);

$sort      = optional_param('tsort', '', PARAM_ALPHA);
$userid    = (!$viewall) ? $USER->id : optional_param('user', '', PARAM_INT);
$datefrom  = optional_param_array('datefrom', [], PARAM_INT);
$dateto    = optional_param_array('dateto', [], PARAM_INT);
$ttype     = optional_param('ttype', '', PARAM_TEXT);
$value     = optional_param('value', '', PARAM_FLOAT);
$limitnum  = optional_param('perpage', 50, PARAM_INT);
$limitfrom = optional_param('page', 0, PARAM_INT);

// Page parameters.
$urlparams = [
    'tsort'   => $sort,
    'perpage' => $limitnum,
    'page'    => $limitfrom,
    'user'    => $userid,
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
$PAGE->set_context($systemcontext);
$PAGE->set_title("Wallet Transactions");
$PAGE->set_heading('Wallet Transactions');

$baseurl = new moodle_url('/enrol/wallet/extra/transaction.php');
$thisurl = new moodle_url('/enrol/wallet/extra/transaction.php', $urlparams);
$PAGE->set_url($thisurl);

echo $OUTPUT->header();

// -------------------------------------------------------------------------------------------------------

// Create the filtration form.
$mform = new MoodleQuickForm('transactions', 'GET', $thisurl);

$mform->addElement('header', 'filter', get_string('filter_transaction', 'enrol_wallet'));

if ($viewall) {
    // Borrow potential users selectors from enrol_manual.
    $options = [
        'ajax'              => 'enrol_manual/form-potential-user-selector',
        'multiple'          => false,
        'courseid'          => SITEID,
        'enrolid'           => 0,
        'perpage'           => $CFG->maxusersperpage,
        'userfields'        => implode(',', \core_user\fields::get_identity_fields($systemcontext, true)),
        'noselectionstring' => get_string('allusers', 'enrol_wallet'),
    ];
    $mform->addElement('autocomplete', 'user', get_string('selectusers', 'enrol_manual'), array(), $options);
    if (!empty($userid)) {
        $mform->setDefault('user', $userid);
    }
}

// Adding starting and ending dates for transactions.
$mform->addElement('date_time_selector', 'datefrom', get_string('datefrom', 'enrol_wallet'), array('optional' => true));
if (!empty($datefrom)) {
    $mform->setDefault('datefrom', $datefrom);
}

$mform->addElement('date_time_selector', 'dateto', get_string('dateto', 'enrol_wallet'), array('optional' => true));
if (!empty($dateto)) {
    $mform->setDefault('dateto', $dateto);
}

// Select specific type of transaction.
$options = [
    ''       => 'All',
    'debit'  => 'debit',
    'credit' => 'credit',
];
$mform->addElement('select', 'ttype', get_string('transaction_type', 'enrol_wallet'), $options);
if (!empty($ttype)) {
    $mform->setDefault('ttype', $ttype);
}

// Select specific value.
$mform->addElement('text', 'value', get_string('value', 'enrol_wallet'));
$mform->setType('value', PARAM_FLOAT);
if (!empty($value) || (int)$value === 0) {
    $mform->setDefault('value', $value);
}

// Transaction perpage.
$limits = [];
for ($i = 25; $i <= 1000; $i = $i + 25) {
    $limits[$i] = $i;
}
$mform->addElement('select', 'perpage', get_string('transaction_perpage', 'enrol_wallet'), $limits);
if (!empty($limitnum)) {
    $mform->setDefault('perpage', $limitnum);
}

$mform->addElement('submit', 'submit', get_string('submit'));
// -------------------------------------------------------------------------------------------------

// Setup the transactions table.
$columns = [
    'user'        => get_string('user'),
    'timecreated' => get_string('time'),
    'amount'      => get_string('amount', 'enrol_wallet'),
    'type'        => get_string('transaction_type', 'enrol_wallet'),
    'balbefore'   => get_string('balance_before', 'enrol_wallet'),
    'balance'     => get_string('balance_after', 'enrol_wallet'),
    'norefund'    => get_string('nonrefundable', 'enrol_wallet'),
    'descripe'    => get_string('description'),
];

$table = new flexible_table('wallet_transactions');

$table->define_baseurl($baseurl->out(true));

$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->set_attribute('class', 'generaltable generalbox wallet-transactions');

// Setup up the sorting properties.
$table->sortable(true);
$table->no_sorting('user');

$table->setup();

// Work out direction of sort required.
$sortcolumns = $table->get_sort_columns();

// Now do sorting if specified.
// Sanity check $sort var before including in sql. Make sure it matches a known column.
$allowedsort = array_diff(array_keys($table->columns), $table->column_nosort);
if (!in_array($sort, $allowedsort)) {
    $sort = '';
}

// Doing the sorting.
$orderby = 'id DESC';
if (!empty($sort)) {
    $direction = ' DESC';
    if (!empty($sortcolumns[$sort]) && $sortcolumns[$sort] == SORT_ASC) {
        $direction = ' ASC';
    }
    $orderby = " $sort $direction";
}

// SQL parameters and select where query.
$params = [];
$select = '1=1 ';
// Check the data from submitted form first.
if (!empty($userid)) {
    $params['userid'] = $userid;
    $select .= "AND userid = :userid ";
}

if (!empty($ttype)) {
    $params['type'] = $ttype;
    $select .= "AND type = :type ";
}

if (!empty($value)) {
    $params['amount'] = $value;
    $select .= "AND amount = :amount ";
}

if (!empty($datefrom)) {
    $timefrom = mktime(
        $datefrom['hour'],
        $datefrom['minute'],
        0,
        $datefrom['month'],
        $datefrom['day'],
        $datefrom['year'],
    );
    $params['timefrom'] = $timefrom;
    $select .= "AND timecreated >= :timefrom ";
}

if (!empty($dateto)) {
    $timeto = mktime(
        $dateto['hour'],
        $dateto['minute'],
        59,
        $dateto['month'],
        $dateto['day'],
        $dateto['year'],
    );
    $params['timeto'] = $timeto;
    $select .= "AND timecreated <= :timeto ";
}

$records = $DB->get_records_select('enrol_wallet_transactions', $select, $params, $orderby, '*', $limitfrom, $limitnum);
$count = $DB->count_records_select('enrol_wallet_transactions', $select, $params);

// Working on pages.
$pages = $count / $limitnum;
$decimal = fmod($pages, 1);
$pages = ($decimal > 0) ? intval($pages) + 1 : intval($pages);
$content = '<p>Page: </p>';
for ($i = 1; $i <= $pages; $i++) {
    $urlparams['page'] = ($i - 1) * $limitnum;

    if ($urlparams['page'] == $limitfrom) {
        $content .= $i;
    } else {
        $url = new moodle_url('/enrol/wallet/extra/transaction.php', $urlparams);
        $content .= html_writer::link($url, $i);
    }

    $content .= ' ';
}

$pageslinks = html_writer::span($content);

foreach ($records as $record) {

    $user = core_user::get_user($record->userid);
    $userfullname = fullname($user);

    $time = userdate($record->timecreated);

    $amount   = number_format($record->amount, 2);
    $before   = number_format($record->balbefore, 2);
    $after    = number_format($record->balance, 2);
    $norefund = number_format($record->norefund, 2);
    $desc = $record->descripe;

    $row = [
        'user'        => $userfullname,
        'timecreated' => $time,
        'amount'      => $amount,
        'type'        => $record->type,
        'balbefore'   => $before,
        'balance'     => $after,
        'norefund'    => $norefund,
        'descripe'    => $desc,
    ];

    $table->add_data_keyed($row);

    flush();
}

// -------------------------------------------------------------------------------------------

// Display the filtration form.
$mform->display();

echo $OUTPUT->single_button($baseurl, get_string('clear_filter', 'enrol_wallet'));

echo $OUTPUT->heading(get_string('transactions', 'enrol_wallet'), 3);

// Display pages links.
echo $pageslinks;

// Display the table.
$table->finish_output();

// Page links again.
echo $pageslinks;

echo $OUTPUT->footer();
