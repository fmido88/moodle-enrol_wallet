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
 * Creating a random coupon according to the options and length provided.
 * @param int $length length of the coupon
 * @param array $options characters options
 * @return string the random coupon generated.
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
 * @return string
 */
function enrol_wallet_display_charger_form() {
    global $CFG, $DB;
    require_once($CFG->libdir.'/formslib.php');
    if (!has_capability('enrol/wallet:creditdebit', context_system::instance())) {
        return '';
    }

    $mform = new \MoodleQuickForm('credit2', 'POST', $CFG->wwwroot.'/enrol/wallet/extra/charger.php');

    // Check the conditional discount.
    $enabled = get_config('enrol_wallet', 'conditionaldiscount_apply');
    if (!empty($enabled)) {
        $params = [
            'time1' => time(),
            'time2' => time(),
        ];
        $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params);

        $i = 0;
        foreach ($records as $record) {
            $i++;
            // The next two elements only used to pass the values to js code.
            $mform->addElement('hidden', 'discount'.$i, '', ['id' => "discounted-value[$i]"]);
            $mform->setType('discount'.$i, PARAM_FLOAT);
            $mform->setConstant('discount'.$i, $record->percent / 100);

            $mform->addElement('hidden', 'condition'.$i, '', ['id' => "discount-condition[$i]"]);
            $mform->setType('condition'.$i, PARAM_FLOAT);
            $mform->setConstant('condition'.$i, $record->cond);
        }
    }

    $mform->addElement('header', 'main', get_string('chargingoptions', 'enrol_wallet'));

    $operations = [
        'credit'  => 'credit',
        'debit'   => 'debit',
        'balance' => 'balance'
    ];
    $oplabel = get_string('chargingoperation', 'enrol_wallet');
    $attr = !empty($i) ? ['id' => 'charge-operation', 'onchange' => 'calculateCharge()'] : [];
    $mform->addElement('select', 'op', $oplabel, $operations, $attr);

    $valuetitle = get_string('chargingvalue', 'enrol_wallet');
    $attr = !empty($i) ? ['id' => 'charge-value', 'onkeyup' => 'calculateCharge()', 'onchange' => 'calculateCharge()'] : [];
    $mform->addElement('text', 'value', $valuetitle, $attr);
    $mform->setType('value', PARAM_FLOAT);
    $mform->hideIf('value', 'op', 'eq', 'balance');

    if (!empty($enabled)) {
        // Empty div used by js to display the calculated final value.
        $enter = get_string('entervalue', 'enrol_wallet');
        $html = '<div id="calculated-value" style="font-weight: 700;" class="alert alert-warning">'.$enter.'</div>';
        $mform->addElement('html', $html);
    }

    $context = context_system::instance();
    $options = [
        'id'         => 'charger-userlist',
        'ajax'       => 'enrol_manual/form-potential-user-selector',
        'multiple'   => false,
        'courseid'   => SITEID,
        'enrolid'    => 0,
        'perpage'    => $CFG->maxusersperpage,
        'userfields' => implode(',', \core_user\fields::get_identity_fields($context, true))
    ];
    $mform->addElement('autocomplete', 'userlist', get_string('selectusers', 'enrol_manual'), [], $options);
    $mform->addRule('userlist', 'select user', 'required', null, 'client');

    $mform->addElement('submit', 'submit', get_string('submit'));

    $mform->addElement('hidden', 'sesskey');
    $mform->setType('sesskey', PARAM_TEXT);
    $mform->setDefault('sesskey', sesskey());

    $charginglabel = get_string('charging_value', 'enrol_wallet');
    // Add some js code to display the actual value to charge the wallet with.
    $js = <<<JS
            function calculateCharge() {
                var value = parseFloat(document.getElementById("charge-value").value);
                var op = document.getElementById("charge-operation").value;

                var maxDiscount = 0;
                var calculatedValue = value;
                for (var i = 1; i <= '$i'; i++) {
                    var discount = parseFloat(document.getElementById("discounted-value["+ i +"]").value);
                    var condition = parseFloat(document.getElementById("discount-condition["+ i +"]").value);
                    var valueBefore = value + (value * discount / (1 - discount));

                    if (valueBefore >= condition && discount > maxDiscount) {
                        maxDiscount = discount;
                        var calculatedValue = valueBefore;
                    }
                }

                if (op == "credit") {
                    document.getElementById("calculated-value").innerHTML = '$charginglabel' + calculatedValue;
                } else {
                    document.getElementById("calculated-value").innerHTML = "";
                }
            }
            JS;
    if (!empty($i)) {
        $mform->addElement('html', '<script>'.$js.'</script>');
    }

    ob_start();
    $mform->display();
    $output = ob_get_clean();
    return $output;
}

/**
 * Display links to generate and view coupons.
 * @return string
 */
function enrol_wallet_display_coupon_urls() {
    if (get_config('enrol_wallet', 'walletsource') !== enrol_wallet\transactions::SOURCE_MOODLE) {
        return '';
    }
    $context = context_system::instance();
    $canviewcoupons = has_capability('enrol/wallet:viewcoupon', $context);
    $cangeneratecoupon = has_capability('enrol/wallet:createcoupon', $context);
    $render = '';
    // Check if the user can view and generate coupons.
    if ($canviewcoupons) {
        $url = new moodle_url('/enrol/wallet/extra/coupontable.php');
        $render .= html_writer::link($url, get_string('coupon_table', 'enrol_wallet')).'<br>';

        if ($cangeneratecoupon) {
            $url = new moodle_url('/enrol/wallet/extra/coupon.php');
            $render .= html_writer::link($url, get_string('coupon_generation', 'enrol_wallet'));
        }
    }
    return $render;
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
    $before = optional_param('before', '', PARAM_FLOAT);
    $after  = optional_param('after', '', PARAM_FLOAT);
    $userid = optional_param('userid', '', PARAM_INT);
    $err    = optional_param('error', '', PARAM_TEXT);

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
 * @param int $userid the user id, if not defined the id of current user used.
 * @return bool|string
 */
function enrol_wallet_display_current_user_balance($userid = 0) {
    global $USER, $OUTPUT;
    $currentuser = false;
    if (empty($userid) || $userid == $USER->id) {
        $userid = $USER->id;
        $currentuser = true;
    }
    // Get the user balance.
    $balance = \enrol_wallet\transactions::get_user_balance($userid);
    $norefund = \enrol_wallet\transactions::get_nonrefund_balance($userid);
    // Get the default currency.
    $currency = get_config('enrol_wallet', 'currency');
    $policy = get_config('enrol_wallet', 'refundpolicy');
    // Prepare transaction URL to display.
    $transactionsurl = new moodle_url('/enrol/wallet/extra/transaction.php');
    $transactions = html_writer::link($transactionsurl, get_string('transactions', 'enrol_wallet'));
    if ($currentuser) {
        // Transfer link.
        $transferenabled = get_config('enrol_wallet', 'transfer_enabled');
        $transferurl = new moodle_url('/enrol/wallet/extra/transfer.php');
        $transfer = html_writer::link($transferurl, get_string('transfer', 'enrol_wallet'));
        // Referral link.
        $refenabled = get_config('enrol_wallet', 'referral_enabled');
        $referralurl = new moodle_url('/enrol/wallet/extra/referral.php');
        $referral = html_writer::link($referralurl, get_string('referral_program', 'enrol_wallet'));

    }

    $tempctx = new stdClass;
    $tempctx->main         = number_format($balance - $norefund, 2);
    $tempctx->balance      = number_format($balance, 2);
    $tempctx->currency     = $currency;
    $tempctx->norefund     = number_format($norefund, 2);
    $tempctx->transactions = $transactions;
    $tempctx->transfer     = !empty($transferenabled) ? $transfer : false;
    $tempctx->referral     = !empty($refenabled) ? $referral : false;
    $tempctx->policy       = !empty($policy) ? $policy : false;
    // Display the current user's balance in the wallet.
    $render = $OUTPUT->render_from_template('enrol_wallet/display', $tempctx);
    return $render;
}

/**
 * Display top-up form by payments gateway and\or coupons.
 * @return string
 */
function enrol_wallet_display_topup_options() {
    global $CFG, $OUTPUT, $PAGE;

    require_once($CFG->dirroot.'/enrol/wallet/classes/form/topup_form.php');
    require_once(__DIR__.'/lib.php');

    $username = optional_param('s', '', PARAM_RAW);
    if (!empty($username)) {
        $user = get_complete_user_data('username', $username);
    } else {
        global $USER;
        $user = $USER;
    }

    // Get the default currency.
    $currency = get_config('enrol_wallet', 'currency');
    // Get the default payment account.
    $account = get_config('enrol_wallet', 'paymentaccount');
    // Get coupons settings.
    $couponsetting = get_config('enrol_wallet', 'coupons');
    // Set the data we want to send to forms.
    $instance = new \stdClass;
    $data = new \stdClass;

    $instance->id         = 0;
    $instance->courseid   = SITEID;
    $instance->currency   = $currency;
    $instance->customint1 = $account;

    $data->instance = $instance;
    $data->user     = $user;
    $render = '';
    // First check if payments is enabled.
    if (enrol_wallet_is_valid_account($account)) {
        // If the user don't have capability to charge others.
        // Display options to charge with coupons or other payment methods.
        $topupurl = new moodle_url('/enrol/wallet/extra/topup.php');
        $topupform = new \enrol_wallet\form\topup_form($topupurl, $data);
        ob_start();
        $topupform->display();
        $render .= ob_get_clean();
    }

    // Check if fixed coupons enabled.
    if ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSFIXED ||
        $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL) {
            // Display the coupon form to enable user to topup wallet using fixed coupon.
            require_once($CFG->dirroot.'/enrol/wallet/classes/form/applycoupon_form.php');
            $action = new moodle_url('/enrol/wallet/extra/action.php');
            $couponform = new \enrol_wallet\form\applycoupon_form($action, $data);
            ob_start();
            $couponform->display();
            $render .= ob_get_clean();
    }

    // If plugin block_vc exist, add credit options byt it.
    if (file_exists("$CFG->dirroot/blocks/vc/lib.php")
            && get_config('block_vc', 'enablecredit')) {

        require_once("$CFG->dirroot/blocks/vc/lib.php");
        $vcform = new \MoodleQuickForm('vc_credit', 'POST', $CFG->wwwroot.'/blocks/vc/credit.php');
        $vcform->addElement('header', 'vccredit', get_string('pluginname', 'block_vc'));
        $vcform->setExpanded('vccredit', false, true); // Not working.

        block_vc_credit_form($vcform); // Elements.

        ob_start();
        $vcform->display();
        $render .= $OUTPUT->box(ob_get_clean());

        // This code make the container collapsed at the load of the page, where setExpanded not working.
        $jscode = "
            var vcContainer = document.getElementById('id_vccreditcontainer');
            vcContainer.setAttribute('class', 'fcontainer collapseable collapse');
        ";
        $PAGE->requires->js_init_code($jscode, true);
    }

    // Display the manual refund policy.
    $policy = get_config('enrol_wallet', 'refundpolicy');
    if (!empty($policy)) {
        $id = random_int(1000, 9999); // So the JS codes not interfere.
        // Policy Wrapper.
        $warn = html_writer::start_div('alert alert-warning');
        // Intro.
        $warn .= html_writer::span(get_string('agreepolicy_intro', 'enrol_wallet'));

        // Hidden policy until the user click the link.
        $data = new stdClass;
        $data->policy = $policy;
        $data->id     = $id;
        $warn .= $OUTPUT->render_from_template('enrol_wallet/manualpolicy', $data);

        // Agree checkbox, the whole topping up options hidden until the user check the box.
        $attributes = ['id' => 'wallet_topup_policy_confirm_'.$id];
        $warn .= html_writer::checkbox('wallet_topup_policy_confirm_'.$id, '1', false,
                                        get_string('agreepolicy_label', 'enrol_wallet'), $attributes);
        $warn .= html_writer::end_div();

        // All topping up options inside a box to control it using JS.
        $attr = ['style' => 'display: none;'];
        $box = $OUTPUT->box_start('enrol_wallet_topup_options generalbox', 'enrol_wallet_topup_box_'.$id, $attr);
        $render = $warn . $box . $render;
        $render .= $OUTPUT->box_end();

        // JS code to Hide/Show topping up options.
        $jscode = "
            var walletPolicyAgreed = document.getElementById('wallet_topup_policy_confirm_$id');
            walletPolicyAgreed.addEventListener('change', function() {
                var topUpBox = document.getElementById('enrol_wallet_topup_box_$id');
                if (walletPolicyAgreed.checked == true) {
                    topUpBox.style.display = 'block';
                } else {
                    topUpBox.style.display = 'none';
                }
            })
        ";
        $PAGE->requires->js_init_code($jscode);
    }

    if (!empty($render)) {
        return $OUTPUT->box($render);
    } else {
        return '';
    }
}

/**
 * Check the payment account id if it is valid or not.
 *
 * @param int $accountid
 * @return bool
 */
function enrol_wallet_is_valid_account($accountid) {
    if (empty($accountid) || !is_number($accountid) || $accountid < 0) {
        return false;
    }

    $account = new \core_payment\account($accountid);
    if (!$account->is_available() || !$account->is_valid()) {
        return false;
    }

    return true;
}

/**
 * Display the form to let users transfer balance to each other.
 * @return string
 */
function enrol_wallet_get_transfer_form() {
    $transferenabled = get_config('enrol_wallet', 'transfer_enabled');
    if (empty($transferenabled)) {
        return '';
    }

    global $CFG, $USER;
    require_once($CFG->libdir.'/formslib.php');

    $url = new moodle_url('/enrol/wallet/extra/transfer.php');

    $mform = new MoodleQuickForm('wallet_transfer', 'post', $url);

    $balance = enrol_wallet\transactions::get_user_balance($USER->id);
    $currency = get_config('enrol_wallet', 'currency');

    $mform->addElement('header', 'transferformhead', get_string('transfer', 'enrol_wallet'));

    $displaybalance = format_string(format_float($balance, 2) . ' ' . $currency);
    $mform->addElement('static', 'displaybalance', get_string('availablebalance', 'enrol_wallet'), $displaybalance);

    $mform->addElement('text', 'email', get_string('email'));
    $mform->setType('email', PARAM_EMAIL);

    $mform->addElement('text', 'amount', get_string('amount', 'enrol_wallet'));
    $mform->setType('amount', PARAM_FLOAT);

    $percentfee = get_config('enrol_wallet', 'transferpercent');
    if (!empty($percentfee)) {
        $a = ['fee' => $percentfee];
        $feefrom = get_config('enrol_wallet', 'transferfee_from');
        $a['from'] = get_string($feefrom, 'enrol_wallet');

        $mform->addElement('static', 'feedesc',
                            get_string('transferpercent', 'enrol_wallet'),
                            get_string('transferfee_desc', 'enrol_wallet', $a));
    }

    $mform->addElement('submit', 'confirm', get_string('confirm'));

    $mform->addElement('hidden', 'sesskey');
    $mform->setType('sesskey', PARAM_TEXT);
    $mform->setDefault('sesskey', sesskey());

    ob_start();
    $mform->display();
    $output = ob_get_clean();
    return $output;
}
