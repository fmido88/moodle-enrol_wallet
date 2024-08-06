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
 * TODO describe file wallet
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once("$CFG->dirroot/enrol/wallet/locallib.php");

require_login(null, false);

$context = context_system::instance();
$frontpagecontext = context_course::instance(SITEID);

$url = new moodle_url('/enrol/wallet/wallet.php');
$PAGE->set_url($url);
$PAGE->set_context($context);

$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('frontpage');
$PAGE->set_secondary_navigation(true, true);

$tabnames = [
    'balance'      => get_string('balance', 'enrol_wallet'),
    'topup'        => get_string('topup', 'enrol_wallet'),
    'charge'       => get_string('charge', 'enrol_wallet'),
    'referral'     => get_string('referral', 'enrol_wallet'),
    'transactions' => get_string('transactions', 'enrol_wallet'),
    'transfer'     => get_string('transfer', 'enrol_wallet'),
    'offers'       => get_string('offers', 'enrol_wallet'),
    'admin'        => get_string('administration'),
];

if (!has_capability('enrol/wallet:creditdebit', $context)) {
    unset($tabnames['charge']);
}

$tabs = [];
$contents = [];
foreach ($tabnames as $key => $name) {
    switch ($key) {
        case 'balance':
            $text = enrol_wallet_display_current_user_balance();
            break;
        case 'topup':
            $text = enrol_wallet_display_topup_options();
            break;
        case 'charge':
            $text = enrol_wallet_display_charger_form();
            break;
        case 'transactions':
            $transactionurl = new moodle_url('/enrol/wallet/extra/transaction.php');
            $class = ['class' => 'btn btn-primary'];
            $text = html_writer::link($transactionurl, get_string('transactions_details', 'enrol_wallet'), $class);
            $table = new enrol_wallet\table\transactions($USER->id, (object)['userid' => $USER->id]);
            $table->define_baseurl($url->out()."#linktransactions");
            ob_start();
            $table->out(15, true);
            $text .= ob_get_clean();
            break;
        case 'referral':
            if ((bool)get_config( 'enrol_wallet', 'referral_enabled')) {
                ob_start();
                enrol_wallet\pages::process_referral_page();
                $text = ob_get_clean();
            } else {
                $text = '';
            }
            break;
        case 'transfer':
            ob_start();
            enrol_wallet\pages::process_transfer_page($url->out()."#transfer");
            $text = ob_get_clean();
            break;
        case 'offers':
            $text = enrol_wallet\pages::get_offers_content();
            break;
        case 'admin':
            $attributes = ['class' => 'btn btn-secondary'];
            $text = '';
            if (has_capability('moodle/site:config', $context)) {
                $configurl = new moodle_url('/admin/settings.php', ['section' => 'enrolsettingswallet']);
                $text .= html_writer::link($configurl, get_string('pluginconfig', 'enrol_wallet'), $attributes) . '<hr>';
            }

            if (has_all_capabilities(['enrol/wallet:config', 'enrol/wallet:manage'] , $frontpagecontext)) {
                $configurl = new moodle_url('/enrol/wallet/extra/conditionaldiscount.php');
                $text .= html_writer::link($configurl, get_string('conditionaldiscount_link_desc', 'enrol_wallet'), $attributes);
                $text .= '<hr>';
            }

            $coupons = enrol_wallet_display_coupon_urls();
            if (!empty($coupons)) {
                $links = explode('<br>', $coupons);
                $class = html_writer::attribute('class', 'btn btn-secondary');
                foreach ($links as $link) {
                    $text .= str_replace('<a', '<a ' . $class, $link) . '<hr>';
                }
            }

            if (has_capability('enrol/wallet:bulkedit', $context)) {
                $configurl = new moodle_url('/enrol/wallet/extra/bulkedit.php');
                $text .= html_writer::link($configurl, get_string('bulkeditor', 'enrol_wallet'), $attributes) . '<hr>';
                $configurl = new moodle_url('/enrol/wallet/extra/bulkinstances.php');
                $text .= html_writer::link($configurl, get_string('walletbulk', 'enrol_wallet'), $attributes) . '<hr>';
            }
            if (!empty($text)) {
                $text = html_writer::div($text, 'enrol-wallet-administration');
            }
            break;
        default:
            $text = '';
    }

    if (!empty($text)) {
        $link = '#link' . $key;
        $contents[$key] = $text;
        $tabitem = html_writer::start_tag('li', ['class' => 'nav-item', 'data-key' => $key]);
        $attributes = [
            'title'       => $name,
            'role'        => 'tab',
            'class'       => 'nav-link',
            'data-toggle' => 'tab',
            'data-text'   => $name,
        ];
        $tabitem .= html_writer::link($link, $name, $attributes);
        $tabitem .= html_writer::end_tag('li');
        $tabs[$key] = $tabitem;
    }
}

echo $OUTPUT->header();

echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs', 'role' => 'tablist']);
foreach ($tabs as $id => $tab) {
    echo $tab;
}
echo html_writer::end_tag('ul');

echo html_writer::start_div('tab-content mt-3');

foreach ($contents as $id => $value) {
    echo html_writer::div($value, 'tab-pane', ['id' => 'link'.$id, 'role' => 'tabpanel']);
}

echo html_writer::end_div();

echo $OUTPUT->footer();
