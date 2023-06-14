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
 * Wallet enrol plugin installation script
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Executes a PHP code right after the plugin's database scheme has been installed.
 * Using it to enable plugin after installation.
 * @return bool
 */
function xmldb_enrol_wallet_install() {
    global $CFG;
    require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
    enrol_wallet_enable_plugin();

    return true;
}
