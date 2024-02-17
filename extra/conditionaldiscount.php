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
require_once($CFG->libdir.'/formslib.php');
global $OUTPUT, $PAGE, $DB;

// Adding some security.
require_login();

$frontpagecontext = context_course::instance(SITEID);
$systemcontext = context_system::instance();
require_capability('enrol/wallet:config', $frontpagecontext);
require_capability('enrol/wallet:manage', $frontpagecontext);

$url = new moodle_url('/enrol/wallet/extra/conditionaldiscount.php');

// Parameters.
$id               = optional_param('id', 0, PARAM_INT);
$condition        = optional_param('cond', '', PARAM_FLOAT);
$catid            = optional_param('category', 0, PARAM_INT);
$percentage       = optional_param('percent', '', PARAM_FLOAT);
if (isset($_GET['timeto']) && is_array($_GET['timeto'])) {
    $timetoarray  = optional_param_array('timeto', [], PARAM_INT);
} else {
    $timeto  = optional_param('timeto', null, PARAM_INT);
}
if (isset($_GET['timefrom']) && is_array($_GET['timefrom'])) {
    $timefromarray = optional_param_array('timefrom', [], PARAM_INT);
} else {
    $timefrom = optional_param('timefrom', null, PARAM_INT);
}
$delete           = optional_param('delete', false, PARAM_BOOL);
$add              = optional_param('add', false, PARAM_BOOL);
$edit             = optional_param('edit', false, PARAM_BOOL);
$confirmedit      = optional_param('confirmedit', false, PARAM_BOOL);
$sesskey          = optional_param('sesskey', '', PARAM_RAW);

if (!empty($sesskey) && confirm_sesskey()) {
    $done = false;
    $arraydates = [];
    if (!empty($timetoarray)) {
        $arraydates['timeto'] = $timetoarray;
    }
    if (!empty($timefromarray)) {
        $arraydates['timefrom'] = $timefromarray;
    }
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
            $$key = 0;
        }
    }
    $dataobject = new stdClass;
    $dataobject->cond = $condition;
    $dataobject->percent = $percentage;
    $dataobject->category = $catid;
    $dataobject->timemodified = time();
    $dataobject->usermodified = $USER->id;
    if (!empty($timeto)) {
        $dataobject->timeto = $timeto;
    }
    if (!empty($timefrom)) {
        $dataobject->timefrom = $timefrom;
    }

    if ($add) {
        $dataobject->timecreated = time();
        $done = $DB->insert_record('enrol_wallet_cond_discount', $dataobject);
    } else if ($confirmedit && !empty($id)) {
        $dataobject->id = $id;
        $done = $DB->update_record('enrol_wallet_cond_discount', $dataobject);
    } else if ($delete && !empty($id)) {
        $done = $DB->delete_records('enrol_wallet_cond_discount', ['id' => $id]);
    }
    if (!empty($done)) {
        redirect($url);
    }
}

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);
$PAGE->set_title(get_string('conditionaldiscount', 'enrol_wallet'));
$PAGE->set_heading(get_string('conditionaldiscount', 'enrol_wallet'));

// --------------------------------------------------------------------------------------
// Form.
// Setup the filtration form.
$mform = new \MoodleQuickForm('conditionaldiscount', 'get', 'conditionaldiscount.php');

$mform->addElement('header', 'conditionaldiscount', get_string('conditionaldiscount', 'enrol_wallet'));

$options = enrol_wallet\category\options::get_all_categories_options();
$mform->addElement('select', 'category', get_string('category'), $options);
$mform->setDefault('category', $catid);

$mform->addElement('text', 'cond', get_string('conditionaldiscount_condition', 'enrol_wallet'));
$mform->setType('cond', PARAM_FLOAT);
$mform->addHelpButton('cond', 'conditionaldiscount_condition', 'enrol_wallet');
if (!empty($condition)) {
    $mform->setDefault('cond', $condition);
}

$mform->addElement('text', 'percent', get_string('conditionaldiscount_percent', 'enrol_wallet'));
$mform->setType('percent', PARAM_FLOAT);
$mform->addHelpButton('percent', 'conditionaldiscount_percent', 'enrol_wallet');
if (!empty($percentage)) {
    $mform->setDefault('percent', $percentage);
}

$mform->addElement('date_time_selector', 'timefrom',
                    get_string('conditionaldiscount_timefrom', 'enrol_wallet'), ['optional' => true]);
$mform->addHelpButton('timefrom', 'conditionaldiscount_timefrom', 'enrol_wallet');
if (!empty($timefrom)) {
    $mform->setDefault('timefrom', $timefrom);
}

$mform->addElement('date_time_selector', 'timeto',
                    get_string('conditionaldiscount_timeto', 'enrol_wallet'), ['optional' => true]);
$mform->addHelpButton('timefrom', 'conditionaldiscount_timeto', 'enrol_wallet');
if (!empty($timeto)) {
    $mform->setDefault('timeto', $timeto);
}

if (!empty($id)) {
    $mform->addElement('hidden', 'id');
    $mform->setType('id', PARAM_INT);
    $mform->setDefault('id', $id);
}

$mform->addElement('hidden', 'sesskey');
$mform->setType('sesskey', PARAM_RAW);
$mform->setConstant('sesskey', sesskey());

if ($edit) {
    $mform->addElement('submit', 'confirmedit', get_string('confirmedit', 'enrol_wallet'));
} else {
    $mform->addElement('submit', 'add', get_string('add'));
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
        $deleteparams = ['delete' => true, 'sesskey' => sesskey(), 'id' => $record->id];
        $editurl = new moodle_url('conditionaldiscount.php', $editparams);
        $deleteurl = new moodle_url('conditionaldiscount.php', $deleteparams);
        $editbutton = $OUTPUT->single_button($editurl, get_string('edit'), 'get');
        $deletebutton = $OUTPUT->single_button($deleteurl, get_string('delete'), 'get');
        if (!empty($record->category)) {
            if ($category = core_course_category::get($record->category, IGNORE_MISSING)) {
                $catname = $category->get_nested_name(false);
            } else {
                $catname = get_string('deleted');
            }
        } else {
            $catname = $SITE->fullname;
        }

        $row = [
            'id'         => $record->id,
            'cond'       => $record->cond,
            'percent'    => $record->percent.'%',
            'category'   => $catname,
            'timefrom'   => !empty($record->timefrom) ? userdate($record->timefrom) : '',
            'timeto'     => !empty($record->timeto) ? userdate($record->timeto) : '',
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
