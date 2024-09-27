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
 * Balance details to be displayed for a certain user.
 *
 * @package   enrol_wallet
 * @copyright 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet\output;

use renderable;
use templatable;
use renderer_base;
use enrol_wallet\util\balance;
use moodle_url;
use html_writer;
use core_course_category;
use stdClass;

/**
 * Prepare the data to be displayed contains all details about the balance of a certain user.
 */
class wallet_balance implements renderable, templatable {

    /**
     * The user that balance belongs to.
     * @var int
     */
    protected $userid;
    /**
     * If this user is the current user.
     * @var bool
     */
    protected $currentuser = false;
    /**
     * If this user is a parent
     * according to auth_parent
     * @var bool
     */
    protected $isparent = false;
    /**
     * Used to calculate and prepare the payment region for enrol wallet
     * instance.
     * @param int $userid the enrol wallet instance record.
     */
    public function __construct($userid = 0) {
        global $USER, $CFG;
        if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
            require_once("$CFG->dirroot/auth/parent/auth.php");
            $authparent = new \auth_plugin_parent;
            $this->isparent = $authparent->is_parent($USER);
        }

        if (empty($userid) || $userid == $USER->id) {
            $this->userid = $USER->id;
            $this->currentuser = true;
        } else {
            $this->userid = $userid;
        }
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * No complex types - only stdClass, array, int, string, float, bool
     * Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     * @param renderer_base $output
     * @return array|\stdClass
     */
    public function export_for_template(renderer_base $output) {

        $helper = new balance($this->userid);
        // Get the user balance.
        $details = $helper->get_balance_details();
        // Get the default currency.
        $currency = get_config('enrol_wallet', 'currency');

        $policy = get_config('enrol_wallet', 'refundpolicy');
        // Prepare transaction URL to display.
        $params = [];
        if (!$this->currentuser) {
            $params['userid'] = $this->userid;
        }

        $transactionsurl = new moodle_url('/enrol/wallet/extra/transaction.php', $params);
        $transactions = html_writer::link($transactionsurl, get_string('transactions', 'enrol_wallet'));
        if ($this->currentuser && !AJAX_SCRIPT) {
            // Transfer link.
            $transferenabled = get_config('enrol_wallet', 'transfer_enabled');
            $transferurl = new moodle_url('/enrol/wallet/extra/transfer.php');
            $transfer = html_writer::link($transferurl, get_string('transfer', 'enrol_wallet'));

            if (!$this->isparent) {
                // Referral link.
                $refenabled = get_config('enrol_wallet', 'referral_enabled');
                $referralurl = new moodle_url('/enrol/wallet/referral_signup.php', ['refcode' => $refcode]);
                $referral = html_writer::link($referralurl, get_string('referral_program', 'enrol_wallet'));
            }
        }

        $balancedetails = [];
        foreach ($details['catbalance'] as $id => $obj) {
            $category = core_course_category::get($id, IGNORE_MISSING, true);

            $balancedetails[$id] = new stdClass;
            if (empty($category)) {
                $balancedetails[$id]->name = get_string('unknowncategory');
            } else {
                $balancedetails[$id]->name = $category->get_nested_name(false);
            }

            $balancedetails[$id]->refundable = number_format($obj->refundable ?? 0, 2);
            $balancedetails[$id]->nonrefundable = number_format($obj->nonrefundable ?? 0, 2);
            $total = $obj->balance ?? (float)(($obj->refundable ?? 0) + ($obj->nonrefundable ?? 0));
            $balancedetails[$id]->total = number_format($total, 2);
        }

        $balancedetails = !(empty($balancedetails)) ? array_values($balancedetails) : false;

        $tempctx = new stdClass;
        $tempctx->main         = number_format($helper->get_main_refundable(), 2);
        $tempctx->norefund     = number_format($helper->get_main_nonrefundable(), 2);
        $tempctx->balance      = number_format($helper->get_total_balance(), 2);
        $tempctx->hasdetails   = !empty($balancedetails);
        $tempctx->catdetails   = $balancedetails;
        $tempctx->currency     = $currency;

        if (!AJAX_SCRIPT) {
            $tempctx->transactions = $transactions;
            $tempctx->transfer     = !empty($transferenabled) ? $transfer : false;
            $tempctx->referral     = !empty($refenabled) ? $referral : false;
            $tempctx->policy       = !empty($policy) ? $policy : false;
            $tempctx->walleturl    = (new moodle_url('/enrol/wallet/wallet.php#linkbalance'))->out();
        }

        $tempctx->currentuser = $this->currentuser;
        return $tempctx;
    }
}
