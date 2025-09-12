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

namespace enrol_wallet;
use core\hook\output\before_standard_top_of_body_html_generation;
use core\hook\output\before_footer_html_generation;
use core\hook\navigation\primary_extend;
use core\output\pix_icon;
use enrol_wallet\local\discounts\offers;
use enrol_wallet\local\urls\pages;
use enrol_wallet\local\wallet\balance;
use navigation_node;

/**
 * Class hooks_callbacks
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hooks_callbacks {
    /**
     * Hook callback to inject price on the enrollment icon.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     * @return void
     */
    public static function show_price(before_footer_html_generation $hook) {
        if (during_initial_install()) {
            return;
        }

        $showprice = (bool)get_config('enrol_wallet', 'showprice');
        if ($showprice) {
            $page = $hook->renderer->get_page();
            $page->requires->js_call_amd('enrol_wallet/overlyprice', 'init');
        }
    }

    /**
     * Hook callback to display notice about low balance.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     * @return void
     */
    public static function low_balance_warning(before_standard_top_of_body_html_generation $hook) {
        // Don't display notice for guests or logged out.
        if (!isloggedin() || isguestuser() || during_initial_install()) {
            return;
        }

        // Check if notice is enabled.
        $notice = get_config('enrol_wallet', 'lowbalancenotice');
        if (empty($notice)) {
            return;
        }

        // Check the conditions.
        $condition = get_config('enrol_wallet', 'noticecondition');

        $op = new balance();
        $balance = $op->get_total_balance();
        if ($balance <= (int)$condition) {
            // Display the warning.
            \core\notification::warning(get_string('lowbalancenotification', 'enrol_wallet', $balance));
        }
    }

    /**
     * Hook callback to extend primary navigation tabs.
     *
     * @param primary_extend $hook
     * @return void
     */
    public static function primary_navigation_tabs(primary_extend $hook) {
        if (during_initial_install()) {
            return;
        }
        self::add_my_wallet($hook);
        self::add_offers($hook);
    }

    /**
     * Add my wallet to primary navigation.
     * @param primary_extend $hook
     * @return void
     */
    public static function add_my_wallet(primary_extend $hook) {
        $enabled = (bool)get_config('enrol_wallet', 'mywalletnav');
        if (empty($enabled)) {
            return;
        }

        $alt = get_string('mywallet', 'enrol_wallet');
        $pix = new pix_icon('wallet', $alt, 'enrol_wallet');
        $url = pages::WALLET->url();

        $primaryview = $hook->get_primaryview();
        $primaryview->add($alt, $url, navigation_node::TYPE_CUSTOM, null, null, $pix);
    }
    /**
     * Add offers to primary navigation.
     *
     * @param \core\hook\navigation\primary_extend $hook
     * @return void
     */
    public static function add_offers(primary_extend $hook) {
        $enabled = (bool)get_config('enrol_wallet', 'offers_nav');
        if (empty($enabled)) {
            return;
        }

        $alt = get_string('offers', 'enrol_wallet');
        $pix = new pix_icon('offer', $alt, 'enrol_wallet');
        $url = pages::OFFERS->url();

        $primaryview = $hook->get_primaryview();
        $primaryview->add($alt, $url, navigation_node::TYPE_CUSTOM, null, null, $pix);
    }
}
