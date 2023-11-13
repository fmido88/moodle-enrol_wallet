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
 * wallet enrol plugin login logout from wordpress action page
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once("$CFG->dirroot/login/lib.php");

$userid      = required_param('userid', PARAM_INT);
$action      = required_param('action', PARAM_TEXT);
$wantsurl    = optional_param('wantsurl', null, PARAM_URL);
$newredirect = optional_param('redirect', '', PARAM_URL);

if ($action == 'login') {
    require_login();
    global $USER;

    if ($USER->id != $userid || isguestuser()) {
        throw new moodle_exception('invalidoperation');
    }

    $redirect = $wantsurl ?? core_login_get_return_url();

    $wordpress = new \enrol_wallet\wordpress;
    $wordpress->login_logout_user_to_wordpress($USER->id, 'login', $redirect);

} else if ($action == 'logout') {
    global $redirect;

    if (!empty($newredirect)) {
        $redirect = $newredirect;
    } else {
        $redirect = (new \moodle_url('/'))->out(false);
    }

    $wordpress = new \enrol_wallet\wordpress;
    $wordpress->login_logout_user_to_wordpress($userid, 'logout', $redirect);

} else {
    throw new moodle_exception('invalidoperation');
}
