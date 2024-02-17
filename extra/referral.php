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

global $DB, $USER;

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

if (!(bool)get_config('referral_enabled', 'enrol_wallet')) {
    redirect(new moodle_url('/'));
}

// Adding some security.
require_login();
$thisurl = new moodle_url('/enrol/wallet/extra/referral.php');

$PAGE->set_url($thisurl);

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$PAGE->set_title(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_heading(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_pagelayout('frontpage');

echo $OUTPUT->header();

enrol_wallet\pages::process_referral_page();

echo $OUTPUT->footer();
