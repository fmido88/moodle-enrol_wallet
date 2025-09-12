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

use core\output\templatable;
use core\output\renderable;
use core\output\renderer_base;
use core\url;
use core_user;
use enrol_wallet\local\config;
use enrol_wallet\local\coupons\coupons;
use enrol_wallet\form\applycoupon_form;
use enrol_wallet\local\discounts\discount_rules;
use enrol_wallet\local\urls\actions;
use enrol_wallet\local\urls\pages;
use enrol_wallet\local\utils\payment;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/enrol/wallet/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

/**
 * Prepare all topup options for output.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topup_options implements templatable, renderable {
    /**
     * User object.
     * @var stdClass
     */
    public readonly stdClass $user;
    /**
     * If false don't display anything.
     * @var bool
     */
    public bool $display = true;
    /**
     * Prepare all available topup options for rendering.
     * @return void
     */
    public function __construct() {
        global $USER;

        if (isloggedin() && !isguestuser()) {
            $this->user = clone $USER;
        } else if ($username = optional_param('s', null, PARAM_USERNAME)) {
            // Only used in auth_wallet.
            $user = get_complete_user_data('username', $username);
            if ($user) {
                $this->user = $user;
            }
        }

        if (!isset($this->user)) {
            $this->display = false;
            return;
        }
    }

    /**
     * Policy warning template context.
     * @return array{haswarn: bool, policy: string, topup: bool}|array{haswarn: bool}
     */
    public function export_policy_warn() {
        $policy = config::make()->refundpolicy;
        if (empty($policy)) {
            return ['haswarn' => false];
        }

        return [
            'haswarn' => true,
            'topup'   => true,
            'policy'  => format_text($policy),
        ];
    }

    /**
     * Get a fake instance for topup and coupon forms.
     * @return stdClass
     */
    protected function get_mocked_instance() {
        // Set the data we want to send to forms.
        $instance = new stdClass;
        $data = new stdClass;

        $config = config::make();
        $instance->id         = 0;
        $instance->courseid   = SITEID;
        $instance->currency   = $config->currency;
        $instance->customint1 = $config->paymentaccount;

        $data->instance = $instance;
        $data->user     = $this->user;

        return $data;
    }

    /**
     * Get fast topup bundles template context.
     * @return array{content: string, key: string, label: string}|null
     */
    public function get_bundles() {
        if (!payment::is_topup_available()) {
            return null;
        }

        $bundles = new bundles();
        $renderer = helper::get_wallet_renderer();
        $out = $renderer->render($bundles);
        if (empty(trim($out ?? ''))) {
            return null;
        }

        return [
            'content' => $out,
            'label'   => get_string('bundle_value', 'enrol_wallet'),
            'key'     => 'bundles',
        ];
    }

    /**
     * Get the topup form content and tab parameters.
     * @return array{content: string, key: string, label: string}|null
     */
    public function get_topup_form() {
        if (!payment::is_topup_available()) {
            return null;
        }

        $data = $this->get_mocked_instance();

        $topupurl = pages::TOPUP->url();
        $topupform = new \enrol_wallet\form\topup_form($topupurl, $data);

        return [
            'content' => $topupform->render(),
            'label'   => get_string('topupbypayment', 'enrol_wallet'),
            'key'     => 'topupform',
        ];
    }

    /**
     * Get the coupons topup form.
     * @return array{content: string, key: string, label: string}|null
     */
    public function get_coupon_topup() {
        // Check if fixed coupons enabled.
        $enabledcoupons = coupons::get_enabled();
        $intersect = array_intersect($enabledcoupons, [coupons::ALL, coupons::FIXED, coupons::CATEGORY]);
        if (empty($intersect)) {
            return null;
        }

        $data = $this->get_mocked_instance();
        // Display the coupon form to enable user to topup wallet using fixed coupon.
        $couponaction = actions::APPLY_COUPON->url();
        $couponform = new applycoupon_form($couponaction, $data);

        if ($submitteddata = $couponform->get_data()) {
            $couponform->process_coupon_data($submitteddata);
        }

        return [
            'content' => $couponform->render(),
            'label'   => get_string('topupbycoupon', 'enrol_wallet'),
            'key'     => 'coupons',
        ];
    }

    /**
     * Get VC charging form.
     * Todo: Transfer the topup options to a hook.
     * @return array{content: string, key: string, label: string}|null
     */
    public function get_vc_credit_form() {
        global $CFG;
        // If plugin block_vc exist, add credit options by it.
        if (file_exists("$CFG->dirroot/blocks/vc/classes/form/vc_credit_form.php")
                && (bool)get_config('block_vc', 'enablecredit')) {

            require_once("$CFG->dirroot/blocks/vc/classes/form/vc_credit_form.php");
            $action = new url('/blocks/vc/credit.php');
            $vcform = new \block_vc\form\vc_credit_form($action);

            return [
                'content' => $vcform->render(),
                'label'   => get_string('topupbyvc', 'enrol_wallet'),
                'key'     => 'vc',
            ];
        }
        return null;
    }

    /**
     * Get teller men info.
     * @param renderer_base $output
     * @return array{content: bool|string, key: string, label: string}|null
     */
    public function get_teller_men(renderer_base $output) {
        // Display teller men (user with capabilities to credit and chosen in the settings to be displayed).
        $tellermen = config::make()->tellermen;
        if (empty($tellermen)) {
            return null;
        }

        $chargerids = explode(',', $tellermen);
        $tellermen = [];
        foreach ($chargerids as $tellerid) {
            if (empty($tellerid)) {
                continue;
            }
            $teller = core_user::get_user($tellerid);
            if (!$teller || isguestuser($teller) || !core_user::is_real_user($tellerid)) {
                continue;
            }

            $tellermen[] = [
                'fullname'       => fullname($teller),
                'canviewprofile' => user_can_view_profile($teller),
                'url'            => new url('/user/view.php', ['id' => $tellerid]),
            ];
        }

        if (empty($tellermen)) {
            return null;
        }

        return [
            'content' => $output->render_from_template('enrol_wallet/tellermen', ['users' => $tellermen]),
            'label'   => get_string('topupbytellerman', 'enrol_wallet'),
            'key'     => 'tellermen',
        ];
    }

    /**
     * Summary of export_for_template
     * @param renderer_base $output
     * @return array{display: bool}|array{display: bool, items: array, haswarn: bool, policy: mixed, topup: bool}
     */
    public function export_for_template(renderer_base $output) {
        if (!$this->display) {
            return ['display' => false];
        }

        $options = [];
        $options[] = $this->get_bundles();
        $options[] = $this->get_topup_form();
        $options[] = $this->get_coupon_topup();
        $options[] = $this->get_vc_credit_form();
        $options[] = $this->get_teller_men($output);

        $options = array_values(array_filter($options));

        $options[0]['show'] = true;

        $context = [
            'display' => $this->display && !empty($options),
            'items'   => $options,
        ];

        if ($context['display']) {
            $context += $this->export_policy_warn();
        }

        return $context;
    }
}
