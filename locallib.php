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
 * wallet enrol plugin implementation.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/enrol/locallib.php');

/**
 * Check if the given password match a group enrolment key in the specified course.
 *
 * @param  int $courseid            course id
 * @param  string $enrolpassword    enrolment password
 * @return bool                     True if match
 * @since  Moodle 3.0
 */
function enrol_wallet_check_group_enrolment_key($courseid, $enrolpassword) {
    global $DB;

    $found = false;
    $groups = $DB->get_records('groups', array('courseid' => $courseid), 'id ASC', 'id, enrolmentkey');

    foreach ($groups as $group) {
        if (empty($group->enrolmentkey)) {
            continue;
        }
        if ($group->enrolmentkey === $enrolpassword) {
            $found = true;
            break;
        }
    }
    return $found;
}

/**
 * Enable enrol Wallet plugin.
 * @return void
 */
function enrol_wallet_enable_plugin() {
    global $CFG;
    if (!enrol_is_enabled('wallet')) {
        $class = \core_plugin_manager::resolve_plugininfo_class('enrol');
        // This method isn't exist in 3.11.
        if (method_exists($class, 'enable_plugin')) {
            $class::enable_plugin('wallet', true);
        } else {
            $plugins = [];
            if (!empty($CFG->enrol_plugins_enabled)) {
                $plugins = array_flip(explode(',', $CFG->enrol_plugins_enabled));
            }
            if (!array_key_exists('wallet', $plugins)) {
                $plugins['wallet'] = 'wallet';
                $new = implode(',', array_flip($plugins));
                add_to_config_log('enrol_plugins_enabled', false, true, 'wallet');
                set_config('enrol_plugins_enabled', $new);
                // Reset caches.
                \core_plugin_manager::reset_caches();
                // Resets all enrol caches.
                $syscontext = \context_system::instance();
                $syscontext->mark_dirty();
            }
        }
    }
}

/**
 * Summary of enrol_wallet_get_random_coupon
 * @param int $length
 * @param array $options
 * @return string
 */
function enrol_wallet_get_random_coupon($length, $options) {
    $randomcoupon = '';
    $upper = $options['upper'];
    $lower = $options['lower'];
    $digits = $options['digits'];
    $charset = '';
    if ($upper) {
        $charset .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    if ($lower) {
        $charset .= 'abcdefghijklmnopqrstuvwxyz';
    }
    if ($digits) {
        $charset .= '0123456789';
    }

    $count = strlen( $charset );

    while ($length--) {
        $randomcoupon .= $charset[mt_rand(0, $count - 1)];
    }

    return $randomcoupon;
}

/**
 * Generating coupons.
 *
 * @param object $options the options from coupon form.
 * @return array|string array of coupon, or string of error.
 */
function enrol_wallet_generate_coupons($options) {
    global $DB;

    $number = $options->number;
    $maxusage = $options->maxusage;
    $from = $options->from;
    $to = $options->to;
    $type = $options->type;
    $value = $options->value;
    $code = $options->code;

    $recorddata = (object)[
        'type' => $type,
        'value' => $value,
        'maxusage' => $maxusage,
        'validfrom' => $from,
        'validto' => $to,
        'timecreated' => time(),
    ];

    if (!$number) {
        return get_string('coupon_generator_nonumber', 'enrol_wallet');
    }
    // Percentage discount coupons cannot be more than 100%.
    if ($type == 'percent' && $value > 100) {
        return get_string('invalidpercentcoupon', 'enrol_wallet');
    }
    $ids = [];
    if (!empty($code)) {
        $recorddata->code = $code;
        if ($DB->record_exists('enrol_wallet_coupons', ['code' => $code])) {
            return get_string('couponexist', 'enrol_wallet');
        }
        $ids[] = $DB->insert_record('enrol_wallet_coupons', $recorddata);
    } else {

        $length = $options->length;
        $lower = $options->lower;
        $upper = $options->upper;
        $digits = $options->digits;

        for ($i = 0; $i < $number; $i++) {
            $gopt = [
                'lower' => $lower,
                'upper' => $upper,
                'digits' => $digits,
            ];
            $recorddata->code = enrol_wallet_get_random_coupon($length, $gopt);
            if (!$recorddata->code) {
                return get_string('coupon_generator_error', 'enrol_wallet');
            }
            if ($DB->record_exists('enrol_wallet_coupons', ['code' => $recorddata->code])) {
                continue;
            }
            $ids[] = $DB->insert_record('enrol_wallet_coupons', $recorddata);
        }
    }
    return $ids;
}

/**
 * Display the form for charging other users.
 * @return bool|string
 */
function enrol_wallet_display_charger_form() {
    global $CFG;
    require_once($CFG->libdir.'/formslib.php');
    if (!has_capability('enrol/wallet:creditdebit', context_system::instance())) {
        return '';
    }

    $mform = new \MoodleQuickForm('credit2', 'POST', $CFG->wwwroot.'/enrol/wallet/extra/charger.php');
    $mform->addElement('header', 'main', get_string('chargingoptions', 'enrol_wallet'));

    $mform->addElement('select', 'op', 'operation', ['credit' => 'credit', 'debit' => 'debit', 'balance' => 'balance']);
    $context = context_system::instance();
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

    $mform->addElement('hidden', 'sesskey');
    $mform->setType('sesskey', PARAM_TEXT);
    $mform->setDefault('sesskey', sesskey());

    ob_start();
    $mform->display();
    $output = ob_get_clean();

    return $output;
}

/**
 * Displaying the results after charging the wallet of other user.
 * @return bool|string
 */
function enrol_wallet_display_transaction_results() {
    global $OUTPUT;
    if (!has_capability('enrol/wallet:viewotherbalance', context_system::instance())) {
        return '';
    }
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
    return ob_get_clean();
}

/**
 * Return html string contains information about current user wallet balance.
 * @return bool|string
 */
function enrol_wallet_display_current_user_balance() {
    global $USER, $OUTPUT;
    // Get the user balance.
    $balance = \enrol_wallet\transactions::get_user_balance($USER->id);
    $norefund = \enrol_wallet\transactions::get_nonrefund_balance($USER->id);
    // Get the default currency.
    $currency = get_config('enrol_wallet', 'currency');
    $policy = get_config('enrol_wallet', 'refundpolicy');
    // Prepare transaction URL to display.
    $transactionsurl = new moodle_url('/enrol/wallet/extra/transaction.php');
    $transactions = html_writer::link($transactionsurl, get_string('transactions', 'enrol_wallet'));
    $tempctx = new stdClass;
    $tempctx->balance = number_format($balance, 2);
    $tempctx->currency = $currency;
    $tempctx->norefund = number_format($norefund, 2);
    $tempctx->transactions = $transactions;
    $tempctx->policy = !empty($policy) ? $policy : false;
    // Display the current user's balance in the wallet.
    $render = $OUTPUT->render_from_template('enrol_wallet/display', $tempctx);
    return $render;
}
