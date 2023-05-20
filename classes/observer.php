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
 * Observers for enrol_wallet
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;
use enrol_wallet_plugin;
/**
 * Observer class for enrol_wallet.
 *
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * This is a callback function after a user completed a couse with an instance of wallet enrollment.
     * the student will be awarded with a certain amount according to its grade also if exceeds a certain grade.
     *
     * @param \core\event\course_completed $event
     * @return void
     */
    public static function wallet_completion_awards(\core\event\course_completed $event) {
        $userid = $event->relateduserid;
        $contex = $event->context;
        $courseid = $event->courseid;
        // Geting the enrol wallet instance in the course (there is only one because multiple isn't allowed).
        $instances = enrol_get_instances($courseid, true);
        $instance = null;
        foreach ($instances as $inst) {
            if ($inst->enrol === 'wallet') {
                $instance = $inst;
                break;
            }
        }

        if (null == $instance) {
            return;
        }
        if ($instance->customint8) {
            global $CFG, $DB;
            require_once($CFG->dirroot.'/grade/querylib.php');
            require_once($CFG->libdir . '/gradelib.php');

            $grades = grade_get_course_grade($userid, $courseid);
            $maxgrade = (float)$grades->item->grademax;
            $usergrade = (float)$grades->grade;
            $percentage = ($usergrade / $maxgrade) * 100;

            $condition = $instance->customdec1;
            // Check if the condition applied.
            if ($percentage < $condition) {
                return;
            }
            // If the user alreadey rewarded for this course, ignore the event.
            if ($DB->record_exists('enrol_wallet_awards', ['userid' => $userid, 'courseid' => $courseid])) {
                return;
            }
            $awardper = $instance->customdec2;
            // Recieving the award per each grade.
            $award = ($percentage - $condition) * $maxgrade * $awardper / 100;
            $coursename = get_course($courseid)->shortname;
            $a = new \stdClass;
            $a->courseshortname = $coursename;
            $a->amount = $award;
            $a->usergrade = $usergrade;
            $a->maxgrade = $maxgrade;

            $desc = get_string('awardingdesc', 'enrol_wallet', $a);
            enrol_wallet_plugin::payment_topup($award , $userid , $desc , $userid);

            $data = [
                'userid' => $userid,
                'courseid' => $courseid,
                'grade' => $usergrade,
                'maxgrade' => $maxgrade,
                'percent' => $percentage,
                'amount' => $award,
                'timecreated' => time()
            ];

            $DB->insert_record('enrol_wallet_awards', $data);
            // TODO Adding award event.
        }
    }

    /** This is a callback function when user created,
     * and send him a gift on his wallet if the setting is enabled.
     * @param \core\event\user_created $event
     * @return void
     */
    public static function wallet_gifting_new_user(\core\event\user_created $event) {
        $userid = $event->relateduserid;
        $time = $event->timecreated;
        if (!get_config('enrol_wallet', 'newusergift')) {
            return;
        }
        $giftvalue = get_config('enrol_wallet', 'newusergiftvalue');

        $a = new \stdClass;
        $a->userid = $userid;
        $a->time = userdate($time);
        $a->amount = $giftvalue;
        $desc = get_string('giftdesc', 'enrol_wallet', $a);

        enrol_wallet_plugin::payment_topup($giftvalue, $userid, $desc, $userid);
        // TODO Adding gifts event.
    }
}

