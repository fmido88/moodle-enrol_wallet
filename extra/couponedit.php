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

$edit = optional_param('edit', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL); // Edit confirmation.

if ($edit) { // The edit form.
    require_once("$CFG->libdir/formslib.php");

    $id = required_param('id', PARAM_INT);
    $code = required_param('code', PARAM_TEXT);
    $type = required_param('type', PARAM_TEXT);
    $value = required_param('value', PARAM_NUMBER);
    $maxusage = optional_param('maxusage', 0, PARAM_INT);
    $usetimes = optional_param('usetimes', 0, PARAM_INT);
    $validfrom = optional_param('validfrom', 0, PARAM_INT);
    $validto = optional_param('validto', 0, PARAM_INT);

    $validfromedit = false;
    if (!empty($validfrom)) {
        $validfromedit = true;
    }

    $validtoedit = false;
    if (!empty($validto)) {
        $validtoedit = true;
    }

    // Setup the page.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/enrol/wallet/extra/couponedit.php'));
    $PAGE->set_title(get_string('coupon_edit_title', 'enrol_wallet'));
    $PAGE->set_heading(get_string('coupon_edit_heading', 'enrol_wallet'));

    $mform = new MoodleQuickForm('wallet_coupons', 'post', 'couponedit.php');

    $mform->addElement('text', 'code', get_string('coupon_code', 'enrol_wallet'));
    $mform->setType('code', PARAM_TEXT);
    $mform->addHelpButton('code', 'coupon_code', 'enrol_wallet');
    $mform->setDefault('code', $code);

    $mform->addElement('text', 'value', get_string('coupon_value', 'enrol_wallet'));
    $mform->setType('value', PARAM_NUMBER);
    $mform->addHelpButton('value', 'coupon_value', 'enrol_wallet');
    $mform->setDefault('value', $value);

    $types = [
        'fixed' => get_string('fixedvaluecoupon', 'enrol_wallet'),
        'percent' => get_string('percentdiscountcoupon', 'enrol_wallet'),
    ];
    $mform->addElement('select', 'type', get_string('coupon_type', 'enrol_wallet'), $types);
    $mform->addHelpButton('type', 'coupon_type', 'enrol_wallet');
    $mform->setDefault('type', $type);

    $mform->addElement('text', 'maxusage', get_string('coupons_maxusage', 'enrol_wallet'));
    $mform->setType('maxusage', PARAM_INT);
    $mform->addHelpButton('maxusage', 'coupons_maxusage', 'enrol_wallet');
    $mform->setDefault('maxusage', $maxusage);

    $mform->addElement('static', 'usetimes', get_string('coupon_usetimes', 'enrol_wallet'), $usetimes);

    $mform->addElement('checkbox', 'usetimesreset', get_string('coupon_resetusetime', 'enrol_wallet'));
    $mform->addHelpButton('usetimesreset', 'coupon_resetusetime', 'enrol_wallet');

    $mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), ['optional' => true]);
    $mform->setDefault('validfrom', $validfrom);

    $mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), ['optional' => true]);
    $mform->setDefault('validto', $validto);

    $mform->addElement('submit', 'confirm', get_string('confirm'));
    $mform->disabledIf('confirm', 'value', 'eq', 0);
    $mform->disabledIf('confirm', 'value', 'eq', '');

    $mform->addElement('hidden', 'sesskey');
    $mform->setType('sesskey', PARAM_TEXT);
    $mform->setDefault('sesskey', sesskey());

    $mform->addElement('hidden', 'id');
    $mform->setType('id', PARAM_INT);
    $mform->setDefault('id', $id);

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

} else if ($confirm && confirm_sesskey()) { // The edit action.
    global $DB;

    $id = required_param('id', PARAM_INT);
    $code = required_param('code', PARAM_TEXT);
    $type = required_param('type', PARAM_TEXT);
    $value = required_param('value', PARAM_NUMBER);
    $maxusage = optional_param('maxusage', 0, PARAM_INT);
    $validfrom = optional_param_array('validfrom', [], PARAM_INT);
    $validto = optional_param_array('validto', [], PARAM_INT);
    $usetimesreset = optional_param('usetimesreset', false, PARAM_BOOL);

    $coupondata = [
        'id'       => $id,
        'code'     => $code,
        'type'     => $type,
        'value'    => $value,
        'maxusage' => $maxusage,
    ];

    if (!empty($validfrom)) {
        $coupondata['validfrom'] = mktime(
            $validfrom['hour'],
            $validfrom['minute'],
            null,
            $validfrom['month'],
            $validfrom['day'],
            $validfrom['year'],
        );
    } else {
        $coupondata['validfrom'] = 0;
    }

    if (!empty($validto)) {
        $coupondata['validto'] = mktime(
            $validto['hour'],
            $validto['minute'],
            null,
            $validto['month'],
            $validto['day'],
            $validto['year'],
        );
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
        $msg = get_string('couponexist', 'enrol_wallet');
        $notify = 'warning';
    } else {
        $done = $DB->update_record('enrol_wallet_coupons', (object)$coupondata);
        $msg = ($done) ? get_string('coupon_update_success', 'enrol_wallet') : get_string('coupon_update_failed', 'enrol_wallet');
        $notify = ($done) ? 'success' : 'error';
    }

    $url = new moodle_url('coupontable.php');
    redirect($url, $msg, null, $notify);
} else {
    $url = new moodle_url('coupontable.php');
    redirect($url);
}
