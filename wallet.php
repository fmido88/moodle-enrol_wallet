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
 * Home page for wallet where all functionalities accessible here.
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_wallet\local\urls\pages;
use enrol_wallet\output\helper;
use enrol_wallet\output\wallet_tabs;

require_once('../../config.php');
require_once("$CFG->dirroot/enrol/wallet/locallib.php");

require_login(null, false);

$context = context_system::instance();

$url = pages::WALLET->url();
$url->set_anchor("linkbalance");

$PAGE->set_url($url);
$PAGE->set_context($context);

$title = get_string('wallet', 'enrol_wallet');

$PAGE->set_heading($title);
$PAGE->set_title("{$SITE->fullname} | $title");
$PAGE->set_pagelayout('frontpage');
$PAGE->set_secondary_navigation(true, true);

$tabs = new wallet_tabs();

$renderer = helper::get_wallet_renderer();

echo $OUTPUT->header();

echo $renderer->render($tabs);

echo $OUTPUT->footer();
