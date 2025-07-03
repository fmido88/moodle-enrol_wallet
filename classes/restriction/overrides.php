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

namespace enrol_wallet\restriction;

/**
 * Class overrides
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overrides {
    /**
     * The database table name.
     * @var string
     */
    public const TABLE = 'enrol_wallet_overrides';

    /**
     * Check if the user has an override from been restricted in this instance.
     * @param int $instanceid
     * @param int $userid
     * @return bool
     */
    public static function is_instance_overridden($instanceid, $userid = 0) {
        $rules = self::get_instance_overridden_rules($instanceid, $userid);
        return !empty($rules) && !empty($rules['restrictions']);
    }

    /**
     * Get restriction rules for an instance for a given user.
     * @param int $instanceid
     * @param int $userid
     * @return array|null
     */
    public static function get_instance_overridden_rules($instanceid, $userid = 0) {
        global $DB, $USER, $CFG;
        if (empty($userid)) {
            $userid = $USER->id;
        }

        if (isguestuser($userid)) {
            return null;
        }

        // Check if this user is a part of an overridden cohort.
        require_once($CFG->dirroot . '/cohort/lib.php');
        $usercohorts = cohort_get_user_cohorts($userid);
        $cohortsids = [];
        foreach ($usercohorts as $cohort) {
            $cohortsids[] = $cohort->id;
        }
        unset($usercohorts);

        $cohortsids = array_unique($cohortsids);
        [$cohortsin, $cohortsparams] = $DB->get_in_or_equal($cohortsids, SQL_PARAMS_NAMED);
        $sql = "SELECT id, rules
                FROM {enrol_wallet_overrides}
                WHERE (userid = :userid
                       OR cohortid $cohortsin)
                  AND thing = :enrol
                  AND thingid = :instanceid";

        $params = [
            'userid' => $userid,
            'thing'  => 'enrol',
            'thingid' => $instanceid,
        ] + $cohortsparams;

        $records = $DB->get_records_sql($sql, $params);

        if (count($records) > 1) {
            $return = [];
            foreach ($records as $r) {
                $rules = json_decode($r->rules, true);
                if (!empty($rules)) {
                    // Merge the rules.
                    foreach ($rules as $key => $bool) {
                        if ((bool)$bool) {
                            $return[$key] = true;
                        }
                    }
                }
            }
            return $return;
        }

        $record = reset($records);
        return json_decode($record->rules, true);
    }

    /**
     * Override a user from restriction for an enrol wallet instance.
     * @param int $instanceid
     * @param int $id user id or cohort id if cohort set to true.
     * @param bool $iscohort if this is a cohort overriding
     * @param array $override the rules to override, default is ['restriction'].
     * @return bool
     */
    public static function override_instance($instanceid, $id, $iscohort = false, $override = ['restriction']) {
        global $DB, $USER;
        $conditions = [
            'thing'   => 'enrol',
            'thingid' => $instanceid,
        ];

        if ($iscohort) {
            $conditions['cohortid'] = $id;
        } else {
            $conditions['userid'] = $id;
        }

        if ($record = $DB->get_record(self::TABLE, $conditions)) {

            $rules = json_decode($record->rules, true);
            if (empty($rules)) {
                $rules = [];
            }

            foreach ($override as $rule) {
                $rules[$rule] = true;
            }

            $record->rules = json_encode($rules);
            $record->timemodified = time();
            $record->usermodified = $USER->id;

            return $DB->update_record(self::TABLE, $record);
        }

        $rules = [
            'restriction' => true,
        ];

        $record = $conditions + [
            'timecreated'  => time(),
            'timemodified' => time(),
            'usermodified' => $USER->id,
            'rules'        => json_encode($rules),
        ];
        return $DB->insert_record(self::TABLE, $record, false);
    }
}
