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
global $DB, $USER;
// Adding some security.
require_login();

$systemcontext = context_system::instance();
$viewall = has_capability('enrol/wallet:transaction', $systemcontext);

$sort = optional_param('tsort', 'userid', PARAM_ALPHA);

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/enrol/wallet/transaction.php'));
$PAGE->set_title("Wallet Transactions");
$PAGE->set_heading('Wallet Transactions');

// If the user didn't have the capability to view all transaction, show him only his transactions.
$conditions = ($viewall) ? [] : ['userid' => $USER->id];

echo $OUTPUT->header();

// TODO Adding a filtiration form.

// Set up the transactions table.
$columns = array(
    'user' => 'User',
    'timecreated' => 'Time',
    'amount' => 'Amount',
    'type' => 'Type of transaction',
    'balbefore' => 'balance before',
    'balance' => 'balance after',
    'descripe' => 'description',
);

$table = new flexible_table('stack_answertests');
$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->set_attribute('class', 'generaltable generalbox stacktestsuite');
$table->define_baseurl($PAGE->url);

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

$orderby = 'id DESC';
if (!empty($sort)) {
    $direction = ' DESC';
    if (!empty($sortcolumns[$sort]) && $sortcolumns[$sort] == SORT_ASC) {
        $direction = ' ASC';
    }
    $orderby = " $sort $direction";
}

$records = $DB->get_records('enrol_wallet_transactions', $conditions, $orderby);
foreach ($records as $record) {
    $user = core_user::get_user($record->userid);
    $userfullname = fullname($user);

    $time = userdate($record->timecreated);

    $amount = number_format($record->amount, 2);
    $before = number_format($record->balbefore, 2);
    $after = number_format($record->balance, 2);
    $desc = $record->descripe;

    $row = [
        'user' => $userfullname,
        'timecreated' => $time,
        'amount' => $amount,
        'type' => $record->type,
        'balbefore' => $before,
        'balance' => $after,
        'descripe' => $desc,
    ];

    $table->add_data_keyed($row);

    flush();
}

$table->finish_output();

echo $OUTPUT->footer();
