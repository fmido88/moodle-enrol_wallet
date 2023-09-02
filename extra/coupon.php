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
 * Page for generating wallet coupons in moodle.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_once($CFG->libdir.'/formslib.php');
// Adding some security.
require_login();

$systemcontext = context_system::instance();
require_capability('enrol/wallet:createcoupon', $systemcontext);

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/enrol/wallet/extra/coupon.php'));
$PAGE->set_title(get_string('coupon_generation_title', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupon_generation_heading', 'enrol_wallet'));

$mform = new enrol_wallet\form\coupons_generator('generator.php');

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
