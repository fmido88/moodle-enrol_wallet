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

use enrol_wallet\local\urls\manage;

// Any error displaying casing page to not redirect and charging operation may be processed twice.
set_debugging(DEBUG_NONE, false);

require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
$context = context_system::instance();

require_login();
require_capability('enrol/wallet:creditdebit', $context);
$result = optional_param('result', '', PARAM_RAW);
$return = optional_param('return', '', PARAM_LOCALURL);

global $OUTPUT;
$pageurl = manage::CHARGE->url(['result' => $result]);
if (empty($return)) {
    $returnurl = $pageurl;
} else {
    $returnurl = new moodle_url($return);
}

$PAGE->set_context($context);
$PAGE->set_url($pageurl);

// Only to validate the results.
$mform = new enrol_wallet\form\charger_form();
$confirm = optional_param('confirm', false, PARAM_BOOL);
$submit = optional_param('submit', false, PARAM_BOOL);

// Don't use get_data() because the submission may be from another page.
if ($submit) {
    $data = [
        'op'       => required_param('op', PARAM_TEXT),
        'value'    => optional_param('value', '', PARAM_FLOAT),
        'category' => optional_param('category', 0, PARAM_INT),
        'userlist' => required_param('userlist', PARAM_INT),
        'neg'      => optional_param('neg', false, PARAM_BOOL),
        'submit'   => $submit,
    ];
    $errors = $mform->validation($data, []);

    if (!empty($errors)) {
        $params = (array)$returnurl->params();
        $params['errors'] = $errors;
        $returnurl->remove_all_params();
        $return = $returnurl->out() .'?'. http_build_query($params);
        redirect(new moodle_url($return));
        exit;
    }

    if ($data['op'] === 'balance' || ($confirm && confirm_sesskey())) {
        $mform->process_form_submission((object)$data);
        redirect($returnurl);
        exit;
    } else {

        $confirm = enrol_wallet\output\pages::get_charger_confirm($data, $returnurl, $pageurl);
        echo $OUTPUT->header();

        echo $confirm;

        echo $OUTPUT->footer();
        exit;
    }
}

echo $OUTPUT->header();
// Display the results.
if (!empty($result)) {
    echo $OUTPUT->box($result);
}

echo $OUTPUT->box($mform->render());

echo $OUTPUT->footer();
