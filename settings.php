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
 * wallet enrolment plugin settings and presets.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use enrol_wallet\util\balance;
use enrol_wallet\coupons;
use enrol_wallet\util\instance;

$context = context_system::instance();

if ($ADMIN->fulltree) {
    global $DB;

    require_once($CFG->dirroot.'/enrol/wallet/lib.php');
    $walletplugin = new enrol_wallet_plugin;

    // Adding an option to migrate credits, enrol instances and users enrollments from enrol_credit to enrol_wallet.
    $creditplugin = enrol_get_plugin('credit');
    if (!empty($creditplugin)) {
        $countcredit = 0;
        $countenrol = 0;
        $credits = $DB->get_records('enrol', ['enrol' => 'credit']);
        if (!empty($credits)) {
            $countcredit = count($credits);
        }
        foreach ($credits as $credit) {
            $countenrol += $DB->count_records('user_enrolments', ['enrolid' => $credit->id]);
        }
        if (!empty($countenrol + $countcredit)) {
            $turl = new moodle_url('/enrol/wallet/extra/credit_transformation.php');

            $a = [
                'enrol' => $countenrol,
                'credit' => $countcredit,
            ];
            $transformbutton = html_writer::link($turl,
                    get_string('transformation_credit_title', 'enrol_wallet'),
                    ['target' => '_blank', 'class' => 'btn btn-secondary']);
            $migration = $OUTPUT->box(get_string('transformation_credit_desc', 'enrol_wallet', $a) . '<br>' . $transformbutton);
            $settings->add(new admin_setting_heading('enrol_wallet_migration',
                                                    '',
                                                $migration));
        }
    }

    // General settings.
    $settings->add(new admin_setting_heading('enrol_wallet_settings', '',
                        get_string('pluginname_desc', 'enrol_wallet')));
    // Adding choice between using wordpress (woowallet) of internal moodle wallet.
    $sources = [
        balance::WP     => get_string('sourcewordpress', 'enrol_wallet'),
        balance::MOODLE => get_string('sourcemoodle', 'enrol_wallet'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/walletsource',
                                                get_string('walletsource', 'enrol_wallet'),
                                                get_string('walletsource_help', 'enrol_wallet'),
                                                balance::MOODLE,
                                                $sources));
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/wordpressloggins',
                                                get_string('wordpressloggins', 'enrol_wallet'),
                                                get_string('wordpressloggins_desc', 'enrol_wallet'),
                                                0));
    $settings->hide_if('enrol_wallet/wordpressloggins', 'enrol_wallet/walletsource',
                                                'eq', balance::MOODLE);
    // Define the WordPress site URL configuration setting.
    $settings->add(new admin_setting_configtext('enrol_wallet/wordpress_url',
                                                get_string('wordpressurl', 'enrol_wallet'),
                                                get_string('wordpressurl_desc', 'enrol_wallet'),
                                                'https://example.com' // Default value for the WordPress site URL.
                                                ));
    $settings->hide_if('enrol_wallet/wordpress_url', 'enrol_wallet/walletsource', 'eq', balance::MOODLE);
    // Secret shared key.
    $settings->add(new admin_setting_configtext('enrol_wallet/wordpress_secretkey',
                                                get_string('wordpress_secretkey', 'enrol_wallet'),
                                                get_string('wordpress_secretkey_help', 'enrol_wallet'),
                                                'S0mTh1ng/123'
                                                ));
    $settings->hide_if('enrol_wallet/wordpress_secretkey', 'enrol_wallet/walletsource',
                                                'eq', balance::MOODLE);

    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    // it describes what should happened when users are not supposed to be enrolled any more.
    $options = [
        ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/expiredaction',
        get_string('expiredaction', 'enrol_wallet'), get_string('expiredaction_help', 'enrol_wallet'),
        ENROL_EXT_REMOVED_KEEP, $options));
    // Expiry notifications.
    $options = [];
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('enrol_wallet/expirynotifyhour',
        get_string('expirynotifyhour', 'core_enrol'), '', 6, $options));
    // Options for multiple instances.
    $settings->add(new admin_setting_configtext('enrol_wallet/allowmultipleinstances',
                        get_string('allowmultiple', 'enrol_wallet'),
                        get_string('allowmultiple_help', 'enrol_wallet'), 1, PARAM_INT));
    // Enable or disable awards across the whole site.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/awardssite',
                        get_string('awardssite', 'enrol_wallet'),
                        get_string('awardssite_help', 'enrol_wallet'),
                        1));
    // Enable or disable unenrol self.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/unenrolselfenabled',
                        get_string('unenrolselfenabled', 'enrol_wallet'),
                        get_string('unenrolselfenabled_desc', 'enrol_wallet'),
                        0));
    // Not allowing unenrol self after certain period of time.
    $settings->add(new admin_setting_configduration('enrol_wallet/unenrollimitafter',
                        get_string('unenrollimitafter', 'enrol_wallet'),
                        get_string('unenrollimitafter_desc', 'enrol_wallet'),
                        0,
                        DAYSECS));
    // Not allowing unenrol self before end of enrolment with this period.
    $settings->add(new admin_setting_configduration('enrol_wallet/unenrollimitbefor',
                        get_string('unenrollimitbefor', 'enrol_wallet'),
                        get_string('unenrollimitbefor_desc', 'enrol_wallet'),
                        0));

    // Show the price on the enrol icon?
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/showprice',
                        get_string('showprice', 'enrol_wallet'),
                        get_string('showprice_desc', 'enrol_wallet'),
                        0));
    // Enable category balance.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/catbalance',
                        'Category balance',
                        'Enable category balance',
                        1));
    $settings->hide_if('enrol_wallet/catbalance', 'enrol_wallet/walletsource', 'eq', balance::WP);

    // Auto Refunding to wallet after un-enrol.
    $settings->add(new admin_setting_heading('enrol_wallet_unenrolrefund',
                        get_string('unenrolrefund_head', 'enrol_wallet'),
                        get_string('unenrolrefund_head_desc', 'enrol_wallet')));
    // Enabling refund upon unenrol.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/unenrolrefund',
                        get_string('unenrolrefund', 'enrol_wallet'),
                        get_string('unenrolrefund_desc', 'enrol_wallet'),
                        0));
    // Unenrol refund grace period.
    $settings->add(new admin_setting_configduration('enrol_wallet/unenrolrefundperiod',
                        get_string('unenrolrefundperiod', 'enrol_wallet'),
                        get_string('unenrolrefundperiod_desc', 'enrol_wallet'),
                        '0',
                        DAYSECS));
    // Refunding after enrolment fee.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/unenrolrefundfee',
                        get_string('unenrolrefundfee', 'enrol_wallet'),
                        get_string('unenrolrefundfee_desc', 'enrol_wallet'),
                        0,
                        PARAM_INT,
                        null,
                        2));
    // Refund upon unenrol policy.
    $settings->add(new admin_setting_confightmleditor('enrol_wallet/unenrolrefundpolicy',
                        get_string('unenrolrefundpolicy', 'enrol_wallet'),
                        get_string('unenrolrefundpolicy_help', 'enrol_wallet'),
                        get_string('unenrolrefundpolicy_default', 'enrol_wallet')));

    // Manual Refunding.
    $settings->add(new admin_setting_heading('enrol_wallet_refund',
                        get_string('refundpolicy', 'enrol_wallet'),
                        ''));
    // Refund policy.
    $settings->add(new admin_setting_confightmleditor('enrol_wallet/refundpolicy',
                        get_string('refundpolicy', 'enrol_wallet'),
                        get_string('refundpolicy_help', 'enrol_wallet'),
                        get_string('refundpolicy_default', 'enrol_wallet')));
    // Enable refunding.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/enablerefund',
                        get_string('enablerefund', 'enrol_wallet'),
                        get_string('enablerefund_desc', 'enrol_wallet'),
                        1));
    // Refunding grace period.
    $settings->add(new admin_setting_configduration('enrol_wallet/refundperiod',
                        get_string('refundperiod', 'enrol_wallet'),
                        get_string('refundperiod_desc', 'enrol_wallet'),
                        14 * DAYSECS,
                        DAYSECS));

    // Add option to display users with capabiliy to credit others on the site.
    $settings->add(new admin_setting_heading('enrol_wallet_tellermen',
                                get_string('tellermen_heading', 'enrol_wallet'),
                                get_string('tellermen_heading_desc', 'enrol_wallet')));
    $tellermen = get_users_by_capability($context, 'enrol/wallet:creditdebit', 'u.id, u.firstname, u.lastname');
    $tellermen += get_admins();
    foreach ($tellermen as $user) {
        $tellermen[$user->id] = $user->firstname . ' '. $user->lastname;
    }

    $settings->add(new admin_setting_configmultiselect('enrol_wallet/tellermen',
                                get_string('tellermen', 'enrol_wallet'),
                                get_string('tellermen_desc', 'enrol_wallet'),
                                [], $tellermen));

    // Adding settings for transfer credit for user to another.
    $settings->add(new admin_setting_heading('enrol_wallet_transfer',
                        get_string('transfer', 'enrol_wallet'),
                        get_string('transfer_desc', 'enrol_wallet')));
    // Enable or disable transfer.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/transfer_enabled',
                        get_string('transfer_enabled', 'enrol_wallet'),
                        get_string('transfer_enabled_desc', 'enrol_wallet'),
                        0));
    // Min transfer amount.
    $settings->add(new admin_setting_configtext('enrol_wallet/mintransfer',
                        get_string('mintransfer_config', 'enrol_wallet'),
                        get_string('mintransfer_config_desc', 'enrol_wallet'),
                        0, PARAM_FLOAT));
    // Transfer fee.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/transferpercent',
                        get_string('transferpercent', 'enrol_wallet'),
                        get_string('transferpercent_desc', 'enrol_wallet'),
                        0,
                        PARAM_INT,
                        null,
                        2));

    $options = [
        'sender'   => get_string('sender', 'enrol_wallet'),
        'receiver' => get_string('receiver', 'enrol_wallet'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/transferfee_from',
                        get_string('transferfee_from', 'enrol_wallet'),
                        get_string('transferfee_from_desc', 'enrol_wallet'),
                        'sender',
                        $options));
    // Adding discounts and coupons.
    $settings->add(new admin_setting_heading('enrol_wallet_discounts',
                                            get_string('discountscopouns', 'enrol_wallet'),
                                            get_string('discountscopouns_desc', 'enrol_wallet')));
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/frontpageoffers',
                                            get_string('frontpageoffers', 'enrol_wallet'),
                                            get_string('frontpageoffers_desc', 'enrol_wallet'), 0));

    if ((int)$CFG->branch < 404) {
        if ($PAGE->has_set_url()) {
            $return = $PAGE->url->out();
        } else {
            $return = (new moodle_url('/admin/settings.php', ['section' => 'enrolsettingswallet']))->out();
        }
        $url = new moodle_url('/enrol/wallet/extra/offers_nav.php', ['return' => $return]);
        $button = html_writer::link($url, get_string('offersnav', 'enrol_wallet'), ['class' => 'btn btn-secondary']);
        $settings->add(new admin_setting_description('enrol_wallet/offers_nav',
                                                    get_string('offersnav_desc', 'enrol_wallet'), $button));
    } else {
        $settings->add(new admin_setting_configcheckbox('enrol_wallet/offers_nav',
                                                        get_string('offersnav', 'enrol_wallet'),
                                                        get_string('offersnav', 'enrol_wallet'),
                                                        '0'));
    }


    $behaviors = [
        instance::B_SEQ => get_string('discount_behavior_sequential', 'enrol_wallet'),
        instance::B_SUM => get_string('discount_behavior_sum', 'enrol_wallet'),
        instance::B_MAX => get_string('discount_behavior_max', 'enrol_wallet'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/discount_behavior',
                        get_string('discount_behavior', 'enrol_wallet'),
                        get_string('discount_behavior_desc', 'enrol_wallet'),
                        instance::B_SEQ, $behaviors));
    // Get the custom profile fields, to select the discount field.
    $menu = $DB->get_records_menu('user_info_field', null, 'id ASC', 'id, name');
    $menu[0] = get_string('not_set', 'enrol_wallet');

    ksort($menu);
    // Adding select menu for custom field related to discounts.
    $settings->add(new admin_setting_configselect('enrol_wallet/discount_field',
                        get_string('profile_field_map', 'enrol_wallet'),
                        get_string('profile_field_map_help', 'enrol_wallet'),
                        0,
                        $menu));

    // Adding options to enable and disable coupons.
    $choices = coupons::get_coupons_options();
    $settings->add(new admin_setting_configmulticheckbox('enrol_wallet/coupons',
                                                get_string('couponstype', 'enrol_wallet'),
                                                get_string('couponstype_help', 'enrol_wallet'),
                                                [],
                                                $choices));

    // Add settings for conditional discount.
    // Enable conditional discount.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/conditionaldiscount_apply',
                        get_string('conditionaldiscount_apply', 'enrol_wallet'),
                        get_string('conditionaldiscount_apply_help', 'enrol_wallet'), 0));
    // Link to conditional discount page.
    $discountspage = new moodle_url('/enrol/wallet/extra/conditionaldiscount.php');
    $conditionaldiscount = html_writer::link($discountspage, get_string('conditionaldiscount_link_desc', 'enrol_wallet'));
    $settings->add(new admin_setting_description('conditionaldiscount',
                        get_string('conditionaldiscount', 'enrol_wallet'), $conditionaldiscount));

    // Adding settings for applying cashback.
    $settings->add(new admin_setting_heading('enrol_wallet_cashback',
        get_string('walletcashback', 'enrol_wallet'), get_string('walletcashback_desc', 'enrol_wallet')));
    // Enable cashback.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/cashback',
        get_string('cashbackenable', 'enrol_wallet'), get_string('cashbackenable_desc', 'enrol_wallet'), 0));
    // Cashback percentage value.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/cashbackpercent',
        get_string('cashbackpercent', 'enrol_wallet'), get_string('cashbackpercent_help', 'enrol_wallet'), 0, PARAM_INT, null, 2));

    // Adding settings for gifting users upon new creation.
    $settings->add(new admin_setting_heading('enrol_wallet_newusergift',
                                            get_string('newusergift', 'enrol_wallet'),
                                            get_string('newusergift_desc', 'enrol_wallet')));
    // Enable new user gifts.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/newusergift',
                                            get_string('newusergift_enable', 'enrol_wallet'),
                                            get_string('newusergift_enable_help', 'enrol_wallet'),
                                            0));
    // New user gift value.
    $settings->add(new admin_setting_configtext('enrol_wallet/newusergiftvalue',
                                            get_string('giftvalue', 'enrol_wallet'),
                                            get_string('giftvalue_help', 'enrol_wallet'),
                                            0,
                                            PARAM_FLOAT));

    // Adding settings for borrowing credit.
    $settings->add(new admin_setting_heading('enrol_wallet_borrow',
                                            get_string('borrow', 'enrol_wallet'),
                                            get_string('borrow_desc', 'enrol_wallet')));
    // Enable Borrow.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/borrowenable',
                                            get_string('borrow_enable', 'enrol_wallet'),
                                            get_string('borrow_enable_help', 'enrol_wallet'),
                                            0));
    // Eligibility for borrowing as number of transactions.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/borrowtrans',
                                            get_string('borrow_trans', 'enrol_wallet'),
                                            get_string('borrow_trans_help', 'enrol_wallet'), 20, PARAM_INT, null, 4));
    // Eligibility for borrowing as period for the selected transaction.
    $settings->add(new admin_setting_configduration('enrol_wallet/borrowperiod',
                                            get_string('borrow_period', 'enrol_wallet'),
                                            get_string('borrow_period_help', 'enrol_wallet'),
                                            30 * DAYSECS, DAYSECS));

    // Adding settings for referral program.
    $settings->add(new admin_setting_heading('enrol_wallet_referral',
                                            get_string('referral_program', 'enrol_wallet'),
                                            get_string('referral_program_desc', 'enrol_wallet')));
    // Enable referrals.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/referral_enabled',
                                            get_string('referral_enabled', 'enrol_wallet'),
                                            '',
                                            0));
    // Referral value.
    $settings->add(new admin_setting_configtext('enrol_wallet/referral_amount',
                                            get_string('referral_amount', 'enrol_wallet'),
                                            get_string('referral_amount_desc', 'enrol_wallet'),
                                            0,
                                            PARAM_FLOAT));
    // Maximum Referal times.
    $settings->add(new admin_setting_configtext('enrol_wallet/referral_max',
                                            get_string('referral_max', 'enrol_wallet'),
                                            get_string('referral_max_desc', 'enrol_wallet'),
                                            0,
                                            PARAM_INT));
    $enrolmethods = array_keys(enrol_get_plugins(false));
    $options = array_combine($enrolmethods, $enrolmethods);
    $settings->add(new admin_setting_configmultiselect('enrol_wallet/referral_plugins',
                                            get_string('referral_plugins', 'enrol_wallet'),
                                            get_string('referral_plugins_desc', 'enrol_wallet'),
                                            ['wallet'],
                                            $options));

    // Add low balance notification settings.
    $settings->add(new admin_setting_heading('enrol_wallet_notify',
                                            get_string('lowbalancenotify', 'enrol_wallet'),
                                            get_string('lowbalancenotify_desc', 'enrol_wallet')));

    $settings->add(new admin_setting_configcheckbox('enrol_wallet/lowbalancenotice',
                                            get_string('lowbalancenotice', 'enrol_wallet'), '', 0));

    $settings->add(new admin_setting_configtext('enrol_wallet/noticecondition',
                                            get_string('noticecondition', 'enrol_wallet'),
                                            get_string('noticecondition_desc', 'enrol_wallet'),
                                            0, PARAM_INT));

    // Enrol instance defaults.
    $settings->add(new admin_setting_heading('enrol_wallet_repurchase',
                                            get_string('repurchase', 'enrol_wallet'),
                                            get_string('repurchase_desc', 'enrol_wallet')));
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/repurchase',
                                            get_string('repurchase', 'enrol_wallet'), '', 0));
    $settings->add(new admin_setting_configtext('enrol_wallet/repurchase_firstdis',
                                            get_string('repurchase_firstdis', 'enrol_wallet'),
                                            get_string('repurchase_firstdis_desc', 'enrol_wallet'),
                                            0, PARAM_INT));
    $settings->add(new admin_setting_configtext('enrol_wallet/repurchase_seconddis',
                                            get_string('repurchase_seconddis', 'enrol_wallet'),
                                            get_string('repurchase_seconddis_desc', 'enrol_wallet'),
                                            0, PARAM_INT));

    // Availability Conditions.
    $settings->add(new admin_setting_heading('enrol_wallet_conditions',
                                            get_string('restrictions', 'enrol_wallet'),
                                            get_string('restrictions_desc', 'enrol_wallet')));
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/restrictionenabled',
                                            get_string('restrictionenabled', 'enrol_wallet'),
                                            get_string('restrictionenabled_desc', 'enrol_wallet'),
                                            0));
    $pluginmanager = \core_plugin_manager::instance();
    $availabilities = $pluginmanager->get_enabled_plugins('availability');
    $options = [];
    foreach ($availabilities as $avplugin => $p) {
        if (in_array($avplugin, ['wallet', 'group', 'grouping', 'maxviews'])) {
            continue;
        }
        $options[$avplugin] = get_string('title', "availability_$avplugin");
    }
    $settings->add(new admin_setting_configmultiselect('enrol_wallet/availability_plugins',
                                            get_string('availability_plugins', 'enrol_wallet'),
                                            get_string('availability_plugins_desc', 'enrol_wallet'),
                                            [],
                                            $options));

    // Enrol instance defaults.
    $settings->add(new admin_setting_heading('enrol_wallet_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));
    // Adding wallet instance automatically upon course creation.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/defaultenrol',
        get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));

    // Adding default payment account.
    if (class_exists('\core_payment\helper')) {
        $accounts = \core_payment\helper::get_payment_accounts_menu($context);
        if (empty($accounts)) {
            $alert = html_writer::span(get_string('noaccountsavilable', 'payment'), 'alert alert-warning');
            $settings->add(new admin_setting_configempty('enrol_wallet/emptypaymentaccount', '', $alert));
            $accounts = [0 => get_string('noaccount', 'enrol_wallet')];
        } else {
            $accounts = [0 => get_string('noaccount', 'enrol_wallet')] + $accounts;
        }
        $settings->add(new admin_setting_configselect('enrol_wallet/paymentaccount', get_string('paymentaccount', 'payment'),
                                                            get_string('paymentaccount_help', 'enrol_wallet'), 0, $accounts));
    }

    // Add default currency.
    $supportedcurrencies = $walletplugin->get_possible_currencies(get_config('enrol_wallet', 'paymentaccount'));
    $settings->add(new admin_setting_configselect('enrol_wallet/currency', get_string('currency', 'enrol_wallet'),
                                            get_string('currency_help', 'enrol_wallet'), '', $supportedcurrencies));

    // Add custom currency.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/customcurrencycode',
                                                get_string('customcurrencycode', 'enrol_wallet'),
                                                get_string('customcurrencycode_desc', 'enrol_wallet'), '', PARAM_TEXT, 5, 3));
    $settings->add(new admin_setting_configtext('enrol_wallet/customcurrency',
                                                get_string('customcurrency', 'enrol_wallet'),
                                                get_string('customcurrency_desc', 'enrol_wallet'), ''));
    $settings->hide_if('enrol_wallet/customcurrency', 'enrol_wallet/paymentaccount', 'neq', '0');
    $settings->hide_if('enrol_wallet/customcurrencycode', 'enrol_wallet/paymentaccount', 'neq', '0');

    // Is instance enabled.
    $options = [
                ENROL_INSTANCE_ENABLED  => get_string('yes'),
                ENROL_INSTANCE_DISABLED => get_string('no'),
            ];
    $settings->add(new admin_setting_configselect('enrol_wallet/status',
        get_string('status', 'enrol_wallet'), get_string('status_desc', 'enrol_wallet'), ENROL_INSTANCE_ENABLED,
        $options));
    // Allow users to enrol into new courses by default.
    $options = [
                    1 => get_string('yes'),
                    0 => get_string('no'),
                ];
    $settings->add(new admin_setting_configselect('enrol_wallet/newenrols',
        get_string('newenrols', 'enrol_wallet'), get_string('newenrols_desc', 'enrol_wallet'), 1, $options));

    // Default role.
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_wallet/roleid',
            get_string('defaultrole', 'enrol_wallet'), get_string('defaultrole_desc', 'enrol_wallet'), $student->id,
            $options));
    }
    // Enrolment period.
    $settings->add(new admin_setting_configduration('enrol_wallet/enrolperiod',
        get_string('enrolperiod', 'enrol_wallet'), get_string('enrolperiod_desc', 'enrol_wallet'), 0));
    // Expiry notification.
    $options = [
        0 => get_string('no'),
        1 => get_string('expirynotifyenroller', 'core_enrol'),
        2 => get_string('expirynotifyall', 'core_enrol'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/expirynotify',
        get_string('expirynotify', 'core_enrol'), get_string('expirynotify_help', 'core_enrol'), 0, $options));
    // Expiry threshold.
    $settings->add(new admin_setting_configduration('enrol_wallet/expirythreshold',
        get_string('expirythreshold', 'core_enrol'), get_string('expirythreshold_help', 'core_enrol'), 86400, 86400));
    // Un-enrol inactive duration.
    $options = $walletplugin->get_longtimenosee_options();
    $settings->add(new admin_setting_configselect('enrol_wallet/longtimenosee',
        get_string('longtimenosee', 'enrol_wallet'), get_string('longtimenosee_help', 'enrol_wallet'), 0, $options));
    // Max enrolled users.
    $settings->add(new admin_setting_configtext('enrol_wallet/maxenrolled',
        get_string('maxenrolled', 'enrol_wallet'), get_string('maxenrolled_help', 'enrol_wallet'), 0, PARAM_INT));
    // Send welcome message.
    $weloptions = [
        ENROL_DO_NOT_SEND_EMAIL                 => get_string('no'),
        ENROL_SEND_EMAIL_FROM_COURSE_CONTACT    => get_string('sendfromcoursecontact', 'enrol'),
        ENROL_SEND_EMAIL_FROM_NOREPLY           => get_string('sendfromnoreply', 'enrol'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/sendcoursewelcomemessage',
            get_string('sendcoursewelcomemessage', 'enrol_wallet'),
            get_string('sendcoursewelcomemessage_help', 'enrol_wallet'),
            ENROL_SEND_EMAIL_FROM_COURSE_CONTACT,
            $weloptions));
    // Adding default settings for awards program.
    // Enable awards.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/awards',
                                                    get_string('awards', 'enrol_wallet'),
                                                    get_string('awards_help', 'enrol_wallet'),
                                                    0));
    // Awards conditions.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/awardcreteria',
                                                                get_string('awardcreteria', 'enrol_wallet'),
                                                                get_string('awardcreteria_help', 'enrol_wallet'),
                                                                0,
                                                                PARAM_FLOAT,
                                                                null,
                                                                2));
    // Award value.
    $settings->add(new admin_setting_configtext('enrol_wallet/awardvalue', get_string('awardvalue', 'enrol_wallet'),
                                                    get_string('awardvalue_help', 'enrol_wallet'), 0, PARAM_FLOAT));

    $settings->hide_if('enrol_wallet/awards', 'enrol_wallet/awardssite');
    $settings->hide_if('enrol_wallet/awardcreteria', 'enrol_wallet/awardssite');
    $settings->hide_if('enrol_wallet/awardvalue', 'enrol_wallet/awardssite');
}
// Include extra pages.
require_once('extrasettings.php');
