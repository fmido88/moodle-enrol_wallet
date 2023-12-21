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
 * Transforming users credits and migrate enrollment to enrol wallet.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

require_login(null, false);
$context = context_system::instance();
$caps = ['enrol/wallet:creditdebit', 'enrol/wallet:manage', 'enrol/wallet:config'];
require_all_capabilities($caps, $context);

$task = new \enrol_wallet\task\migrate_enrollments();
$task->set_custom_data(null);
$task->set_next_run_time(time() + 2);

\core\task\manager::queue_adhoc_task($task);
$title = get_string('transformation_credit_title', 'enrol_wallet');
$donemsg = get_string('transformation_credit_done', 'enrol_wallet');

$PAGE->set_context($context);
$PAGE->set_url('/enrol/wallet/extra/credit_transformation.php');
$PAGE->set_pagelayout('popup');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

echo $OUTPUT->box($donemsg);

$options = ['type' => 'primary', 'primary' => 'true', 'class' => 'continuebutton', 'onClick' => "self.close()"];
echo $OUTPUT->single_button(new moodle_url('/admin/settings.php'), get_string('continue'), 'post', $options);

echo $OUTPUT->footer();
