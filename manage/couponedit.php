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
 * Edit coupons.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_wallet\form\coupons_edit;
use enrol_wallet\local\urls\manage;
use enrol_wallet\local\urls\reports;

require_once('../../../config.php');

require_login(null, false);
require_capability('enrol/wallet:editcoupon', context_system::instance());

$id = required_param('id', PARAM_INT);

// Setup the page.
$PAGE->set_context(context_system::instance());
manage::EDIT_COUPON->set_page_url_to_me();
$PAGE->set_title(get_string('coupon_edit_title', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupon_edit_heading', 'enrol_wallet'));

$form = new coupons_edit(null, ['id' => $id]);

if ($data = $form->get_data()) {
    global $DB;

    $id            = $data->id;
    $code          = $data->code;
    $type          = $data->type;
    $value         = $data->value ?? 0;
    $category      = $data->category ?? null;
    $courses       = !empty($data->courses) ? implode(',', $data->courses) : null;
    $maxusage      = $data->maxusage ?? 0;
    $maxperuser    = $data->maxperuser ?? 0;
    $validfrom     = $data->validfrom ?? [];
    $validto       = $data->validto ?? [];
    $usetimesreset = $data->usetimesreset ?? false;

    $coupondata = [
        'id'         => $id,
        'code'       => $code,
        'type'       => $type,
        'value'      => $value,
        'category'   => $category,
        'courses'    => $courses,
        'maxusage'   => $maxusage,
        'maxperuser' => $maxperuser,
    ];

    if (!empty($validfrom)) {
        if (is_array($validfrom)) {
            $coupondata['validfrom'] = mktime(
                $validfrom['hour'],
                $validfrom['minute'],
                null,
                $validfrom['month'],
                $validfrom['day'],
                $validfrom['year'],
            );
        } else {
            $coupondata['validfrom'] = $validfrom;
        }

    } else {
        $coupondata['validfrom'] = 0;
    }

    if (!empty($validto)) {
        if (is_array($validto)) {
            $coupondata['validto'] = mktime(
                $validto['hour'],
                $validto['minute'],
                null,
                $validto['month'],
                $validto['day'],
                $validto['year'],
            );
        } else {
            $coupondata['validto'] = $validto;
        }

    } else {
        $coupondata['validto'] = 0;
    }

    if (!empty($usetimesreset)) {
        $coupondata['usetimes'] = 0;
    }

    $done = $DB->update_record('enrol_wallet_coupons', (object)$coupondata);
    $msg = $done ? get_string('coupon_update_success', 'enrol_wallet') : get_string('coupon_update_failed', 'enrol_wallet');
    $notify = $done ? 'success' : 'error';

    $url = reports::COUPONS->url();
    redirect($url, $msg, null, $notify);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
