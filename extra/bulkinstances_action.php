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
 * Action for bulk edit instances.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();
$frontpagectx = context_course::instance(SITEID);
require_capability('enrol/wallet:manage', $frontpagectx);
// Initialize the data object.
$data = new stdClass;
// Get all parameters form the form.
// First we need the courses to edit.
$courses = required_param_array('courses', PARAM_INT);

// No get the data to change.
// If empty or -1 that's mean that we don't need to add it to the $data object.
$name = required_param('name', PARAM_TEXT);
if ($name != '') {
    $data->name = $name;
}
$cost = optional_param('cost', '', PARAM_NUMBER);
if ($cost != '') {
    $data->cost = $cost;
}
$currency = required_param('currency', PARAM_RAW);
if ($currency != -1) {
    $data->currency = $currency;
}
$status = required_param('status', PARAM_INT);
if ($status >= 0) {
    $data->status = (int)$status;
}
$customint1 = required_param('customint1', PARAM_INT);
if ($customint1 >= 0) {
    $data->customint1 = $customint1;
}
$customint2 = required_param('customint2', PARAM_INT);
if ($customint2 >= 0) {
    $data->customint2 = $customint2;
}
$customint3 = required_param('customint3', PARAM_INT);
if ($customint3 >= 0) {
    $data->customint3 = $customint3;
}
$customint4 = required_param('customint4', PARAM_INT);
if ($customint4 >= 0) {
    $data->customint4 = $customint4;
    if ($customint4 > 0) {
        $data->customtext1 = required_param('customtext1', PARAM_TEXT);
    }
}
$customint5 = required_param('customint5', PARAM_INT);
if ($customint5 >= 0) {
    $data->customint5 = $customint5;
}
$customint6 = required_param('customint6', PARAM_INT);
if ($customint6 >= 0) {
    $data->customint6 = $customint6;
}
$awards = optional_param('awards', 0, PARAM_INT);
if ($awards) {
    $customint8 = optional_param('customint8', 0, PARAM_INT);
    if ($customint8 >= 0) {
        $data->customint8 = $customint8;
    }
    if ($customint8) {
        $customdec1 = required_param('customdec1', PARAM_NUMBER);
        $customdec2 = required_param('customdec2', PARAM_NUMBER);
        $data->customdec1 = $customdec1;
        $data->customdec2 = $customdec2;
    } else {
        $customdec1 = -1;
        $customdec2 = -1;
    }
}

$roleid = required_param('roleid', PARAM_INT);
if ($roleid >= 0) {
    $data->roleid = $roleid;
}
$enrolperiod = optional_param_array('enrolperiod', [], PARAM_INT);
$enrolperiod = (!empty($enrolperiod)) ? $enrolperiod['number'] * $enrolperiod['timeunit'] : -1;
if ($enrolperiod >= 0) {
    $data->enrolperiod = $enrolperiod;
}
$expirynotify = required_param('expirynotify', PARAM_INT);
if ($expirynotify < 0) {
    $expirythreshold = -1;
} else {
    $expirythreshold = optional_param_array('expirythreshold', 0, PARAM_INT);
    if (!empty($expirythreshold)) {
        $data->expirythreshold = $expirythreshold['number'] * $expirythreshold['timeunit'];
    } else {
        $data->expirythreshold = 0;
    }
}
$enrolstartdate = optional_param_array('enrolstartdate', [], PARAM_INT);
if (!empty($enrolstartdate)) {
    $data->enrolstartdate = mktime(
        $enrolstartdate['hour'],
        $enrolstartdate['minute'],
        0,
        $enrolstartdate['month'],
        $enrolstartdate['day'],
        $enrolstartdate['year'],
    );
}

$enrolenddate = optional_param_array('enrolenddate', [], PARAM_INT);
if (!empty($enrolenddate)) {
    $data->enrolenddate = mktime(
        $enrolenddate['hour'],
        $enrolenddate['minute'],
        0,
        $enrolenddate['month'],
        $enrolenddate['day'],
        $enrolenddate['year'],
    );
}
if (confirm_sesskey()) {
    // Initialize the counting variables.
    $i = 0; // For updated instances.
    $y = 0; // For added instances.
    global $DB;
    require_once(__DIR__.'/lib.php');
    foreach ($courses as $courseid) {
        $context = context_course::instance($courseid);
        $wallet = new enrol_wallet_plugin;
        if (!has_capability('enrol/wallet:manage', $context)) {
            continue;
        }
        $enrolinstances = enrol_get_instances($courseid, true);
        $count = 0;
        foreach ($enrolinstances as $instance) {
            if ($instance->enrol != 'wallet') {
                continue;
            }
            $data->timemodified = time();
            $count++;
            $i++;
            $wallet->update_instance($instance, $data);
        }
        if ($count < 1) {
            $data->timecreated = time();
            $course = get_course($courseid);
            $wallet->add_instance($course, (array)$data);
            $y++;
        }
    }
    $url = new moodle_url('/enrol/wallet/extra/bulkinstances.php');
    if ($i == 0 && $y == 0) {
        $msg = get_string('bulk_instancesno', 'enrol_wallet');
        redirect($url, $msg, null, 'warning');
    } else {
        $a = [
            'updated' => $i,
            'created' => $y,
        ];
        $msg = get_string('bulk_instancesyes', 'enrol_wallet', $a);
        redirect($url, $msg, null, 'info');
    }
}


