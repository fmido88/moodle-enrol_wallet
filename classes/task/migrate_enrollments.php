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
 * Task to turn transaction to non refundable after the refund period is over.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\task;

use enrol_wallet\util\balance_op;
/**
 * Send expiry notifications task.
 */
class migrate_enrollments extends \core\task\adhoc_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('migrate_enrollments_task', 'enrol_wallet');
    }

    /**
     * Run task to migrate all data from enrol_credit to enrol_wallet.
     */
    public function execute() {
        $trace = new \text_progress_trace();
        $trace->output("Starting the task...\n");
        $creditplugin = enrol_get_plugin('credit');
        if (empty($creditplugin)) {
            $trace->output("The credit plugin is not exist...\n");
            $trace->finished();
            return;
        }

        raise_memory_limit(MEMORY_HUGE);
        \core_php_time_limit::raise();

        $this->transform_credit($trace);

        $this->transform_enrol_instances($trace);

        $trace->output('Task finished.');
        $trace->finished();
    }

    /**
     * Get all user credit and transform it into wallet balance.
     * @param \text_progress_trace $trace
     */
    private function transform_credit($trace) {
        global $DB, $CFG;
        $creditplugin = enrol_get_plugin('credit');

        $allusers = $DB->get_records('user', [], '', 'id');
        $trace->output("Starting to transform credit to wallet for ".count($allusers)." users...\n");
        $desc = get_string('credit_wallet_transformation_desc', 'enrol_wallet');
        foreach ($allusers as $user) {
            $credit = $creditplugin->get_user_credits($user->id);
            if (empty($credit)) {
                continue;
            }

            $creditplugin->deduct_credits($user->id, $credit);
            $op = new balance_op($user->id);
            $op->credit($credit, $op::OTHER, 0, $desc, false, false);
        }
        $trace->output("Finished transforming credit...\n");
    }

    /**
     * Transform all enrol credit instances to enrol wallet.
     * @param \text_progress_trace $trace
     */
    private function transform_enrol_instances($trace) {
        global $DB;
        $trace->output("Start transform enrol instances to wallet...\n");
        $params = ['wallet' => 'wallet', 'credit' => 'credit'];
        $sql = "SELECT ce.id
                FROM {enrol} ce
                JOIN {enrol} we ON (we.courseid = ce.courseid)
                WHERE ce.enrol = :credit
                    AND we.enrol = :wallet";
        $todeleterecords = $DB->get_records_sql($sql, $params);

        $exclude = [];
        foreach ($todeleterecords as $record) {
            $exclude[] = $record->id;
        }
        unset($params);
        if (!empty($exclude)) {
            [$in, $params] = $DB->get_in_or_equal($exclude, SQL_PARAMS_NAMED);
            $params['credit'] = 'credit';
            $sql = "SELECT * FROM {enrol} e WHERE (e.id NOT $in) AND e.enrol = :credit";
        } else {
            $params['credit'] = 'credit';
            $sql = "SELECT * FROM {enrol} e WHERE e.enrol = :credit";
        }

        $records = $DB->get_records_sql($sql, $params);

        $wallet = enrol_get_plugin('wallet');
        $default = $wallet->get_instance_defaults();
        foreach ($records as $record) {
            $record->enrol = 'wallet';
            $record->cost = $record->customint7;
            unset($record->customint7);
            foreach ($default as $key => $value) {
                if (!isset($record->$key) || is_null($record->$key)) {
                    $record->$key = $value;
                }
            }
            $DB->update_record('enrol', $record);
        }

        $this->migrate_users_enrollments($trace);
        if (!empty($exclude)) {
            $DB->delete_records_select('enrol', "id $in AND enrol = :credit", $params);
        }
    }

    /**
     * Migrate all user enrollments from enrol credit to the newly created enrol wallet instances.
     * @param \text_progress_trace $trace
     */
    private function migrate_users_enrollments($trace) {
        global $DB;
        $enrol = 'credit';
        $walletplugin = enrol_get_plugin('wallet');
        $trace->output("Start migrating enrolments...\n");

        $params = ['enrol' => $enrol];
        $sql = "SELECT e.id, e.courseid, e.status, MIN(we.id) AS wid, COUNT(ue.id) AS cu
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
                  JOIN {course} c ON (c.id = e.courseid)
             LEFT JOIN {enrol} we ON (we.courseid = e.courseid AND we.enrol='wallet')
                 WHERE e.enrol = :enrol
              GROUP BY e.id, e.courseid, e.status
              ORDER BY e.id";
        $rs = $DB->get_recordset_sql($sql, $params);

        foreach ($rs as $e) {
            $winstance = false;
            if (!$e->wid) {
                // Wallet instance does not exist yet, add a new one.
                $course = $DB->get_record('course', ['id' => $e->courseid], '*', MUST_EXIST);
                if ($winstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'wallet'])) {
                    // Already created by previous iteration.
                    $e->wid = $winstance->id;
                } else if ($e->wid = $walletplugin->add_default_instance($course)) {
                    $winstance = $DB->get_record('enrol', ['id' => $e->wid]);
                    if ($e->status != ENROL_INSTANCE_ENABLED) {
                        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $e->wid]);
                        $winstance->status = ENROL_INSTANCE_DISABLED;
                    }
                }
            } else {
                $winstance = $DB->get_record('enrol', ['id' => $e->wid]);
            }

            if (!$winstance) {
                // This should never happen unless transform_instance fails unexpectedly.
                $trace->output('Failed to find wallet enrolment instance in the course with id '. $e->courseid."\n");
                continue;
            }

            // First delete potential role duplicates.
            $params = ['id' => $e->id, 'component' => 'enrol_'.$enrol, 'empt' => ''];
            $sql = "SELECT ra.id
                      FROM {role_assignments} ra
                      JOIN {role_assignments} mra
                        ON (mra.contextid = ra.contextid
                            AND mra.userid = ra.userid
                            AND mra.roleid = ra.roleid
                            AND mra.component = :empt
                            AND mra.itemid = 0)
                     WHERE ra.component = :component AND ra.itemid = :id";
            $ras = $DB->get_records_sql($sql, $params);
            $ras = array_keys($ras);
            $DB->delete_records_list('role_assignments', 'id', $ras);
            unset($ras);

            // Migrate roles.
            $sql = "UPDATE {role_assignments}
                       SET itemid = 0, component = :empty
                     WHERE itemid = :id AND component = :component";
            $params = ['empty' => '', 'id' => $e->id, 'component' => 'enrol_'.$enrol];
            $DB->execute($sql, $params);

            // Delete potential enrol duplicates.
            $params = ['id' => $e->id, 'wid' => $e->wid];
            $sql = "SELECT ue.id
                      FROM {user_enrolments} ue
                      JOIN {user_enrolments} mue ON (mue.userid = ue.userid AND mue.enrolid = :wid)
                     WHERE ue.enrolid = :id";
            $ues = $DB->get_records_sql($sql, $params);
            $ues = array_keys($ues);
            $DB->delete_records_list('user_enrolments', 'id', $ues);
            unset($ues);

            // Migrate to wallet enrol instance.
            $params = ['id' => $e->id, 'wid' => $e->wid];
            if ($e->status != ENROL_INSTANCE_ENABLED && $winstance->status == ENROL_INSTANCE_ENABLED) {
                $status = ", status = :disabled";
                $params['disabled'] = ENROL_USER_SUSPENDED;
            } else {
                $status = "";
            }

            $sql = "UPDATE {user_enrolments}
                       SET enrolid = :wid $status
                     WHERE enrolid = :id";
            $DB->execute($sql, $params);
        }
        $rs->close();
        $trace->output("All users enrollment migrated...\n");
    }

}
