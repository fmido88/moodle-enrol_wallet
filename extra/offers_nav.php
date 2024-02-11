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
 * TODO describe file offers_nav
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/enrol/wallet/extra/offers.php'));

require_admin();

$return = optional_param('return', '/', PARAM_LOCALURL);
$return = new moodle_url($return);

$content = '-'.get_string('offers', 'enrol_wallet')."|/enrol/wallet/extra/offers.php||".current_language();

$all = $CFG->custommenuitems;
if (stristr($all, $content)) {
    redirect($return);
}

if (!empty($all)) {
    $items = explode("\n", $all);
} else {
    $items = [];
}

$items[] = $content;

$config = implode("\n", $items);

$CFG->custommenuitems = $config;
set_config('custommenuitems', $config);

redirect($return);
