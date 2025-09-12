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
 * Display page for coupons in moodle website.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\system_report_factory;
use enrol_wallet\local\urls\reports;
use enrol_wallet\reportbuilder\local\systemreports\coupon_usage;

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');
global $OUTPUT, $PAGE;

// Adding some security.
require_login();

$systemcontext = context_system::instance();
// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
reports::COUPONS_USAGE->set_page_url_to_me();
$PAGE->set_title(get_string('coupons', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupon_usage', 'enrol_wallet'));

$report = system_report_factory::create(coupon_usage::class, $systemcontext);

echo $OUTPUT->header();

echo $report->output();

echo $OUTPUT->footer();
