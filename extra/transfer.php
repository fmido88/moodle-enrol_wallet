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
 * The page to transfer wallet ballance to other user.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

$context = context_system::instance();

require_login();
require_capability('enrol/wallet:transfer', $context);

// Check if transfer isn't enabled in this website.
$transferenabled = get_config('enrol_wallet', 'transfer_enabled');
if (empty($transferenabled)) {
    redirect(new moodle_url('/'), get_string('transfer_notenabled', 'enrol_wallet'), null, 'error');
}
$url = new moodle_url('/enrol/wallet/extra/transfer.php');
$title = get_string('transferpage', 'enrol_wallet');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($url);

$mform = new \enrol_wallet\form\transfer_form();

if ($data = $mform->get_data()) {

    $catid  = $data->category;
    $op = new enrol_wallet\util\balance_op(0, $catid);

    $msg = $op->transfer_to_other($data, $mform);
    if (stristr($msg, 'error')) {
        $type = 'error';
    } else {
        $type = 'success';
    }
    // All done.
    redirect($url, $msg, null, $type);

} else {

    echo $OUTPUT->header();

    $mform->display();

    echo $OUTPUT->footer();
}

