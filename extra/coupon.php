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
 * Page for generating wallet coupons in moodle.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
require_once($CFG->libdir.'/formslib.php');
// Adding some security.
require_login();

$systemcontext = context_system::instance();
require_capability('enrol/wallet:createcoupon', $systemcontext);

$pageurl = new moodle_url('/enrol/wallet/extra/coupon.php');
// Setup the page.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('coupon_generation_title', 'enrol_wallet'));
$PAGE->set_heading(get_string('coupon_generation_heading', 'enrol_wallet'));

$mform = new enrol_wallet\form\coupons_generator();

if ($options = $mform->get_data()) {
    $method = $options->method;
    if ($method == 'single') {
        $options->number = 1;
        $options->length = '';
        $options->characters = [];
    } else if ($method == 'random') {
        $options->code = '';
    }

    if (!empty($options->characters)) {
        $characters = $options->characters;
        $options->lower  = isset($characters['lower']) ? $characters['lower'] : false;
        $options->upper  = isset($characters['upper']) ? $characters['upper'] : false;
        $options->digits = isset($characters['digits']) ? $characters['digits'] : false;
    }

    if (!empty($options->validto)) {
        $validto = $options->validto;
        if (is_array($validto)) {
            $options->to = mktime(
                $validto['hour'],
                $validto['minute'],
                0,
                $validto['month'],
                $validto['day'],
                $validto['year'],
            );
        } else {
            $options->to = $validto;
        }

    } else {
        $options->to = 0;
    }
    unset($options->validto);
    if (!empty($options->validfrom)) {
        $validfrom = $options->validfrom;
        if (is_array($validfrom)) {
            $options->from = mktime(
                $validfrom['hour'],
                $validfrom['minute'],
                0,
                $validfrom['month'],
                $validfrom['day'],
                $validfrom['year'],
            );
        } else {
            $options->from = $validfrom;
        }

    } else {
        $options->from = 0;
    }
    unset($options->validfrom);
    $options->courses = !empty($options->courses) ? implode(',', $options->courses) : '';

    if ($options->type == 'enrol') {
        $options->value = 0;
    }

    $options->timecreated = time();

    // Generate coupons with the options specified.
    $task = new enrol_wallet\task\generate_coupons();
    $task->set_custom_data($options);
    $task->set_next_run_time(time() + 1);
    \core\task\manager::queue_adhoc_task($task);

    $couponsurl = new moodle_url('/enrol/wallet/extra/coupontable.php', [
        'createdfrom' => $options->timecreated,
        'createdto'   => $options->timecreated,
    ]);
    $msg = get_string('coupons_generation_taskcreated', 'enrol_wallet', [
        'count' => $options->number,
        'link'  => html_writer::link($couponsurl, get_string('check')),
    ]);
    redirect($pageurl, $msg);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
