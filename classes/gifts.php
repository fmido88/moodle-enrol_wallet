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

/** Gifting new users observer for enrol_wallet.
 *
 * As the course marked as completed for a student, this observer check his overall grade
 * and award him according the award setting defined in the enrolment instant.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;
use enrol_wallet_plugin;

/** Gifting new users observer for enrol_wallet.
 *
 * As the course marked as completed for a student, this observer check his overall grade
 * and award him according the award setting defined in the enrolment instant.
 *
 */
class enrol_wallet_gifts {

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

