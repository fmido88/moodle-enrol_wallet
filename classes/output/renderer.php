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
 * renderer for enrol_wallet.
 *
 * @package   enrol_wallet
 * @copyright 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\output;

use plugin_renderer_base;

/**
 * Enrol wallet renderer.
 */
class renderer extends plugin_renderer_base {
    /**
     * Render payment info and payment button for an instance.
     * @param payment_info $page
     * @return string
     */
    public function render_payment_info(payment_info $page) {
        $data = (object)$page->export_for_template($this);
        if (empty($data)) {
            return '';
        } else if (isset($data->nocost)) {
            return $data->nocost;
        } else {
            return $this->render_from_template('enrol_wallet/payment_region', $data);
        }
    }

    /**
     * Render the balance details for a certain user.
     * @param wallet_balance $page
     * @return string
     */
    public function render_wallet_balance(wallet_balance $page) {
        $data = (object)$page->export_for_template($this);
        return $this->render_from_template('enrol_wallet/display', $data);
    }

    /**
     * Render all topping up options.
     * @return string
     */
    public function render_top_up_options() {
        return '';
    }
}
