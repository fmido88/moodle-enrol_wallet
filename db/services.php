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
 * wallet enrol plugin external functions and service definitions.
 *
 * @package   enrol_wallet
 * @copyright 2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'enrol_wallet_get_instance_info' => [
        'classname'   => 'enrol_wallet_external',
        'methodname'  => 'get_instance_info',
        'classpath'   => 'enrol/wallet/externallib.php',
        'description' => 'wallet enrolment instance information.',
        'type'        => 'read',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'enrol_wallet_enrol_user' => [
        'classname'   => 'enrol_wallet_external',
        'methodname'  => 'enrol_user',
        'classpath'   => 'enrol/wallet/externallib.php',
        'description' => 'wallet enrol the current user in the given course.',
        'type'        => 'write',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'enrol_wallet_get_balance_details' => [
        'classname'   => 'enrol_wallet\api\balance_op',
        'methodname'  => 'get_balance_details',
        'classpath'   => 'enrol/wallet/classes/api/balance_op.php',
        'description' => 'Get the balance details for a certain user',
        'type'        => 'read',
        'services'    => [],
        'ajax'        => true,
    ],
    'enrol_wallet_get_offer_form_fragment' => [
        'classname'   => 'enrol_wallet\api\offers_form',
        'methodname'  => 'get_form_fragment',
        'classpath'   => 'enrol/wallet/classes/api/offers_form.php',
        'description' => 'Get a fragment of the offer form',
        'type'        => 'read',
        'services'    => [],
        'ajax'        => true,
    ],
];
