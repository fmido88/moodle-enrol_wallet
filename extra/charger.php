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
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
global $USER, $DB, $CFG;

require_once($CFG->dirroot.'/enrol/wallet/lib.php');

$context = context_system::instance();

require_login();
require_capability('enrol/wallet:creditdebit', $context);

$op = optional_param('op', 'none', PARAM_TEXT);

if ($op != 'none' && $op != 'result' && confirm_sesskey()) {

    $value = optional_param('value', '', PARAM_NUMBER);
    $userid = required_param("userlist", PARAM_INT);
    $err = '';

    $charger = $USER->id;
    if ((empty($value) || !isset($value)) && ($op !== 'balance')) {
        $err = 'No value';
        $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('error' => $err,
                                                                                   'op' => 'result'));
        redirect($redirecturl, $err);
        exit;
    }

    if (empty($userid)) {
        $err = 'No user selected';
        $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('error' => $err,
                                                                                   'op' => 'result'));
        redirect($redirecturl, $err);
        exit;
    }

    $before = enrol_wallet_plugin::get_user_balance($userid);
    if ($op === 'credit') {
        $desc = 'charging from wallet block by '.fullname($USER);
        // Process the transaction.
        $result = enrol_wallet_plugin::payment_topup($value, $userid, $desc, $charger);
        $after = enrol_wallet_plugin::get_user_balance($userid);
    } else if ($op === 'debit') {
        if ($value > $before) {
            $err = 'The value ('.$value.') is greater that the user\'s balance ('.$before.')';
            $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('error' => $err,
                                                                                       'op' => 'result'));
            redirect($redirecturl);
            exit;
        } else {
            // Process the payment.
            $result = enrol_wallet_plugin::debit($userid, $value, '(deduct from wallet block by '.fullname($USER).')', $charger);
            $after = enrol_wallet_plugin::get_user_balance($userid);
        }

    } else if ($op = 'balance') {
        $result = $before;
    } else {
        $result = 'invalid operation';
    }

    // Redirect to same page to show results.
    $redirecturl = new moodle_url('/enrol/wallet/extra/charger.php', array('result' => $result,
    'before' => $before,
    'after' => ($op == 'balance') ? $before : $after,
    'userid' => $userid,
    'op' => 'result'));

    redirect($redirecturl);

} else {

    $PAGE->set_context($context);
    $PAGE->set_url($CFG->wwwroot.'/enrol/wallet/extra/charger.php');

    echo $OUTPUT->header();

    if ($op == 'result') {
        $result = optional_param('result', '', PARAM_ALPHANUM);
        $before = optional_param('before', '', PARAM_NUMBER);
        $after = optional_param('after', '', PARAM_NUMBER);
        $userid = optional_param('userid', '', PARAM_INT);
        $err = optional_param('error', '', PARAM_TEXT);

        if ($err !== '') {
            $info = '<span style="text-align: center; width: 100%;"><h5>'
            .$err.
            '</h5></span>';
            $errormsg = '<p style = "text-align: center;"><b> ERROR <br>'
                        .$err.
                        '<br> Please go back and check it again</b></p>';
            echo $OUTPUT->notification($errormsg);

        } else {

            $user = \core_user::get_user($userid);
            $userfull = $user->firstname.' '.$user->lastname.' ('.$user->email.')';
            // Display the result to the user.
            echo $OUTPUT->notification('<p>Balance Before: <b>' .$before.'</b></p>', 'notifysuccess').'<br>';

            if (!empty($result) && is_numeric($result)  && false != $result) {
                $result = 'success';
            }

            if ($after !== $before) {

                echo $OUTPUT->notification('succession: ' .$result.' .', 'notifysuccess').'<br>';
                $info = '<span style="text-align: center; width: 100%;"><h5>
                    the user: '.$userfull.' is now having a balance of '.$after.' after charging him/her by '.( $after - $before).
                    '</h5></span>';
                if ($after !== '') {
                    echo $OUTPUT->notification('<p>Balance After: <b>' .$after.'</b></p>', 'notifysuccess');
                }
                if ($after < 0) {
                    echo $OUTPUT->notification('<p><b>THIS USER HAS A NEGATIVE BALANCE</b></p>');
                }

            } else {

                $info = '<span style="text-align: center; width: 100%;"><h5>
                the user: '.$userfull.' is having a balance of '.$before.
                '</h5></span>';

            }
        }
        // Display the results.
        ob_start();
        echo $info;
        $output = ob_get_clean();
        echo $OUTPUT->box($output);
    }

    require_once($CFG->libdir.'/formslib.php');

    $mform = new \MoodleQuickForm('credit2', 'POST', $CFG->wwwroot.'/enrol/wallet/extra/charger.php');
    $mform->addElement('header', 'main', get_string('chargingoptions', 'enrol_wallet'));

    $mform->addElement('select', 'op', 'operation', ['credit' => 'credit', 'debit' => 'debit', 'balance' => 'balance']);

    $options = array(
        'ajax' => 'enrol_manual/form-potential-user-selector',
        'multiple' => false,
        'courseid' => SITEID,
        'enrolid' => 0,
        'perpage' => $CFG->maxusersperpage,
        'userfields' => implode(',', \core_user\fields::get_identity_fields($context, true))
    );
    $mform->addElement('autocomplete', 'userlist', get_string('selectusers', 'enrol_manual'), array(), $options);
    $mform->addRule('userlist', 'select user', 'required');

    $mform->addElement('text', 'value', 'Value');
    $mform->setType('value', PARAM_INT);
    $mform->hideIf('value', 'op', 'eq', 'balance');

    $mform->addElement('submit', 'submit', 'submit');

    ob_start();
    $mform->display();
    $output = ob_get_clean();
    echo $OUTPUT->box($output);

    echo $OUTPUT->footer();
}
