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
 * Bulk edit all wallet enrollment instances in selected courses.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once(__DIR__.'/../lib.php');

// Adding some security.
require_login();

$systemcontext = context_system::instance();

$frontpagectx = context_course::instance(SITEID);
require_capability('enrol/wallet:manage', $frontpagectx);

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/enrol/wallet/bulkinstances.php'));
$PAGE->set_title(get_string('bulk_instancestitle', 'enrol_wallet'));
$PAGE->set_heading(get_string('bulk_instanceshead', 'enrol_wallet'));

echo $OUTPUT->header();

$mform = new MoodleQuickForm('courses', 'post', 'bulkinstances_action.php');

// Prepare the course selector.
$courses = get_courses();
foreach ($courses as $course) {
    if ($course->id == 1) {
        continue;
    }

    $category = core_course_category::get($course->category);
    $parentname = $category->name.': ';

    while ($category->parent > 0) {
        $parent = core_course_category::get($category->parent);
        $parentname = $parent->name . ': ' . $parentname;
        $category = $parent;
    }

    $options[$course->id] = $parentname.$course->fullname;
}

$select = $mform->addElement('select', 'courses', 'Courses', $options);
$select->setMultiple(true);

$nameattribs = ['size' => '20', 'maxlength' => '255', 'optional' => true];
$mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
$mform->setType('name', PARAM_TEXT);
$mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

$mform->addElement('text', 'cost', get_string('credit_cost', 'enrol_wallet'), ['optional' => true]);
$mform->setType('cost', PARAM_INT);
$mform->addHelpButton('cost', 'credit_cost', 'enrol_wallet');

$accounts = \core_payment\helper::get_payment_accounts_menu($systemcontext);
if ($accounts) {
    $accounts = ((count($accounts) > 1) ? ['' => ''] : []) + $accounts;
    $mform->addElement('select', 'customint1', get_string('paymentaccount', 'payment'), $accounts, ['optional' => true]);
} else {
    $mform->addElement('static', 'customint1_text', get_string('paymentaccount', 'payment'),
        html_writer::span(get_string('noaccountsavilable', 'payment'), 'alert alert-danger'));
    $mform->addElement('hidden', 'customint1');
    $mform->setType('customint1', PARAM_INT);
}
$mform->addHelpButton('customint1', 'paymentaccount', 'enrol_wallet');

$enrol = enrol_get_plugin('wallet');
$supportedcurrencies = $enrol->get_possible_currencies();
$supportedcurrencies = [ '-1' => 'No change' ] + $supportedcurrencies;
$mform->addElement('select', 'currency', get_string('currency', 'enrol_wallet'), $supportedcurrencies, ['optional' => true]);

$options = ['-1' => 'No Change',
            ENROL_INSTANCE_ENABLED => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no')];
$mform->addElement('select', 'status', get_string('status', 'enrol_wallet'), $options, ['optional' => true]);
$mform->addHelpButton('status', 'status', 'enrol_wallet');

$options = [-1 => 'No change',
            1 => get_string('yes'),
            0 => get_string('no')];
$mform->addElement('select', 'customint6', get_string('newenrols', 'enrol_wallet'), $options, ['optional' => true]);
$mform->addHelpButton('customint6', 'newenrols', 'enrol_wallet');
$mform->disabledIf('customint6', 'status', 'eq', ENROL_INSTANCE_DISABLED);

$roles = $enrol->extend_assignable_roles($frontpagectx, 5);
$roles = [-1 => 'No change'] + $roles;
$mform->addElement('select', 'roleid', get_string('role', 'enrol_wallet'), $roles, ['optional' => true]);
$mform->setDefault('roleid', 5);

$options = ['optional' => true, 'defaultunit' => 86400];
$mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_wallet'), $options);
$mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_wallet');

$options = [-1 => 'No change',
            0 => get_string('no'),
            1 => get_string('expirynotifyenroller', 'core_enrol'),
            2 => get_string('expirynotifyall', 'core_enrol')];
$mform->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
$mform->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');

$options = ['optional' => false, 'defaultunit' => 86400];
$mform->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), $options);
$mform->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
$mform->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);
$mform->disabledIf('expirythreshold', 'expirynotify', 'eq', -1);

$options = ['optional' => true];
$mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_wallet'), $options);
$mform->setDefault('enrolstartdate', 0);
$mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_wallet');

$options = ['optional' => true];
$mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_wallet'), $options);
$mform->setDefault('enrolenddate', 0);
$mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_wallet');

$options = [-1 => 'No change'];
$options[] = $enrol->get_longtimenosee_options();
$mform->addElement('select', 'customint2', get_string('longtimenosee', 'enrol_wallet'), $options);
$mform->addHelpButton('customint2', 'longtimenosee', 'enrol_wallet');

$mform->addElement('text', 'customint3', get_string('maxenrolled', 'enrol_wallet'));
$mform->addHelpButton('customint3', 'maxenrolled', 'enrol_wallet');
$mform->setType('customint3', PARAM_INT);

require_once($CFG->dirroot.'/cohort/lib.php');

$cohorts = [-1 => 'No change',
            0 => get_string('no')];
$allcohorts = cohort_get_all_cohorts();

foreach ($allcohorts['cohorts'] as $c) {
    $cohorts[$c->id] = format_string($c->name, true, ['context' => context::instance_by_id($c->contextid)]);
    if ($c->idnumber) {
        $cohorts[$c->id] .= ' ['.s($c->idnumber).']';
    }
}

if (count($cohorts) > 1) {
    $mform->addElement('select', 'customint5', get_string('cohortonly', 'enrol_wallet'), $cohorts);
    $mform->addHelpButton('customint5', 'cohortonly', 'enrol_wallet');
} else {
    $mform->addElement('hidden', 'customint5');
    $mform->setType('customint5', PARAM_INT);
    $mform->setConstant('customint5', 0);
}

// Add course restriction options.
$coursesoptions = $enrol->get_courses_options($instance->courseid);
if (!empty($coursesoptions)) {
    $options = [-1 => 'no change'];
    for ($i = 0; $i <= 50; $i++) {
        $options[$i] = $i;
    }
    $select = $mform->addElement('select', 'customint7', get_string('coursesrestriction_num', 'enrol_wallet'), $options);
    $select->setMultiple(false);
    $mform->addHelpButton('customint7', 'coursesrestriction_num', 'enrol_wallet');

    $mform->addElement('hidden', 'customchar3', '', ['id' => 'wallet_customchar3']);
    $mform->setType('customchar3', PARAM_TEXT);

    $params = [
        'id' => 'wallet_courserestriction',
        'onChange' => 'restrictByCourse()'
    ];
    $restrictionlable = get_string('coursesrestriction', 'enrol_wallet');
    $select = $mform->addElement('select', 'courserestriction', $restrictionlable, $coursesoptions, $params);
    $select->setMultiple(true);
    $mform->addHelpButton('courserestriction', 'coursesrestriction', 'enrol_wallet');
    $mform->hideIf('courserestriction', 'customint7', 'eq', 0);
    $mform->hideIf('courserestriction', 'customint7', 'eq', -1);

} else {
    $mform->addElement('hidden', 'customint7');
    $mform->setType('customint7', PARAM_INT);
    $mform->setConstant('customint7', 0);

    $mform->addElement('hidden', 'customchar3');
    $mform->setType('customchar3', PARAM_TEXT);
    $mform->setConstant('customchar3', '');
}

$options = [-1 => 'no change'] + enrol_send_welcome_email_options();
$mform->addElement('select', 'customint4', get_string('sendcoursewelcomemessage', 'enrol_wallet'), $options);
$mform->addHelpButton('customint4', 'sendcoursewelcomemessage', 'enrol_wallet');

$options = ['cols' => '60', 'rows' => '8'];
$mform->addElement('textarea', 'customtext1', get_string('customwelcomemessage', 'enrol_wallet'), $options);
$mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_wallet');
$mform->disabledIf('customtext1', 'customint4', 'eq', -1);
$mform->disabledIf('customtext1', 'customint4', 'eq', 0);

// Adding the awarding program options for this course.
$mform->addElement('checkbox', 'awards', get_string('awardsalter', 'enrol_wallet'));
$mform->setDefault('awards', false);
$mform->addHelpButton('awards', 'awardsalter', 'enrol_wallet');

$mform->addElement('checkbox', 'customint8', get_string('awards', 'enrol_wallet'));
$mform->setDefault('customint8', false);
$mform->addHelpButton('customint8', 'awards', 'enrol_wallet');
$mform->disabledIf('customint8', 'awards', 'notchecked');

$mform->addElement('text', 'customdec1', get_string('awardcreteria', 'enrol_wallet'));
$mform->setType('customdec1', PARAM_NUMBER);
$mform->disabledIf('customdec1', 'customint8', 'notchecked');
$mform->addHelpButton('customdec1', 'awardcreteria', 'enrol_wallet');
$mform->hideIf('customdec1', 'awards', 'notchecked');

$mform->addElement('text', 'customdec2', get_string('awardvalue', 'enrol_wallet'));
$mform->setType('customdec2', PARAM_NUMBER);
$mform->disabledIf('customdec2', 'customint8', 'notchecked');
$mform->addHelpButton('customdec2', 'awardvalue', 'enrol_wallet');
$mform->hideIf('customdec2', 'awards', 'notchecked');

$mform->addElement('submit' , 'submit', 'submit');
$mform->disabledIf('submit', 'courses[]', 'noitemselected');

$mform->addElement('hidden', 'sesskey');
$mform->setType('sesskey', PARAM_TEXT);
$mform->setDefault('sesskey', sesskey());

if (!empty($coursesoptions)) {
    // Add some js code to set the value of customchar3 element for the restriction course enrolment.
    $js = <<<JS
            function restrictByCourse() {
                var textelement = document.getElementById("wallet_customchar3");
                var courseArray = document.getElementById("wallet_courserestriction").selectedOptions;
                var selectedValues = [];
                for (var i = 0; i < courseArray.length; i++) {
                    selectedValues.push(courseArray[i].value);
                }
                // Set the value of the hidden input field to the comma-separated string of selected values.
                textelement.value = selectedValues.join(",");
            }
        JS;
    $mform->addElement('html', '<script>'.$js.'</script>');
}

// Now let's display the form.
ob_start();
$mform->display();
$output = ob_get_clean();

echo $OUTPUT->box($output);

echo $OUTPUT->footer();
