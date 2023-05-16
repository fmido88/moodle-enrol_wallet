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
$PAGE->set_title("bulk enrollment edit");
$PAGE->set_heading('Bulk Enrollment Edit (for all users in selected courses)');
$PAGE->set_pagetype('course-view-participants');
$PAGE->set_docs_path('enrol/users');
$PAGE->add_body_class('path-user'); // So we can style it independently.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

echo $OUTPUT->header();
$form = new MoodleQuickForm('courses', 'post', 'bulkedit_action.php');

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

$select = $form->addElement('select', 'courses', 'Courses', $options);
$select->setMultiple(true);

// Prepare enrollment plugins selectors.
$enrolplugins = enrol_get_plugins(true);
foreach ($enrolplugins as $name => $object) {
    if ($name == 'guest' || $name == 'cohort' || $name == 'category') {
        continue;
    }
    $plugoptions[$name] = $name;
}

$selectenrol = $form->addElement('select', 'plugins', 'Enrollment Plugins', $plugoptions);
$selectenrol->setMultiple(true);

$statusoptions = array(-1 => get_string('nochange', 'enrol'),
        ENROL_USER_ACTIVE => get_string('participationactive', 'enrol'),
        ENROL_USER_SUSPENDED => get_string('participationsuspended', 'enrol'));
$form->addElement('select', 'status', get_string('alterstatus', 'enrol_manual'), $statusoptions, array('optional' => true));

$form->addElement('date_time_selector', 'timestart', get_string('altertimestart', 'enrol_manual'), array('optional' => true));
$form->addElement('date_time_selector', 'timeend', get_string('altertimeend', 'enrol_manual'), array('optional' => true));

$form->addElement('submit' , 'submit', 'submit');
$form->disabledIf('submit', 'courses[]', 'noitemselected');
$form->disabledIf('submit', 'plugins[]', 'noitemselected');

// Now let's display the form.
ob_start();
$form->display();
$output = ob_get_clean();

echo $OUTPUT->box($output);
echo '<style>
select#id_courses {
    height: 361px;
}
</style>';
echo $OUTPUT->footer();
