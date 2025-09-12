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
 * Confirm page before enrolment.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_wallet\local\config;
use enrol_wallet\local\urls\pages;
use enrol_wallet\output\helper;

define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

use enrol_wallet\local\entities\instance;
use enrol_wallet\local\wallet\balance;

global $USER;

require_login(null, false);

$instanceid = required_param('instance', PARAM_INT);
$courseid   = required_param('id', PARAM_INT);
$confirm    = optional_param('confirm', false, PARAM_BOOL);

$params = [
    'instance' => $instanceid,
    'confirm'  => $confirm,
    'id'       => $courseid,
];

$pageurl = pages::CONFIRM_ENROL->url($params);
$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url($pageurl);

if (is_enrolled($context, null, '', true)) {
    redirect($courseurl);
}

$helper = new instance($instanceid);
$wallet = new enrol_wallet_plugin;
$instance = $helper->get_instance();
$cost = $helper->get_cost_after_discount();
$course = get_course($courseid);

$canselfenrol = ($wallet->can_self_enrol($instance, false) === true);

// Some security as in the enrol page.
if (
    empty($course)
    || empty($instance)
    || $courseid == SITEID
    || $instance->courseid != $course->id
    || !$canselfenrol
    ) {
    $msg = get_string('confirm_enrol_error', 'enrol_wallet');
    redirect($courseurl, $msg, null, 'error');
}

if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
    throw new \moodle_exception('coursehidden');
}

// Do not allow enrols when in login-as session.
if (\core\session\manager::is_loggedinas() && $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
    throw new \moodle_exception('loginasnoenrol', '', $CFG->wwwroot.'/course/view.php?id='.$USER->loginascontext->instanceid);
}

// Check if user has access to the category where the course is located.
if (!core_course_category::can_view_course_info($course) && !is_enrolled($context, $USER, '', true)) {
    throw new \moodle_exception('coursehidden', '', $CFG->wwwroot . '/');
}

if ($cost <= 0.01 || ($confirm && confirm_sesskey())) {
    // Notice and warnings may cause double deduct to the balance.
    set_debugging(DEBUG_NONE, false);

    $wallet->enrol_self($instance);

    redirect($courseurl);
}

$PAGE->set_title($course->shortname);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_secondary_navigation(false);
$PAGE->navbar->add(get_string('enrolmentoptions', 'enrol'));

$balance = balance::create_from_instance($instance);
$a = [
    'balance'  => $balance->get_valid_balance(),
    'cost'     => $cost,
    'currency' => $instance->currency,
    'course'   => $course->fullname,
    'policy'   => '',
];

$config = config::make();

// Display refund policy if enabled.
$refund = $config->unenrolrefund;
$policy = $config->unenrolrefundpolicy;
if (!empty($refund) && !empty($policy)) {
    $period = $config->unenrolrefundperiod;
    $period = (!empty($period)) ? $period / DAYSECS : '('.get_string('unlimited').')';

    $fee = $config->unenrolrefundfee;
    $fee = !(empty($fee)) ? $fee : 0;

    $policy = str_replace('{fee}', $fee, $policy);
    $policy = str_replace('{period}', $period, $policy);

    $a['policy'] = $policy;
}

$cancelurl = new moodle_url('/enrol/index.php', ['id' => $course->id]);
$cancelbutton = new single_button($cancelurl, get_string('cancel'));

$params['confirm'] = true;
$pageurl->param('confirm', true);
$confirmbutton = new single_button($pageurl, get_string('confirm'));

$confirmationmsg = get_string('confirm_enrol_confirm', 'enrol_wallet', $a);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($wallet->get_instance_name($instance), true, ['context' => $context]));

$courserenderer = helper::get_course_renderer();

echo $OUTPUT->confirm($confirmationmsg, $confirmbutton, $cancelbutton);

echo $courserenderer->course_info_box($course);

echo $OUTPUT->footer();
