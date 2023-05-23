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
 * wallet enrolment plugin settings and presets.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
// Adding some security.
require_login();

$systemcontext = context_system::instance();

require_capability('enrol/wallet:createcoupon', $systemcontext);

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/enrol/wallet/extra/coupon.php'));
$PAGE->set_title("generate coupons");
$PAGE->set_heading('Add new coupons');

echo $OUTPUT->header();

$mform = new MoodleQuickForm('wallet_coupons', 'post', 'generator.php');

$method = [
    'single' => get_string('singlecoupon', 'enrol_wallet'),
    'random' => get_string('randomcoupons', 'enrol_wallet'),
];
$mform->addElement('select', 'method', get_string('coupon_generation_method', 'enrol_wallet'), $method);
$mform->addHelpButton('method', 'coupon_generation_method', 'enrol_wallet');

$mform->addElement('text', 'code', get_string('coupon_code', 'enrol_wallet'));
$mform->setType('code', PARAM_TEXT);
$mform->addHelpButton('code', 'coupon_code', 'enrol_wallet');
$mform->hideIf('code' , 'method', 'eq', 'random');

$mform->addElement('text', 'value', get_string('coupon_value', 'enrol_wallet'));
$mform->setType('value', PARAM_NUMBER);
$mform->addHelpButton('value', 'coupon_value', 'enrol_wallet');

$mform->addElement('text', 'number', get_string('coupons_number', 'enrol_wallet'));
$mform->setType('number', PARAM_INT);
$mform->addHelpButton('number', 'coupons_number', 'enrol_wallet');
$mform->setDefault('number', 1);
$mform->disabledIf('number' , 'method', 'eq', 'single');

$mform->addElement('text', 'length', get_string('coupons_length', 'enrol_wallet'));
$mform->setType('length', PARAM_INT);
$mform->addHelpButton('length', 'coupons_length', 'enrol_wallet');
$mform->setDefault('length', 8);
$mform->hideIf('length' , 'method', 'eq', 'single');

$types = [
    'fixed' => get_string('fixedvaluecoupon', 'enrol_wallet'),
    'percent' => get_string('percentdiscountcoupon', 'enrol_wallet'),
];
$mform->addElement('select', 'type', get_string('coupon_type', 'enrol_wallet'), $types);
$mform->addHelpButton('type', 'coupon_type', 'enrol_wallet');

$mform->addElement('text', 'maxusage', get_string('coupons_maxusage', 'enrol_wallet'));
$mform->setType('maxusage', PARAM_INT);
$mform->addHelpButton('maxusage', 'coupons_maxusage', 'enrol_wallet');
$mform->setDefault('maxusage', 1);

$mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'enrol_wallet'), array('optional' => true));
$mform->addElement('date_time_selector', 'validto', get_string('validto', 'enrol_wallet'), array('optional' => true));

$group = [];
$group[] = $mform->createElement('checkbox', 'upper', get_string('upperletters', 'enrol_wallet'));
$group[] = $mform->createElement('checkbox', 'lower', get_string('lowerletters', 'enrol_wallet'));
$group[] = $mform->createElement('checkbox', 'digits', get_string('digits', 'enrol_wallet'));
$mform->setDefault('lower', 'checked');
$mform->setDefault('digits', 1);

$mform->addGroup($group, 'characters', get_string('characters', 'enrol_wallet'), '-');
$mform->addHelpButton('characters', 'characters', 'enrol_wallet');
$mform->hideIf('characters' , 'method', 'eq', 'single');

$mform->addElement('submit', 'submit', get_string('submit_coupongenerator', 'enrol_wallet'));
$mform->disabledIf('submit', 'value', 'eq', 0);
$mform->disabledIf('submit', 'value', 'eq', '');

$mform->addElement('hidden', 'sesskey');
$mform->setType('sesskey', PARAM_TEXT);
$mform->setDefault('sesskey', sesskey());

// Now let's display the form.
ob_start();
$mform->display();
$output = ob_get_clean();

echo $OUTPUT->box($output);

echo $OUTPUT->footer();
