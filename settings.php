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

$context = context_system::instance();

if ($ADMIN->fulltree) {
    global $DB;

    require_once($CFG->dirroot.'/enrol/wallet/lib.php');
    $walletplugin = enrol_get_plugin('wallet');
    // General settings.
    $settings->add(new admin_setting_heading('enrol_wallet_settings', '',
        get_string('pluginname_desc', 'enrol_wallet')));
    // Adding choice between using wordpress (woowallet) of internal moodle wallet.
    $sources = [
        enrol_wallet\transactions::SOURCE_WORDPRESS => get_string('sourcewordpress', 'enrol_wallet'),
        enrol_wallet\transactions::SOURCE_MOODLE    => get_string('sourcemoodle', 'enrol_wallet'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/walletsource',
                                                get_string('walletsource', 'enrol_wallet'),
                                                get_string('walletsource_help', 'enrol_wallet'),
                                                enrol_wallet\transactions::SOURCE_MOODLE,
                                                $sources));
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/wordpressloggins',
                                                get_string('wordpressloggins', 'enrol_wallet'),
                                                get_string('wordpressloggins_desc', 'enrol_wallet'),
                                                0));
    // Define the WordPress site URL configuration setting.
    $settings->add(new admin_setting_configtext('enrol_wallet/wordpress_url',
                                                get_string('wordpressurl', 'enrol_wallet'),
                                                get_string('wordpressurl_desc', 'enrol_wallet'),
                                                'https://example.com' // Default value for the WordPress site URL.
                                                ));
    // Secret shared key.
    $settings->add(new admin_setting_configtext('enrol_wallet/wordpress_secretkey',
                                                get_string('wordpress_secretkey', 'enrol_wallet'),
                                                get_string('wordpress_secretkey_help', 'enrol_wallet'),
                                                'S0mTh1ng/123'
                                                ));
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

    // Adding settings for transfer credit for user to another.
    $settings->add(new admin_setting_heading('enrol_wallet_transfer',
                        get_string('transfer', 'enrol_wallet'),
                        get_string('transfer_desc', 'enrol_wallet')));
    // Enable or disable transfer.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/transfer_enabled',
                        get_string('transfer_enabled', 'enrol_wallet'),
                        get_string('transfer_enabled_desc', 'enrol_wallet'),
                        0));
    // Transfer fee.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/transferpercent',
                        get_string('transferpercent', 'enrol_wallet'),
                        get_string('transferpercent_desc', 'enrol_wallet'),
                        0,
                        PARAM_INT,
                        null,
                        2));

    $options = [
        'sender' => get_string('sender', 'enrol_wallet'),
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
    // Get the custom profile fields, to select the discount field.
    $menu = $DB->get_records_menu('user_info_field', null, 'id ASC', 'id, name');
    $menu[0] = get_string('not_set', 'enrol_wallet');

    ksort($menu);
    // Adding select menu for custom field related to discounts.
    $settings->add(new admin_setting_configselect('enrol_wallet/discount_field',
                        get_string('profile_field_map', 'enrol_wallet'),
                        get_string('profile_field_map_help', 'enrol_wallet'),
                        null,
                        $menu));

    // Adding options to enable and disable coupons.
    $choices = [
        enrol_wallet_plugin::WALLET_NOCOUPONS       => get_string('nocoupons', 'enrol_wallet'),
        enrol_wallet_plugin::WALLET_COUPONSFIXED    => get_string('couponsfixed', 'enrol_wallet'),
        enrol_wallet_plugin::WALLET_COUPONSDISCOUNT => get_string('couponsdiscount', 'enrol_wallet'),
        enrol_wallet_plugin::WALLET_COUPONSALL      => get_string('couponsall', 'enrol_wallet'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/coupons',
                                                get_string('couponstype', 'enrol_wallet'),
                                                get_string('couponstype_help', 'enrol_wallet'),
                                                enrol_wallet_plugin::WALLET_NOCOUPONS,
                                                $choices));
    // Add settings for conditional discount.
    // Enable conditional discount.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/conditionaldiscount_apply',
                        get_string('conditionaldiscount_apply', 'enrol_wallet'),
                        get_string('conditionaldiscount_apply_help', 'enrol_wallet'), 0));
    // Condtion for applying conditional discount.
    $settings->add(new admin_setting_configtext('enrol_wallet/conditionaldiscount_condition',
                        get_string('conditionaldiscount_condition', 'enrol_wallet'),
                        get_string('conditionaldiscount_condition_help', 'enrol_wallet'),
                        0, PARAM_INT));
    // Percentage discount.
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/conditionaldiscount_percent',
                        get_string('conditionaldiscount_percent', 'enrol_wallet'),
                        get_string('conditionaldiscount_percent_help', 'enrol_wallet'),
                        0, PARAM_INT, null, 2));

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
                                            PARAM_NUMBER));

    // Enrol instance defaults.
    $settings->add(new admin_setting_heading('enrol_wallet_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));
    // Adding wallet instance automatically upon course creation.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/defaultenrol',
        get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));

    // Adding default payment account.
    $accounts = \core_payment\helper::get_payment_accounts_menu($context);
    if ($accounts) {
        $accounts = ((count($accounts) > 1) ? ['' => ''] : []) + $accounts;
        $settings->add(new admin_setting_configselect('enrol_wallet/paymentaccount', get_string('paymentaccount', 'payment'),
                                                            get_string('paymentaccount_help', 'enrol_wallet'), '', $accounts));
    } else {
        $alert = html_writer::span(get_string('noaccountsavilable', 'payment'), 'alert alert-danger');
        $settings->add(new admin_setting_configempty('enrol_wallet/paymentaccount',
                                                    get_string('paymentaccount', 'payment'),
                                                    $alert));
    }

    // Add default currency.
    $supportedcurrencies = $walletplugin->get_possible_currencies();
    $settings->add(new admin_setting_configselect('enrol_wallet/currency', get_string('currency', 'enrol_wallet'),
                                            get_string('currency_help', 'enrol_wallet'), '', $supportedcurrencies));
    // Is instance enabled.
    $options = [ENROL_INSTANCE_ENABLED  => get_string('yes'),
                ENROL_INSTANCE_DISABLED => get_string('no')];
    $settings->add(new admin_setting_configselect('enrol_wallet/status',
        get_string('status', 'enrol_wallet'), get_string('status_desc', 'enrol_wallet'), ENROL_INSTANCE_ENABLED,
        $options));
    // Allow users to enrol into new courses by default.
    $options = [1 => get_string('yes'), 0 => get_string('no')];
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
    $options = [0 => get_string('no'), 1 => get_string('expirynotifyenroller', 'core_enrol'), 2 =>
        get_string('expirynotifyall', 'core_enrol')];
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
        ENROL_SEND_EMAIL_FROM_NOREPLY           => get_string('sendfromnoreply', 'enrol')
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
                                                                PARAM_NUMBER,
                                                                null,
                                                                2));
    // Award value.
    $settings->add(new admin_setting_configtext('enrol_wallet/awardvalue', get_string('awardvalue', 'enrol_wallet'),
                                                    get_string('awardvalue_help', 'enrol_wallet'), 0, PARAM_NUMBER));
}
// Include extra pages.
require_once('extrasettings.php');
