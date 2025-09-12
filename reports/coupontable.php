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

require_once('../../../config.php');

use core_reportbuilder\local\filters\date;
use enrol_wallet\local\urls\actions;
use enrol_wallet\local\urls\reports;

$url           = reports::COUPONS->url();
$systemcontext = context_system::instance();

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);
$PAGE->set_title(get_string('coupons', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupons', 'enrol_wallet'));

require_login();
require_capability('enrol/wallet:viewcoupon', $systemcontext);

$class  = enrol_wallet\reportbuilder\local\systemreports\coupons::class;
$report = core_reportbuilder\system_report_factory::create($class, $systemcontext);

$createdfrom = optional_param('createdfrom', 0, PARAM_INT);
$createdto   = optional_param('createdto', 0, PARAM_INT);

if (!empty($createdfrom) || !empty($createdto)) {
    $report->set_filter_values([
        'coupon:timecreated_operator' => date::DATE_RANGE,
        'coupon:timecreated_from'     => $createdfrom,
        'coupon:timecreated_to'       => $createdto,
    ]);
}

echo $OUTPUT->header();

echo html_writer::start_div('', ['data-region' => 'coupons-table-report-wrapper']);
echo $report->output();

if (has_capability('enrol/wallet:deletecoupon', $systemcontext)) {
    $PAGE->requires->js_call_amd('enrol_wallet/coupons_report', 'init');

    $deleteform = new MoodleQuickForm(
        'coupondelete',
        'post',
        actions::DELETE_COUPON->url(),
        '',
        ['id' => 'enrolwallet_coupondelete']
    );
    $deletebutton = $deleteform->createElement('button', 'delete', get_string('delete'));
    $deleteform->addGroup([$deletebutton], null, get_string('coupons_delete_selected', 'enrol_wallet'));

    $deleteform->addElement('hidden', 'ids');
    $deleteform->setType('ids', PARAM_TEXT);

    $deleteform->addElement('hidden', 'sesskey');
    $deleteform->setDefault('sesskey', sesskey());
    $deleteform->setType('sesskey', PARAM_TEXT);

    $deleteform->display();
}

echo html_writer::end_div();
echo $OUTPUT->footer();
