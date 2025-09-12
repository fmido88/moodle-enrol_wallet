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

use enrol_wallet\local\config;
use enrol_wallet\local\urls\pages;
use enrol_wallet\local\urls\reports;
use enrol_wallet\local\wallet\balance;
use renderable;
use templatable;
use renderer_base;
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

        $config = config::make();
        // Get the default currency.
        $currency = $config->currency;

        $policy = $config->refundpolicy;
        // Prepare transaction URL to display.
        $params = [];
        if (!$this->currentuser) {
            $params['userid'] = $this->userid;
        }

        $transactionsurl = reports::TRANSACTIONS->url($params);
        $transactions = html_writer::link($transactionsurl, get_string('transactions', 'enrol_wallet'));
        if ($this->currentuser && !AJAX_SCRIPT) {
            // Transfer link.
            $transferenabled = $config->transfer_enabled;
            $transferurl = pages::TRANSFER->url();
            $transfer = html_writer::link($transferurl, get_string('transfer', 'enrol_wallet'));

            if (!$this->isparent) {
                // Referral link.
                $refenabled = $config->referral_enabled;
                $referralurl = pages::REFERRAL->url();
                $referral = html_writer::link($referralurl, get_string('referral_program', 'enrol_wallet'));
            }
        }

        $balancedetails = [];
        foreach ($details->catbalance as $id => $obj) {
            $category = core_course_category::get($id, IGNORE_MISSING, true);

            $balancedetails[$id] = new stdClass;
            if (empty($category)) {
                $balancedetails[$id]->name = get_string('unknowncategory');
            } else {
                $balancedetails[$id]->name = $category->get_nested_name(false);
            }

            $balancedetails[$id]->refundable = format_float($obj->refundable, 2);
            $balancedetails[$id]->nonrefundable = format_float($obj->nonrefundable, 2);
            $balancedetails[$id]->total = format_float($obj->balance, 2);
        }

        $balancedetails = !(empty($balancedetails)) ? array_values($balancedetails) : false;

        $tempctx = new stdClass;
        $tempctx->main         = format_float($helper->get_main_refundable(), 2);
        $tempctx->norefund     = format_float($helper->get_main_nonrefundable(), 2);
        $tempctx->balance      = format_float($helper->get_total_balance(), 2);
        $tempctx->hasdetails   = !empty($balancedetails);
        $tempctx->catdetails   = $balancedetails;
        $tempctx->currency     = $currency;

        if (!AJAX_SCRIPT) {
            $currenturl = $output->get_page()->url;
            $walleturl = pages::WALLET->url();
            if (!$walleturl->compare($currenturl, URL_MATCH_BASE)) {
                $walleturl->set_anchor('linkbalance');
                $tempctx->walleturl = $walleturl->out(false);
            }

            $tempctx->transactions = $transactions;
            $tempctx->transfer     = !empty($transferenabled) ? $transfer : false;
            $tempctx->referral     = !empty($refenabled) ? $referral : false;
            $tempctx->policy       = !empty($policy) ? $policy : false;
        }

        $tempctx->currentuser = $this->currentuser;
        return $tempctx;
    }
}
