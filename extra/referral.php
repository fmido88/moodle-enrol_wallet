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
 * wallet enrolment plugin referral page.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');

$isparent = false;
if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
    require_once("$CFG->dirroot/auth/parent/auth.php");
    require_once("$CFG->dirroot/auth/parent/lib.php");
    $authparent = new auth_plugin_parent;
    $isparent = $authparent->is_parent($USER);
}

if ($isparent) {
    redirect(new moodle_url('/'), 'Parents not allow to access referral program.');
}

global $DB, $USER;
// Adding some security.
require_login();
$thisurl = new moodle_url('/enrol/wallet/extra/referral.php');

$amount = get_config('enrol_wallet', 'referral_amount');
$maxref = get_config('enrol_wallet', 'referral_max');

$exist = $DB->get_record('enrol_wallet_referral', ['userid' => $USER->id]);
if (!$exist) {
    $data = (object)[
        'userid' => $USER->id,
        'code' => random_string(15) . $USER->id,
    ];
    $DB->insert_record('enrol_wallet_referral', $data);
    $exist = $DB->get_record('enrol_wallet_referral', ['userid' => $USER->id]);
}

$PAGE->set_url($thisurl);

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$PAGE->set_title(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_heading(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_pagelayout('frontpage');

$refusers = $DB->get_records('enrol_wallet_hold_gift', ['referrer' => $USER->id]);
if (!empty($refusers)) {
    $table = new html_table;
    $headers = [
        get_string('user'),
        get_string('status'),
        get_string('referral_amount', 'enrol_wallet'),
        get_string('referral_timecreated', 'enrol_wallet'),
        get_string('referral_timereleased' , 'enrol_wallet')
    ];
    $table->data[] = $headers;
    foreach ($refusers as $data) {
        $referred = \core_user::get_user_by_username($data->referred);
        $status = empty($data->released) ? get_string('referral_hold', 'enrol_wallet')
                                         : get_string('referral_done', 'enrol_wallet');
        $table->data[] = [
            $referred->firstname . ' ' . $referred->lastname,
            $status,
            format_float($data->amount, 2),
            userdate($data->timecreated),
            !empty($data->timemodified) ? userdate($data->timemodified) : ''
        ];
    }
    $output = html_writer::table($table);
} else {
    $message = get_string('noreferraldata', 'enrol_wallet');
    $output = $OUTPUT->notification($message);
}

$mform = new MoodleQuickForm('referral_info', 'get', $thisurl);

$signup = new moodle_url('/login/signup.php', ['refcode' => $exist->code]);
$mform->addElement('static', 'refurl', get_string('referral_url', 'enrol_wallet'), $signup->out(false));
$mform->addHelpButton('refurl',  'referral_url',  'enrol_wallet');

$mform->addElement('static', 'refcode', get_string('referral_code', 'enrol_wallet'), $exist->code);
$mform->addHelpButton('refcode',  'referral_code',  'enrol_wallet');

$mform->addElement('text', 'refamount', get_string('referral_amount', 'enrol_wallet'));
$mform->addHelpButton('refamount', 'referral_amount', 'enrol_wallet');
$mform->setType('refamount', PARAM_FLOAT);
$mform->setConstant('refamount', $amount);

$mform->addElement('hidden', 'disable');
$mform->setType('disable', PARAM_INT);
$mform->setConstant('disable', 0);

$mform->disabledIf('refamount',  'disable',  'neq',  1);

if (!empty($maxref)) {
    $mform->addElement('text', 'refremain', get_string('referral_remain', 'enrol_wallet'));
    $mform->setType('refremain', PARAM_INT);
    $mform->addHelpButton('refremain', 'referral_remain', 'enrol_wallet');
    $mform->setConstant('refremain', $maxref - $exist->usetimes);
    $mform->disabledIf('refremain',  'disable',  'neq',  1);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('referral_past', 'enrol_wallet'));

echo $output;

echo $OUTPUT->heading(get_string('referral_data', 'enrol_wallet'));

$mform->display();

echo $OUTPUT->footer();
