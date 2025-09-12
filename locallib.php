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
use enrol_wallet\form\applycoupon_form;
use enrol_wallet\form\charger_form;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet\output\static_renderer;
use enrol_wallet\output\topup_options;
use enrol_wallet\output\wallet_balance;

defined('MOODLE_INTERNAL') || die();
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
#[Deprecated('This function is deprecated, please use coupons generator class')]
function enrol_wallet_get_random_coupon($length, $options) {
    return enrol_wallet\local\coupons\generator::generate_random_coupon($length, $options);
}

/**
 * Generating coupons.
 *
 * @param object $options the options from coupon form.
 * @param ?progress_trace $trace
 * @return array|string array of coupon, or string of error.
 */
#[Deprecated('This function is deprecated, please use coupons generator class')]
function enrol_wallet_generate_coupons($options, ?progress_trace $trace = null) {
    return enrol_wallet\local\coupons\generator::create_coupons($options, $trace);
}

/**
 * Display the form for charging other users.
 * @param bool $return
 * @return string|void
 */
#[Deprecated('Use static_renderer class instead')]
function enrol_wallet_display_charger_form($return = true) {
    $output = static_renderer::charger_form();
    if ($return) {
        return $output;
    }
    echo $output;
}

/**
 * Process the data submitted by the charger form.
 * @param object $data
 * @return bool
 */
#[Deprecated()]
function enrol_wallet_handle_charger_form($data) {
    $form = new charger_form();
    return $form->process_form_submission($data);
}

/**
 * Displaying the results after charging the wallet of other user.
 * @param array $params parameters from the charging form results.
 * @return bool
 */
#[Deprecated()]
function enrol_wallet_display_transaction_results($params = []) {
    $form = new charger_form();
    return $form->notify_result($params);
}

/**
 * Process the data from apply_coupon_form
 * @param object $data
 * @return string the redirect url.
 */
#[Deprecated()]
function enrol_wallet_process_coupon_data($data = null) {
    $form = new applycoupon_form();
    return $form->process_coupon_data($data);
}
/**
 * Display links to generate and view coupons.
 * @return string
 */
#[Deprecated()]
function enrol_wallet_display_coupon_urls() {
    return static_renderer::coupons_urls();
}

/**
 * Return html string contains information about current user wallet balance.
 * @param int $userid the user id, if not defined the id of current user used.
 * @return bool|string
 */
#[Deprecated]
function enrol_wallet_display_current_user_balance($userid = 0) {
    global $PAGE;
    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $renderable = new wallet_balance($userid);
    $renderer = $PAGE->get_renderer('enrol_wallet');
    return $renderer->render($renderable);
}

/**
 * Display top-up form by payments gateway and\or coupons.
 * @return string
 */
function enrol_wallet_display_topup_options() {
    global $PAGE;
    $topup = new topup_options();
    $renderer = $PAGE->get_renderer('enrol_wallet');
    return $renderer->render($topup);
}

/**
 * Check the payment account id if it is valid or not.
 *
 * @param int $accountid
 * @return bool
 */
#[Deprecated()]
function enrol_wallet_is_valid_account($accountid) {
    return enrol_wallet\local\utils\payment::is_valid_account($accountid);
}

/**
 * Check if the user is eligible to get enrolled with insufficient balance.
 * @param null|int|stdClass $userid null for current user.
 * @return bool
 */
function enrol_wallet_is_borrow_eligible($userid = null) {
    global $USER, $DB;
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

    if ($user->firstaccess > timedate::time() - 60 * DAYSECS) {
        return false;
    }

    $op = new balance_op($userid);
    $balance = $op->get_main_balance();

    if ($balance < 0) {
        return false;
    }

    $params = [
        'period' => timedate::time() - $period,
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
