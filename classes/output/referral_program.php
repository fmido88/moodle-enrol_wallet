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
 * Referral program output class.
 *
 * @package    enrol_wallet
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Referral program output class.
 *
 * @package    enrol_wallet
 * @copyright  2023 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class referral_program implements renderable, templatable {

    /**
     * @var int The user ID
     */
    protected $userid;

    /**
     * Constructor.
     *
     * @param int $userid The user ID
     */
    public function __construct($userid) {
        $this->userid = $userid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $CFG;

        $data = new stdClass();

        // Get referral program settings
        $data->enabled = get_config('enrol_wallet', 'referral_enabled');
        $data->amount = get_config('enrol_wallet', 'referral_amount');
        $data->max_referrals = get_config('enrol_wallet', 'referral_max');

        if (!$data->enabled) {
            return $data;
        }

        // Get user's referral code
        $referral = $DB->get_record('enrol_wallet_referral', ['userid' => $this->userid]);
        if (!$referral) {
            $referral = new stdClass();
            $referral->userid = $this->userid;
            $referral->code = substr(md5(uniqid(mt_rand(), true)), 0, 10);
            $referral->usetimes = 0;
            $referral->id = $DB->insert_record('enrol_wallet_referral', $referral);
        }

        $data->referral_code = $referral->code;
        $data->referral_url = new \moodle_url('/enrol/wallet/referral_signup.php', ['refcode' => $referral->code]);

        // Calculate remaining referrals
        if ($data->max_referrals > 0) {
            $data->remaining_referrals = max(0, $data->max_referrals - $referral->usetimes);
        } else {
            $data->remaining_referrals = get_string('unlimited', 'enrol_wallet');
        }

        // Get past referrals
        $data->past_referrals = [];
        $referrals = $DB->get_records('enrol_wallet_hold_gift', ['referrer' => $this->userid]);
        foreach ($referrals as $ref) {
            $referred_user = $DB->get_record('user', ['username' => $ref->referred]);
            if ($referred_user) {
                $data->past_referrals[] = [
                    'name' => fullname($referred_user),
                    'date' => userdate($ref->timecreated),
                    'status' => $ref->released ? get_string('referral_done', 'enrol_wallet') : get_string('referral_hold', 'enrol_wallet'),
                    'amount' => $ref->amount
                ];
            }
        }

        return $data;
    }
}