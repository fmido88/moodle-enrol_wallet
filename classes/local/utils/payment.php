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

use core\output\html_writer;
use core\url;
use core_payment\account;
use core_payment\helper;
use core_plugin_manager;

/**
 * Class payment
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment {
    /**
     * Get payment button attributes.
     *
     * @param int $itemid
     * @param float $cost
     * @param string $description
     * @param ?url $successurl
     * @param string $paymentarea
     * @param string $classes
     * @return array{class:            string,
     *               data-action:      string,
     *               data-component:   string,
     *               data-cost:        float,
     *               data-description: string,
     *               data-itemid:      int,
     *               data-paymentarea: string,
     *               data-successurl:  string,
     *               id:               string,
     *               type:             string}
     */
    public static function get_payment_button_attributes(
        int $itemid,
        float $cost,
        string $description,
        ?url $successurl = null,
        string $paymentarea = "wallettopup",
        string $classes = "btn-primary"
    ): array {

        if ($successurl === null) {
            helper::get_success_url('enrol_wallet', $paymentarea, $itemid);
        }

        return [
            'class'            => "btn $classes",
            'type'             => "button",
            'id'               => html_writer::random_id("gateways-modal-trigger-"),
            'data-action'      => "core_payment/triggerPayment",
            'data-component'   => "enrol_wallet",
            'data-paymentarea' => $paymentarea,
            'data-itemid'      => $itemid,
            'data-cost'        => $cost,
            'data-successurl'  => $successurl->out(false),
            'data-description' => s($description),
        ];
    }

    /**
     * Init payment gateways modal js.
     * Todo: use our own to exclude paygw_wallet.
     * @return void
     */
    public static function init_payment_js() {
        global $PAGE;
        $PAGE->requires->js_call_amd('core_payment/gateways_modal', 'init');
    }

    /**
     * Check if the payment account is valid or not.
     * @param int $accountid
     * @return bool
     */
    public static function is_valid_account($accountid): bool {
        if (empty($accountid) || !is_number($accountid) || $accountid < 0) {
            return false;
        }

        if (!class_exists('\core_payment\account')) {
            return false;
        }

        $account = new account($accountid);
        if (!$account->is_available() || !$account->is_valid()) {
            return false;
        }

        $gateways = $account->get_gateways(true);
        if (count($gateways) > 1) {
            return true;
        }

        $gatewaysnames = array_keys($gateways);
        if (count($gatewaysnames) === 1 && 'wallet' === $gatewaysnames[0]) {
            // This means that the only gateway available is paygw_wallet
            // which cannot be used with wallet.
            return false;
        }

        return true;
    }

    /**
     * Check if the currency is a valid currency and not a custom currency.
     * @param string $currency
     * @return bool
     */
    public static function is_valid_currency($currency, $sensitive = true): bool {
        if (empty($currency) || !is_string($currency) || strlen($currency) !== 3) {
            return false;
        }

        if (!$sensitive) {
            $currency = strtoupper($currency);
        }

        $plugins = core_plugin_manager::instance()->get_enabled_plugins('paygw');
        foreach ($plugins as $plugin) {
            if ($plugin === 'wallet') {
                continue;
            }

            $classname = '\paygw_' . $plugin . '\gateway';

            $currencies = component_class_callback($classname, 'get_supported_currencies', [], []);
            if (in_array($currency, $currencies)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if topup is available.
     * @return bool
     */
    public static function is_topup_available(): bool {
        $currency = get_config('enrol_wallet', 'currency');
        $accountid = get_config('enrol_wallet', 'paymentaccount');
        return self::is_valid_currency($currency) && self::is_valid_account($accountid);
    }
}
