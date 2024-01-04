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

// Parameters.
$code             = optional_param('code', '', PARAM_TEXT);
$userid           = optional_param('userid', '', PARAM_INT);
$value            = optional_param('value', '', PARAM_FLOAT);
$valuerelation    = optional_param('valuerelation', '', PARAM_TEXT);
$type             = optional_param('type', '', PARAM_TEXT);
$category         = optional_param('category', '', PARAM_INT);
$courses          = optional_param_array('courses', '', PARAM_RAW);
$validtoarray     = optional_param_array('validto', [], PARAM_INT);
$validfromarray   = optional_param_array('validfrom', [], PARAM_INT);
$createdtoarray   = optional_param_array('createdto', [], PARAM_INT);
$createdfromarray = optional_param_array('createdfrom', [], PARAM_INT);
$usedtoarray      = optional_param_array('usedto', [], PARAM_INT);
$usedfromarray    = optional_param_array('usedfrom', [], PARAM_INT);
$maxusage         = optional_param('maxusage', '', PARAM_INT);
$maxrelation      = optional_param('maxrelation', '', PARAM_TEXT);
$usetimes         = optional_param('usetimes', '', PARAM_INT);
$userelation      = optional_param('userelation', '', PARAM_TEXT);
$sort             = optional_param('tsort', 'userid', PARAM_ALPHA);
$download         = optional_param('download', '', PARAM_ALPHA);
$page             = optional_param('page', 0, PARAM_INT);
$limitnum         = optional_param('perpage', 50, PARAM_INT);

// Sterilize the url parameters and conditions for sql.
$conditions = '1=1';
$urlparams = [];

$urlparams['tsort']   = (!empty($sort)) ? $sort : null;
$urlparams['page']    = (!empty($page)) ? $page : null;
$urlparams['perpage'] = (!empty($limitnum)) ? $limitnum : null;

$conditions .= (!empty($code)) ? ' AND c.code = \''.$code.'\'' : '';
$urlparams['code'] = (!empty($code)) ? $code : null;

$conditions .= (!empty($userid)) ? ' AND u.userid = \''.$userid.'\'' : '';
$urlparams['code'] = (!empty($userid)) ? $userid : null;

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
    $conditions .= ' AND u.value '.$op.' \''.$value.'\'';
    $urlparams['value'] = $value;
    $urlparams['valuerelation'] = $valuerelation;
}


$conditions .= (!empty($type)) ? ' AND u.type = \''.$type.'\'' : '';
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
    $conditions .= ' AND c.maxusage '.$op.' \''.$maxusage.'\'';
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
    $conditions .= ' AND c.usetimes '.$op.' \''.$usetimes.'\'';
    $urlparams['usetimes'] = $usetimes;
    $urlparams['userelation'] = $userelation;
}

$conditions .= (!empty($category)) ? ' AND c.category = \''.$category.'\'' : '';
$urlparams['category'] = (!empty($category)) ? $category : null;

if (!empty($courses)) {
    foreach ($courses as $courseid) {
        $conditions .= (!empty($courses)) ? ' AND c.courses like \'%'.$courseid.'%\'' : '';
    }
}
$urlparams['courses'] = (!empty($courses)) ? http_build_query($courses) : null;

$arraydates = [
    'createdfrom' => $createdfromarray,
    'createdto'   => $createdtoarray,
    'validfrom'   => $validfromarray,
    'validto'     => $validtoarray,
    'usedfrom'    => $usedfromarray,
    'usedto'      => $usedtoarray,
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

$conditions .= (!empty($cratedfrom)) ? ' AND c.timecreated >= \''.$createdfrom.'\'' : '';
$conditions .= (!empty($cratedto)) ? ' AND c.timecreated <= \''.$createdto.'\'' : '';
$conditions .= (!empty($validto)) ? ' AND c.validto = \''.$validto.'\'' : '';
$conditions .= (!empty($validfrom)) ? ' AND c.validfrom = \''.$validfrom.'\'' : '';
$conditions .= (!empty($usedto)) ? ' AND u.timeused <= \''.$usedto.'\'' : '';
$conditions .= (!empty($usedfrom)) ? ' AND u.timeused >= \''.$usedfrom.'\'' : '';

// Unset empty params.
foreach ($urlparams as $key => $val) {
    if (empty($val)) {
        unset($urlparams[$key]);
    }
}

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$url = new moodle_url('/enrol/wallet/extra/couponusage.php', $urlparams);
$PAGE->set_url($url);
$PAGE->set_title(get_string('coupons', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupon_usage', 'enrol_wallet'));

// --------------------------------------------------------------------------------------
// Form.
// Setup the filtration form.
$mform = new \MoodleQuickForm('couponfilter', 'get', 'couponusage.php');

$mform->addElement('header', 'filter', get_string('filter_coupons', 'enrol_wallet'));


// User selector.
$attributes = [
    'multiple' => false,
    'ajax' => 'core_user/form_user_selector',
];

if (class_exists('core_user\fields')) {
    $attributes['valuehtmlcallback'] = function($userid) {
        global $OUTPUT;

        $context = \context_system::instance();
        $fields = \core_user\fields::for_name()->with_identity($context, true);
        $record = core_user::get_user($userid, 'id ' . $fields->get_sql()->selects, MUST_EXIST);

        $user = (object)[
            'id' => $record->id,
            'fullname' => fullname($record, has_capability('moodle/site:viewfullnames', $context)),
            'extrafields' => [],
        ];

        foreach ($fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]) as $extrafield) {
            $user->extrafields[] = (object)[
                'name' => $extrafield,
                'value' => s($record->$extrafield),
            ];
        }

        return $OUTPUT->render_from_template('core_user/form_user_selector_suggestion', $user);
    };
} else {
    $attributes['valuehtmlcallback'] = function($userid) {
        global $OUTPUT;

        $context = \context_system::instance();
        $record = core_user::get_user($userid, 'id, firstname, lastname, email', MUST_EXIST);

        $user = (object)[
            'id' => $record->id,
            'fullname' => fullname($record, has_capability('moodle/site:viewfullnames', $context)),
            'extrafields' => [],
        ];

        return $OUTPUT->render_from_template('core_user/form_user_selector_suggestion', $user);
    };
}

$mform->addElement('autocomplete', 'userid', get_string('user'), [], $attributes);
if ($userid != null && $userid != '') {
    $mform->setDefault('userid', $userid);
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

$mform->addElement('date_time_selector', 'usedfrom', get_string('usedfrom', 'enrol_wallet'), ['optional' => true]);
if (!empty($usedfrom)) {
    $mform->setDefault('usedfrom', $usedfrom);
}

$mform->addElement('date_time_selector', 'usedto', get_string('usedto', 'enrol_wallet'), ['optional' => true]);
if (!empty($usedto)) {
    $mform->setDefault('usedto', $usedto);
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
$baseurl = new moodle_url('/enrol/wallet/extra/couponusage.php');
$table = new flexible_table('walletcouponsusagetable');

$table->define_baseurl($url->out(false));

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->single_button($baseurl, get_string('clear_filter', 'enrol_wallet'));


// Set up the coupons table.
$columns = [
            'id'          => 'id',
            'code'        => get_string('coupon_t_code', 'enrol_wallet'),
            'value'       => get_string('coupon_t_value', 'enrol_wallet'),
            'type'        => get_string('coupon_t_type', 'enrol_wallet'),
            'category'    => get_string('category'),
            'courses'     => get_string('courses'),
            'maxusage'    => get_string('coupons_maxusage', 'enrol_wallet'),
            'usetimes'    => get_string('coupon_t_usage', 'enrol_wallet'),
            'validfrom'   => get_string('validfrom', 'enrol_wallet'),
            'validto'     => get_string('validto', 'enrol_wallet'),
            'timeused'    => get_string('coupon_usetimes', 'enrol_wallet'),
            'user'        => get_string('user'),
            'course'      => get_string('course'),
            'timecreated' => get_string('coupon_t_timecreated', 'enrol_wallet'),
        ];

$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->set_attribute('class', 'generaltable generalbox wallet-couponstable');

// Setup the sorting properties.
$table->sortable(true);

$table->no_sorting('user');
$table->no_sorting('course');

$table->setup();

// Work out direction of sort required.
$sortcolumns = $table->get_sort_columns();

// Now do sorting if specified.
// Sanity check $sort var before including in sql. Make sure it matches a known column.
$allowedsort = array_diff(array_keys($table->columns), $table->column_nosort);
if (!in_array($sort, $allowedsort)) {
    $sort = '';
}

$orderby = 'c.id ASC';
if (!empty($sort)) {
    $direction = ' DESC';
    if (!empty($sortcolumns[$sort]) && $sortcolumns[$sort] == SORT_ASC) {
        $direction = ' ASC';
    }
    if ($sort == 'timeused' || $sort == 'id') {
        $orderby = " u.$sort $direction";
    } else {
        $orderby = " c.$sort $direction";
    }
}

// SQL.
$sql = '';
$sql = ' FROM {enrol_wallet_coupons_usage} u JOIN {enrol_wallet_coupons} c ON c.code = u.code ';
if (!empty($conditions)) {
    $sql .= 'WHERE ' . $conditions;
}

if (!empty($orderby)) {
    $sql .= ' ORDER BY ' . $orderby;
}

// The sql for records.
$sqlr = 'SELECT u.id as uid, c.id as id, u.userid, u.instanceid, u.timeused,
        c.code, u.type, u.value, c.category, c.courses, c.maxusage,
        c.usetimes, c.validfrom, c.validto, c.timecreated '. $sql;

// Count all data to get the number of pages later.
$count = count($DB->get_records_sql($sqlr, null));

$records = $DB->get_records_sql($sqlr, null, $page * $limitnum, $limitnum);

// Pages links.
$pages = $count / $limitnum;
$decimal = fmod($pages, 1);
$pages = ($decimal > 0) ? intval($pages) + 1 : intval($pages);
$url = new moodle_url('/enrol/wallet/extra/couponusage.php', $urlparams);
$paging = new paging_bar($count, $page, $limitnum, $url);
$pageslinks = $OUTPUT->render($paging);

$wallet = enrol_get_plugin('wallet');
foreach ($records as $record) {

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

    $username = '';
    if (!empty($record->userid) && $user = \core_user::get_user($record->userid)) {
        $username = fullname($user);
        $userurl = new moodle_url('/user/profile.php', ['id' => $user->id]);
        $username = html_writer::link($userurl, $username);
    }

    $coursename = '';
    if (!empty($record->instanceid)) {
        try {
            $course = $wallet->get_course_by_instance_id($record->instanceid);
            $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
            $coursename = html_writer::link($courseurl, $course->fullname);
        } catch (moodle_exception $e) {
            $coursename = '';
        }
    }
    $row = [
        'id'          => $record->id,
        'code'        => $record->code,
        'value'       => number_format($record->value, 2),
        'type'        => $record->type,
        'category'    => $category ?? '',
        'courses'     => $courses,
        'maxusage'    => $record->maxusage,
        'usetimes'    => $record->usetimes,
        'validfrom'   => !empty($record->validfrom) ? userdate($record->validfrom) : '',
        'validto'     => !empty($record->validto) ? userdate($record->validto) : '',
        'timeused'    => !empty($record->timeused) ? userdate($record->timeused) : '',
        'user'        => $username,
        'course'      => $coursename,
        'timecreated' => !empty($record->timecreated) ? userdate($record->timecreated) : '',
    ];

    $table->add_data_keyed($row);

    flush();
}

echo $OUTPUT->heading(get_string('coupon_usage', 'enrol_wallet'), 3);
echo $OUTPUT->box($pageslinks);

$table->finish_output();

echo $OUTPUT->box($pageslinks);
echo $OUTPUT->footer();
