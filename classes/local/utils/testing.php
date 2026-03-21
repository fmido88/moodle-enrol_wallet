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

use core_payment_generator;
use enrol_wallet_generator;
use phpunit_util;

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/lib/phpunit/classes/util.php");
/**
 * Class testing contains helpful method for testing.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testing {
    /**
     * Return instance of enrol_wallet_generator.
     * @return enrol_wallet_generator
     */
    public static function get_generator(): enrol_wallet_generator {
        return phpunit_util::get_data_generator()->get_plugin_generator('enrol_wallet');
    }
    /**
     * Return the core payment generator.
     * @return \core_payment_generator
     */
    public static function get_core_payment_generator(): core_payment_generator {
        return phpunit_util::get_data_generator()->get_plugin_generator('core_payment');
    }
}
