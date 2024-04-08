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
 * file coupon_action
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
// Any error displaying causing page to not redirect and charging operation may be proceeded twice.
set_debugging(DEBUG_NONE, false);
require_login(null, false);

$data = [
    'cancel'       => optional_param('cancel', false, PARAM_BOOL),
    'submitcoupon' => optional_param('submitcoupon', false, PARAM_BOOL),
    'coupon'       => required_param('coupon', PARAM_ALPHANUMEXT),
    'instanceid'   => optional_param('instanceid', null, PARAM_INT),
    'cmid'         => optional_param('cmid', null, PARAM_INT),
    'sectionid'    => optional_param('sectionid', null, PARAM_INT),
    'courseid'     => optional_param('courseid', null, PARAM_INT),
    'url'          => optional_param('url', '', PARAM_LOCALURL),
];

$PAGE->set_url('/enrol/wallet/extra/coupon_action.php', $data);

$redirect = enrol_wallet_process_coupon_data((object)$data);

redirect($redirect);
