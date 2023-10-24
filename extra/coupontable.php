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
$ids              = optional_param('ids', false, PARAM_RAW);
$code             = optional_param('code', '', PARAM_TEXT);
$value            = optional_param('value', '', PARAM_FLOAT);
$valuerelation    = optional_param('valuerelation', '', PARAM_TEXT);
$type             = optional_param('type', '', PARAM_TEXT);
$category         = optional_param('category', '', PARAM_INT);
$courses          = optional_param_array('courses', '', PARAM_RAW);
$validtoarray     = optional_param_array('validto', [], PARAM_INT);
$validfromarray   = optional_param_array('validfrom', [], PARAM_INT);
$createdtoarray   = optional_param_array('createdto', [], PARAM_INT);
$createdfromarray = optional_param_array('createdfrom', [], PARAM_INT);
$maxusage         = optional_param('maxusage', '', PARAM_INT);
$maxrelation      = optional_param('maxrelation', '', PARAM_TEXT);
$usetimes         = optional_param('usetimes', '', PARAM_INT);
$userelation      = optional_param('userelation', '', PARAM_TEXT);
$sort             = optional_param('tsort', 'userid', PARAM_ALPHA);
$download         = optional_param('download', '', PARAM_ALPHA);
$limitfrom        = optional_param('page', 0, PARAM_INT);
$limitnum         = optional_param('perpage', 50, PARAM_INT);

// Sterilize the url parameters and conditions for sql.
$conditions = '1=1';
$urlparams = [];

$urlparams['tsort']   = (!empty($sort)) ? $sort : null;
$urlparams['page']    = (!empty($limitfrom)) ? $limitfrom : null;
$urlparams['perpage'] = (!empty($limitnum)) ? $limitnum : null;
$urlparams['ids']     = (!empty($ids)) ? $ids : null;

$conditions .= (!empty($code)) ? ' AND code = \''.$code.'\'' : '';
$urlparams['code'] = (!empty($code)) ? $code : null;

if ($value != '' && $value != null) {
    switch ($valuerelation) {
        case 'eq':
            $op = '=';
            break;
        case 'neq':
            $op = '!=';
            break;
        case 'st':
            $op = '<';
            break;
        case 'steq':
            $op = '<=';
            break;
        case 'gt':
            $op = '>';
            break;
        case 'gteq':
            $op = '>=';
            break;
        default:
            $op = '=';
    }
    $conditions .= ' AND value '.$op.' \''.$value.'\'';
    $urlparams['value'] = $value;
    $urlparams['valuerelation'] = $valuerelation;
}


$conditions .= (!empty($type)) ? ' AND type = \''.$type.'\'' : '';
$urlparams['type'] = (!empty($type)) ? $type : null;

if ($maxusage != '' && $maxusage != null) {
    switch ($maxrelation) {
        case 'eq':
            $op = '=';
            break;
        case 'neq':
            $op = '!=';
            break;
        case 'st':
            $op = '<';
            break;
        case 'steq':
            $op = '<=';
            break;
        case 'gt':
            $op = '>';
            break;
        case 'gteq':
            $op = '>=';
            break;
        default:
            $op = '=';
    }
    $conditions .= ' AND maxusage '.$op.' \''.$maxusage.'\'';
    $urlparams['maxusage'] = $maxusage;
    $urlparams['maxrelation'] = $maxrelation;
}

if ($usetimes != '' && $usetimes != null) {
    switch ($userelation) {
        case 'eq':
            $op = '=';
            break;
        case 'neq':
            $op = '!=';
            break;
        case 'st':
            $op = '<';
            break;
        case 'steq':
            $op = '<=';
            break;
        case 'gt':
            $op = '>';
            break;
        case 'gteq':
            $op = '>=';
            break;
        default:
            $op = '=';
    }
    $conditions .= ' AND usetimes '.$op.' \''.$usetimes.'\'';
    $urlparams['usetimes'] = $usetimes;
    $urlparams['userelation'] = $userelation;
}

$conditions .= (!empty($category)) ? ' AND category = \''.$category.'\'' : '';
$urlparams['category'] = (!empty($category)) ? $category : null;

if (!empty($courses)) {
    foreach ($courses as $courseid) {
        $conditions .= (!empty($courses)) ? ' AND courses like \'%'.$courseid.'%\'' : '';
    }
}
$urlparams['courses'] = (!empty($courses)) ? http_build_query($courses) : null;

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

$conditions .= (!empty($cratedfrom)) ? ' AND timecreated >= \''.$createdfrom.'\'' : '';
$conditions .= (!empty($cratedto)) ? ' AND timecreated <= \''.$createdto.'\'' : '';
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

$opt = [
    'eq'   => get_string('equalsto', 'enrol_wallet'),
    'neq'  => get_string('notequal', 'enrol_wallet'),
    'gt'   => get_string('greaterthan', 'enrol_wallet'),
    'gteq' => get_string('greaterthanorequal', 'enrol_wallet'),
    'st'   => get_string('smallerthan', 'enrol_wallet'),
    'steq' => get_string('smallerthanorequal', 'enrol_wallet'),
];
$valuegroup[] = $mform->createElement('select', 'valuerelation', '', $opt);
$valuegroup[] = $mform->createElement('text', 'value');
$mform->setType('value', PARAM_FLOAT);
if ($value != '' && $value != null) {
    $mform->setDefault('value', $value);
    $mform->setDefault('valuerelation', $userelation);
}
$mform->addGroup($valuegroup, 'valuegroup', get_string('coupon_value', 'enrol_wallet'), null, false);

$types = [
    ''         => get_string('any'),
    'fixed'    => get_string('fixedvaluecoupon', 'enrol_wallet'),
    'percent'  => get_string('percentdiscountcoupon', 'enrol_wallet'),
    'enrol'    => get_string('enrolcoupon', 'enrol_wallet'),
    'category' => get_string('categorycoupon', 'enrol_wallet'),
];
$mform->addElement('select', 'type', get_string('coupon_type', 'enrol_wallet'), $types);
$mform->setDefault('type', $type);

$categories = \core_course_category::get_all();
$catoptions = ['' => get_string('any')];
foreach ($categories as $cat) {
    $catoptions[$cat->id] = $cat->get_nested_name(false);
}
$mform->addElement('select', 'category',  get_string('category'),  $catoptions);
$mform->addHelpButton('category', 'category_options', 'enrol_wallet');
$mform->hideIf('category', 'type', 'neq', 'category');
if (!empty($category)) {
    $mform->setDefault('category', $category);
}

$allcourses = get_courses();
$courseoptions = [];
foreach ($allcourses as $course) {
    $courseoptions[$course->id] = $course->fullname;
}
$mform->addElement('autocomplete', 'courses', get_string('courses'), $courseoptions, ['multiple' => true]);
$mform->addHelpButton('courses', 'courses_options', 'enrol_wallet');
$mform->hideIf('courses', 'type', 'neq', 'enrol');
if (!empty($courses)) {
    $mform->setDefault('courses', $courses);
}


$usetimesgroup[] = $mform->createElement('select', 'userelation', '', $opt);

$usetimesgroup[] = $mform->createElement('text', 'usetimes', get_string('coupon_t_usage', 'enrol_wallet'));
$mform->setType('usetimes', PARAM_RAW);
if ($usetimes != '' && $usetimes != null) {
    $mform->setDefault('usetimes', $usetimes);
    $mform->setDefault('userelation', $userelation);
}

$mform->addGroup($usetimesgroup, 'usagegroup', get_string('coupon_t_usage', 'enrol_wallet'), null, false);

$maxusagegroup[] = $mform->createElement('select', 'maxrelation', '', $opt);

$maxusagegroup[] = $mform->createElement('text', 'maxusage');
$mform->setType('maxusage', PARAM_RAW);
if ($maxusage != '' && $maxusage != null) {
    $mform->setDefault('maxusage', $maxusage);
    $mform->setDefault('maxrelation', $maxrelation);
}

$mform->addGroup($maxusagegroup, 'maxusagegroup', get_string('coupons_maxusage', 'enrol_wallet'), null, false);

$mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), array('optional' => true));
if (!empty($validfrom)) {
    $mform->setDefault('validfrom', $validfrom);
}

$mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), array('optional' => true));
if (!empty($validto)) {
    $mform->setDefault('validto', $validto);
}

$mform->addElement('date_time_selector', 'createdfrom', get_string('createdfrom', 'enrol_wallet'), array('optional' => true));
if (!empty($createdfrom)) {
    $mform->setDefault('createdfrom', $createdfrom);
}

$mform->addElement('date_time_selector', 'createdto', get_string('createdto', 'enrol_wallet'), array('optional' => true));
if (!empty($createdto)) {
    $mform->setDefault('createdto', $createdto);
}

$limits = [];
for ($i = 25; $i <= 1000; $i = $i + 25) {
    $limits[$i] = $i;
}

$mform->addElement('select', 'perpage', get_string('coupon_perpage', 'enrol_wallet'), $limits);
$mform->setDefault('perpage', $limitnum);

$mform->addElement('submit', 'submit', get_string('coupon_applyfilter', 'enrol_wallet'));

// ----------------------------------------------------------------------------------------------
// Table.
$baseurl = new moodle_url('/enrol/wallet/extra/coupontable.php');
$table = new flexible_table('walletcouponstable');

$table->define_baseurl($url->out(false));

if (!$table->is_downloading($download, 'walletcoupons')) {
    if ($candelete) {
        // Only way to not mix this form with the download button is to print this before the header (till I figure something else).
        echo '<form id="enrolwallet_coupondelet" name="coupondelete" method="post" action="coupondelete.php">';
    }

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->single_button($baseurl, get_string('clear_filter', 'enrol_wallet'));
}

// Set up the coupons table.
$columns = [
            'checkbox'    => html_writer::link('#', get_string('selectall'), ['onClick' => 'selectAllToDelete()']),
            'id'          => 'id',
            'code'        => get_string('coupon_t_code', 'enrol_wallet'),
            'value'       => get_string('coupon_t_value', 'enrol_wallet'),
            'type'        => get_string('coupon_t_type', 'enrol_wallet'),
            'category'    => get_string('category'),
            'courses'     => get_string('courses'),
            'maxusage'    => get_string('coupons_maxusage', 'enrol_wallet'),
            'maxperuser'  => get_string('coupons_maxperuser', 'enrol_wallet'),
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

// If we download the table we need all the pages.
if ($table->is_downloading()) {
    $limitfrom = 0;
    $limitnum = 0;
}

if (empty($ids)) {
    // Count all data to get the number of pages later.
    $count = $DB->count_records_select('enrol_wallet_coupons', $conditions, []);

    // The sql for records.
    $sqlr = 'SELECT * '. $sql;
    $records = $DB->get_records_sql($sqlr, [], $limitfrom, $limitnum);
} else {
    $ids = explode(',', $ids);
    foreach ($ids as $id) {
        if ($record = $DB->get_record('enrol_wallet_coupons', ['id' => $id])) {
            $records[$id] = $record;
        }
    }
    $count = count($records);
}

// Make the table downloadable.
if ($candownload) {
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
} else {
    $table->is_downloadable(false);
}

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
    $editbutton = ($canedit) ? $OUTPUT->single_button($editurl, get_string('edit'), 'get') : '';

    $chkbox = ($candelete) ? '<input type="checkbox" name="select['.$record->id.']" value="1" >' : '';

    if (!empty($record->category)) {
        if ($category = core_course_category::get($record->category, IGNORE_MISSING)) {
            $category = $category->get_nested_name(false);
        }
    }

    $courses = '';
    if (!empty($record->courses)) {
        $courses = [];
        $coursesids = explode(',', $record->courses);
        foreach ($coursesids as $courseid) {
            if ($DB->record_exists('course', ['id' => $courseid])) {
                $courses[] = get_course($courseid)->shortname;
            }
        }
        $courses = implode('/', $courses);
    }

    $row = [
        'checkbox'    => $chkbox,
        'id'          => $record->id,
        'code'        => $record->code,
        'value'       => number_format($record->value, 2),
        'type'        => $record->type,
        'category'    => $category ?? '',
        'courses'     => $courses,
        'maxusage'    => !empty($record->maxusage) ? $record->maxusage : 'unlimited',
        'maxperuser'  => !empty($record->maxperuser) ? $record->maxperuser : 'max. limit',
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
    echo $OUTPUT->footer();
    if ($candelete) {
        $code = <<<JS
            function selectAllToDelete() {
                var form = document.getElementById('enrolwallet_coupondelet');
                var checkBoxes = form.querySelectorAll('input[type="checkbox"]');

                if (checkBoxes) {
                    var i = 0;
                    checkBoxes.forEach((checkBox) => {
                        if (!checkBox.checked && checkBox.id == '') {
                            checkBox.checked = true;
                            i++;
                        }
                    })
                    if (i == 0) {
                        checkBoxes.forEach((checkBox) => {
                            if (checkBox.checked) {
                                checkBox.checked = false;
                            }
                        })
                    }
                }
            }
        JS;
        echo "<script>$code</script>";
    }
}
