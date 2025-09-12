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
use core\exception\coding_exception;
use core\output\html_writer;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use enrol_wallet\local\config;
use enrol_wallet\local\urls\reports;
use enrol_wallet\table\transactions;

/**
 * Prepare wallets home page taps.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wallet_tabs implements renderable, templatable {
    /**
     * System context.
     * @var context
     */
    protected context $context;

    /**
     * Course context that the page belongs to.
     * @var context
     */
    protected context $coursecontext;

    /**
     * The user id, usually the current user.
     * @var int
     */
    protected int $userid;

    /**
     * The main tabs to be rendered.
     * @var array
     */
    protected array $tabnames;

    /**
     * The admin links.
     * @var array
     */
    protected array $adminlinks = [];

    /**
     * Prepare the wallet taps to be rendered.
     * @param int      $userid
     * @param ?context $coursecontext
     */
    public function __construct($userid = 0, ?context $coursecontext = null) {
        global $PAGE, $USER;
        $this->context = system::instance();

        $this->userid = $userid ? $userid : (int)$USER->id;

        if (!$coursecontext) {
            if (isset($PAGE->context) && $PAGE->context->level >= course::LEVEL) {
                $coursecontext = $PAGE->context;
            } else {
                $coursecontext = course::instance(SITEID);
            }
        }

        $this->coursecontext = $coursecontext;

        $this->load_admin_tabs();
        $this->get_tab_labels();
    }

    /**
     * Load available administration links according to capabilities.
     * @return void
     */
    protected function load_admin_tabs() {
        $this->adminlinks = static_renderer::get_admins_links($this->coursecontext);
    }

    /**
     * Get all tabs labels available.
     * @return array
     */
    protected function get_tab_labels() {
        if (isset($this->tabnames)) {
            return $this->tabnames;
        }

        $this->tabnames = [
            'balance'      => get_string('balance', 'enrol_wallet'),
            'topup'        => get_string('topup', 'enrol_wallet'),
            'charge'       => get_string('charge', 'enrol_wallet'),
            'referral'     => get_string('referral', 'enrol_wallet'),
            'transactions' => get_string('transactions', 'enrol_wallet'),
            'transfer'     => get_string('transfer', 'enrol_wallet'),
            'offers'       => get_string('offers', 'enrol_wallet'),
            'admin'        => get_string('administration'),
        ];

        if (!has_capability('enrol/wallet:creditdebit', $this->context)) {
            unset($this->tabnames['charge']);
        }

        if (empty($this->adminlinks)) {
            unset($this->tabnames['admin']);
        }

        return $this->tabnames;
    }

    /**
     * Get wallet balance template data.
     * @param  renderer_base   $output
     * @return array|\stdClass
     */
    public function export_balance(renderer_base $output) {
        $wallet = new wallet_balance($this->userid);

        return $wallet->export_for_template($output);
    }

    /**
     * Get admin links data.
     * @param  renderer_base $output
     * @return array
     */
    public function export_admin($output = null) {
        return $this->adminlinks;
    }

    /**
     * Get topup options template data.
     * @param  renderer_base $output
     * @return array{display: bool, haswarn: bool, items: array, policy: mixed, topup: bool}|array{display: bool}
     */
    public function export_topup(renderer_base $output) {
        $topup = new topup_options();

        return $topup->export_for_template($output);
    }

    /**
     * Render charger form.
     * @param null|renderer_base $ignore
     * @return string
     */
    public function render_charge($ignore = null) {
        return static_renderer::charger_form();
    }

    /**
     * Render transaction table part.
     * @param renderer_base $output
     * @return string
     */
    public function render_transactions(renderer_base $output) {
        $url = clone $output->get_page()->url;
        $url->set_anchor('linktransactions');

        $transactionurl = reports::TRANSACTIONS->url();
        $class          = ['class' => 'btn btn-primary'];

        $out = html_writer::link($transactionurl, get_string('transactions_details', 'enrol_wallet'), $class);

        $table = new transactions('wallet-page-transactions-table', (object)['userid' => $this->userid]);
        $table->define_baseurl($url);

        ob_start();
        $table->out(15, true);
        $out .= ob_get_clean();

        return $out;
    }

    /**
     * Render referral page content.
     * @param null|renderer_base $ignore
     * @return bool|string
     */
    public function render_referral($ignore = null) {
        if ((bool)config::make()->referral_enabled) {
            ob_start();
            pages::process_referral_page();

            return ob_get_clean();
        }

        return '';
    }

    /**
     * Render transfer balance to other form.
     * @param renderer_base $output
     * @return bool|string
     */
    public function render_transfer(renderer_base $output) {
        $url = clone $output->get_page()->url;
        $url->set_anchor('linktransfer');

        ob_start();
        pages::process_transfer_page($url);

        return ob_get_clean();
    }

    /**
     * Render the offers content.
     * @param null|renderer_base $ignore
     * @return string
     */
    public function render_offers($ignore = null) {
        return pages::get_offers_content();
    }

    /**
     * Export data for render.
     * @param  renderer_base       $output
     * @throws coding_exception
     * @return array{pages: array}
     */
    public function export_for_template(renderer_base $output) {
        $pages = [];

        foreach ($this->tabnames as $key => $label) {
            $page = [
                'key'  => $key,
                'name' => $label,
            ];
            $exportmethod = "export_{$key}";
            $rendermethod = "render_{$key}";

            if (method_exists($this, $exportmethod)) {
                $page["is{$key}"] = true;
                $page['context']  = $this->$exportmethod($output);

                if (empty($page['context'])) {
                    continue;
                }
            } else if (method_exists($this, $rendermethod)) {
                $page['prerendered'] = true;
                $page['content']     = $this->$rendermethod($output);

                if (empty($page['content'])) {
                    continue;
                }
            } else {
                throw new coding_exception("neither the method $rendermethod nor $exportmethod is existed.");
            }
            $pages[] = $page;
        }

        return ['pages' => $pages];
    }
}
