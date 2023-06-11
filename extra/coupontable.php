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
 * Display page for coupons in moodle website.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');
global $OUTPUT, $PAGE;

// Adding some security.
require_login();

$systemcontext = context_system::instance();
$candelete = has_capability('enrol/wallet:deletecoupon', $systemcontext);

$candownload = has_capability('enrol/wallet:downloadcoupon', $systemcontext);

require_capability('enrol/wallet:viewcoupon', $systemcontext);

$limitnum = optional_param('perpage', 50, PARAM_INT);
$code = optional_param('code', '', PARAM_TEXT);
$value = optional_param('value', '', PARAM_NUMBER);
$type = optional_param('type', '', PARAM_TEXT);
$validtoarray = optional_param_array('validto', [], PARAM_INT);
$validfromarray = optional_param_array('validfrom', [], PARAM_INT);
$createdtoarray = optional_param_array('createdto', [], PARAM_INT);
$createdfromarray = optional_param_array('createdfrom', [], PARAM_INT);
$sort = optional_param('tsort', 'userid', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

$defaultop = (!$candownload) ? 'delete' : 'download';
$operation = optional_param('operation', $defaultop, PARAM_TEXT);

$limitfrom = optional_param('page', 0, PARAM_INT);

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$url = new moodle_url('/enrol/wallet/extra/coupontable.php');
$PAGE->set_url($url);
$PAGE->set_title("coupons");
$PAGE->set_heading('Coupons');

// Setup the filtration form.
$mform = new \MoodleQuickForm('couponfilter', 'get', $url);

$mform->addElement('text', 'code', get_string('coupon_code', 'enrol_wallet'));
$mform->setType('code', PARAM_TEXT);
$mform->setDefault('code', $code);

$mform->addElement('text', 'value', get_string('coupon_value', 'enrol_wallet'));
$mform->setType('value', PARAM_NUMBER);
if ($value) {
    $mform->setDefault('value', $value);
} else {
    $mform->setDefault('value', '');
}

$types = [
    'fixed' => get_string('fixedvaluecoupon', 'enrol_wallet'),
    'percent' => get_string('percentdiscountcoupon', 'enrol_wallet'),
];
$mform->addElement('select', 'type', get_string('coupon_type', 'enrol_wallet'), $types);
$mform->setDefault('type', $type);

$mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), array('optional' => true));
$mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), array('optional' => true));

$mform->addElement('date_time_selector', 'createdfrom', get_string('createdfrom', 'enrol_wallet'), array('optional' => true));
$mform->addElement('date_time_selector', 'createdto', get_string('createdto', 'enrol_wallet'), array('optional' => true));

if ($candownload && $candelete) {
    $options = [
        'delete' => get_string('delete'),
        'download' => get_string('download'),
    ];
    $mform->addElement('select', 'operation', get_string('coupon_operation', 'enrol_wallet'), $options);
    $mform->addHelpButton('operation', 'coupon_operation', 'enrol_wallet');
    $mform->setDefault('operation', $defaultop);
}

$limits = [];
for ($i = 25; $i <= 1000; $i = $i + 25) {
    $limits[$i] = $i;
}

$mform->addElement('select', 'perpage', get_string('coupon_perpage', 'enrol_wallet'), $limits);
$mform->addElement('submit', 'submit', get_string('coupon_applyfilter', 'enrol_wallet'));

// Now let's display the form.
ob_start();
$mform->display();
$filterform = ob_get_clean();

$arraydates = [
    'createdfrom' => $createdfromarray,
    'createdto' => $createdtoarray,
    'validfrom' => $validfromarray,
    'validto' => $validtoarray,
];

// ...mktime all dates.
foreach ($arraydates as $key => $date) {
    if (!empty($date)) {
        $$key = mktime(
            $date['hour'],
            $date['minute'],
            0,
            $date['month'],
            $date['day'],
            $date['year'],
        );
    } else {
        $$key = '';
    }
}

// Setting up the conditions.
$conditions = '';

if (!empty($code)) {
    $conditions .= ' code = '.$code;
}

if (!empty($value)) {
    $conditions .= (!empty($conditions)) ? ' AND' : '';
    $conditions .= ' value = \''.$value.'\'';
}

if (!empty($type)) {
    $conditions .= (!empty($conditions)) ? ' AND' : '';
    $conditions .= ' type = \''.$type.'\'';
}

if (!empty($validto)) {
    $conditions .= (!empty($conditions)) ? ' AND' : '';
    $conditions .= ' validto = \''.$validto.'\'';
}

if (!empty($validfrom)) {
    $conditions .= (!empty($conditions)) ? ' AND' : '';
    $conditions .= ' validfrom = \''.$validfrom.'\'';
}

if (!empty($cratedfrom)) {
    $conditions .= (!empty($conditions)) ? ' AND' : '';
    $conditions .= ' timecreated <= \''.$createdfrom.'\'';
}

if (!empty($cratedto)) {
    $conditions .= (!empty($conditions)) ? ' AND' : '';
    $conditions .= ' timecreated >= \''.$createdto.'\'';
}

$table = new flexible_table('walletcouponstable');
$table->define_baseurl($PAGE->url);

if (!$table->is_downloading($download, 'walletcoupons')) {
    echo $OUTPUT->header();
}
if (!$table->is_downloading()) {
    echo $OUTPUT->box($filterform);
}
if (!$table->is_downloading() && $candelete && $operation == 'delete') {
    echo '<form name="couponedit" method="post" action="couponedit.php">';
}
// Set up the coupons table.
$columns = array(
    'checkbox' => null,
    'id' => 'id',
    'code' => 'Code',
    'value' => 'Value',
    'type' => 'Type',
    'maxusage' => 'Maximum usage',
    'usetimes' => 'Usage',
    'validfrom' => 'Valid from',
    'validto' => 'Valid to',
    'lastuse' => 'lastuse',
    'timecreated' => 'timecreated',
);


$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->set_attribute('class', 'generaltable generalbox stacktestsuite');

// Setup the sorting properties.
$table->sortable(true);

// Make the table downloadable.
if ($operation == 'download' && $candownload) {
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_BOTTOM]);
} else {
    $table->is_downloadable(false);
}

$table->setup();

// Work out direction of sort required.
$sortcolumns = $table->get_sort_columns();
// Now do sorting if specified.

// Sanity check $sort var before including in sql. Make sure it matches a known column.
$allowedsort = array_diff(array_keys($table->columns), $table->column_nosort);
if (!in_array($sort, $allowedsort)) {
    $sort = '';
}

$orderby = 'id ASC';
if (!empty($sort)) {
    $direction = ' DESC';
    if (!empty($sortcolumns[$sort]) && $sortcolumns[$sort] == SORT_ASC) {
        $direction = ' ASC';
    }
    $orderby = " $sort $direction";
}
$sql = '';
$sql = ' FROM {enrol_wallet_coupons} ';
if (!empty($conditions)) {
    $sql .= 'WHERE '.$conditions;
}
if (!empty($orderby)) {
    $sql .= ' ORDER BY '.$orderby;
}
// The sql for counting.
$sqlc = 'SELECT COUNT(id) '.$sql;

// Count all data to get the number of pages later.
$count = $DB->count_records_sql($sqlc);

// If we download the table we need all the pages.
if ($table->is_downloading()) {
    $limitfrom = 0;
    $limitnum = 0;
}
// The sql for records.
$sqlr = 'SELECT * '. $sql;
$records = $DB->get_records_sql($sqlr, [], $limitfrom, $limitnum);

if (!$table->is_downloading()) {
    $pages = intval($count / $limitnum);
    $content = '<p>Page: </p>';
    for ($i = 0; $i <= $pages; $i++) {
        $params = [
            'perpage' => $limitnum,
            'code' => $code,
            'value' => $value,
            'type' => $type,
            'sort' => $sort,
            'download' => $download,
            'operation' => $operation,
            'page' => $i * $limitnum,
        ];
        if (isset($validtoarray) && !empty($validtoarray)) {
            $params['validto'] = http_build_query($validtoarray);
        }
        if (isset($validfromarray) && !empty($validfromarray)) {
            $params['validfrom'] = http_build_query($validfromarray);
        }
        if (isset($createdtoarray) && !empty($createdtoarray)) {
            $params['createdto'] = http_build_query($createdtoarray);
        }
        if (isset($createdfromarray) && !empty($createdfromarray)) {
            $params['createdfrom'] = http_build_query($createdfromarray);
        }
        if ($params['page'] == $limitfrom) {
            $content .= $i;
        } else {
            $url = new moodle_url('/enrol/wallet/extra/coupontable.php', $params);
            $content .= html_writer::link($url, $i);
        }

        $content .= ' ';
    }

    $pageslinks = html_writer::span($content);
}

foreach ($records as $record) {
    if ($candelete && $operation == 'delete') {
        $chkbox = '<input type="checkbox" name="select['.$record->id.']" value="1" >';
    } else {
        $chkbox = '';
    }

    $row = [
        'checkbox' => $chkbox,
        'id' => $record->id,
        'code' => $record->code,
        'value' => $record->value,
        'type' => $record->type,
        'maxusage' => $record->maxusage,
        'usetimes' => $record->usetimes,
        'validfrom' => $record->validfrom != 0 ? userdate($record->validfrom) : '',
        'validto' => $record->validto != 0 ? userdate($record->validto) : '',
        'lastuse' => $record->lastuse != 0 ? userdate($record->lastuse) : '',
        'timecreated' => $record->timecreated != 0 ? userdate($record->timecreated) : '',
    ];

    $table->add_data_keyed($row);

    flush();
}

if (!$table->is_downloading()) {
    echo $OUTPUT->box($pageslinks);
}

$table->finish_output();
if (!$table->is_downloading($download, 'walletcoupons') && $candelete && $operation == 'delete') {
    echo '<button name="delete" value="delete" type="submit" class="btn btn-secondary">Delete</button>';
    echo '<input type="hidden" value="'.sesskey().'" name="sesskey">';
    echo '</form>';
}

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
