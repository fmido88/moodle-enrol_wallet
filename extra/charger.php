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

// Any error displaying casing page to not redirect and charging operation may be processed twice.
set_debugging(DEBUG_NONE, false);

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

// Only to validate the results.
$mform = new enrol_wallet\form\charger_form();
$confirm = optional_param('confirm', false, PARAM_BOOL);
$submit = optional_param('submit', false, PARAM_BOOL);

// Don't use get_data() because the submission may be from another page.
if ($submit) {
    $data = [
        'op'       => required_param('op', PARAM_TEXT),
        'value'    => optional_param('value', '', PARAM_FLOAT),
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
    }

    if ($data['op'] === 'balance' || ($confirm && confirm_sesskey())) {
        $result = enrol_wallet_handle_charger_form((object)$data);
        redirect($returnurl);
    } else {
        $confirmurl = new moodle_url($pageurl, $data);
        $confirmurl->param('confirm', true);
        $confirmurl->param('return', $returnurl->out_as_local_url());
        $confirmbutton = new single_button($confirmurl, get_string('confirm'), 'post');
        $cancelbutton = new single_button($returnurl, get_string('cancel'), 'post');
        $userbalance = enrol_wallet\transactions::get_user_balance($data['userlist']);
        $user = core_user::get_user($data['userlist']);
        $name = html_writer::link(new moodle_url('/user/view.php', ['id' => $user->id]), fullname($user), ['target' => '_blank']);
        $a = [
            'name' => $name,
            'amount' => $data['value'],
            'balance' => $userbalance,
        ];
        $negativewarn = false;
        switch ($data['op']) {
            case 'debit':
                $a['after'] = ($userbalance - $data['value']);
                if ($a['after'] < 0) {
                    $negativewarn = true;
                }
                $msg = get_string('confirm_debit', 'enrol_wallet', $a);
                break;
            case 'credit':
                $msg = get_string('confirm_credit', 'enrol_wallet', $a);
                break;
            default:
                $msg = '';
        }


        echo $OUTPUT->header();
        if ($negativewarn) {
            $warning = get_string('confirm_negative', 'enrol_wallet');
            $msg .= $OUTPUT->notification($warning, 'error', false);
        }
        echo $OUTPUT->confirm($msg, $confirmbutton, $cancelbutton);
        echo $OUTPUT->footer();
    }

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
