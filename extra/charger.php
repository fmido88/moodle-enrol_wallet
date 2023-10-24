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
 * The page to charge wallet for other users.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_once($CFG->dirroot.'/enrol/wallet/classes/form/charger_form.php');
$context = context_system::instance();

require_login();
require_capability('enrol/wallet:creditdebit', $context);
$result = optional_param('result', '', PARAM_RAW);
$return = optional_param('return', '', PARAM_LOCALURL);

global $OUTPUT;
$pageurl = new moodle_url("$CFG->wwwroot/enrol/wallet/extra/charger.php", ['result' => $result]);
if (empty($return)) {
    $returnurl = $pageurl;
} else {
    $returnurl = new moodle_url($return);
}

$PAGE->set_context($context);
$PAGE->set_url($pageurl);

$mform = new enrol_wallet\form\charger_form();

$msg = '';
$type = 'info';
$data = [
    'op' => optional_param('op', '', PARAM_TEXT),
    'value' => optional_param('value', '', PARAM_FLOAT),
    'userlist' => optional_param('userlist', '', PARAM_INT),
];
if (optional_param('submit', false, PARAM_BOOL)) {
    $errors = $mform->validation($data, []);
    if (!empty($errors)) {
        redirect($returnurl->out(false) . "?" . http_build_query(['errors' => $errors]));
    }
    $result = enrol_wallet_handle_charger_form((object)$data);
    if ($result) {
        $msg = $result;
        $type = 'success';
    } else {
        $type = 'error';
    }
    $returnurl = new moodle_url($return, ['result' => $result]);
    redirect($return, $msg, null, $type);
    exit;
}

echo $OUTPUT->header();
// Display the results.
if (!empty($result)) {
    echo $OUTPUT->box($result);
}

// Display the charger form.
$form = enrol_wallet_display_charger_form();
echo $OUTPUT->box($form);

echo $OUTPUT->footer();

