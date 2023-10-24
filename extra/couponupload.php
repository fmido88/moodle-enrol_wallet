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
 * Upload coupons.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

require_login();
$context = context_system::instance();
require_capability('enrol/wallet:createcoupon', $context);
require_capability('enrol/wallet:editcoupon', $context);

$url = new moodle_url('/enrol/wallet/extra/couponupload.php');
$heading = get_string('upload_coupons', 'enrol_wallet');
$PAGE->set_context($context);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);
$PAGE->set_url($url);

// Set up the form.
$form = new enrol_wallet\form\coupons_upload();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/'));
}

echo $OUTPUT->header();

// Display or process the form.
if ($data = $form->get_data()) {
    // Process the CSV file.
    $importid = csv_import_reader::get_new_iid('enrol_wallet');
    $cir = new csv_import_reader($importid, 'enrol_wallet');
    $content = $form->get_file_content('csvfile');
    $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);
    unset($content);
    if ($readcount === false) {
        throw new \moodle_exception(get_string('csvfileerror', 'tool_uploadcourse', $url . " " . $cir->get_error()));
    } else if ($readcount == 0) {
        throw new \moodle_exception(get_string('csvemptyfile', 'error', $url . " " . $cir->get_error()));
    }

    // We've got a live file with some entries, so process it.
    $processor = new enrol_wallet\uploadcoupon\processor($cir, $data->allowed ?? 'all');
    echo $OUTPUT->heading(get_string('upload_result', 'enrol_wallet'));
    $processor->execute(new enrol_wallet\uploadcoupon\tracker(enrol_wallet\uploadcoupon\tracker::OUTPUT_HTML));

    echo $OUTPUT->continue_button($url);
} else {
    // Display the form.
    echo $OUTPUT->heading($heading);

    $form->display();

}

// Footer.
echo $OUTPUT->footer();
