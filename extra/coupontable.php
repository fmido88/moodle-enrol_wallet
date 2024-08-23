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
$ids   = optional_param('ids', false, PARAM_RAW);
$code  = optional_param('code', '', PARAM_TEXT);
$value = optional_param('value', null, PARAM_TEXT);
if (!empty($value) || $value === '0') {
    $value = clean_param($value, PARAM_FLOAT);
} else {
    $value = null;
}

$valuerelation = optional_param('valuerelation', null, PARAM_TEXT);
$type          = optional_param('type', null, PARAM_TEXT);
$category      = optional_param('category', null, PARAM_INT);
$courses       = optional_param_array('courses', null, PARAM_RAW);

$checkarrays = ['validto', 'validfrom', 'createdfrom', 'createdto'];
foreach ($checkarrays as $arrayparam) {
    $param = $arrayparam.'array';
    if (isset($_GET[$arrayparam]) && is_array($_GET[$arrayparam])) {
        $$param = optional_param_array($arrayparam, [], PARAM_INT);
    } else {
        $$param = optional_param($arrayparam, '', PARAM_INT);
    }
}

$maxusage = optional_param('maxusage', null, PARAM_TEXT);
if (!empty($maxusage) || $maxusage === '0') {
    $maxusage = clean_param($maxusage, PARAM_INT);
} else {
    $maxusage = null;
}

$maxrelation = optional_param('maxrelation', null, PARAM_TEXT);
$usetimes    = optional_param('usetimes', null, PARAM_TEXT);
if (!empty($usetimes) || $usetimes === '0') {
    $usetimes = clean_param($usetimes, PARAM_INT);
} else {
    $usetimes = null;
}

$userelation = optional_param('userelation', null, PARAM_TEXT);
$sort        = optional_param('tsort', 'id', PARAM_ALPHA);
$download    = optional_param('download', '', PARAM_ALPHA);
$page        = optional_param('page', 0, PARAM_INT);
$limitnum    = optional_param('perpage', 50, PARAM_INT);

// Sterilize the url parameters and conditions for sql.
$conditions = '1=1';
$urlparams = [];
$qparams = [];

$urlparams['tsort']   = (!empty($sort)) ? $sort : null;
$urlparams['page']    = (!empty($page)) ? $page : null;
$urlparams['perpage'] = (!empty($limitnum)) ? $limitnum : null;
$urlparams['ids']     = (!empty($ids)) ? $ids : null;

if (!empty($code)) {
    $conditions .= ' AND code = :code';
    $qparams['code'] = $code;
    $urlparams['code'] = $code;
}

if (!empty($value) || $value === 0.0) {
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
    $conditions .= ' AND value '.$op.' :value';
    $urlparams['value'] = $qparams['value'] = $value;
    $urlparams['valuerelation'] = $valuerelation;
}

if (!empty($type)) {
    $conditions .= ' AND type = :type';
    $urlparams['type'] = $qparams['type'] = $type;
}

if ($maxusage !== '' && $maxusage !== null) {
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
    $conditions .= ' AND maxusage '.$op.' :maxusage';
    $urlparams['maxusage'] = $qparams['maxusage'] = $maxusage;
    $urlparams['maxrelation'] = $maxrelation;
}

if ($usetimes !== '' && $usetimes !== null) {
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
    $conditions .= ' AND usetimes '.$op.' :usetimes';
    $urlparams['usetimes'] = $qparams['usetimes'] = $usetimes;
    $urlparams['userelation'] = $userelation;
}

if (!empty($category)) {
    $conditions .= ' AND category = :category';
    $urlparams['category'] = $qparams['category'] = $category;
}

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
        if (is_array($date)) {
            $$key = mktime(
                $date['hour'],
                $date['minute'],
                0,
                $date['month'],
                $date['day'],
                $date['year'],
            );
        } else {
            $$key = $date;
        }

        $urlparams[$key] = $$key;
        $qparams[$key] = $$key;
        $qparams[$key.'_empty'] = '';
        switch ($key) {
            case 'createdfrom':
                $operator = '>=';
                $column = 'timecreated';
                break;
            case 'createdto':
                $operator = '<=';
                $column = 'timecreated';
                break;
            case 'validfrom':
                $operator = '<=';
                $column = 'validfrom';
                break;
            case 'validto':
                $operator = '>=';
                $column = 'validto';
                break;
        }

        $conditions .= " AND ($column $operator :$key OR $column IS NULL OR $column = :$key"."_empty)";
    }
}

if (!empty($ids)) {
    $ids = trim($ids);
    $ids = str_replace(' ', '', $ids);
    $couponids = explode(",", $ids);
    list($idssql, $idsparams) = $DB->get_in_or_equal($couponids, SQL_PARAMS_NAMED);
    $conditions .= ' AND id '.$idssql;
    $qparams = array_merge($qparams, $idsparams);
}

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

$mform->addElement('text', 'ids', get_string('coupons_ids', 'enrol_wallet'));
$mform->setType('ids', PARAM_TEXT);
if (!empty($ids)) {
    $mform->setDefault('ids', $ids);
}

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
$mform->setType('value', PARAM_TEXT);
if ($value !== '' && $value !== null) {
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

$allcourses = get_courses('all', 'c.sortorder ASC', 'c.id, c.fullname');
$courseoptions = [];
foreach ($allcourses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
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

$mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), ['optional' => true]);
if (!empty($validfrom)) {
    $mform->setDefault('validfrom', $validfrom);
}

$mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), ['optional' => true]);
if (!empty($validto)) {
    $mform->setDefault('validto', $validto);
}

$mform->addElement('date_time_selector', 'createdfrom', get_string('createdfrom', 'enrol_wallet'), ['optional' => true]);
if (!empty($createdfrom)) {
    $mform->setDefault('createdfrom', $createdfrom);
}

$mform->addElement('date_time_selector', 'createdto', get_string('createdto', 'enrol_wallet'), ['optional' => true]);
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
$table->set_attribute('id', 'enrol-wallet-coupons-table');

// Setup the sorting properties.
$table->sortable(true);

// Start rendering the table.
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
} else {
    $limitfrom = $page * $limitnum;
}

// Count all data to get the number of pages later.
$count = $DB->count_records_select('enrol_wallet_coupons', $conditions, $qparams);

// The sql for records.
$sqlr = 'SELECT * '. $sql;
$records = $DB->get_records_sql($sqlr, $qparams, $limitfrom, $limitnum);

// Pages links.
if (!$table->is_downloading()) {
    $pages = $count / $limitnum;
    $decimal = fmod($pages, 1);
    $pages = ($decimal > 0) ? intval($pages) + 1 : intval($pages);
    $url = new moodle_url('/enrol/wallet/extra/coupontable.php', $urlparams);
    $paging = new paging_bar($count, $page, $limitnum, $url);

    $pageslinks = $OUTPUT->render($paging);
    echo $OUTPUT->box($pageslinks);
}

// Make the table downloadable.
if ($candownload) {
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_BOTTOM]);
} else {
    $table->is_downloadable(false);
}

foreach ($records as $record) {
    $editparams = array_merge(['edit' => true], (array)$record);
    $editurl = new moodle_url('couponedit.php', $editparams);
    $editbutton = ($canedit) ? $OUTPUT->single_button($editurl, get_string('edit'), 'get') : '';

    $chkbox = ($candelete) ? '<input id="id_select_'.$record->id.'" type="checkbox" name="select['.$record->id.']" value="1"' : '';

    if (!empty($record->category)) {
        if ($category = core_course_category::get($record->category, IGNORE_MISSING, true)) {
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

$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->box($pageslinks);

    if ($candelete) {
        $deleteform = new MoodleQuickForm('coupondelete', 'post', 'coupondelete.php', '', ['id' => 'enrolwallet_coupondelete']);
        $deletebutton = $deleteform->createElement('button', 'delete', get_string('delete'), ['onclick' => 'deleteCoupons()']);
        $deleteform->addGroup([$deletebutton], null, get_string('coupons_delete_selected', 'enrol_wallet'));

        $deleteform->addElement('hidden', 'sesskey');
        $deleteform->setDefault('sesskey', sesskey());
        $deleteform->setType('sesskey', PARAM_TEXT);

        $deleteform->display();
    }

    echo $OUTPUT->footer();

    if ($candelete) {
        $code = <<<JS
            function selectAllToDelete() {
                var table = document.getElementById('enrol-wallet-coupons-table');
                var checkBoxes = table.querySelectorAll('input[type="checkbox"]');

                if (checkBoxes) {
                    var i = 0;
                    checkBoxes.forEach((checkBox) => {
                        if (!checkBox.checked) {
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

            function deleteCoupons() {
                var form = document.getElementById('enrolwallet_coupondelete');
                var table = document.getElementById('enrol-wallet-coupons-table');
                var checkBoxes = table.querySelectorAll('input[type="checkbox"]');

                if (checkBoxes) {
                    var i = 0;
                    checkBoxes.forEach((checkBox) => {
                        if (checkBox.checked) {
                            var exist = document.getElementById(checkBox.id + '_cloned');
                            if (!exist) {
                                var TODELETE = document.createElement("input");
                                TODELETE.setAttribute("type", "hidden");
                                TODELETE.setAttribute("name", checkBox.name);
                                TODELETE.setAttribute("id", checkBox.id + '_cloned');
                                TODELETE.setAttribute("value", checkBox.value);
                                form.appendChild(TODELETE);
                            }
                            i++;
                        } else {
                            var remove = document.getElementById(checkBox.id + '_cloned');
                            if (remove) {
                                form.removeChild(remove);
                            }
                        }
                    })
                    if (i > 0) {
                        form.submit();
                    } else {
                        window.alert('No coupons selected to be deleted.');
                    }
                }
            }
        JS;
        echo "<script>$code</script>";
    }
}
