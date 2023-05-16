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
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_login();

$select = optional_param_array('select', [], PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$ids = optional_param('ids', '', PARAM_TEXT);
echo '<pre>';
var_dump($select, $_POST);
echo '</pre>';
require_login();
require_capability('enrol/wallet:deletecoupon', context_system::instance());
// TODO adding options to edit coupons.
if (confirm_sesskey()) {
    if ($confirm) {
        $ids = explode(',', $ids);
        $n = 0;
        foreach ($ids as $id) {
            $delete = $DB->delete_records('enrol_wallet_coupons', ['id' => $id]);
            $n += (int)$delete;
        }

        $url = new moodle_url('/enrol/wallet/extra/coupontable.php');
        redirect($url, get_string('couponsdeleted', 'enrol_wallet', $n));

    } else {

        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('standard');
        $PAGE->set_url(new moodle_url('/enrol/wallet/extra/couponedit.php'));
        $PAGE->set_title(new lang_string('confirm'));

        echo $OUTPUT->header();

        $ids = implode(',', array_keys($select));
        $optionsyes = ['sesskey' => sesskey(), 'confirm' => 1, 'ids' => $ids];

        $url = new moodle_url('/enrol/wallet/extra/couponedit.php', $optionsyes);
        $buttoncontinue = new single_button($url, get_string('yes'), 'post');

        $url = new moodle_url('/enrol/wallet/extra/coupontable.php');
        $buttoncancel = new single_button($url, get_string('no'));

        $message = get_string('confirmdeletecoupon', 'enrol_wallet', $ids);

        echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
        echo $OUTPUT->footer();
    }
}
