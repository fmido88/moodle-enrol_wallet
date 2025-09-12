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

use enrol_wallet\local\config;
use enrol_wallet\local\urls\pages;
require_once('../../../config.php');

$isparent = false;
if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
    require_once("$CFG->dirroot/auth/parent/auth.php");
    $authparent = new auth_plugin_parent;
    $isparent = $authparent->is_parent($USER);
}

if ($isparent) {
    redirect(new moodle_url('/'), get_string('referral_noparents', 'enrol_wallet'));
}

if (empty(config::make()->referral_enabled)) {
    redirect(new moodle_url('/'), get_string('referral_not_enabled', 'enrol_wallet'));
}

// Adding some security.
require_login(null, false);

pages::REFERRAL->set_page_url_to_me();

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$PAGE->set_title(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_heading(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_pagelayout('frontpage');

echo $OUTPUT->header();

enrol_wallet\output\pages::process_referral_page();

echo $OUTPUT->footer();
