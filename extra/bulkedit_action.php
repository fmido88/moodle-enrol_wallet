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
 * Action for bulk edit user enrollments.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_login();

$courses = required_param_array('courses', PARAM_INT);
$timeend = optional_param_array('timeend', [], PARAM_INT);
if (!empty($timeend)) {
    $end = mktime(
        $timeend['hour'],
        $timeend['minute'],
        0,
        $timeend['month'],
        $timeend['day'],
        $timeend['year'],
    );
} else {
    $end = [];
}

$timestart = optional_param_array('timestart', [], PARAM_INT);
if (!empty($timestart)) {
    $start = mktime(
        $timestart['hour'],
        $timestart['minute'],
        0,
        $timestart['month'],
        $timestart['day'],
        $timestart['year'],
    );
} else {
    $start = [];
}

$status = optional_param('status', '-1', PARAM_INT);
$plugins = optional_param_array('plugins', [], PARAM_TEXT);
$i = 0;
global $DB, $USER;
// Check the sesskey before action.
if (confirm_sesskey()) {
    foreach ($courses as $courseid) {
        $context = context_course::instance($courseid);
        if (!has_capability('enrol/wallet:manage', $context)) {
            continue;
        }
        $enrolusers = enrol_get_course_users($courseid);

        foreach ($enrolusers as $euser) {
            $enrol = $DB->get_record('enrol', ['id' => $euser->ueenrolid], 'enrol');
            if (!in_array($enrol->enrol , $plugins)) {
                continue;
            }

            $data = new stdClass;
            $data->id = $euser->ueid;
            if ($status !== -1) {
                $data->status = $status;
            }
            if (!empty($start) && $euser->uetimestart > $start) {
                $data->timestart = $start;
            }
            if (!empty($end) && $euser->uetimeend < $end && $euser->uetimeend !== 0) {
                $data->timeend = $end;
            }

            $data->modifierid = $USER->id;
            $data->timemodified = time();

            $result = $DB->update_record('user_enrolments', $data);
            $i++;
        }
    }
    if ($i == 0) {
        redirect(new moodle_url('/'), $i.' enrollment has been updated', null, 'warning');
    } else {
        redirect(new moodle_url('/'), $i.' enrollments has been updated', null, 'info');
    }
}
