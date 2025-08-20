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
 * TODO describe file renamedclasses
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$renamedclasses = [
    "enrol_wallet\\util\\balance"    => enrol_wallet\local\wallet\balance::class,
    "enrol_wallet\\util\\balance_op" => enrol_wallet\local\wallet\balance_op::class,
    "enrol_wallet\\category\\operations" => enrol_wallet\local\wallet\catop::class,

    "enrol_wallet\\coupons" => enrol_wallet\local\coupons\coupons::class,
    "enrol_wallet\\uploadcoupon\\processor" => enrol_wallet\local\coupons\uploadcoupon\processor::class,
    "enrol_wallet\\uploadcoupon\\tracker" => enrol_wallet\local\coupons\uploadcoupon\tracker::class,

    "enrol_wallet\\util\\cm"         => enrol_wallet\local\entities\cm::class,
    "enrol_wallet\\util\\section"    => enrol_wallet\local\entities\section::class,
    "enrol_wallet\\util\\instance"   => enrol_wallet\local\entities\instance::class,
    "enrol_wallet\\category\\helper" => enrol_wallet\local\entities\category::class,
    "enrol_wallet\\util\\discount_rules" => enrol_wallet\local\discounts\discount_rules::class,
    "enrol_wallet\\util\\offers"     => enrol_wallet\local\discounts\offers::class,

    "enrol_wallet\\util\\options"    => enrol_wallet\local\utils\options::class,
    "enrol_wallet\\util\\form"       => enrol_wallet\local\utils\form::class,
    "enrol_wallet\\category\\options" => enrol_wallet\local\utils\catoptions::class,

    "enrol_wallet\\restriction\\frontend" => enrol_wallet\local\restriction\frontend::class,
    "enrol_wallet\\restriction\\info"     => enrol_wallet\local\restriction\info::class,

    "enrol_wallet\\pages" => enrol_wallet\output\pages::class,

    "enrol_wallet_external" => enrol_wallet\external\enrol::class,
    "enrol_wallet\\api\\balance_op"  => enrol_wallet\external\balance_op::class,
    "enrol_wallet\\api\\instance"    => enrol_wallet\external\instance::class,
    "enrol_wallet\\api\\offers_form" => enrol_wallet\external\offers_form::class,
    
];