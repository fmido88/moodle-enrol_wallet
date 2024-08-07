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
 * Page handles the conditional discounts on the website.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_once($CFG->libdir.'/tablelib.php');

global $OUTPUT, $PAGE, $DB;

// Adding some security.
require_login();

$frontpagecontext = context_course::instance(SITEID);
$systemcontext = context_system::instance();
require_capability('enrol/wallet:config', $frontpagecontext);
require_capability('enrol/wallet:manage', $frontpagecontext);

$url = new moodle_url('/enrol/wallet/extra/conditionaldiscount.php');

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);
$PAGE->set_title(get_string('conditionaldiscount', 'enrol_wallet'));
$PAGE->set_heading(get_string('conditionaldiscount', 'enrol_wallet'));

$id     = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', false, PARAM_BOOL);
$add    = optional_param('add', false, PARAM_BOOL);
$edit   = optional_param('edit', false, PARAM_BOOL);
$confirmedit = optional_param('confirmedit', false, PARAM_BOOL);

$customdata = [
    'edit' => $edit,
    'id'   => $id,
];
$mform = new enrol_wallet\form\conditional_discount(null, $customdata, 'get');
$done = false;
if ($data = $mform->get_data()) {

    $dataobject = new stdClass;
    $dataobject->cond = $data->cond;
    $dataobject->percent = $data->percent;
    $dataobject->category = $data->category;
    $dataobject->timemodified = time();
    $dataobject->usermodified = $USER->id;
    $dataobject->timeto = $data->timeto ?? null;
    $dataobject->timefrom = $data->timefrom ?? null;

    if (!empty($data->have_bundle)) {
        $dataobject->bundle = $data->bundle_value;
        $dataobject->bundledesc = $data->bundle_desc['text'];
        $dataobject->descformat = $data->bundle_desc['format'];
    } else {
        $dataobject->bundle = null;
        $dataobject->bundledesc = null;
        $dataobject->descformat = null;
    }
    if ($add) {
        $dataobject->timecreated = time();
        $done = $DB->insert_record('enrol_wallet_cond_discount', $dataobject);
    } else if ($confirmedit && !empty($data->id)) {
        $dataobject->id = $data->id;
        $done = $DB->update_record('enrol_wallet_cond_discount', $dataobject);
    }
}

if ($delete && !empty($id) && confirm_sesskey()) {
    $done = $DB->delete_records('enrol_wallet_cond_discount', ['id' => $id]);
}

if (!empty($done)) {
    redirect($url);
}

echo $OUTPUT->header();

$mform->display();

// ----------------------------------------------------------------------------------------------
// Table.
$baseurl = new moodle_url('/enrol/wallet/extra/conditionaldiscount.php');
$table = new flexible_table('conditionaldiscount');

$table->define_baseurl($baseurl->out());

// Set up the discounts table.
$columns = [
            'id'          => 'id',
            'cond'        => get_string('condition', 'enrol_wallet'),
            'percent'     => get_string('conditionaldiscount_percentage', 'enrol_wallet'),
            'category'    => get_string('category'),
            'timeto'      => get_string('conditionaldiscount_timeto', 'enrol_wallet'),
            'timefrom'    => get_string('conditionaldiscount_timefrom', 'enrol_wallet'),
            'bundle'      => get_string('bundle_value', 'enrol_wallet'),
            'bundledesc'  => get_string('bundle_desc', 'enrol_wallet'),
            'edit'        => null,
            'delete'      => null,
        ];

$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->set_attribute('class', 'generaltable generalbox wallet-conditionaldiscount');

// Setup the sorting properties.
$table->sortable(false);

$table->is_downloadable(false);

$table->setup();

$records = $DB->get_records('enrol_wallet_cond_discount');

if (!empty($records)) {
    foreach ($records as $record) {
        if (empty($record->timeto)) {
            unset($record->timeto);
        }
        if (empty($record->timefrom)) {
            unset($record->timefrom);
        }

        $editparams = array_merge(['edit' => true, 'sesskey' => sesskey()], (array)$record);
        if (!empty($record->bundle)) {
            $editparams['have_bundle'] = 1;
            $editparams['bundle_value'] = $record->bundle;
            $editparams['bundle_desc[text]'] = $record->bundledesc;
            $editparams['bundle_desc[format]'] = $record->descformat;
        }
        $deleteparams = ['delete' => true, 'sesskey' => sesskey(), 'id' => $record->id];
        $editurl = new moodle_url('conditionaldiscount.php', $editparams);
        $deleteurl = new moodle_url('conditionaldiscount.php', $deleteparams);
        $editbutton = $OUTPUT->single_button($editurl, get_string('edit'), 'get');
        $deletebutton = $OUTPUT->single_button($deleteurl, get_string('delete'), 'get');
        if (!empty($record->category)) {
            if ($category = core_course_category::get($record->category, IGNORE_MISSING)) {
                $catname = $category->get_nested_name(false);
            } else {
                $catname = get_string('unknowncategory');
            }
        } else {
            $catname = format_string($SITE->fullname);
        }

        $row = [
            'id'         => $record->id,
            'cond'       => $record->cond,
            'percent'    => $record->percent.'%',
            'category'   => $catname,
            'timefrom'   => !empty($record->timefrom) ? userdate($record->timefrom) : '',
            'timeto'     => !empty($record->timeto) ? userdate($record->timeto) : '',
            'bundle'     => $record->bundle ?? '',
            'bundledesc' => !empty($record->bundledesc) ? format_text($record->bundledesc, $record->descformat) : '',
            'edit'       => $editbutton,
            'delete'     => $deletebutton,
        ];

        $table->add_data_keyed($row);

        flush();
    }

    $table->finish_output();
} else {
    echo html_writer::span(get_string('nodiscountstoshow', 'enrol_wallet'), 'alert alert-info');
}

echo $OUTPUT->footer();
