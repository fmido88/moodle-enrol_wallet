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
 * wallet enrolment plugin - support for user self unenrolment.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use enrol_wallet\local\urls\actions;

$enrolid = required_param('enrolid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'wallet'], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login();
if (!is_enrolled($context)) {
    redirect(new moodle_url('/'));
}
require_login($course);

$plugin = enrol_get_plugin('wallet');

// Security defined inside following function.
if (empty($plugin->get_unenrolself_link($instance))) {
    $msg = get_string('unenrolself_notallowed', 'enrol_wallet');
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]), $msg);
}

actions::UNENROL_SELF->set_page_url_to_me(['enrolid' => $instance->id]);
$PAGE->set_title($plugin->get_instance_name($instance));

if ($confirm && confirm_sesskey()) {
    $plugin->unenrol_user($instance, $USER->id);

    redirect(new moodle_url('/index.php'));
}

echo $OUTPUT->header();

$yesurl = new moodle_url($PAGE->url, ['confirm' => 1, 'sesskey' => sesskey()]);
$nourl = new moodle_url('/course/view.php', ['id' => $course->id]);
$message = get_string('unenrolselfconfirm', 'enrol_wallet', format_string($course->fullname));

echo $OUTPUT->confirm($message, $yesurl, $nourl);

echo $OUTPUT->footer();
