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
use enrol_wallet\util\balance;
use enrol_wallet\util\balance_op;
use enrol_wallet\util\discount_rules;
use enrol_wallet\coupons;

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

    $number     = $options->number;
    $maxusage   = $options->maxusage;
    $maxperuser = $options->maxperuser;
    $from       = $options->from;
    $to         = $options->to;
    $type       = $options->type;
    $value      = $options->value ?? (($type == 'enrol') ? 0 : null);
    $code       = $options->code;

    $recorddata = (object)[
        'type'        => $type,
        'value'       => $value,
        'category'    => $options->category ?? '',
        'courses'     => $options->courses ?? '',
        'maxusage'    => $maxusage,
        'maxperuser'  => $maxperuser,
        'validfrom'   => $from,
        'validto'     => $to,
        'timecreated' => time(),
    ];

    $ids = [];
    if (!empty($code)) {
        $recorddata->code = $code;

        $ids[] = $DB->insert_record('enrol_wallet_coupons', $recorddata);
    } else {

        $length = $options->length;
        $lower  = $options->lower;
        $upper  = $options->upper;
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
    global $CFG, $PAGE;
    require_once($CFG->dirroot.'/enrol/wallet/classes/form/charger_form.php');
    if (!has_capability('enrol/wallet:creditdebit', context_system::instance())) {
        return '';
    }

    $action = new moodle_url('/enrol/wallet/extra/charger.php', ['return' => $PAGE->url]);
    $mform = new enrol_wallet\form\charger_form($action, null, 'get');
    $result = optional_param('result', '', PARAM_RAW);

    $output = $result;
    $output .= $mform->render();
    return $output;
}

/**
 * Process the data submitted by the charger form.
 * @param object $data
 * @return bool
 */
function enrol_wallet_handle_charger_form($data) {
    global $USER, $DB;
    $data = (array)$data;
    $op = $data['op'] ?? '';

    if (!empty($op) && $op != 'result') {

        $value  = $data['value'] ?? '';
        $userid = $data['userlist'];
        $catid  = $data['category'] ?? 0;

        $charger = $USER->id;

        $operations = new balance_op($userid, $catid);
        $before = $operations->get_total_balance();
        if ($op === 'credit') {

            $desc = get_string('charger_credit_desc', 'enrol_wallet', fullname($USER));
            // Process the transaction.
            $operations->credit($value, $operations::USER, $charger, $desc);
            $after = $operations->get_total_balance();

        } else if ($op === 'debit') {
            $neg = $data['neg'] ?? optional_param('neg', false, PARAM_BOOL);
            // Process the payment.
            $operations->debit($value, $operations::USER, $charger, '', $neg);
            $after = $operations->get_total_balance();

        }

        $params = [
            'before' => $before,
            'after'  => ($op == 'balance') ? $before : $after,
            'userid' => $userid,
            'op'     => 'result',
        ];

        return enrol_wallet_display_transaction_results($params);
    }
    return false;
}

/**
 * Displaying the results after charging the wallet of other user.
 * @param array $params parameters from the charging form results.
 * @return bool
 */
function enrol_wallet_display_transaction_results($params = []) {
    global $OUTPUT;
    if (!has_capability('enrol/wallet:viewotherbalance', context_system::instance())) {
        return false;
    }

    $result = $params['result'] ?? optional_param('result', false, PARAM_TEXT);
    $before = $params['before'] ?? optional_param('before', '', PARAM_FLOAT);
    $after  = $params['after'] ?? optional_param('after', '', PARAM_FLOAT);
    $userid = $params['userid'] ?? optional_param('userid', '', PARAM_INT);
    $err    = $params['err'] ?? optional_param('error', '', PARAM_TEXT);

    $info = '';
    if (!empty($err)) {

        $info .= get_string('ch_result_error', 'enrol_wallet', $err);
        $type = 'error';

    } else {

        $user = \core_user::get_user($userid);
        $userfull = $user->firstname.' '.$user->lastname.' ('.$user->email.')';
        // Display the result to the user.
        $info .= get_string('ch_result_before', 'enrol_wallet', $before);
        $type = 'success';
        if (!empty($result) && is_numeric($result)) {
            $success = true;
        } else {
            $success = false;
            if (is_string($result)) {
                $info .= $result;
            }
        }
        $a = [
            'userfull'     => $userfull,
            'after'        => $after,
            'after_before' => ($after - $before),
            'before'       => $before,
        ];
        if ($after !== $before) {

            if ($after !== '') {
                $info .= get_string('ch_result_after', 'enrol_wallet', $after);
            }
            if ($after < 0) {
                $info .= get_string('ch_result_negative', 'enrol_wallet');
                $type = 'warning';
            }

            $info .= get_string('ch_result_info_charge', 'enrol_wallet', $a);

        } else {

            $info .= get_string('ch_result_info_balance', 'enrol_wallet', $a);
            $type = $success ? 'info' : 'error';

        }
    }
    // Display the results.
    core\notification::add($info, $type);

    return true;
}

/**
 * Process the data from apply_coupon_form
 * @param object $data
 * @return string the redirect url.
 */
function enrol_wallet_process_coupon_data($data) {
    global $USER, $DB, $PAGE;
    $data = (array)$data;

    $cancel = $data['cancel'] ?? optional_param('cancel', false, PARAM_BOOL);
    $url = $data['url'] ?? '';
    if (empty($url)) {
        $redirecturl = new moodle_url('/');
    } else {
        $redirecturl = new moodle_url($url);
    }

    if ($cancel) {
        // Important to unset the session coupon.
        coupons::unset_session_coupon();
        $redirecturl->remove_params('coupon', 'submitcoupon');
        redirect($redirecturl);
    }

    $couponutil = new coupons($data['coupon']);

    if (!empty($data['instanceid'])) {
        $area = $couponutil::AREA_ENROL;
        $areaid = $data['instanceid'];
    } else if (!empty($data['cmid'])) {
        $area = $couponutil::AREA_CM;
        $areaid = $data['cmid'];
    } else if (!empty($data['sectionid'])) {
        $area = $couponutil::AREA_SECTION;
        $areaid = $data['sectionid'];
    } else {
        $area = $couponutil::AREA_TOPUP;
        $areaid = 0;
    }

    $couponutil->validate_coupon($area, $areaid);
    if ($error = $couponutil->has_error()) {
        $msg = get_string('coupon_applyerror', 'enrol_wallet', $error ?? '');
        $msgtype = 'error';
        // This mean that the function return error.
    } else {

        $value = $couponutil->get_value();
        $type = $couponutil->type;
        switch($area) {
            case coupons::AREA_ENROL:
                $id = $DB->get_field('enrol', 'courseid', ['id' => $areaid, 'enrol' => 'wallet'], IGNORE_MISSING);
                if (!empty($id)) {
                    $redirecturl = new moodle_url('/enrol/index.php', ['id' => $id, 'coupon' => $couponutil->code]);
                }
                break;
            case coupons::AREA_CM:
            case coupons::AREA_SECTION:
                if (!empty($url)) {
                    $redirecturl = $url . '&' . http_build_query(['coupon' => $couponutil->code]);
                }
                break;
            default:
        }

        $couponutil->apply_coupon();
        // Check the type to determine what to do.
        if ($type == $couponutil::FIXED) {
            // Apply the coupon code to add its value to the user's wallet and enrol if value is enough.
            $currency = get_config('enrol_wallet', 'currency');
            $a = [
                'value'    => $value,
                'currency' => $currency,
            ];
            $msg = get_string('coupon_applyfixed', 'enrol_wallet', $a);
            $msgtype = 'success';

        } else if ($type == $couponutil::DISCOUNT && $area == $couponutil::AREA_ENROL) {
            // Percentage discount coupons applied in enrolment.
            if (!empty($id)) {
                $msg = get_string('coupon_applydiscount', 'enrol_wallet', $value);
                $msgtype = 'success';
            } else {
                $msg = get_string('coupon_applynocourse', 'enrol_wallet');
                $msgtype = 'error';
            }

        } else if ($type == $couponutil::DISCOUNT
                    && in_array($area, [$couponutil::AREA_SECTION, $couponutil::AREA_CM])) {

            // This is the case when the coupon applied by availability wallet.

            $msg = get_string('coupon_applydiscount', 'enrol_wallet', $value);
            $msgtype = 'success';

        } else if ($type == $couponutil::CATEGORY) {
            // This type of coupons is restricted to be used in certain categories.
            $msg = get_string('coupon_categoryapplied', 'enrol_wallet');
            $msgtype = 'success';

        } else if ($type == $couponutil::AREA_ENROL) {
            // Apply the coupon and enrol the user.
            $msg = get_string('coupon_enrolapplied', 'enrol_wallet');
            $msgtype = 'success';

        } else {
            $msg = get_string('invalidcoupon_operation', 'enrol_wallet');
            $msgtype = 'error';
        }
    }
    if ($msgtype === 'error') {
        $redirecturl->param('error', $msg);
    }
    core\notification::add($msg, $msgtype);
    return $redirecturl;
}
/**
 * Display links to generate and view coupons.
 * @return string
 */
function enrol_wallet_display_coupon_urls() {
    if (get_config('enrol_wallet', 'walletsource') !== balance::MOODLE) {
        return '';
    }
    $context = context_system::instance();
    $canviewcoupons = has_capability('enrol/wallet:viewcoupon', $context);
    $cangeneratecoupon = has_capability('enrol/wallet:createcoupon', $context);
    $caneditcoupon = has_capability('enrol/wallet:editcoupon', $context);
    $render = '';
    // Check if the user can view and generate coupons.
    if ($canviewcoupons) {
        $url = new moodle_url('/enrol/wallet/extra/coupontable.php');
        $render .= html_writer::link($url, get_string('coupon_table', 'enrol_wallet')).'<br>';

        $url = new moodle_url('/enrol/wallet/extra/couponusage.php');
        $render .= html_writer::link($url, get_string('coupon_usage', 'enrol_wallet')).'<br>';

        if ($cangeneratecoupon) {
            $url = new moodle_url('/enrol/wallet/extra/coupon.php');
            $render .= html_writer::link($url, get_string('coupon_generation', 'enrol_wallet'));
        }
        if ($cangeneratecoupon && $caneditcoupon) {
            $url = new moodle_url('/enrol/wallet/extra/couponupload.php');
            $render .= html_writer::link($url, get_string('upload_coupons', 'enrol_wallet'));
        }
    }
    return $render;
}



/**
 * Return html string contains information about current user wallet balance.
 * @param int $userid the user id, if not defined the id of current user used.
 * @return bool|string
 */
function enrol_wallet_display_current_user_balance($userid = 0) {
    global $USER, $OUTPUT, $CFG, $PAGE;
    $renderable = new \enrol_wallet\output\wallet_balance($userid);
    $renderer = $PAGE->get_renderer('enrol_wallet');
    return $renderer->render($renderable);
}

/**
 * Display top-up form by payments gateway and\or coupons.
 * @return string
 */
function enrol_wallet_display_topup_options() {
    global $CFG, $OUTPUT, $PAGE;

    require_once($CFG->dirroot.'/enrol/wallet/classes/form/topup_form.php');
    require_once(__DIR__.'/lib.php');

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $username = optional_param('s', '', PARAM_USERNAME);
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
        $render .= $topupform->render();
    }

    // Check if fixed coupons enabled.
    $enabledcoupons = coupons::get_enabled();
    $intersect = array_intersect($enabledcoupons, [coupons::ALL, coupons::FIXED, coupons::CATEGORY]);
    if (!empty($intersect)) {
        // Display the coupon form to enable user to topup wallet using fixed coupon.
        require_once($CFG->dirroot.'/enrol/wallet/classes/form/applycoupon_form.php');
        $couponaction = new moodle_url('/enrol/wallet/extra/coupon_action.php');
        $couponform = new enrol_wallet\form\applycoupon_form($couponaction, $data);

        if ($submitteddata = $couponform->get_data()) {
            enrol_wallet_process_coupon_data($submitteddata);
        }

        $render .= $couponform->render();
    }

    // If plugin block_vc exist, add credit options by it.
    if (file_exists("$CFG->dirroot/blocks/vc/classes/form/vc_credit_form.php")
            && get_config('block_vc', 'enablecredit')) {

        require_once("$CFG->dirroot/blocks/vc/classes/form/vc_credit_form.php");
        $vcform = new \block_vc\form\vc_credit_form($CFG->wwwroot.'/blocks/vc/credit.php');

        $render .= $OUTPUT->box($vcform->render());

        // This code make the container collapsed at the load of the page, where setExpanded not working.
        $jscode = "
            var vcContainer = document.getElementById('id_vccreditcontainer');
            vcContainer.setAttribute('class', 'fcontainer collapseable collapse');
        ";
        $PAGE->requires->js_init_code($jscode, true);
    }

    // Display teller men (user with capabilities to credit and choosen in the settings to be displayed).
    $tellermen = get_config('enrol_wallet', 'tellermen');
    if (!empty($tellermen)) {
        require_once($CFG->dirroot.'/user/lib.php');
        $chargerids = explode(',', $tellermen);
        $render .= $OUTPUT->box_start();
        $render .= $OUTPUT->heading(get_string('tellermen_display_guide', 'enrol_wallet'), 6);
        $render .= html_writer::start_tag('ul');
        foreach ($chargerids as $tellerid) {
            $teller = core_user::get_user($tellerid);
            $tellername = fullname($teller);
            if (user_can_view_profile($teller)) {
                $tellername = html_writer::link(new moodle_url('/user/view.php', ['id' => $tellerid]), $tellername);
            }
            $render .= html_writer::tag('li', $tellername);
        }
        $render .= html_writer::end_tag('ul');
        $render .= $OUTPUT->box_end();
    }

    if (!empty($render)) {
        $render = discount_rules::get_the_discount_line(-1) . $render;
    }
    // Display the manual refund policy.
    $policy = get_config('enrol_wallet', 'refundpolicy');
    if (!empty($policy) && !empty($render)) {
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
    if (!class_exists('\core_payment\account')) {
        return false;
    }
    $account = new \core_payment\account($accountid);
    if (!$account->is_available() || !$account->is_valid()) {
        return false;
    }

    return true;
}

/**
 * Check if the user is eligible to get enrolled with insufficient balance.
 * @param null|int|stdClass $userid null for current user.
 * @return bool
 */
function enrol_wallet_is_borrow_eligible($userid = null) {
    global $CFG, $USER, $DB;
    $enabled = get_config('enrol_wallet', 'borrowenable');
    $number = get_config('enrol_wallet', 'borrowtrans');
    $period = get_config('enrol_wallet', 'borrowperiod');

    if (empty($enabled)) {
        return false;
    }

    if (is_null($userid)) {
        $user = $USER;
        $userid == $USER->id;
    } else if (is_object($userid)) {
        $user = $userid;
        $userid = $userid->id;
    } else {
        $user = \core_user::get_user($userid);
    }

    if ($user->firstaccess > time() - 60 * DAYSECS) {
        return false;
    }

    $op = new enrol_wallet\util\balance_op($userid);
    $balance = $op->get_main_balance();

    if ($balance < 0) {
        return false;
    }

    $params = [
        'period' => time() - $period,
        'type' => 'credit',
        'userid' => $userid,
    ];
    $where = 'timecreated >= :period AND type = :type AND userid = :userid';
    $where .= " AND (category is NULL OR category = 0)";
    $count = $DB->count_records_select('enrol_wallet_transactions', $where, $params);
    if ($count >= $number) {
        return true;
    }

    return false;
}

/**
 * For versions lower than 3.11 the class core_user/fields not exists
 * so we use this.
 * Copied from core_user/fields::get_identity
 * @param context $context
 * @param bool $allowcustom
 * @return array
 */
function enrol_wallet_get_identity_fields($context, $allowcustom = true) {
    global $CFG;

    // Only users with permission get the extra fields.
    if ($context && !has_capability('moodle/site:viewuseridentity', $context)) {
        return [];
    }

    // Split showuseridentity on comma (filter needed in case the showuseridentity is empty).
    $extra = array_filter(explode(',', $CFG->showuseridentity));

    // If there are any custom fields, remove them if necessary (either if allowcustom is false,
    // or if the user doesn't have access to see them).
    foreach ($extra as $key => $field) {
        if (preg_match('~^profile_field_(.*)$~', $field, $matches)) {
            $allowed = false;
            if ($allowcustom) {
                require_once($CFG->dirroot . '/user/profile/lib.php');

                // Ensure the field exists (it may have been deleted since user identity was configured).
                $field = profile_get_custom_field_data_by_shortname($matches[1], false);
                if ($field !== null) {
                    $fieldinstance = profile_get_user_field($field->datatype, $field->id, 0, $field);
                    $allowed = $fieldinstance->is_visible($context);
                }
            }
            if (!$allowed) {
                unset($extra[$key]);
            }
        }
    }

    // For standard user fields, access is controlled by the hiddenuserfields option and
    // some different capabilities. Check and remove these if the user can't access them.
    $hiddenfields = array_filter(explode(',', $CFG->hiddenuserfields));
    $hiddenidentifiers = array_intersect($extra, $hiddenfields);

    if ($hiddenidentifiers) {
        if (!$context) {
            $canviewhiddenuserfields = true;
        } else if ($context->get_course_context(false)) {
            // We are somewhere inside a course.
            $canviewhiddenuserfields = has_capability('moodle/course:viewhiddenuserfields', $context);
        } else {
            // We are not inside a course.
            $canviewhiddenuserfields = has_capability('moodle/user:viewhiddendetails', $context);
        }

        if (!$canviewhiddenuserfields) {
            // Remove hidden identifiers from the list.
            $extra = array_diff($extra, $hiddenidentifiers);
        }
    }

    // Re-index the entries and return.
    $extra = array_values($extra);
    return array_map([core_text::class, 'strtolower'], $extra);
}
