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
 * The page to transfer wallet ballance to other user.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_wallet\local\config;
use enrol_wallet\local\urls\pages;

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

$context = context_system::instance();

require_login();
require_capability('enrol/wallet:transfer', $context);

// Check if transfer isn't enabled in this website.
$transferenabled = config::make()->transfer_enabled;
if (empty($transferenabled)) {
    redirect(new moodle_url('/'), get_string('transfer_notenabled', 'enrol_wallet'), null, 'error');
}

$url = pages::TRANSFER->url();
$title = get_string('transferpage', 'enrol_wallet');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($url);

ob_start();

// This could redirect so it must be called before the output of the headers.
enrol_wallet\output\pages::process_transfer_page($url);

$form = ob_get_clean();

echo $OUTPUT->header();

echo $form;

echo $OUTPUT->footer();
