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
use enrol_wallet\transactions;
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
     * This is a callback function after a user completed a course with an instance of wallet enrollment.
     * the student will be awarded with a certain amount according to its grade also if exceeds a certain grade.
     *
     * @param \core\event\course_completed $event
     * @return void
     */
    public static function wallet_completion_awards(\core\event\course_completed $event) {
        global $CFG, $DB;
        $siteaward = get_config('enrol_wallet', 'awardssite');
        if (empty($siteaward)) {
            return;
        }

        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        // If the user already rewarded for this course, ignore the event.
        if ($DB->record_exists('enrol_wallet_awards', ['userid' => $userid, 'courseid' => $courseid])) {
            return;
        }

        require_once($CFG->dirroot.'/grade/querylib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $grades = grade_get_course_grade($userid, $courseid);
        $maxgrade = (float)$grades->item->grademax;
        $usergrade = (float)$grades->grade;

        if ($maxgrade <= 0 || $usergrade <= 0) {
            return;
        }

        $percentage = ($usergrade / $maxgrade) * 100;

        // Getting the enrol wallet instance in the course (there is only one because multiple isn't allowed).
        $instances = enrol_get_instances($courseid, true);
        $instance = null;
        $ta = 0; // Estimated Total award (Just for check and not used again).
        foreach ($instances as $inst) {
            // Check for multiple wallet instances and get the higher award available.
            // Check if awards enabled in this instance & if the condition applied and the student deserve the award.
            if ($inst->enrol === 'wallet' // Wallet enrollments only.
            && !empty($inst->customint8) // Awards enabled.
            && $inst->customdec1 <= $percentage // Condition for award applied.
            && $inst->customdec2 * (100 - $percentage) > $ta // Maximum award available.
            ) {
                $instance = $inst;
                $ta = $inst->customdec2 * (100 - $percentage);
            }
        }

        // Check if wallet enrolment instance found in this course.
        if (null === $instance) {
            return;
        }

        $condition = $instance->customdec1;

        // Award per each grade.
        $awardper = $instance->customdec2;

        // Calculating the total award.
        $award = ($percentage - $condition) * $maxgrade * $awardper / 100;
        $coursename = get_course($courseid)->shortname;

        $a = new \stdClass;
        $a->courseshortname = $coursename;
        $a->amount = $award;
        $a->usergrade = $usergrade;
        $a->maxgrade = $maxgrade;

        $desc = get_string('awardingdesc', 'enrol_wallet', $a);

        // Award the student.
        transactions::payment_topup($award , $userid , $desc , $userid, false);

        // Insert the record.
        $data = [
            'userid'   => $userid,
            'courseid' => $courseid,
            'grade'    => $usergrade,
            'maxgrade' => $maxgrade,
            'percent'  => $percentage,
            'amount'   => $award,
            'timecreated' => time(),
        ];
        $id = $DB->insert_record('enrol_wallet_awards', $data);

        // Trigger award event.
        $eventdata = [
            'context'       => \context_course::instance($courseid),
            'userid'        => $userid,
            'relateduserid' => $userid,
            'objectid'      => $id,
            'courseid'      => $courseid,
            'other' => [
                'grade'  => number_format($percentage, 2),
                'amount' => $award,
            ],
        ];
        $event = \enrol_wallet\event\award_granted::create($eventdata);
        $event->trigger();
    }

    /** This is a callback function when user created,
     * and send him a gift on his wallet if the setting is enabled.
     * @param \core\event\user_created $event
     * @return void
     */
    public static function wallet_gifting_new_user(\core\event\user_created $event) {
        $userid = $event->relateduserid;

        // First check if we gifting new users.
        $giftenabled = get_config('enrol_wallet', 'newusergift');
        if (!empty($giftenabled)) {
            $time   = $event->timecreated;
            $giftvalue = get_config('enrol_wallet', 'newusergiftvalue');

            $balance = transactions::get_user_balance($userid);
            if (!is_numeric($balance) || $balance == 0) {
                $a = new \stdClass;
                $a->userid = $userid;
                $a->time = userdate($time);
                $a->amount = $giftvalue;
                $desc = get_string('giftdesc', 'enrol_wallet', $a);

                $id = transactions::payment_topup($giftvalue, $userid, $desc, $userid, false, false);

                // Trigger gifts event.
                $eventdata = [
                    'context'       => \context_system::instance(),
                    'userid'        => $userid,
                    'relateduserid' => $userid,
                    'objectid'      => $id,
                    'other' => [
                        'amount' => $giftvalue,
                    ],
                ];
                $giftevent = \enrol_wallet\event\newuser_gifted::create($eventdata);
                $giftevent->trigger();
            }
        }
    }

    /**
     * Callback function to apply conditional discount rule.
     * The conditional discount acts like this, for example a discount 25% for 200 cost
     * the user pay 150 then this function credit him by 50.
     *
     * @param \enrol_wallet\event\transactions_triggered $event
     * @return void
     */
    public static function conditional_discount_charging(\enrol_wallet\event\transactions_triggered $event) {
        global $DB;
        $charger = $event->userid;
        $userid  = $event->relateduserid;
        $amount  = $event->other['amount'];

        $enabled = get_config('enrol_wallet', 'conditionaldiscount_apply');
        $percentdiscount = 0;
        if (!empty($enabled)) {
            $params = [
                'time1' => time(),
                'time2' => time(),
            ];
            $select = '(timefrom <= :time1 OR timefrom = 0 ) AND (timeto >= :time2 OR timeto = 0)';
            $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params);

            foreach ($records as $record) {
                if ($record->percent >= 100) {
                    continue;
                }
                $beforediscount = $amount + ($amount * $record->percent / (100 - $record->percent));
                if ($beforediscount >= $record->cond && $record->percent > $percentdiscount) {

                    $percentdiscount = $record->percent;
                    $condition = $record->cond;
                }
            }
        }
        if (
            empty($enabled) // If the discount enabled.
            || empty($percentdiscount) // If there is a value for discount.
            || $event->other['type'] != 'credit' // Only apply to credit transaction.
            ) {
            return;
        }

        // Discount more than 100 is not acceptable.
        $percentdiscount = min(100, $percentdiscount);
        $discount = $percentdiscount / 100;

        // The rest of the amount after subtract the part the user paid.
        $rest = $amount * $discount / (1 - $discount);

        $desc = get_string('conditionaldiscount_desc', 'enrol_wallet', ['rest' => $rest, 'condition' => $condition]);
        // Credit the user with the rest amount.
        transactions::payment_topup($rest, $userid, $desc, $charger, false, false);
    }

    /**
     * Release referral gift after making sure that the referred user is an active user and enrolled in a course already.
     * @param \core\event\user_enrolment_created $event
     * @return void
     */
    public static function release_referral_gift(\core\event\user_enrolment_created $event) {
        $courseid = $event->courseid;
        $enrolmethod = $event->other['enrol'];
        $userid = $event->relateduserid;

        $plugins = explode(',', get_config('enrol_wallet', 'referral_plugins'));
        if (empty($plugins) || !in_array($enrolmethod, $plugins, true)) {
            return;
        }

        $enabled = get_config('enrol_wallet', 'referral_enabled');
        if (!$enabled) {
            return;
        }

        global $DB;
        $referred = \core_user::get_user($userid, 'username,firstname');

        $hold = $DB->get_record('enrol_wallet_hold_gift', ['referred' => $referred->username]);

        if (empty($hold) || !empty($hold->released)) {
            return;
        }

        $referrer = \core_user::get_user($hold->referrer, 'id,firstname');
        // TopUp the referred user.
        $desc = get_string('referral_gift', 'enrol_wallet', $referrer->firstname);
        transactions::payment_topup($hold->amount, $userid, $desc, $referrer->id, false, false);

        // TopUp the referrer user.
        $refdesc = get_string('referral_topup', 'enrol_wallet', $referred->firstname);
        transactions::payment_topup($hold->amount, $referrer->id, $refdesc, $userid, false, false);

        // Updating the hold_gift record.
        $hold->timemodified = time();
        $hold->released = 1;
        $hold->courseid = $courseid;
        $DB->update_record('enrol_wallet_hold_gift', $hold);
    }

    /**
     * This is a callback function after a user is logged in successfully
     * to login user in wordpress website.
     *
     * @param \core\event\user_loggedin $event
     * @return void
     */
    public static function login_to_wordpress(\core\event\user_loggedin $event) {
        global $SESSION;
        $userid = $event->userid;
        $walletsource = get_config('enrol_wallet', 'walletsource');
        if ($walletsource != transactions::SOURCE_WORDPRESS) {
            return;
        }

        $user = \core_user::get_user($userid);
        if (!$user || isguestuser($user) || !empty($user->deleted) || !empty($user->suspended)) {
            return;
        }

        // Double check that is the same user.
        $usernameevent = $event->other['username'];
        $username = $user->username;
        if ($username != $usernameevent) {
            return;
        }

        $wordpressurl = get_config('enrol_wallet', 'wordpress_url');
        $wordpressurl = clean_param($wordpressurl, PARAM_URL);
        $allowed      = get_config('enrol_wallet', 'wordpressloggins');
        if (empty($allowed) || empty($wordpressurl)) {
            return;
        }

        // Clone the old wantsurl.
        $params = [];
        if (isset($SESSION->wantsurl)) {
            $params['wantsurl'] = $SESSION->wantsurl;
            unset($SESSION->wantsurl);
        }

        $params['userid'] = $userid;
        $params['action'] = 'login';

        // Using the observer to set redirect page so the operation done on foreground client side.
        $SESSION->wantsurl = (new \moodle_url('/enrol/wallet/wplogin.php', $params))->out(false);
    }

    /**
     * This is a callback function after a user is logged out successfully
     * to logout user from wordpress website.
     *
     * @param \core\event\user_loggedout $event
     * @return void
     */
    public static function logout_from_wordpress(\core\event\user_loggedout $event) {
        global  $redirect;
        $walletsource = get_config('enrol_wallet', 'walletsource');
        if ($walletsource != transactions::SOURCE_WORDPRESS) {
            return;
        }

        $wordpressurl = get_config('enrol_wallet', 'wordpress_url');
        $wordpressurl = clean_param($wordpressurl, PARAM_URL);
        $allowed = get_config('enrol_wallet', 'wordpressloggins');
        if (empty($allowed) || empty($wordpressurl)) {
            return;
        }

        $userid = $event->userid;

        $user = \core_user::get_user($userid);

        if (!$user || isguestuser($user)) {
            return;
        }

        $params = [];
        if (!empty($redirect)) {
            $params['redirect'] = $redirect;
        }

        $params['userid'] = $userid;
        $params['action'] = 'logout';

        // Using the observer to set redirect page so the operation done on foreground client side.
        $redirect = new \moodle_url('/enrol/wallet/wplogin.php', $params);
    }
}

