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
$frontpagectx = context_course::instance(SITEID);
require_capability('enrol/wallet:manage', $frontpagectx);

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

$status = optional_param('status', -1, PARAM_INT);
$plugins = optional_param_array('plugins', [], PARAM_TEXT);
foreach ($plugins as $plugin) {
    $$plugin = enrol_get_plugin($plugin);
}

$i = 0;
global $DB, $USER;
// Check the sesskey before action.
if (confirm_sesskey()) {
    foreach ($courses as $courseid) {
        $context = context_course::instance($courseid);

        $enrolusers = enrol_get_course_users($courseid);

        foreach ($enrolusers as $euser) {
            $instance = $DB->get_record('enrol', ['id' => $euser->ueenrolid]);

            if (!in_array($instance->enrol , $plugins)) {
                continue;
            }

            if (!has_capability("enrol/$instance->enrol:manage", $context)) {
                continue;
            }

            if ($euser->uestatus == $status
                && $euser->uetimestart == $start
                && $euser->uetimeend == $end) {
                // No change.
                continue;
            }

            $data = new stdClass;
            if ($status !== -1) {
                $data->status = $status;
            } else {
                $data->status = $euser->uestatus;
            }

            if (!empty($start) && $euser->uetimestart > $start) {
                $data->timestart = $start;
            } else {
                $data->timestart = $euser->uetimestart;
            }

            if (!empty($end) && $euser->uetimeend < $end && $euser->uetimeend !== 0) {
                $data->timeend = $end;
            } else {
                $data->timeend = $euser->uetimeend;
            }

            $plugin = $instance->enrol;
            $$plugin->update_user_enrol($instance, $euser->id, $data->status, $data->timestart, $data->timeend);

            $i++;
        }
    }

    $url = new moodle_url('/enrol/wallet/extra/bulkedit.php');
    $msg = get_string('enrollmentupdated', 'enrol_wallet');
    if ($i == 0) {
        redirect($url, $i . $msg, null, 'warning');
    } else {
        redirect($url, $i . ' '. $msg, null, 'info');
    }
}
