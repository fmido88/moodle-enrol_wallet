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

require_once('../../../config.php');

require_login();
require_capability('enrol/wallet:editcoupon', context_system::instance());

$edit = optional_param('edit', false, PARAM_BOOL);
$defaultdata = [
    'id'         => required_param('id', PARAM_INT),
    'code'       => required_param('code', PARAM_TEXT),
    'type'       => required_param('type', PARAM_TEXT),
    'category'   => optional_param('category', '', PARAM_INT),
    'value'      => optional_param('value', 0, PARAM_FLOAT),
    'maxusage'   => optional_param('maxusage', 1, PARAM_INT),
    'maxperuser' => optional_param('maxperuser', 0, PARAM_INT),
    'usetimes'   => optional_param('usetimes', 0, PARAM_INT),
    'validfrom'  => optional_param('validfrom', 0, PARAM_INT),
    'validto'    => optional_param('validto', 0, PARAM_INT),
];
if ($edit) {
    $defaultdata['courses'] = optional_param('courses', '', PARAM_INT);
} else {
    $courses = optional_param_array('courses', '', PARAM_INT);
    if (!empty($courses)) {
        $defaultdata['courses'] = implode(',', $courses);
    }
}

// Setup the page.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/enrol/wallet/extra/couponedit.php'));
$PAGE->set_title(get_string('coupon_edit_title', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupon_edit_heading', 'enrol_wallet'));

$mform = new enrol_wallet\form\coupons_edit(null, $defaultdata);

if ($data = $mform->get_data()) {
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
    // Check if there is another code similar to this one.
    $params = [
        'id'   => $id,
        'code' => $code,
    ];
    $select = 'code = :code AND id != :id';
    $notvalid = $DB->record_exists_select('enrol_wallet_coupons', $select, $params);

    if ($notvalid) {
        $msg = get_string('coupon_exist', 'enrol_wallet');
        $notify = 'warning';
    } else {
        $done = $DB->update_record('enrol_wallet_coupons', (object)$coupondata);
        $msg = ($done) ? get_string('coupon_update_success', 'enrol_wallet') : get_string('coupon_update_failed', 'enrol_wallet');
        $notify = ($done) ? 'success' : 'error';
    }

    $url = new moodle_url('coupontable.php');
    redirect($url, $msg, null, $notify);
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
