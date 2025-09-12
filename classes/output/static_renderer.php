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

namespace enrol_wallet\output;

use context;
use core\context\course;
use core\context\system;
use core\output\html_writer;
use core\url;
use enrol_wallet\form\charger_form;
use enrol_wallet\local\urls\manage;
use enrol_wallet\local\urls\reports;
use enrol_wallet\local\wallet\balance;

/**
 * Render parts statically.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_renderer {
    /**
     * Display the form for charging other users.
     * @return string|void
     */
    public static function charger_form() {
        if (!has_capability('enrol/wallet:creditdebit', system::instance())) {
            return '';
        }

        $action = manage::CHARGE->url(['return' => qualified_me()]);
        $mform = new charger_form($action, null, 'get');
        $result = optional_param('result', '', PARAM_RAW);

        $output = format_text($result);
        $output .= $mform->render();

        return $output;
    }

    /**
     * Coupons urls.
     * @param bool $array if set to true will return array of urls with labels.
     * @return array{label: string, url: url[]}|string
     */
    public static function coupons_urls($array = false) {
        if (get_config('enrol_wallet', 'walletsource') != balance::MOODLE) {
            return $array ? [] : '';
        }

        if (!isloggedin() || isguestuser()) {
            return $array ? [] : '';
        }

        $context = system::instance();
        $canviewcoupons = has_capability('enrol/wallet:viewcoupon', $context);
        $cangeneratecoupon = has_capability('enrol/wallet:createcoupon', $context);
        $caneditcoupon = has_capability('enrol/wallet:editcoupon', $context);
        $out = [];
        // Check if the user can view and generate coupons.
        if ($canviewcoupons) {
            $out[] = [
                'url'   => reports::COUPONS->out(),
                'label' => get_string('coupon_table', 'enrol_wallet'),
            ];

            $out[] = [
                'url'   => reports::COUPONS_USAGE->out(),
                'label' => get_string('coupon_usage', 'enrol_wallet'),
            ];

            if ($cangeneratecoupon) {
                $out[] = [
                    'url' => manage::GENERATE_COUPON->out(),
                    'label' => get_string('coupon_generation', 'enrol_wallet'),
                ];
            }

            if ($cangeneratecoupon && $caneditcoupon) {
                $out[] = [
                    'url' => manage::UPLOAD_COUPONS->out(),
                    'label' => get_string('upload_coupons', 'enrol_wallet'),
                ];
            }
        }

        if ($array) {
            return $out;
        }

        $out = array_map(function($entry) {
            return html_writer::link($entry['url'], $entry['label']);
        }, $out);

        return implode("<br>", $out);
    }

    /**
     * Wallet administration links.
     * @param ?context $coursecontext
     * @return array{adminlinks: array{url: url, label: string}, isadmintabs: bool}
     */
    public static function get_admins_links(?context $coursecontext = null) {
        // phpcs:disable moodle.PHP.ForbiddenGlobalUse.BadGlobal
        global $PAGE;
        $context = system::instance();

        if (!$coursecontext) {
            if (isset($PAGE->context) && $PAGE->context->level >= course::LEVEL) {
                $coursecontext = $PAGE->context;
            } else {
                $coursecontext = course::instance(SITEID);
            }
        }
        // phpcs:enable moodle.PHP.ForbiddenGlobalUse.BadGlobal
        $links = [];
        if (has_capability('moodle/site:config', $context)) {
            $links[] = [
                'url'   => new url('/admin/settings.php', ['section' => 'enrolsettingswallet']),
                'label' => get_string('pluginconfig', 'enrol_wallet'),
            ];
        }

        if (has_all_capabilities(['enrol/wallet:config', 'enrol/wallet:manage'] , $context)) {
            $links[] = [
                'url'   => manage::CONDITIONAL_DISCOUNT->url(),
                'label' => get_string('conditionaldiscount_link_desc', 'enrol_wallet'),
            ];
        }

        $links = array_merge($links, self::coupons_urls(true));

        if (has_capability('enrol/wallet:bulkedit', $context)) {
            $links[] = [
                'url' => manage::BULKENROLMENTS->url(),
                'label' => get_string('bulkeditor', 'enrol_wallet'),
            ];

            $links[] = [
                'url'   => manage::BULKINSTANCES->url(),
                'label' => get_string('walletbulk', 'enrol_wallet'),
            ];
        }

        return [
            'adminlinks'   => $links,
            'isadmintabs'  => !empty($links),
        ];
    }
}
