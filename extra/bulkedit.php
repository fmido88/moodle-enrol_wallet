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
 * Bulk edit all users enrollments in selected courses ans instances.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');

// Adding some security.
require_login();

$systemcontext = context_system::instance();

$frontpagectx = context_course::instance(SITEID);
require_capability('enrol/wallet:manage', $frontpagectx);
course_require_view_participants($frontpagectx);

// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/enrol/wallet/bulkedit.php'));
$PAGE->set_title(get_string('bulkeditor', 'enrol_wallet'));
$PAGE->set_heading(get_string('bulkeditor_head', 'enrol_wallet'));

echo $OUTPUT->header();
$form = new MoodleQuickForm('courses', 'post', 'bulkedit_action.php');

// Prepare the course selector.
$courses = get_courses('all', 'c.sortorder ASC', 'c.id, c.fullname');
foreach ($courses as $course) {

    if ($course->id == 1) {
        continue;
    }

    $category = core_course_category::get($course->category, IGNORE_MISSING, true);
    if (!$category) {
        continue;
    }

    $categoryname = $category->get_nested_name();

    $options[$course->id] = $categoryname . ': ' . $course->fullname;
}

$select = $form->addElement('select', 'courses', get_string('courses'), $options);
$select->setMultiple(true);

// Prepare enrollment plugins selectors.
$enrolplugins = enrol_get_plugins(true);
$plugoptions = [];
foreach ($enrolplugins as $name => $object) {
    if ($name == 'guest' || $name == 'cohort' || $name == 'category') {
        continue;
    }
    $plugoptions[$name] = $name;
}

$selectenrol = $form->addElement('select', 'plugins', get_string('enrol_type', 'enrol_wallet'), $plugoptions);
$selectenrol->setMultiple(true);

$statusoptions = [
        -1                   => get_string('nochange', 'enrol'),
        ENROL_USER_ACTIVE    => get_string('participationactive', 'enrol'),
        ENROL_USER_SUSPENDED => get_string('participationsuspended', 'enrol'),
    ];
$form->addElement('select', 'status', get_string('alterstatus', 'enrol_manual'), $statusoptions, ['optional' => true]);

$form->addElement('date_time_selector', 'timestart', get_string('altertimestart', 'enrol_manual'), ['optional' => true]);
$form->addElement('date_time_selector', 'timeend', get_string('altertimeend', 'enrol_manual'), ['optional' => true]);

$form->addElement('submit' , 'submit', get_string('submit'));
$form->disabledIf('submit', 'courses[]', 'noitemselected');
$form->disabledIf('submit', 'plugins[]', 'noitemselected');

$form->addElement('hidden', 'sesskey');
$form->setType('sesskey', PARAM_TEXT);
$form->setDefault('sesskey', sesskey());

// Now let's display the form.
ob_start();
$form->display();
$output = ob_get_clean();

echo $OUTPUT->box($output);

echo $OUTPUT->footer();
