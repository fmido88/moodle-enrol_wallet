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
 * wallet enrol plugin external classes including.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

if ($CFG->version >= 2023042400) {
    class_alias(\core_external\external_api::class, 'external_api');
    class_alias(\core_external\restricted_context_exception::class, 'restricted_context_exception');
    class_alias(\core_external\external_description::class, 'external_description');
    class_alias(\core_external\external_value::class, 'external_value');
    class_alias(\core_external\external_format_value::class, 'external_format_value');
    class_alias(\core_external\external_single_structure::class, 'external_single_structure');
    class_alias(\core_external\external_multiple_structure::class, 'external_multiple_structure');
    class_alias(\core_external\external_function_parameters::class, 'external_function_parameters');
    class_alias(\core_external\util::class, 'external_util');
    class_alias(\core_external\external_files::class, 'external_files');
    class_alias(\core_external\external_warnings::class, 'external_warnings');
    class_alias(\core_external\external_settings::class, 'external_settings');
} else {
    require_once("$CFG->libdir/externallib.php");
}
