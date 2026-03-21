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

use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\timedate;

/**
 * Data generator class.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_wallet_generator extends component_generator_base {

    /**
     * Create an enrol instance or return the existed one for the given course.
     * If the courseid passed as 0, it will create a new course.
     * @param  int      $courseid
     * @param  bool     $new
     * @param  ?float   $cost
     * @return instance
     */
    public function create_instance(int $courseid = 0, bool $new = false, ?float $cost = null): instance {
        global $DB;
        $generator = phpunit_util::get_data_generator();
        $plugin    = enrol_wallet_plugin::get_plugin();

        if (!$courseid) {
            $course   = $generator->create_course();
            $courseid = $course->id;
        } else {
            $course = get_course($courseid);
        }

        $existed = $DB->get_records('enrol', ['enrol' => 'wallet', 'courseid' => $courseid]);

        if (empty($existed) || $new) {
            $instanceid = $plugin->add_default_instance($course);
            $instance   = $DB->get_record('enrol', ['id' => $instanceid]);
        } else {
            $instance = reset($existed);
        }

        $instance->status = ENROL_INSTANCE_ENABLED;
        $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);

        if (null !== $cost) {
            $instance->cost = $cost;
            $plugin->update_instance($instance, (object)['cost' => $cost]);
        }

        return new instance($instance);
    }

    /**
     * Helper to create a discount rule record.
     *
     * @param  array  $overrides
     * @return object
     */
    public function create_discount_rule(array $overrides = []): object {
        global $DB;

        $now    = timedate::time();
        $record = (object)($overrides + [
            'category'     => 0,
            'cond'         => 100,
            'percent'      => 10,
            'bundle'       => null,
            'timefrom'     => 0,
            'timeto'       => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        $record->id = $DB->insert_record('enrol_wallet_cond_discount', $record);

        return $record;
    }

    /**
     * Signup a user with referral code.
     * @param stdClass|array $userdata
     * @param string $referralcode
     */
    public function signup_user(stdClass|array $userdata = [], string $referralcode = '') {
        global $USER, $DB, $CFG;
        $CFG->registerauth = 'email';

        $olduser = fullclone($USER);
        $this->set_user(null);

        $authplugin = signup_is_enabled();

        // I'm too lazy to generate some random data like first and last name.
        // So I create a user, get its generated data then delete it.
        $user1 = phpunit_util::get_data_generator()->create_user($userdata);
        $user = clone $user1;
        delete_user($user1);
        $DB->delete_records('user', ['id' => $user->id]);

        $user2 = new \stdClass;
        $user2->username  = $user->username;
        $user2->password  = generate_password();
        $user2->email     = $user->email;
        $user2->email2    = $user->email;
        $user2->firstname = $user->firstname;
        $user2->lastname  = $user->lastname;
        $user2->country   = $user->country;
        $user2->refcode   = $referralcode;
        $user2->sesskey   = sesskey();

        // Mock signup process.
        $sink = phpunit_util::start_phpmailer_redirection();
        $user2 = signup_setup_new_user($user2);

        core_login_post_signup_requests($user2);

        $authplugin->user_signup($user2, false);

        $user2 = get_complete_user_data('username', $user->username);

        $DB->set_field('user', 'confirmed', 1, ['id' => $user2->id]);
        $sink->close();

        $this->set_user($olduser);

        return $user2;
    }
}
