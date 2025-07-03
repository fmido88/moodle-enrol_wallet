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
 * Offers page for the wallet enrollment plugin.
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing
require_once('../../../config.php');

$title = get_string('offers', 'enrol_wallet');

$PAGE->set_context(context_course::instance(SITEID));
$PAGE->set_url(new moodle_url('/enrol/wallet/extra/offers.php'));
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_pagelayout('frontpage');

$out = enrol_wallet\pages::get_offers_content();

echo $OUTPUT->header(),
     $out,
     $OUTPUT->footer();
