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
 * Delete coupons.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

use enrol_wallet\local\urls\actions;
use enrol_wallet\local\urls\reports;

$confirm = optional_param('confirm', 0, PARAM_BOOL); // Delete confirmation.
$ids     = required_param('ids', PARAM_TEXT);

$contextsys = context_system::instance();

require_login();
require_capability('enrol/wallet:deletecoupon', $contextsys);
require_sesskey();

$PAGE->set_context($contextsys);
$PAGE->set_pagelayout('standard');
$PAGE->set_url(actions::DELETE_COUPON->url());
$PAGE->set_title(new lang_string('confirm'));

$returnurl = reports::COUPONS->url();

if ($confirm) {
    $ids = explode(',', $ids);
    $n = 0;
    foreach ($ids as $id) {
        $delete = $DB->delete_records('enrol_wallet_coupons', ['id' => $id]);
        $n += (int)$delete;
    }

    redirect($returnurl, get_string('couponsdeleted', 'enrol_wallet', $n));

}

echo $OUTPUT->header();

$optionsyes = ['sesskey' => sesskey(), 'confirm' => 1, 'ids' => $ids];

$buttoncontinue = new single_button(actions::DELETE_COUPON->url($optionsyes), get_string('yes'), 'post');

$buttoncancel = new single_button($returnurl, get_string('no'));

$message = get_string('confirmdeletecoupon', 'enrol_wallet', $ids);

echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);

echo $OUTPUT->footer();
