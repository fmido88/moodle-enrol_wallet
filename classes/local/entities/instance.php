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
 * Helper.
 *
 * @package   enrol_wallet
 * @copyright 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\entities;

use enrol_wallet\local\config;
use enrol_wallet\local\utils\timedate;
use enrol_wallet_plugin;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/wallet/lib.php');

use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\discounts\offers;
use enrol_wallet_plugin as wallet;

/**
 * Helper class for wallet enrolment instance.
 * @package enrol_wallet
 *
 * @property int    $id                         The instance id.
 * @property int    $courseid                   The course id.
 * @property string $enrol                      The enrolment method (always "wallet").
 * @property int    $status                     The status of the instance (enabled or disabled).
 * @property int    $sortorder                  The sort order of the instance in the course.
 * @property string $name                       The name of the instance.
 * @property int    $enrolperiod                The duration of enrolment in seconds.
 * @property int    $enrolstartdate             The start date of enrolment.
 * @property int    $enrolenddate               The end date of enrolment.
 * @property int    $expirynotify               Whom to notify about expiration? (0 - no, 1 - enroller, 2 - all enrolled).
 * @property int    $expirythreshold            When to send notification? (in seconds).
 * @property bool   $notifyall                  If to notify enrolled and enroller or not, (overridden by expirynotify).
 * @property string $password                   The password for enrolment (not used).
 * @property float  $cost                       The cost of the enrolment instance.
 * @property string $currency                   The currency of the enrolment instance.
 * @property int    $roleid                     The id of the role assigned to users enrolled in this instance.
 * @property int    $customint1                 The payment account id.
 * @property int    $paymentaccountid           The payment account id (alias for customint1).
 * @property int    $customint2                 The long time no see (unenrol inactive after) in seconds.
 * @property int    $longtimenosee              The long time no see (unenrol inactive after) in seconds (alias for customint2).
 * @property int    $customint3                 The maximum number of users enrolled in this instance.
 * @property int    $maxenrolled                The maximum number of users enrolled in this instance (alias for customint3).
 * @property int    $customint4                 If to send welcome email.
 * @property bool   $sendcoursewelcomemessage   If to send welcome email (alias for customint4).
 * @property int    $customint5                 The cohort restriction id.
 * @property int    $cohortrestrictionid        The cohort restriction id (alias for customint5).
 * @property int    $customint6                 If to allow new enrolments.
 * @property bool   $allownewenrol              If to allow new enrolments (alias for customint6).
 * @property int    $customint7                 The minimum number of required courses for enrolment restriction.
 * @property int    $minrequiredcourses         The minimum number of required courses for enrolment restriction
 *                                              (alias for customint7).
 * @property int    $customint8                 If to enable awards.
 * @property bool   $enableawards               If to enable awards (alias for customint8).
 * @property string $customchar1                Not used.
 * @property string $customchar2                Not used.
 * @property string $customchar3                The ids of the courses required for enrolment restriction (string)
 *                                              integers imploded by ','.
 * @property string $requiredcourses            The ids of the courses required for enrolment restriction (alias for customchar3).
 * @property int    $customdec1                 The condition for award (percentage) (int) 0 - 99.
 * @property float  $awardcondition             The condition for award (percentage) (alias for customdec1).
 * @property float  $customdec2                 The award value per each raw mark above the condition.
 * @property float  $awardvalue                 The award value per each raw mark above the condition (alias for customdec2).
 * @property string $customtext1                The welcome email content.
 * @property string $welcomemessage             The welcome email content (alias for customtext1).
 * @property string $customtext2                The restriction rules in JSON format.
 * @property string $availabilityconditionsjson The availability conditions in JSON format (alias for customtext2).
 * @property string $customtext3                The offers rules in JSON format.
 * @property string $offersrules                The offers rules in JSON format (alias for customtext3).
 * @property string $customtext4                Not used.
 * @property int    $timecreated                The time at which the instance was created.
 * @property int    $timemodified               The time at which the instance was modified.
 * @property-read \stdClass $instance The enrol wallet instance object.
 * @property-read float $costafter The cost after calculating discounts.
 * @property-read coupons $couponutil The coupon helper class object.
 * @property-read int $userid The id of the user we need to calculate the discount for.
 */
class instance extends entity implements \IteratorAggregate {
    /**
     * Calculate the cost after discount sequentially.
     * @var int
     */
    public const B_SEQ = 1;

    /**
     * Apply the sum of discounts.
     * @var int
     */
    public const B_SUM = 2;

    /**
     * Apply max discount.
     * @var int
     */
    public const B_MAX = 0;

    /**
     * The enrol wallet instance.
     * @var stdClass
     */
    public stdClass $instance;

    /**
     * The all discounts in this instance.
     * @var float[]
     */
    private array $discounts = [];

    /**
     * Caching instances.
     * @var array
     */
    protected static array $cached = [];

    /**
     * If the instance class in dirty state and the cached values
     * of $costafter could be cleared.
     * @var bool
     */
    private bool $dirty = false;

    /**
     * Check if this instance as no cost set.
     * @var bool
     */
    private bool $nocost = false;

    /**
     * Create a new enrol wallet instance helper class.
     * store the cost after discount.
     *
     * @param int|\stdClass $instanceorid The enrol wallet instance or its id.
     * @param int           $userid       the id of the user, 0 means the current user.
     */
    public function __construct(int|stdClass $instanceorid, int $userid = 0) {
        $this->instance = match (true) {
            is_number($instanceorid)      => self::get_instance_by_id($instanceorid),
            $instanceorid instanceof self => $instanceorid->get_instance(),
            default                       => $instanceorid,
        };

        parent::__construct($this->instance->courseid, $this->instance->id, $userid);
    }

    /**
     * Get the course context this instance belongs to.
     * @return \core\context
     */
    public function get_context(): \context {
        return $this->get_course_context();
    }

    /**
     * Return the coupon area const value AREA_ENROL.
     * @return int
     */
    protected static function get_coupon_area(): int {
        return coupons::AREA_ENROL;
    }

    /**
     * Magic getter for instance properties.
     *
     * @param  string $name The property name.
     * @return mixed
     */
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        if (property_exists($this->instance, $name)) {
            return $this->instance->$name;
        }

        // If the property is not found in the instance object, try to get it from the field map.
        $fieldname = $this->get_instance_field_map($name);

        if ($fieldname && property_exists($this->instance, $fieldname)) {
            return $this->instance->$fieldname;
        }
        debugging('Invalid property: ' . $name . ' in instance helper class', DEBUG_ALL);

        return null;
    }

    /**
     * Magic setter for instance properties.
     *
     * @param  string $name  The property name.
     * @param  mixed  $value The value to set.
     * @return void
     */
    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }

        if (property_exists($this->instance, $name)) {
            $this->instance->$name = $value;
            $this->$name           = $value;
        } else {
            // If the property is not found in the instance object, try to get it from the field map.
            $fieldname = $this->get_instance_field_map($name);

            if ($fieldname) {
                $this->instance->$fieldname = $value;
                $this->$fieldname           = $value;
            } else {
                debugging('Invalid property: ' . $name . ' in instance helper class', DEBUG_ALL);
            }
        }
        $this->mark_as_dirty();
    }

    /**
     * Magic isset for instance properties.
     *
     * @param  string $name The property name.
     * @return bool
     */
    public function __isset($name) {
        if (property_exists($this, $name) && isset($this->$name)) {
            return true;
        }

        if (property_exists($this->instance, $name) && isset($this->instance->$name)) {
            return true;
        }

        // If the property is not found in the instance object, try to get it from the field map.
        $fieldname = $this->get_instance_field_map($name);

        if ($fieldname && property_exists($this->instance, $fieldname) && isset($this->instance->$fieldname)) {
            return true;
        }

        return false;
    }

    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
    /**
     * Returns an external iterator.
     * @return \ArrayIterator
     */
    public function getIterator(): \Traversable {
        // phpcs:enable
        return new \ArrayIterator($this->instance);
    }

    /**
     * Get the field map for the instance.
     * The map is used to get the field name from the instance object.
     *
     * @param  string|null       $fieldname The field name to get the map for, if null returns the whole map.
     * @return array|string|null
     */
    protected function get_instance_field_map(?string $fieldname = null): array|string|null {
        $map = [
            'customint1'  => 'paymentaccountid', // Payment Account id.
            'customint2'  => 'longtimenosee', // Long time no see.
            'customint3'  => 'maxenrolled', // Max enrolled users.
            'customint4'  => 'sendcoursewelcomemessage', // Send welcome message.
            'customint5'  => 'cohortrestrictionid', // Cohort restriction id.
            'customint6'  => 'allownewenrol', // Allow new enrol.
            'customint7'  => 'minrequiredcourses', // Min number or required courses.
            'customint8'  => 'enableawards', // Enable Awards.
            'customchar1' => null, // Not used...
            'customchar2' => null, // Not used...
            'customchar3' => 'requiredcourses', // Ids of the courses required for enrol
                                                // restriction (string) integers imploded by ','.
            'customdec1'  => 'awardcondition', // Condition for award (percentage) (int) 0 - 99.
            'customdec2'  => 'awardvalue', // Award value per each raw mark above the condition (float).
            'customtext1' => 'welcomemessage', // Welcome email content (string).
            'customtext2' => 'availabilityconditionsjson', // Restriction rules (JSON).
            'customtext3' => 'offersrules', // Offers rules (JSON).
            'customtext4' => null, // Not used....
        ];

        if (empty($fieldname)) {
            return $map;
        }

        foreach ($map as $key => $value) {
            if ($value === $fieldname) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Set static values to prevent recalculating the discounts
     * for multiple callings.
     * @return void
     */
    private function set_static_cache(): void {
        $cache            = new \stdClass();
        $cache->costafter = $this->get_cost_after_discount() ?? null;
        $cache->discounts = $this->discounts;

        self::$cached[$this->id . '-' . $this->userid] = $cache;
    }

    /**
     * Reset static values.
     * @return void
     */
    public static function reset_static_cache(): void {
        self::$cached = [];
    }

    /**
     * Check if the user has enrolment record existed for this instance.
     * @param  bool $activeonly
     * @return bool
     */
    public function is_enrolled(bool $activeonly = true): bool {
        global $DB;
        $sql = 'SELECT 1 FROM {user_enrolments} ue
                WHERE ue.enrolid = :enrolid
                  AND ue.userid  = :userid';
        $params = ['enrolid' => $this->id, 'userid' => $this->userid];

        if ($activeonly) {
            $sql .= ' AND ue.status = :status AND (ue.timeend = 0 OR ue.timeend > :timenow1) AND ue.timestart < :timenow2';
            $now                = timedate::time();
            $params['status']   = ENROL_USER_ACTIVE;
            $params['timenow1'] = $now;
            $params['timenow2'] = $now;
        }

        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Check if the user has a wallet enrollment in
     * a given course.
     * @param int $courseid
     * @param int $userid
     * @param bool $activeonly
     * @return bool
     */
    public static function is_enrolled_by_wallet(int $courseid, int $userid = 0, bool $activeonly = true) {
        global $DB, $USER;
        if ($userid <= 0) {
            $userid = (int)$USER->id;
        }

        $sql = 'SELECT 1
                FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE ue.userid  = :userid
                  AND e.enrol = :wallet
                  AND e.courseid = :courseid';
        $params = [
            'wallet'  => 'wallet',
            'userid'  => $userid,
            'courseid' => $courseid,
        ];

        if ($activeonly) {
            $sql .= ' AND ue.status = :status AND (ue.timeend = 0 OR ue.timeend > :timenow1) AND ue.timestart < :timenow2';
            $now                = timedate::time();
            $params['status']   = ENROL_USER_ACTIVE;
            $params['timenow1'] = $now;
            $params['timenow2'] = $now;
        }

        return $DB->record_exists_sql($sql, $params);
    }
    /**
     * Get the user enrollments records for this instance.
     * @return array
     */
    public function get_enrollments(): array {
        global $DB;
        $sql = 'SELECT *
                FROM {user_enrolments} ue
                WHERE ue.enrolid = :enrolid
                  AND ue.userid  = :userid';
        $params = ['enrolid' => $this->id, 'userid' => $this->userid];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get the enrol wallet instance by id.
     * @param  int      $instanceid
     * @return stdClass
     */
    private static function get_instance_by_id(int $instanceid): stdClass {
        global $DB;
        $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $instanceid], '*', MUST_EXIST);

        return $instance;
    }

    /**
     * Get the enrol wallet instance record.
     * @return \stdClass
     */
    public function get_instance(): stdClass {
        return $this->instance;
    }

    /**
     * Get course context object at which the instance belongs to.
     * @return \core\context
     */
    public function get_course_context(): \core\context\course {
        return \core\context\course::instance($this->courseid);
    }

    /**
     * Get instance name.
     * @return string
     */
    public function get_name(): string {
        $wallet = new wallet();

        return $wallet->get_instance_name($this->instance);
    }

    /**
     * Calculate and return discount due to repurchasing the course.
     * @return float from 0 to 1
     */
    private function get_repurchase_discount(): float {
        global $DB;
        $userid     = $this->userid;
        $instanceid = $this->instance->id;
        $discount   = 0;

        if ($ue = $DB->get_record('user_enrolments', ['enrolid' => $instanceid, 'userid' => $userid])) {
            $config = config::make();

            if (!empty($ue->timeend) && $config->repurchase) {
                if ($first = $config->repurchase_firstdis) {
                    $discount   = min($first / 100, 1);
                    $second     = $config->repurchase_seconddis;
                    $timepassed = $ue->timemodified > $ue->timecreated + $ue->timeend - $ue->timestart;

                    if ($second && $ue->modifierid == $userid && $timepassed) {
                        $discount = max($second / 100, $discount);
                    }
                }
            }
        }

        return min($discount, 1);
    }

    /**
     * Calculate and return the discount due to offers.
     * @return float from 0 to 1
     */
    private function get_offers_discount(): float {
        $offers   = new offers($this->instance, $this->userid);
        $discount = 0;

        switch ((int)config::make()->discount_behavior) {
            case self::B_SUM:
                $discount = $offers->get_sum_discounts();
                break;

            case self::B_MAX:
                $discount = $offers->get_max_valid_discount();
                break;

            case self::B_SEQ:
            default:
                $discount = $this->calculate_sequential_discount($offers->get_available_discounts(), true);
        }

        return min(1, $discount / 100);
    }

    /**
     * Calculate, store and return all types of discounts.
     * @return array
     */
    private function calculate_discounts(): array {
        $this->discounts = [
            'coupons'    => $this->get_coupon_discount($this->cost),
            'profile'    => $this->get_profile_field_discount(),
            'repurchase' => $this->get_repurchase_discount(),
            'offers'     => $this->get_offers_discount(),
        ];

        return $this->discounts;
    }

    /**
     * Getter for dicounts values (no details).
     * @return float[]
     */
    public function get_discounts(): array {
        return $this->discounts;
    }

    /**
     * Get percentage discount for a user from custom profile field and coupon code.
     * and then calculate the cost of the course after discount.
     * @return void
     */
    private function calculate_cost_after_discount(): void {
        $instance = $this->instance;
        $cost     = $instance->cost;

        if (!is_numeric($cost) || $cost < 0) {
            $this->nocost = true;
            return;
        }

        $cost = (float)$cost;

        if ($cost == 0) {
            $this->costafter = $cost;
            return;
        }

        $cache = self::$cached[$this->id . '-' . $this->userid] ?? null;

        if ($cache && isset($cache->costafter)) {
            $this->discounts = $cache->discounts;
            $this->costafter = $cache->costafter;

            return;
        }

        $discounts = $this->calculate_discounts();
        $discount  = 0;

        $behavior = (int)config::make()->discount_behavior;
        switch ($behavior) {
            case self::B_SUM:
                foreach ($discounts as $d) {
                    $discount += $d;
                }
                break;
            case self::B_MAX:
                $discount = max($discounts);
                break;
            case self::B_SEQ:
                $discount = $this->calculate_sequential_discount($discounts);
                break;
            default:
                $discount = max($discounts);
                debugging("Unsupported configuration 'discount_behavior' $behavior", DEBUG_DEVELOPER);
        }

        $discount        = min(1, $discount);
        $this->costafter = $cost * (1 - $discount);
    }

    /**
     * sequentially calculate discount.
     * @param  array $discounts
     * @param  bool  $percentage
     * @return float
     */
    private function calculate_sequential_discount(array $discounts, bool $percentage = false): float {
        \core_collator::asort($discounts, \core_collator::SORT_NUMERIC);
        $discounts = array_reverse($discounts);

        $discount = 0;

        foreach ($discounts as $d) {
            $d        = $percentage ? $d / 100 : $d;
            $discount = 1 - (1 - $discount) * (1 - $d);
        }
        $discount = min(1, $discount);

        if ($percentage) {
            return $discount * 100;
        }

        return $discount;
    }

    /**
     * Check if the cached values of cost after discount need to be cleared first.
     * @return bool
     */
    public function is_dirty(): bool {
        return $this->dirty;
    }

    /**
     * Mark as dirty to clear the cached values of cost after discount.
     * @return void
     */
    public function mark_as_dirty(): void {
        $this->dirty = true;
    }

    /**
     * Check if the instance is dirty and hence clear
     * caches.
     * @return void
     */
    protected function check_dirty(): void {
        if ($this->is_dirty()) {
            self::reset_static_cache();
            $this->calculate_cost_after_discount();
            $this->dirty = false;
        }
    }
    /**
     * Update the instance record in the database.
     * @return bool
     */
    public function update(): bool {
        global $DB;
        $record = $this->get_instance();
        $plugin = enrol_wallet_plugin::get_plugin();
        $done = $plugin->update_instance($record, $record);
        $this->instance = $DB->get_record('enrol', ['id' => $this->id]);
        $this->mark_as_dirty();
        return $done;
    }

    /**
     * Set the userid to calculate the discount for.
     * @param int|stdClass $user
     * @return void
     */
    public function set_user(int|stdClass $user = 0): void {
        parent::set_user($user);
        $this->mark_as_dirty();
    }
    /**
     * Get the cost of the enrol instance after discount.
     * @param  ?float     $unused
     * @return float|null the cost after discount.
     */
    public function get_cost_after_discount(?float $unused = null): ?float {
        $this->check_dirty();

        if ($this->nocost) {
            return null;
        }

        if (!isset($this->costafter)) {
            $this->calculate_cost_after_discount();
            $this->set_static_cache();
        }

        if (isset($this->costafter)) {
            return $this->costafter;
        }

        return null;
    }

    /**
     * Check if there is a discount in this instance.
     * @return bool
     */
    public function has_discount(): bool {
        $this->check_dirty();

        $costafter = $this->get_cost_after_discount();
        if ($costafter === null) {
            return false;
        }

        if ($costafter < $this->instance->cost || $costafter === 0.0) {
            return true;
        }

        $costs = $this->get_all_costs();

        if ($costafter < max($costs)) {
            return true;
        }

        return false;
    }

    /**
     * get the discount in this instance in percentage.
     * @return int from 0 to 100
     */
    public function get_rounded_discount(): int {
        $this->check_dirty();
        $costafter = $this->get_cost_after_discount();
        if ($costafter === null) {
            return 0;
        }

        if ($costafter === 0.0) {
            return 100;
        }

        $difference = $this->instance->cost - $costafter;

        if ($difference <= 0) {
            $costs      = $this->get_all_costs();
            $difference = max($costs) - $costafter;
        }

        if ($difference > 0) {
            return (int)($difference / $this->instance->cost * 100);
        }

        return 0;
    }

    /**
     * Return all discounts in all instances.
     * @return int[]
     */
    public function get_all_discounts(): array {
        global $DB;
        $instances = $DB->get_records('enrol', ['courseid' => $this->courseid]);
        $discounts = [];

        foreach ($instances as $instance) {
            $helper      = new static($instance);
            $discounts[] = $helper->get_rounded_discount();
        }

        return $discounts;
    }

    /**
     * Return an array of costs of non restricted instances keyed with the instance id;.
     * @return array
     */
    public function get_all_costs(): array {
        global $DB;
        $instances = $DB->get_records('enrol', ['courseid' => $this->courseid]);
        $costs     = [];

        foreach ($instances as $instance) {
            $wallet    = new wallet($instance);
            $cost      = $wallet->get_cost_after_discount($this->userid, $instance);
            $enrolstat = $wallet->can_self_enrol($instance);

            if (in_array($enrolstat, [true, wallet::INSUFFICIENT_BALANCE, wallet::INSUFFICIENT_BALANCE_DISCOUNTED], true)) {
                $costs[$instance->id] = $cost;
            }
        }

        return $costs;
    }

    /**
     * Return the id of cheapest instance in this course.
     * @return int|null
     */
    public function get_the_cheapest_instance_id(): ?int {
        $costs = $this->get_all_costs();
        $min   = min($costs);

        foreach ($costs as $id => $cost) {
            if ($cost == $min) {
                return $id;
            }
        }

        return null;
    }
}
