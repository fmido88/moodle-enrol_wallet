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
 * Action page to generate coupons.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_login();

$method = required_param('method', PARAM_TEXT);

if ($method == 'single') {
    $code = required_param('code', PARAM_TEXT);
    $number = 1;
    $length = '';
    $characters = [];
} else {
    $code = '';
    $number = required_param('number', PARAM_INT);
    $length = required_param('length', PARAM_INT);
    $characters = required_param_array('characters', PARAM_BOOL);
}

$value = required_param('value', PARAM_NUMBER);
$type = required_param('type', PARAM_TEXT);
$maxusage = required_param('maxusage', PARAM_INT);
$validto = optional_param_array('validto', [], PARAM_INT);
$validfrom = optional_param_array('validfrom', [], PARAM_INT);

$redirecturl = new moodle_url('/enrol/wallet/extra/coupontable.php');

if (!empty($validto)) {
    $to = mktime(
        $validto['hour'],
        $validto['minute'],
        0,
        $validto['month'],
        $validto['day'],
        $validto['year'],
    );
} else {
    $to = 0;
}

if (!empty($validfrom)) {
    $from = mktime(
        $validfrom['hour'],
        $validfrom['minute'],
        0,
        $validfrom['month'],
        $validfrom['day'],
        $validfrom['year'],
    );
} else {
    $from = 0;
}

$options = new stdClass;

if (!empty($characters)) {
    $options->lower = isset($characters['lower']) ? $characters['lower'] : false;
    $options->upper = isset($characters['upper']) ? $characters['upper'] : false;
    $options->digits = isset($characters['digits']) ? $characters['digits'] : false;
}

$options->number = $number;
$options->length = $length;
$options->maxusage = $maxusage;
$options->from = $from;
$options->to = $to;
$options->type = $type;
$options->value = $value;
$options->code = $code;

// Generate coupons with the options specified.
if (confirm_sesskey()) {
    $ids = enrol_wallet_generate_coupons($options);
}

if (is_string($ids)) {
    $msg = $ids;
} else {
    $count = count($ids);
    $msg = get_string('coupons_generation_success', 'enrol_wallet', $count);
}

redirect($redirecturl, $msg);
