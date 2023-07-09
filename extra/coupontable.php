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
require_capability('enrol/wallet:viewcoupon', $systemcontext);

$candelete   = has_capability('enrol/wallet:deletecoupon', $systemcontext);
$canedit     = has_capability('enrol/wallet:editcoupon', $systemcontext);
$candownload = has_capability('enrol/wallet:downloadcoupon', $systemcontext);

// Parameters.
$code             = optional_param('code', '', PARAM_TEXT);
$value            = optional_param('value', '', PARAM_NUMBER);
$type             = optional_param('type', '', PARAM_TEXT);
$validtoarray     = optional_param_array('validto', [], PARAM_INT);
$validfromarray   = optional_param_array('validfrom', [], PARAM_INT);
$createdtoarray   = optional_param_array('createdto', [], PARAM_INT);
$createdfromarray = optional_param_array('createdfrom', [], PARAM_INT);
$sort             = optional_param('tsort', 'userid', PARAM_ALPHA);
$download         = optional_param('download', '', PARAM_ALPHA);
$limitfrom        = optional_param('page', 0, PARAM_INT);
$limitnum         = optional_param('perpage', 50, PARAM_INT);

// Sterilize the url parameters and conditions for sql.
$conditions = '1=1';
$urlparams = [];

$urlparams['tsort'] = (!empty($sort)) ? $sort : null;
$urlparams['page'] = (!empty($limitfrom)) ? $limitfrom : null;
$urlparams['perpage'] = (!empty($limitnum)) ? $limitnum : null;

$conditions .= (!empty($code)) ? ' AND code = \''.$code.'\'' : '';
$urlparams['code'] = (!empty($code)) ? $code : null;

$conditions .= (!empty($value)) ? ' AND value = \''.$value.'\'' : '';
$urlparams['value'] = (!empty($value)) ? $value : null;

$conditions .= (!empty($type)) ? ' AND type = \''.$type.'\'' : '';
$urlparams['type'] = (!empty($type)) ? $type : null;

$arraydates = [
    'createdfrom' => $createdfromarray,
    'createdto'   => $createdtoarray,
    'validfrom'   => $validfromarray,
    'validto'     => $validtoarray,
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

        foreach ($date as $k => $val) {
            $urlparams[$key."[$k]"] = $val;
        }
    }
}

$conditions .= (!empty($cratedfrom)) ? ' AND timecreated <= \''.$createdfrom.'\'' : '';
$conditions .= (!empty($cratedto)) ? ' AND timecreated >= \''.$createdto.'\'' : '';
$conditions .= (!empty($validto)) ? ' AND validto = \''.$validto.'\'' : '';
$conditions .= (!empty($validfrom)) ? ' AND validfrom = \''.$validfrom.'\'' : '';

// Unset empty params.
foreach ($urlparams as $key => $val) {
    if (empty($val)) {
        unset($urlparams[$key]);
    }
}

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$url = new moodle_url('/enrol/wallet/extra/coupontable.php', $urlparams);
$PAGE->set_url($url);
$PAGE->set_title(get_string('coupons', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupons', 'enrol_wallet'));

// --------------------------------------------------------------------------------------
// Form.
// Setup the filtration form.
$mform = new \MoodleQuickForm('couponfilter', 'get', 'coupontable.php');

$mform->addElement('header', 'filter', get_string('filter_coupons', 'enrol_wallet'));

$mform->addElement('text', 'code', get_string('coupon_code', 'enrol_wallet'));
$mform->setType('code', PARAM_TEXT);
$mform->setDefault('code', $code);

$mform->addElement('text', 'value', get_string('coupon_value', 'enrol_wallet'));
$mform->setType('value', PARAM_NUMBER);
$mform->setDefault('value', $value);

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

$limits = [];
for ($i = 25; $i <= 1000; $i = $i + 25) {
    $limits[$i] = $i;
}
$mform->addElement('select', 'perpage', get_string('coupon_perpage', 'enrol_wallet'), $limits);
$mform->setDefault('perpage', $limitnum);

$mform->addElement('submit', 'submit', get_string('coupon_applyfilter', 'enrol_wallet'));

// Now let's display the form.
ob_start();
$mform->display();
$filterform = ob_get_clean();

// ----------------------------------------------------------------------------------------------
// Table.
$baseurl = new moodle_url('/enrol/wallet/extra/coupontable.php');
$table = new flexible_table('walletcouponstable');

$table->define_baseurl($baseurl->out());

if (!$table->is_downloading($download, 'walletcoupons')) {
    if ($candelete) {
        echo '<form name="coupondelete" method="post" action="coupondelete.php">';
    }
    echo $OUTPUT->header();

    echo $OUTPUT->box($filterform);
}

// Set up the coupons table.
$columns = [
            'checkbox'    => null,
            'id'          => 'id',
            'code'        => get_string('coupon_t_code', 'enrol_wallet'),
            'value'       => get_string('coupon_t_value', 'enrol_wallet'),
            'type'        => get_string('coupon_t_type', 'enrol_wallet'),
            'maxusage'    => get_string('coupons_maxusage', 'enrol_wallet'),
            'usetimes'    => get_string('coupon_t_usage', 'enrol_wallet'),
            'validfrom'   => get_string('validfrom', 'enrol_wallet'),
            'validto'     => get_string('validto', 'enrol_wallet'),
            'lastuse'     => get_string('coupon_t_lastuse', 'enrol_wallet'),
            'timecreated' => get_string('coupon_t_timecreated', 'enrol_wallet'),
            'edit'        => null,
        ];

if ($table->is_downloading() || !$candelete) {
    unset($columns['checkbox']);
}

if ($table->is_downloading() || !$canedit) {
    unset($columns['edit']);
}

$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->set_attribute('class', 'generaltable generalbox wallet-couponstable');

// Setup the sorting properties.
$table->sortable(true);

// Make the table downloadable.
if ($candownload) {
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_TOP]);
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

// SQL.
$sql = '';
$sql = ' FROM {enrol_wallet_coupons} ';
if (!empty($conditions)) {
    $sql .= 'WHERE ' . $conditions;
}

if (!empty($orderby)) {
    $sql .= ' ORDER BY ' . $orderby;
}

// Count all data to get the number of pages later.
$count = $DB->count_records_select('enrol_wallet_coupons', $conditions, []);

// If we download the table we need all the pages.
if ($table->is_downloading()) {
    $limitfrom = 0;
    $limitnum = 0;
}
// The sql for records.
$sqlr = 'SELECT * '. $sql;
$records = $DB->get_records_sql($sqlr, [], $limitfrom, $limitnum);

// Pages links.
if (!$table->is_downloading()) {
    $pages = $count / $limitnum;
    $decimal = fmod($pages, 1);
    $pages = ($decimal > 0) ? intval($pages) + 1 : intval($pages);

    $content = '<p>Page: </p>';
    for ($i = 1; $i <= $pages; $i++) {
        $urlparams['page'] = ($i - 1) * $limitnum;

        if ($urlparams['page'] == $limitfrom) {
            $content .= $i;
        } else {
            $url = new moodle_url('/enrol/wallet/extra/coupontable.php', $urlparams);
            $content .= html_writer::link($url, $i);
        }

        $content .= ' ';
    }

    $pageslinks = html_writer::span($content);
}

foreach ($records as $record) {
    $editparams = array_merge(['edit' => true], (array)$record);
    $editurl = new moodle_url('couponedit.php', $editparams);
    $editbutton = ($canedit) ? $OUTPUT->single_button($editurl, get_string('edit'), 'post') : '';

    $chkbox = ($candelete) ? '<input type="checkbox" name="select['.$record->id.']" value="1" >' : '';

    $row = [
        'checkbox'    => $chkbox,
        'id'          => $record->id,
        'code'        => $record->code,
        'value'       => number_format($record->value, 2),
        'type'        => $record->type,
        'maxusage'    => $record->maxusage,
        'usetimes'    => $record->usetimes,
        'validfrom'   => !empty($record->validfrom) ? userdate($record->validfrom) : '',
        'validto'     => !empty($record->validto) ? userdate($record->validto) : '',
        'lastuse'     => !empty($record->lastuse) ? userdate($record->lastuse) : '',
        'timecreated' => !empty($record->timecreated) ? userdate($record->timecreated) : '',
        'edit'        => $editbutton,
    ];

    $table->add_data_keyed($row);

    flush();
}

if (!$table->is_downloading()) {
    echo $OUTPUT->heading(get_string('coupons', 'enrol_wallet'), 3);
    echo $OUTPUT->box($pageslinks);
}

$table->finish_output();

if (!$table->is_downloading($download, 'walletcoupons') && $candelete) {
    echo '<button name="delete" value="delete" type="submit" class="btn btn-secondary">'.get_string('delete').'</button>';
    echo '<input type="hidden" value="'.sesskey().'" name="sesskey">';
    echo '</form>';
}

if (!$table->is_downloading()) {
    echo $OUTPUT->box($pageslinks);
}

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
