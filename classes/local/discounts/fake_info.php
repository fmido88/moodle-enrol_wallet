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

namespace enrol_wallet\local\discounts;

use core\context\system;

/**
 * Fake availability info class.
 *
 * Fake availability info class used to check the availability
 * of profile field base offer by availability_field
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fake_info extends \core_availability\info {
    /**
     * Override parent construct and don't set any data
     * to create a fake info class just to avoid errors.
     */
    public function __construct() {
    }

    /**
     * Not used but return some context.
     * @return \context
     */
    public function get_context() {
        return system::instance();
    }

    /**
     * Not used.
     * @return string
     */
    protected function get_thing_name() {
        return '';
    }

    /**
     * Not used.
     * @return string
     */
    protected function get_view_hidden_capability() {
        return 'enrol/wallet:manage';
    }

    /**
     * Not used.
     * @param  string $availability
     * @return void
     */
    protected function set_in_database($availability) {
    }
}

