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

require_once($CFG->dirroot.'/enrol/wallet/lib.php');
$context = context_system::instance();
$captransactions = has_capability('enrol/wallet:transaction', $context);
$capbulkedit = has_capability('enrol/wallet:bulkedit', $context);

// Adding these pages for only users with required capability.
// Don't know why these aren't appear to user's with capablities? Only admins!
// I added these to the wallet block.
if ($hassiteconfig || $captransactions || $capbulkedit) {
    // Adding new admin category.
    $ADMIN->add('root', new admin_category('enrol_wallet_settings',
    get_string('bulkfolder', 'enrol_wallet')));

    // Adding page to generate coupons.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_coupongenerate',
                                                get_string('coupon_generation', 'enrol_wallet'),
                                                new moodle_url('/enrol/wallet/extra/coupon.php'),
                                                'enrol/wallet:createcoupon'));

    // Adding page to generate coupons.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_coupontable',
                                                get_string('coupon_table', 'enrol_wallet'),
                                                new moodle_url('/enrol/wallet/extra/coupontable.php'),
                                                'enrol/wallet:viewcoupon'));

    // Adding page to generate coupons.
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_charging',
                                                get_string('chargingoptions', 'enrol_wallet'),
                                                new moodle_url('/enrol/wallet/extra/charger.php'),
                                                'enrol/wallet:creditdebit'));

    // Adding page to show user's transactions.
    $url = new moodle_url('/enrol/wallet/extra/transaction.php');
    $pagename = get_string('transactions', 'enrol_wallet');
    $page = new admin_externalpage('wallettransactions', $pagename, $url, 'enrol/wallet:transaction');
    $ADMIN->add('enrol_wallet_settings', $page);

    // Adding new page to bulk edit all user enrolments.
    $bulkeditor = get_string('bulkeditor', 'enrol_wallet');

    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_bulkedit',
            $bulkeditor,
            new moodle_url('/enrol/wallet/extra/bulkedit.php'),
            "enrol/wallet:bulkedit"));

    // Adding page to bulk edit all instances.
    $walletbulk = get_string('walletbulk', 'enrol_wallet');
    $ADMIN->add('enrol_wallet_settings', new admin_externalpage('enrol_wallet_bulkedit',
                $walletbulk,
                new moodle_url('/enrol/wallet/extra/bulkinstances.php'),
                "enrol/wallet:bulkedit"));
}

if ($ADMIN->fulltree) {
    global $DB;

    // General settings.
    $settings->add(new admin_setting_heading('enrol_wallet_settings', '',
        get_string('pluginname_desc', 'enrol_wallet')));
    // Adding choice between using wordpress (woowallet) of internal moodle wallet.
    $sources = [
        enrol_wallet\transactions::SOURCE_WORDPRESS => get_string('sourcewordpress', 'enrol_wallet'),
        enrol_wallet\transactions::SOURCE_MOODLE => get_string('sourcemoodle', 'enrol_wallet'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/walletsource',
                                                get_string('walletsource', 'enrol_wallet'),
                                                get_string('walletsource_help', 'enrol_wallet'),
                                                enrol_wallet\transactions::SOURCE_WORDPRESS,
                                                $sources));
    // Define the WordPress site URL configuration setting.
    $settings->add(new admin_setting_configtext(
        'enrol_wallet/wordpress_url',
        get_string('wordpressurl', 'enrol_wallet'),
        get_string('wordpressurl_desc', 'enrol_wallet'),
        'https://example.com' // Default value for the WordPress site URL.
    ));

    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    // it describes what should happend when users are not supposed to be enerolled any more.
    $options = array(
        ENROL_EXT_REMOVED_KEEP => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
        ENROL_EXT_REMOVED_UNENROL => get_string('extremovedunenrol', 'enrol'),
    );
    $settings->add(new admin_setting_configselect('enrol_wallet/expiredaction',
        get_string('expiredaction', 'enrol_wallet'), get_string('expiredaction_help', 'enrol_wallet'),
        ENROL_EXT_REMOVED_KEEP, $options));

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('enrol_wallet/expirynotifyhour',
        get_string('expirynotifyhour', 'core_enrol'), '', 6, $options));

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
        enrol_wallet_plugin::WALLET_NOCOUPONS => get_string('nocoupons', 'enrol_wallet'),
        enrol_wallet_plugin::WALLET_COUPONSFIXED => get_string('couponsfixed', 'enrol_wallet'),
        enrol_wallet_plugin::WALLET_COUPONSDISCOUNT => get_string('couponsdiscount', 'enrol_wallet'),
        enrol_wallet_plugin::WALLET_COUPONSALL => get_string('couponsall', 'enrol_wallet'),
    ];
    $settings->add(new admin_setting_configselect('enrol_wallet/coupons',
                                                get_string('couponstype', 'enrol_wallet'),
                                                get_string('couponstype_help', 'enrol_wallet'),
                                                enrol_wallet_plugin::WALLET_NOCOUPONS,
                                                $choices));

    // Adding settings for applying cashback.
    $settings->add(new admin_setting_heading('enrol_wallet_cashback',
        get_string('walletcashback', 'enrol_wallet'), get_string('walletcashback_desc', 'enrol_wallet')));

    $settings->add(new admin_setting_configcheckbox('enrol_wallet/cashback',
        get_string('cashbackenable', 'enrol_wallet'), get_string('cashbackenable_desc', 'enrol_wallet'), 0));
    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/cashbackpercent',
        get_string('cashbackpercent', 'enrol_wallet'), get_string('cashbackpercent_help', 'enrol_wallet'), 0, PARAM_INT, null, 2));

    // Adding settings for gifting users upon new creation.
    $settings->add(new admin_setting_heading('enrol_wallet_newusergift',
                                            get_string('newusergift', 'enrol_wallet'),
                                            get_string('newusergift_desc', 'enrol_wallet')));
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/newusergift',
                                            get_string('newusergift_enable', 'enrol_wallet'),
                                            get_string('newusergift_enable_help', 'enrol_wallet'),
                                            0));
    $settings->add(new admin_setting_configtext('enrol_wallet/newusergiftvalue',
                                            get_string('giftvalue', 'enrol_wallet'),
                                            get_string('giftvalue_help', 'enrol_wallet'),
                                            0,
                                            PARAM_NUMBER));

    // Enrol instance defaults.
    $settings->add(new admin_setting_heading('enrol_wallet_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

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
    $wallet = new \enrol_wallet_plugin;
    $supportedcurrencies = $wallet->get_possible_currencies();
    $settings->add(new admin_setting_configselect('enrol_wallet/currency', get_string('currency', 'enrol_wallet'),
                                            get_string('currency_help', 'enrol_wallet'), '', $supportedcurrencies));

    $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_wallet/status',
        get_string('status', 'enrol_wallet'), get_string('status_desc', 'enrol_wallet'), ENROL_INSTANCE_DISABLED,
        $options));

    $options = array(1 => get_string('yes'), 0 => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_wallet/newenrols',
        get_string('newenrols', 'enrol_wallet'), get_string('newenrols_desc', 'enrol_wallet'), 1, $options));

    $options = array(1 => get_string('yes'),
                     0 => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_wallet/groupkey',
        get_string('groupkey', 'enrol_wallet'), get_string('groupkey_desc', 'enrol_wallet'), 0, $options));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_wallet/roleid',
            get_string('defaultrole', 'enrol_wallet'), get_string('defaultrole_desc', 'enrol_wallet'), $student->id,
            $options));
    }

    $settings->add(new admin_setting_configduration('enrol_wallet/enrolperiod',
        get_string('enrolperiod', 'enrol_wallet'), get_string('enrolperiod_desc', 'enrol_wallet'), 0));

    $options = array(0 => get_string('no'), 1 => get_string('expirynotifyenroller', 'core_enrol'), 2 =>
        get_string('expirynotifyall', 'core_enrol'));
    $settings->add(new admin_setting_configselect('enrol_wallet/expirynotify',
        get_string('expirynotify', 'core_enrol'), get_string('expirynotify_help', 'core_enrol'), 0, $options));

    $settings->add(new admin_setting_configduration('enrol_wallet/expirythreshold',
        get_string('expirythreshold', 'core_enrol'), get_string('expirythreshold_help', 'core_enrol'), 86400, 86400));

    $options = array(0 => get_string('never'),
                     1800 * 3600 * 24 => get_string('numdays', '', 1800),
                     1000 * 3600 * 24 => get_string('numdays', '', 1000),
                     365 * 3600 * 24 => get_string('numdays', '', 365),
                     180 * 3600 * 24 => get_string('numdays', '', 180),
                     150 * 3600 * 24 => get_string('numdays', '', 150),
                     120 * 3600 * 24 => get_string('numdays', '', 120),
                     90 * 3600 * 24 => get_string('numdays', '', 90),
                     60 * 3600 * 24 => get_string('numdays', '', 60),
                     30 * 3600 * 24 => get_string('numdays', '', 30),
                     21 * 3600 * 24 => get_string('numdays', '', 21),
                     14 * 3600 * 24 => get_string('numdays', '', 14),
                     7 * 3600 * 24 => get_string('numdays', '', 7));
    $settings->add(new admin_setting_configselect('enrol_wallet/longtimenosee',
        get_string('longtimenosee', 'enrol_wallet'), get_string('longtimenosee_help', 'enrol_wallet'), 0, $options));

    $settings->add(new admin_setting_configtext('enrol_wallet/maxenrolled',
        get_string('maxenrolled', 'enrol_wallet'), get_string('maxenrolled_help', 'enrol_wallet'), 0, PARAM_INT));

    $settings->add(new admin_setting_configselect('enrol_wallet/sendcoursewelcomemessage',
            get_string('sendcoursewelcomemessage', 'enrol_wallet'),
            get_string('sendcoursewelcomemessage_help', 'enrol_wallet'),
            ENROL_SEND_EMAIL_FROM_COURSE_CONTACT,
            enrol_send_welcome_email_options()));
    // Adding default settings for awards program.
    $settings->add(new admin_setting_configcheckbox('enrol_wallet/awards',
                                                    get_string('awards', 'enrol_wallet'),
                                                    get_string('awards_help', 'enrol_wallet'),
                                                    0));

    $settings->add(new admin_setting_configtext_with_maxlength('enrol_wallet/awardcreteria',
                                                                get_string('awardcreteria', 'enrol_wallet'),
                                                                get_string('awardcreteria_help', 'enrol_wallet'),
                                                                0,
                                                                PARAM_NUMBER,
                                                                null,
                                                                2));

    $settings->add(new admin_setting_configtext('enrol_wallet/awardvalue', get_string('awardvalue', 'enrol_wallet'),
                                                    get_string('awardvalue_help', 'enrol_wallet'), 0, PARAM_NUMBER));

}
