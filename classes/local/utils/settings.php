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

namespace enrol_wallet\local\utils;

use admin_root;
use admin_setting_configcheckbox;
use admin_setting_configduration;
use admin_setting_configempty;
use admin_setting_confightmleditor;
use admin_setting_configmulticheckbox;
use admin_setting_configmultiselect;
use admin_setting_configselect;
use admin_setting_configtext;
use admin_setting_configtext_with_maxlength;
use admin_setting_description;
use admin_setting_heading;
use admin_settingpage;
use context_system;
use core\output\html_writer;
use core\plugininfo\enrol;
use enrol_wallet\admin\admin_setting_wp_notice;
use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\urls\actions;
use enrol_wallet\local\urls\manage;
use enrol_wallet\local\wallet\balance;
use enrol_wallet_plugin;
use theme_boost_admin_settingspage_tabs;

/**
 * Load settings to admin root.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings {
    /**
     * The main settings page.
     * @var theme_boost_admin_settingspage_tabs
     */
    protected theme_boost_admin_settingspage_tabs $settings;

    /**
     * Enrol wallet main class.
     * @var enrol_wallet_plugin
     */
    protected enrol_wallet_plugin $plugin;

    /**
     * Prepage settings tabs.
     * @param enrol $plugininfo
     */
    public function __construct(
        /** @var enrol enrol_wallet plugin info */
        protected enrol $plugininfo
    ) {
        $this->settings = new theme_boost_admin_settingspage_tabs(
            $plugininfo->get_settings_section_name(),
            $plugininfo->displayname,
            'moodle/site:config',
            $plugininfo->is_enabled() === false
        );
        $this->plugin = new enrol_wallet_plugin();
    }

    /**
     * Load all settings and return them.
     * @param admin_root $admin
     *
     * @return ?theme_boost_admin_settingspage_tabs
     */
    public function load_all(admin_root $admin) {
        if (!$admin->fulltree) {
            return null;
        }

        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            if (preg_match('/load_(\w+)_settings_tab/', $method, $matches)) {
                $tab = $matches[1];
                $page = new admin_settingpage("enrol_wallet_{$tab}_tab", get_string($tab, 'enrol_wallet'));
                $this->{$method}($page);
                $this->settings->add_tab($page);
            }
        }

        return $this->settings;
    }

    /**
     * Main source and plugin settings.
     * @param  admin_settingpage $page
     * @return void
     */
    protected function load_source_settings_tab(admin_settingpage $page) {
        global $DB, $OUTPUT;
        // Adding an option to migrate credits, enrol instances and users enrollments from enrol_credit to enrol_wallet.
        $creditplugin = enrol_get_plugin('credit');

        if (!empty($creditplugin)) {
            $countcredit = 0;
            $countenrol = 0;
            $credits = $DB->get_records('enrol', ['enrol' => 'credit']);

            if (!empty($credits)) {
                $countcredit = \count($credits);
            }

            foreach ($credits as $credit) {
                $countenrol += $DB->count_records('user_enrolments', ['enrolid' => $credit->id]);
            }

            if (!empty($countenrol + $countcredit)) {
                $turl = actions::TRANSFORM_ENROL_CREDIT->url();

                $a = [
                    'enrol'  => $countenrol,
                    'credit' => $countcredit,
                ];
                $transformbutton = html_writer::link(
                    $turl,
                    get_string('transformation_credit_title', 'enrol_wallet'),
                    ['target' => '_blank', 'class' => 'btn btn-secondary']
                );
                $migration = $OUTPUT->box(get_string('transformation_credit_desc', 'enrol_wallet', $a) . '<br>' . $transformbutton);
                $page->add(new admin_setting_heading(
                    'enrol_wallet_migration',
                    '',
                    $migration
                ));
            }
        }

        // Wallet source settings.
        $page->add(new admin_setting_heading('enrol_wallet_walletsource', get_string('walletsource', 'enrol_wallet'), ''));
        // Adding choice between using wordpress (woowallet) of internal moodle wallet.
        $sources = [
            balance::WP     => get_string('sourcewordpress', 'enrol_wallet'),
            balance::MOODLE => get_string('sourcemoodle', 'enrol_wallet'),
        ];
        $sourcesetting = new admin_setting_configselect(
            'enrol_wallet/walletsource',
            get_string('walletsource', 'enrol_wallet'),
            get_string('walletsource_help', 'enrol_wallet'),
            balance::MOODLE,
            $sources
        );
        $page->add($sourcesetting);

        $notice = new admin_setting_wp_notice($sourcesetting->get_setting() == balance::MOODLE);

        $page->add($notice);

        $page->hide_if(
            'enrol_wallet/wp_notice',
            'enrol_wallet/walletsource',
            'eq',
            balance::MOODLE
        );

        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/wordpressloggins',
            get_string('wordpressloggins', 'enrol_wallet'),
            get_string('wordpressloggins_desc', 'enrol_wallet'),
            0
        ));
        $page->hide_if(
            'enrol_wallet/wordpressloggins',
            'enrol_wallet/walletsource',
            'eq',
            balance::MOODLE
        );
        // Define the WordPress site URL configuration setting.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/wordpress_url',
            get_string('wordpressurl', 'enrol_wallet'),
            get_string('wordpressurl_desc', 'enrol_wallet'),
            'https://example.com' // Default value for the WordPress site URL.
        ));
        $page->hide_if('enrol_wallet/wordpress_url', 'enrol_wallet/walletsource', 'eq', balance::MOODLE);
        // Secret shared key.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/wordpress_secretkey',
            get_string('wordpress_secretkey', 'enrol_wallet'),
            get_string('wordpress_secretkey_help', 'enrol_wallet'),
            'S0mTh1ng/123'
        ));
        $page->hide_if(
            'enrol_wallet/wordpress_secretkey',
            'enrol_wallet/walletsource',
            'eq',
            balance::MOODLE
        );
    }

    /**
     * General enrollment settings.
     * @param  admin_settingpage $page
     * @return void
     */
    protected function load_generalenrolsetting_settings_tab(admin_settingpage $page) {
        // General plugin settings.
        $page->add(new admin_setting_heading(
            'enrol_wallet_generalenrolsetting',
            get_string('generalenrolsetting', 'enrol_wallet'),
            ''
        ));

        // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
        // it describes what should happened when users are not supposed to be enrolled any more.
        $options = options::get_expire_actions_options();
        $page->add(new admin_setting_configselect(
            'enrol_wallet/expiredaction',
            get_string('expiredaction', 'enrol_wallet'),
            get_string('expiredaction_help', 'enrol_wallet'),
            ENROL_EXT_REMOVED_KEEP,
            $options
        ));
        // Expiry notifications.
        $options = [];

        for ($i = 0; $i < 24; $i++) {
            $options[$i] = $i;
        }
        $page->add(new admin_setting_configselect(
            'enrol_wallet/expirynotifyhour',
            get_string('expirynotifyhour', 'core_enrol'),
            '',
            6,
            $options
        ));
        // Options for multiple instances.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/allowmultipleinstances',
            get_string('allowmultiple', 'enrol_wallet'),
            get_string('allowmultiple_help', 'enrol_wallet'),
            1,
            PARAM_INT
        ));

        // Enable or disable unenrol self.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/unenrolselfenabled',
            get_string('unenrolselfenabled', 'enrol_wallet'),
            get_string('unenrolselfenabled_desc', 'enrol_wallet'),
            0
        ));
        // Not allowing unenrol self after certain period of time.
        $page->add(new admin_setting_configduration(
            'enrol_wallet/unenrollimitafter',
            get_string('unenrollimitafter', 'enrol_wallet'),
            get_string('unenrollimitafter_desc', 'enrol_wallet'),
            0,
            DAYSECS
        ));
        // Not allowing unenrol self before end of enrolment with this period.
        $page->add(new admin_setting_configduration(
            'enrol_wallet/unenrollimitbefor',
            get_string('unenrollimitbefor', 'enrol_wallet'),
            get_string('unenrollimitbefor_desc', 'enrol_wallet'),
            0
        ));
        // Repurchase.
        $page->add(new admin_setting_heading(
            'enrol_wallet_repurchase',
            get_string('repurchase', 'enrol_wallet'),
            get_string('repurchase_desc', 'enrol_wallet')
        ));
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/repurchase',
            get_string('repurchase', 'enrol_wallet'),
            '',
            0
        ));
        $page->add(new admin_setting_configtext(
            'enrol_wallet/repurchase_firstdis',
            get_string('repurchase_firstdis', 'enrol_wallet'),
            get_string('repurchase_firstdis_desc', 'enrol_wallet'),
            0,
            PARAM_INT
        ));
        $page->add(new admin_setting_configtext(
            'enrol_wallet/repurchase_seconddis',
            get_string('repurchase_seconddis', 'enrol_wallet'),
            get_string('repurchase_seconddis_desc', 'enrol_wallet'),
            0,
            PARAM_INT
        ));
    }

    /**
     * Dislay.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_display_settings_tab(admin_settingpage $page) {
        // Show the price on the enrol icon?
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/showprice',
            get_string('showprice', 'enrol_wallet'),
            get_string('showprice_desc', 'enrol_wallet'),
            0
        ));
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/mywalletnav',
            get_string('mywalletnav', 'enrol_wallet'),
            get_string('mywalletnav_desc', 'enrol_wallet'),
            '0'
        ));
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/frontpageoffers',
            get_string('frontpageoffers', 'enrol_wallet'),
            get_string('frontpageoffers_desc', 'enrol_wallet'),
            0
        ));

        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/offers_nav',
            get_string('offersnav', 'enrol_wallet'),
            get_string('offersnav', 'enrol_wallet'),
            '0'
        ));

        // Add low balance notification settings.
        $page->add(new admin_setting_heading(
            'enrol_wallet_notify',
            get_string('lowbalancenotify', 'enrol_wallet'),
            get_string('lowbalancenotify_desc', 'enrol_wallet')
        ));

        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/lowbalancenotice',
            get_string('lowbalancenotice', 'enrol_wallet'),
            '',
            0
        ));

        $page->add(new admin_setting_configtext(
            'enrol_wallet/noticecondition',
            get_string('noticecondition', 'enrol_wallet'),
            get_string('noticecondition_desc', 'enrol_wallet'),
            0,
            PARAM_INT
        ));
    }

    /**
     * Wallet balance and coupons settings.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_wallet_balance_coupons_settings_tab(admin_settingpage $page) {
        // Adding default payment account.
        $context = context_system::instance();
        $accounts = \core_payment\helper::get_payment_accounts_menu($context);

        if (empty($accounts)) {
            $alert = html_writer::span(get_string('noaccountsavilable', 'payment'), 'alert alert-warning');
            $page->add(new admin_setting_configempty('enrol_wallet/emptypaymentaccount', '', $alert));
            $accounts = [0 => get_string('noaccount', 'enrol_wallet')];
        } else {
            $accounts = [0 => get_string('noaccount', 'enrol_wallet')] + $accounts;
        }
        $paymentaccount = new admin_setting_configselect(
            'enrol_wallet/paymentaccount',
            get_string('paymentaccount', 'payment'),
            get_string('paymentaccount_help', 'enrol_wallet'),
            0,
            $accounts
        );
        $page->add($paymentaccount);

        // Add custom currency.
        $page->add(new admin_setting_configtext_with_maxlength(
            'enrol_wallet/customcurrencycode',
            get_string('customcurrencycode', 'enrol_wallet'),
            get_string('customcurrencycode_desc', 'enrol_wallet'),
            '',
            PARAM_TEXT,
            5,
            3
        ));
        $page->add(new admin_setting_configtext(
            'enrol_wallet/customcurrency',
            get_string('customcurrency', 'enrol_wallet'),
            get_string('customcurrency_desc', 'enrol_wallet'),
            ''
        ));
        $page->hide_if('enrol_wallet/customcurrency', 'enrol_wallet/paymentaccount', 'neq', '0');
        $page->hide_if('enrol_wallet/customcurrencycode', 'enrol_wallet/paymentaccount', 'neq', '0');

        // Add default currency.
        $supportedcurrencies = options::get_possible_currencies($paymentaccount->get_setting());
        $currencysettigs = new admin_setting_configselect(
            'enrol_wallet/currency',
            get_string('currency', 'enrol_wallet'),
            get_string('currency_help', 'enrol_wallet'),
            '',
            $supportedcurrencies
        );

        $page->add($currencysettigs);

        // Enable category balance.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/catbalance',
            'Category balance',
            'Enable category balance',
            1
        ));
        $page->hide_if('enrol_wallet/catbalance', 'enrol_wallet/walletsource', 'eq', balance::WP);
        // Adding options to enable and disable coupons.
        $choices = coupons::get_coupons_options();
        $page->add(new admin_setting_configmulticheckbox(
            'enrol_wallet/coupons',
            get_string('couponstype', 'enrol_wallet'),
            get_string('couponstype_help', 'enrol_wallet'),
            [],
            $choices
        ));
    }

    /**
     * Refunds settings.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_refund_settings_tab(admin_settingpage $page) {
        // Auto Refunding to wallet after un-enrol.
        $page->add(new admin_setting_heading(
            'enrol_wallet_unenrolrefund',
            get_string('unenrolrefund_head', 'enrol_wallet'),
            get_string('unenrolrefund_head_desc', 'enrol_wallet')
        ));
        // Enabling refund upon unenrol.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/unenrolrefund',
            get_string('unenrolrefund', 'enrol_wallet'),
            get_string('unenrolrefund_desc', 'enrol_wallet'),
            0
        ));
        // Unenrol refund grace period.
        $page->add(new admin_setting_configduration(
            'enrol_wallet/unenrolrefundperiod',
            get_string('unenrolrefundperiod', 'enrol_wallet'),
            get_string('unenrolrefundperiod_desc', 'enrol_wallet'),
            '0',
            DAYSECS
        ));
        // Refunding after enrolment fee.
        $page->add(new admin_setting_configtext_with_maxlength(
            'enrol_wallet/unenrolrefundfee',
            get_string('unenrolrefundfee', 'enrol_wallet'),
            get_string('unenrolrefundfee_desc', 'enrol_wallet'),
            0,
            PARAM_INT,
            null,
            2
        ));
        // Refund upon unenrol policy.
        $page->add(new admin_setting_confightmleditor(
            'enrol_wallet/unenrolrefundpolicy',
            get_string('unenrolrefundpolicy', 'enrol_wallet'),
            get_string('unenrolrefundpolicy_help', 'enrol_wallet'),
            get_string('unenrolrefundpolicy_default', 'enrol_wallet')
        ));

        // Manual Refunding.
        $page->add(new admin_setting_heading(
            'enrol_wallet_refund',
            get_string('refundpolicy', 'enrol_wallet'),
            ''
        ));
        // Refund policy.
        $page->add(new admin_setting_confightmleditor(
            'enrol_wallet/refundpolicy',
            get_string('refundpolicy', 'enrol_wallet'),
            get_string('refundpolicy_help', 'enrol_wallet'),
            get_string('refundpolicy_default', 'enrol_wallet')
        ));
        // Enable refunding.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/enablerefund',
            get_string('enablerefund', 'enrol_wallet'),
            get_string('enablerefund_desc', 'enrol_wallet'),
            1
        ));
        // Refunding grace period.
        $page->add(new admin_setting_configduration(
            'enrol_wallet/refundperiod',
            get_string('refundperiod', 'enrol_wallet'),
            get_string('refundperiod_desc', 'enrol_wallet'),
            14 * DAYSECS,
            DAYSECS
        ));
    }

    /**
     * Topup options.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_topup_options_settings_tab(admin_settingpage $page) {
        $context = context_system::instance();
        // Todo: add a note that payment topup option will be available when
        // choose a payment gateway and currency.
        // Add option to display users with capabiliy to credit others on the site.
        $page->add(new admin_setting_heading(
            'enrol_wallet_tellermen',
            get_string('tellermen_heading', 'enrol_wallet'),
            get_string('tellermen_heading_desc', 'enrol_wallet')
        ));
        $ufselects = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $tellermen = get_users_by_capability($context, 'enrol/wallet:creditdebit', $ufselects);
        $tellermen += get_admins();
        $contactinfo = [];

        foreach ($tellermen as $user) {
            $tellermen[$user->id] = fullname($user);
            $contactinfo[] = new admin_setting_confightmleditor(
                "enrol_wallet/teller_{$user->id}",
                get_string('tellercontactinfo', 'enrol_wallet', $tellermen[$user->id]),
                get_string('tellercontactinfo_desc', 'enrol_wallet'),
                '',
                PARAM_RAW_TRIMMED
            );
        }

        $page->add(new admin_setting_configmultiselect(
            'enrol_wallet/tellermen',
            get_string('tellermen', 'enrol_wallet'),
            get_string('tellermen_desc', 'enrol_wallet'),
            [],
            $tellermen
        ));

        foreach ($contactinfo as $cinfo) {
            $page->add($cinfo);
            // Todo: find a way to add this using js and ajax to be instantenuously apear
            // when the admin selects a tellerman.
        }
    }

    /**
     * Transfer balance.
     * @param  admin_settingpage $page
     * @return void
     */
    protected function load_transfer_settings_tab(admin_settingpage $page) {
        // Adding settings for transfer credit for user to another.
        $page->add(new admin_setting_heading(
            'enrol_wallet_transfer',
            get_string('transfer', 'enrol_wallet'),
            get_string('transfer_desc', 'enrol_wallet')
        ));
        // Enable or disable transfer.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/transfer_enabled',
            get_string('transfer_enabled', 'enrol_wallet'),
            get_string('transfer_enabled_desc', 'enrol_wallet'),
            0
        ));
        // Min transfer amount.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/mintransfer',
            get_string('mintransfer_config', 'enrol_wallet'),
            get_string('mintransfer_config_desc', 'enrol_wallet'),
            0,
            PARAM_FLOAT
        ));
        // Transfer fee.
        $page->add(new admin_setting_configtext_with_maxlength(
            'enrol_wallet/transferpercent',
            get_string('transferpercent', 'enrol_wallet'),
            get_string('transferpercent_desc', 'enrol_wallet'),
            0,
            PARAM_INT,
            null,
            2
        ));

        $options = [
            'sender'   => get_string('sender', 'enrol_wallet'),
            'receiver' => get_string('receiver', 'enrol_wallet'),
        ];
        $page->add(new admin_setting_configselect(
            'enrol_wallet/transferfee_from',
            get_string('transferfee_from', 'enrol_wallet'),
            get_string('transferfee_from_desc', 'enrol_wallet'),
            'sender',
            $options
        ));
    }

    /**
     * Wallet balance dicounts.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_wallet_discounts_settings_tab(admin_settingpage $page) {
        // Add settings for conditional discount.
        // Enable conditional discount.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/conditionaldiscount_apply',
            get_string('conditionaldiscount_apply', 'enrol_wallet'),
            get_string('conditionaldiscount_apply_help', 'enrol_wallet'),
            0
        ));
        // Link to conditional discount page.
        $discountspage = manage::CONDITIONAL_DISCOUNT->url();
        $conditionaldiscount = html_writer::link($discountspage, get_string('conditionaldiscount_link_desc', 'enrol_wallet'));
        $page->add(new admin_setting_description(
            'conditionaldiscount',
            get_string('conditionaldiscount', 'enrol_wallet'),
            $conditionaldiscount
        ));
    }

    /**
     * Purchace discounts.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_purchase_discounts_settings_tab(admin_settingpage $page) {
        global $DB;
        $behaviors = options::get_discount_behavior_options();
        $page->add(new admin_setting_configselect(
            'enrol_wallet/discount_behavior',
            get_string('discount_behavior', 'enrol_wallet'),
            get_string('discount_behavior_desc', 'enrol_wallet'),
            instance::B_SEQ,
            $behaviors
        ));
        // Get the custom profile fields, to select the discount field.
        $menu = $DB->get_records_menu('user_info_field', null, 'id ASC', 'id, name');
        $menu[0] = get_string('not_set', 'enrol_wallet');

        ksort($menu);
        // Adding select menu for custom field related to discounts.
        $page->add(new admin_setting_configselect(
            'enrol_wallet/discount_field',
            get_string('profile_field_map', 'enrol_wallet'),
            get_string('profile_field_map_help', 'enrol_wallet'),
            0,
            $menu
        ));
    }

    /**
     * Free gifting.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_freegifts_settings_tab(admin_settingpage $page) {
        // Adding settings for applying cashback.
        $page->add(new admin_setting_heading(
            'enrol_wallet_cashback',
            get_string('walletcashback', 'enrol_wallet'),
            get_string('walletcashback_desc', 'enrol_wallet')
        ));
        // Enable cashback.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/cashback',
            get_string('cashbackenable', 'enrol_wallet'),
            get_string('cashbackenable_desc', 'enrol_wallet'),
            0
        ));
        // Cashback percentage value.
        $page->add(new admin_setting_configtext_with_maxlength(
            'enrol_wallet/cashbackpercent',
            get_string('cashbackpercent', 'enrol_wallet'),
            get_string('cashbackpercent_help', 'enrol_wallet'),
            0,
            PARAM_INT,
            null,
            2
        ));

        // Adding settings for gifting users upon new creation.
        $page->add(new admin_setting_heading(
            'enrol_wallet_newusergift',
            get_string('newusergift', 'enrol_wallet'),
            get_string('newusergift_desc', 'enrol_wallet')
        ));
        // Enable new user gifts.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/newusergift',
            get_string('newusergift_enable', 'enrol_wallet'),
            get_string('newusergift_enable_help', 'enrol_wallet'),
            0
        ));
        // New user gift value.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/newusergiftvalue',
            get_string('giftvalue', 'enrol_wallet'),
            get_string('giftvalue_help', 'enrol_wallet'),
            0,
            PARAM_FLOAT
        ));
        // Awards.
        $page->add(new admin_setting_heading(
            'enrol_wallet_awards',
            get_string('awards', 'enrol_wallet'),
            ''
        ));
        // Enable or disable awards across the whole site.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/awardssite',
            get_string('awardssite', 'enrol_wallet'),
            get_string('awardssite_help', 'enrol_wallet'),
            1
        ));
    }

    /**
     * Borrow.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_borrow_settings_tab(admin_settingpage $page) {
        // Adding settings for borrowing credit.
        $page->add(new admin_setting_heading(
            'enrol_wallet_borrow',
            get_string('borrow', 'enrol_wallet'),
            get_string('borrow_desc', 'enrol_wallet')
        ));
        // Enable Borrow.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/borrowenable',
            get_string('borrow_enable', 'enrol_wallet'),
            get_string('borrow_enable_help', 'enrol_wallet'),
            0
        ));
        // Eligibility for borrowing as number of transactions.
        $page->add(new admin_setting_configtext_with_maxlength(
            'enrol_wallet/borrowtrans',
            get_string('borrow_trans', 'enrol_wallet'),
            get_string('borrow_trans_help', 'enrol_wallet'),
            20,
            PARAM_INT,
            null,
            4
        ));
        // Eligibility for borrowing as period for the selected transaction.
        $page->add(new admin_setting_configduration(
            'enrol_wallet/borrowperiod',
            get_string('borrow_period', 'enrol_wallet'),
            get_string('borrow_period_help', 'enrol_wallet'),
            30 * DAYSECS,
            DAYSECS
        ));
    }

    /**
     * Referral.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_referral_settings_tab(admin_settingpage $page) {
        // Adding settings for referral program.
        $page->add(new admin_setting_heading(
            'enrol_wallet_referral',
            get_string('referral_program', 'enrol_wallet'),
            get_string('referral_program_desc', 'enrol_wallet')
        ));
        // Enable referrals.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/referral_enabled',
            get_string('referral_enabled', 'enrol_wallet'),
            '',
            0
        ));
        // Referral value.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/referral_amount',
            get_string('referral_amount', 'enrol_wallet'),
            get_string('referral_amount_desc', 'enrol_wallet'),
            0,
            PARAM_FLOAT
        ));
        // Maximum Referal times.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/referral_max',
            get_string('referral_max', 'enrol_wallet'),
            get_string('referral_max_desc', 'enrol_wallet'),
            0,
            PARAM_INT
        ));
        $enrolmethods = array_keys(enrol_get_plugins(false));
        $options = array_combine($enrolmethods, $enrolmethods);
        $page->add(new admin_setting_configmultiselect(
            'enrol_wallet/referral_plugins',
            get_string('referral_plugins', 'enrol_wallet'),
            get_string('referral_plugins_desc', 'enrol_wallet'),
            ['wallet'],
            $options
        ));
    }

    /**
     * Restrictions.
     * @param  admin_settingpage $page
     * @return void
     */
    protected function load_restriction_settings_tab(admin_settingpage $page) {
        // Availability Conditions.
        $page->add(new admin_setting_heading(
            'enrol_wallet_conditions',
            get_string('restrictions', 'enrol_wallet'),
            get_string('restrictions_desc', 'enrol_wallet')
        ));
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/restrictionenabled',
            get_string('restrictionenabled', 'enrol_wallet'),
            get_string('restrictionenabled_desc', 'enrol_wallet'),
            0
        ));
        $pluginmanager = \core_plugin_manager::instance();

        $availabilities = $pluginmanager->get_enabled_plugins('availability');
        $options = [];

        foreach ($availabilities as $avplugin => $p) {
            if (in_array($avplugin, ['wallet', 'group', 'grouping', 'maxviews'])) {
                continue;
            }

            if (empty($p->rootdir) || !check_dir_exists($p->rootdir, false, false)) {
                continue;
            }
            $options[$avplugin] = get_string('title', "availability_$avplugin");
        }

        if (!empty($options)) {
            $page->add(new admin_setting_configmultiselect(
                'enrol_wallet/availability_plugins',
                get_string('availability_plugins', 'enrol_wallet'),
                get_string('availability_plugins_desc', 'enrol_wallet'),
                [],
                $options
            ));
        }
    }

    /**
     * Default instance values.
     * @param admin_settingpage $page
     *
     * @return void
     */
    protected function load_default_instance_settings_tab(admin_settingpage $page) {
        // Enrol instance defaults.
        $page->add(new admin_setting_heading(
            'enrol_wallet_defaults',
            get_string('enrolinstancedefaults', 'admin'),
            get_string('enrolinstancedefaults_desc', 'admin')
        ));
        // Adding wallet instance automatically upon course creation.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/defaultenrol',
            get_string('defaultenrol', 'enrol'),
            get_string('defaultenrol_desc', 'enrol'),
            1
        ));

        // Todo: Add a note that default currency and payment account placed in the
        // wallet page.
        // Is instance enabled.
        $options = options::get_status_options();
        $page->add(new admin_setting_configselect(
            'enrol_wallet/status',
            get_string('status', 'enrol_wallet'),
            get_string('status_desc', 'enrol_wallet'),
            ENROL_INSTANCE_ENABLED,
            $options
        ));
        // Allow users to enrol into new courses by default.
        $options = [
                        1 => get_string('yes'),
                        0 => get_string('no'),
                    ];
        $page->add(new admin_setting_configselect(
            'enrol_wallet/newenrols',
            get_string('newenrols', 'enrol_wallet'),
            get_string('newenrols_desc', 'enrol_wallet'),
            1,
            $options
        ));

        // Default role.
        if (!during_initial_install()) {
            $options = get_default_enrol_roles(context_system::instance());
            $student = get_archetype_roles('student');
            $student = reset($student);
            $page->add(new admin_setting_configselect(
                'enrol_wallet/roleid',
                get_string('defaultrole', 'enrol_wallet'),
                get_string('defaultrole_desc', 'enrol_wallet'),
                $student->id,
                $options
            ));
        }
        // Enrolment period.
        $page->add(new admin_setting_configduration(
            'enrol_wallet/enrolperiod',
            get_string('enrolperiod', 'enrol_wallet'),
            get_string('enrolperiod_desc', 'enrol_wallet'),
            0
        ));
        // Expiry notification.
        $options = options::get_expirynotify_options();
        $page->add(new admin_setting_configselect(
            'enrol_wallet/expirynotify',
            get_string('expirynotify', 'core_enrol'),
            get_string('expirynotify_help', 'core_enrol'),
            0,
            $options
        ));
        // Expiry threshold.
        $page->add(new admin_setting_configduration(
            'enrol_wallet/expirythreshold',
            get_string('expirythreshold', 'core_enrol'),
            get_string('expirythreshold_help', 'core_enrol'),
            86400,
            86400
        ));
        // Un-enrol inactive duration.
        $options = options::get_longtimenosee_options();
        $page->add(new admin_setting_configselect(
            'enrol_wallet/longtimenosee',
            get_string('longtimenosee', 'enrol_wallet'),
            get_string('longtimenosee_help', 'enrol_wallet'),
            0,
            $options
        ));
        // Max enrolled users.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/maxenrolled',
            get_string('maxenrolled', 'enrol_wallet'),
            get_string('maxenrolled_help', 'enrol_wallet'),
            0,
            PARAM_INT
        ));
        // Send welcome message.
        $weloptions = options::get_send_welcome_email_option();
        $page->add(new admin_setting_configselect(
            'enrol_wallet/sendcoursewelcomemessage',
            get_string('sendcoursewelcomemessage', 'enrol_wallet'),
            get_string('sendcoursewelcomemessage_help', 'enrol_wallet'),
            ENROL_SEND_EMAIL_FROM_COURSE_CONTACT,
            $weloptions
        ));
        // Adding default settings for awards program.
        // Enable awards by default for instances.
        $page->add(new admin_setting_configcheckbox(
            'enrol_wallet/awards',
            get_string('awards', 'enrol_wallet'),
            get_string('awards_help', 'enrol_wallet'),
            0
        ));
        // Awards conditions.
        $page->add(new admin_setting_configtext_with_maxlength(
            'enrol_wallet/awardcreteria',
            get_string('awardcreteria', 'enrol_wallet'),
            get_string('awardcreteria_help', 'enrol_wallet'),
            0,
            PARAM_FLOAT,
            null,
            2
        ));
        // Award value.
        $page->add(new admin_setting_configtext(
            'enrol_wallet/awardvalue',
            get_string('awardvalue', 'enrol_wallet'),
            get_string('awardvalue_help', 'enrol_wallet'),
            0,
            PARAM_FLOAT
        ));

        $page->hide_if('enrol_wallet/awards', 'enrol_wallet/awardssite');
        $page->hide_if('enrol_wallet/awardcreteria', 'enrol_wallet/awardssite');
        $page->hide_if('enrol_wallet/awardvalue', 'enrol_wallet/awardssite');
        $page->hide_if('enrol_wallet/awardcreteria', 'enrol_wallet/awards');
        $page->hide_if('enrol_wallet/awardvalue', 'enrol_wallet/awards');
    }
}
