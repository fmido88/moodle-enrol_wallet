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
 * Functions to handle all coupons operations.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/enrol/wallet/lib.php");

use enrol_wallet\util\balance;
use enrol_wallet\util\balance_op;
use enrol_wallet_plugin as wallet;
use enrol_wallet\util\instance;
use enrol_wallet\category\helper as cat_helper;

/**
 * Class to handle coupons operations.
 */
class coupons {
    /**
     * Coupons disabled.
     */
    const NOCOUPONS = 0;
    /**
     * Fixed type coupons.
     */
    const FIXED = 1;
    /**
     * Percentage discount type coupons.
     */
    const DISCOUNT = 2;
    /**
     * Enrol type coupons.
     */
    const ENROL = 3;
    /**
     * Category type coupons.
     */
    const CATEGORY = 4;
    /**
     * All types enabled.
     */
    const ALL = 5;
    /**
     * Coupons types
     * keys according to how it stored in database
     */
    const TYPES = [
        'fixed'    => self::FIXED,
        'percent'  => self::DISCOUNT,
        'category' => self::CATEGORY,
        'enrol'    => self::ENROL,
    ];
    /**
     * Applying coupon on enrol area.
     */
    const AREA_ENROL = 5;
    /**
     * Applying coupon on cm (for availability_wallet)
     */
    const AREA_CM = 7;
    /**
     * Applying coupon on section (for availability_wallet)
     */
    const AREA_SECTION = 9;
    /**
     * Applying coupon in topping up form.
     */
    const AREA_TOPUP = 11;
    /**
     * Array of aras codes.
     */
    const AREAS = [
        'enrol'   => self::AREA_ENROL,
        'cm'      => self::AREA_CM,
        'section' => self::AREA_SECTION,
        'topup'   => self::AREA_TOPUP,
    ];
    /**
     * The coupon code.
     * @var string
     */
    public $code;
    /**
     * The coupon type
     * @var string
     */
    public $type = null;
    /**
     * The coupon value
     * @var float
     */
    public $value = 0;
    /**
     * The user id.
     * @var int
     */
    protected $userid;
    /**
     * Is the coupon valid?
     * @var bool
     */
    public $valid;
    /**
     * The area code at which the coupon applied.
     * @var int
     */
    protected $area = self::AREA_TOPUP;
    /**
     * The id of the area at which the coupon applied
     * enrol_wallet instance id, cm id, section id or 0 for toping up.
     * @var int
     */
    protected $areaid = 0;
    /**
     * Array of courses ids at which the coupon can be used.
     * @var array
     */
    protected $courses = [];
    /**
     * category id at which the coupon could be used.
     * @var int
     */
    protected $category = 0;
    /**
     * The record of the coupon in the database.
     * @var \stdClass
     */
    protected $record;
    /**
     * Error string
     * @var string
     */
    public $error;
    /**
     * The coupons source.
     * @var int
     */
    private $source;
    /**
     * Constructor function for coupons operations, This will retrieve the coupon data and validate it.
     * @param string $code the coupon code.
     * @param int $userid The id of the user checking for the coupon, 0 means the current user.
     */
    public function __construct($code, $userid = 0) {
        global $USER;
        $this->source = get_config('enrol_wallet', 'walletsource');
        $this->code = $code;
        if (empty($userid)) {
            $this->userid = $USER->id;
        } else {
            $this->userid = $userid;
        }
        if (isguestuser($this->userid) || empty($this->userid)) {
            $this->valid = false;
            $this->error = 'Please login to use coupons';
        }
        $this->set_coupon_data($code, $this->userid);
    }

    /**
     * Check the coupon's data and cashing it.
     * @param string $coupon the coupon code to check.
     * @param int $userid
     */
    protected function set_coupon_data($coupon, $userid): void {
        global $DB;
        if (!$this->is_enabled()) {
            // This means that coupons is disabled in the site.
            $this->valid = false;
            $this->error = 'Coupons are disabled in this site.';
            return;
        }

        if ($this->source == balance::WP) {
            $wordpress = new \enrol_wallet\wordpress;
            $coupondata = $wordpress->get_coupon($coupon, $userid, 0, false);

            if (!is_array($coupondata)) {
                // Error from wordpress.
                $this->valid = false;
                $this->error = $coupondata;
                return;
            }

        } else {
            // If it is on moodle website.
            // Get the coupon data from the database.
            $couponrecord = $DB->get_record('enrol_wallet_coupons', ['code' => $coupon]);
            if (!$couponrecord) {
                $this->valid = false;
                $this->error = get_string('coupon_notexist', 'enrol_wallet');
                return;
            }
            $this->record = $couponrecord;
            // Set the returning coupon data.
            $coupondata = [
                'value' => $couponrecord->value,
                'type'  => $couponrecord->type,
            ];
            if (!empty($couponrecord->courses)) {
                $coupondata['courses'] = explode(',', $couponrecord->courses);
            }
            if (!empty($couponrecord->category)) {
                $coupondata['category'] = $couponrecord->category;
            }
        }
        foreach ($coupondata as $key => $value) {
            $this->$key = $value;
        }
        $this->type = self::TYPES[$this->type];
        if ($this->type !== self::DISCOUNT) {
            self::unset_session_coupon();
        }
        $this->check_enabled();
        $this->validated_record();
    }

    /**
     * Check if the current coupon type is enabled.
     */
    protected function check_enabled() {
        // First check if this type is enabled in the website.
        if (!$this->is_enabled_type()) {
            $identifier = $this->get_key($this->type, self::TYPES) . 'coupondisabled';
            $this->error = get_string($identifier, 'enrol_wallet');
            $this->valid = false;
        }
    }

    /**
     * Check if the current type is enabled or not
     * @return bool
     */
    public function is_enabled_type() {
        $type = $this->type;
        $enabled = $this->get_enabled();
        if (in_array(self::NOCOUPONS, $enabled)) {
            return false;
        }
        if (!is_number($type)) {
            $type = self::TYPES[$type];
        }
        if (in_array(self::ALL, $enabled) && $this->source == balance::MOODLE) {
            return true;
        }
        $wpcoupons = [self::FIXED, self::DISCOUNT];
        if ($this->source == balance::WP && !in_array($type, $wpcoupons)) {
            return false;
        }
        if (in_array($type, $enabled)) {
            return true;
        }
        return false;
    }

    /**
     * Get an array of enabled coupons types
     * @return array
     */
    public static function get_enabled() {
        $config = get_config('enrol_wallet', 'coupons');
        if (empty($config)) {
            return [self::NOCOUPONS];
        }
        $types = explode(',', $config);
        if (count($types) >= 4 || in_array(self::ALL, $types)) {
            return [self::ALL];
        }
        return $types;
    }

    /**
     * Return an array of enabled coupons options keyed with the type code
     * @return array
     */
    public static function get_enabled_options() {
        $options = [];
        if (!$config = get_config('enrol_wallet', 'coupons')) {
            return $options;
        }
        $enabled = explode(',', $config);
        if (in_array(self::NOCOUPONS, $enabled)) {
            return $options;
        }
        $names = self::get_coupons_options();
        if (in_array(self::ALL, $enabled)) {
            return $names;
        }
        foreach ($enabled as $key) {
            $options[$key] = $names[$key];
        }
        return $options;
    }

    /**
     * Return all options of coupons types and their names to be used in plugin settings.
     * @return array
     */
    public static function get_coupons_options() {
        return [
            self::FIXED    => get_string('fixedvaluecoupon', 'enrol_wallet'),
            self::DISCOUNT => get_string('percentdiscountcoupon', 'enrol_wallet'),
            self::CATEGORY => get_string('categorycoupon', 'enrol_wallet'),
            self::ENROL    => get_string('enrolcoupon', 'enrol_wallet'),
        ];
    }

    /**
     * Check if coupons is enabled on this site or not.
     * @return bool
     */
    public static function is_enabled() {
        if (in_array(self::NOCOUPONS, self::get_enabled())) {
            return false;
        }
        return true;
    }

    /**
     * Validate the coupon's record (time usage, number of usage ...)
     */
    protected function validated_record() {
        if (!empty($this->error)) {
            $this->valid = false;
            return;
        }

        if (!is_numeric($this->value) || ($this->value <= 0 && $this->type != self::ENROL)) {
            $this->valid = false;
            $this->error = get_string('coupon_invalidrecord', 'enrol_wallet');
            return;
        }

        if ($this->source == balance::MOODLE) {
            // If it is on moodle website.
            $couponrecord = $this->record;
            // Make sure that the coupon didn't exceed the max usage (0 mean unlimited).
            if (!empty($couponrecord->maxusage) && $couponrecord->maxusage <= $this->get_total_use()) {
                $this->valid = false;
                $this->error = get_string('coupon_exceedusage', 'enrol_wallet');
                return;
            }

            // Make sure that this coupon is within validation time (0 mean any time).
            if (!empty($couponrecord->validfrom) && $couponrecord->validfrom > time()) {
                $this->valid = false;
                $date = userdate($couponrecord->validfrom);
                $this->error = get_string('coupon_notvalidyet', 'enrol_wallet', $date);
                return;
            }

            if (!empty($couponrecord->validto) && $couponrecord->validto < time()) {
                $this->valid = false;
                $this->error = get_string('coupon_expired', 'enrol_wallet');
                return;
            }

            // Check the maximum limit per each user has not been reached.
            if (!empty($couponrecord->maxperuser) && $this->get_user_use() >= $couponrecord->maxperuser) {
                $this->valid = false;
                $this->error = get_string('coupon_exceedusage', 'enrol_wallet');
                return;
            }
        }
    }

    /**
     * Set the area of applying the coupon and its id.
     * @param int $area the area code {@see self::AREAS}
     * @param int $id The instance, cm or section id.
     */
    protected function set_area($area, $id = 0) {
        $this->area = $area;
        $this->areaid = $id;
    }

    /**
     * Check if the area is valid for applying this coupon.
     * MUST CALL ::set_area before using this method.
     * @return void
     */
    protected function validate_area() {
        if (!isset($this->type) || !empty($this->error) || empty($this->area)) {
            $this->valid = false;
        } else {
            switch ($this->area) {
                // For toping up the wallet.
                case self::AREA_TOPUP:
                    if (!in_array($this->type, [self::FIXED, self::CATEGORY])) {
                        $this->valid = false;
                    }
                    break;
                // At enrolment page.
                case self::AREA_ENROL:
                    if (empty($this->areaid)) {
                        $this->valid = false;
                    }
                    break;
                // For accessing course module or section.
                case self::AREA_CM:
                case self::AREA_SECTION:
                    if ($this->type == self::ENROL) {
                        $this->valid = false;
                    }
                    break;
                default: $this->valid = false;
            }
        }
        if (!$this->valid) {
            $this->error = get_string('coupon_applynothere', 'enrol_wallet');
        }
    }

    /**
     * Check if the category coupon if valid to be used here.
     */
    private function validate_category_coupon() {
        if (!$this->type == self::CATEGORY) {
            return;
        }
        $catenabled = (bool)get_config('enrol_wallet', 'catbalance') && $this->source == balance::MOODLE;
        if (empty($this->category) || (!$catenabled && $this->area == self::AREA_TOPUP)) {
            $this->error = get_string('coupon_applynothere_category', 'enrol_wallet');
            $this->valid = false;
            return;
        }
        $catid = $this->category;
        $catop = new cat_helper($catid);
        // This type of coupons is restricted to be used in certain category and its children.
        if (!$this->is_same_category($catop)) {
            $this->valid = false;
            $categoryname = $catop->get_category()->get_nested_name(false);
            $this->error = get_string('coupon_categoryfail', 'enrol_wallet', $categoryname);
        }
    }

    /**
     * Check if the applying area is within the same category in property category
     * @param cat_helper $catop
     * @return bool
     */
    private function is_same_category($catop = null) {
        if (empty($catop)) {
            $catid = $this->category;
            $catop = new cat_helper($catid);
        }

        switch ($this->area) {
            case self::AREA_ENROL:
                return $catop->is_child_instance($this->areaid);
            case self::AREA_CM:
                return $catop->is_child_cm($this->areaid);
            case self::AREA_SECTION:
                return $catop->is_child_section($this->areaid);
            case self::AREA_TOPUP:
                return true;
            default:
                return false;
        }
    }
    /**
     * Validate enrol coupon.
     */
    private function validate_enrol_coupon() {
        if (empty($this->courses)) {
            $this->valid = false;
            $this->error = get_string('coupon_applynothere_enrol', 'enrol_wallet');
            return;
        }
        global $DB;

        $courseid = $DB->get_field('enrol', 'courseid', ['id' => $this->areaid, 'enrol' => 'wallet'], MUST_EXIST);
        if (!in_array($courseid, $this->courses)) {
            $available = '';
            foreach ($this->courses as $courseid) {
                $coursename = get_course($courseid)->fullname;
                $available .= '- ' . $coursename . '<br>';
            }
            $this->valid = false;
            $this->error = get_string('coupon_enrolerror', 'enrol_wallet', $available);
        }
    }

    /**
     * Check if the coupon is valid to be used in this area.
     * returns string on error and true if valid.
     * @param int $area code, value, type, courses, category
     * @param int $areaid the area at which the coupon applied (instanceid, cmid, sectionid)
     * @return bool|string
     */
    public function validate_coupon($area = null, $areaid = 0) {
        if (!empty($this->error)) {
            return $this->error;
        }

        $this->valid = true;
        if (is_string($area) && !is_number($area)) {
            $area = self::AREAS[$area];
        }

        if (!is_null($area) && in_array($area, self::AREAS)) {
            $this->set_area($area, $areaid);
        }

        $this->validate_area();

        if (!empty($this->error)) {
            return $this->error;
        }

        switch ($this->type) {
            case self::DISCOUNT:
                if ($this->value > 100) {
                    $this->error = get_string('invalidpercentcoupon', 'enrol_wallet');
                    $this->valid = false;
                } else if (!empty($this->category) && !$this->is_same_category()) {
                    $this->error = get_string('coupon_applynothere_discount', 'enrol_wallet');
                    $this->valid = false;
                }
                break;
            case self::FIXED:
                break;
            case self::CATEGORY:
                $this->validate_category_coupon();
                break;
            case self::ENROL:
                $this->validate_enrol_coupon();
                break;
            default:
        }

        if (!empty($this->error)) {
            return $this->error;
        }
        return $this->valid;
    }

    /**
     * Check if the coupons is passed primary validation of not the
     * area set yet.
     * @return bool
     */
    public function is_valid_for_now() {
        return empty($this->error) && (is_null($this->valid) || $this->valid === true);
    }
    /**
     * Return the value of this coupon.
     * @return float
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Return the type as string
     * @return string
     */
    public function get_type() {
        return $this->get_key($this->type, self::TYPES);
    }

    /**
     * Get the key of a value in the given array.
     * @param mixed $value
     * @param array $array
     */
    protected function get_key($value, $array) {
        $fliped = array_flip($array);
        return $fliped[$value] ?? null;
    }

    /**
     * Return the error due to validation process.
     * false if there is none and the coupon is valid.
     * @return bool|string
     */
    public function has_error() {
        if (!empty($this->error)) {
            return $this->error;
        }
        return !$this->valid;
    }

    /**
     * Get the coupons data
     * @return array
     */
    public function get_data() {
        if (!$this->valid) {
            return [];
        }
        $data = [
            'code' => $this->code,
            'value' => $this->value,
            'type' => $this->get_type(),
            'category' => $this->category ?? null,
            'courses' => $this->courses ?? null,
        ];
        if (!empty($this->record)) {
            foreach ($this->record as $key => $value) {
                if (!isset($data[$key])) {
                    $data[$key] = $value;
                }
            }
        }
        return $data;
    }

    /**
     * Apply the coupon for enrolment or topping up the wallet.
     *
     * @param string $area
     * @param int $areaid
     * @return void
     */
    public function apply_coupon($area = self::AREA_TOPUP, $areaid = 0) {
        if (!isset($this->valid)) {
            $this->validate_coupon($area, $areaid);
        }

        if (!$this->valid) {
            \core\notification::error($this->error ?? get_string('error', 'error'));
            return;
        }

        $used = false;
        $op = $this->get_balance_operation();
        // Check if we applying the coupon (fixed value coupons) charge the wallet directly.
        if (in_array($this->type, [self::FIXED, self::CATEGORY])) {
            $desc = get_string('topupcoupon_desc', 'enrol_wallet', $this->code);
            $op->credit($this->value, $op::C_COUPON, $this->record->id, $desc, false);
            $used = true;
        }

        // After we get the coupon data now we check if this coupon used from enrolment page.
        // If true and the value >= the fee, save time for student and enrol directly.
        if ($this->area == self::AREA_ENROL) {
            $balance = $op->get_valid_balance();
            $util = new instance($this->areaid, $this->userid);
            $instance = $util->get_instance();
            $user = \core_user::get_user($this->userid);
            $fee = (float)$util->get_cost_after_discount();
            $plugin = new wallet();

            if ($this->type == self::ENROL
                || (
                    ($this->type == self::CATEGORY || $this->type == self::FIXED)
                    && $balance >= $fee
                    )
                ) {

                $used = true;
                // Check if the coupon value is grater than or equal the fee.
                // Enrol the user in the course.
                $context = \context_course::instance($instance->courseid);
                if (!is_enrolled($context, $user, '', true)) {
                    if ($this->type == self::ENROL) {
                        $charge = false;
                    } else {
                        $charge = true;
                    }
                    $plugin->enrol_self($instance, $user, $charge);
                } else if ($this->type == self::ENROL) {
                    $used = false;
                }

            } else if ($this->type == self::CATEGORY && $balance < $fee) {
                $error = get_string('coupon_cat_notsufficient', 'enrol_wallet');
                \core\notification::error($error);
            }
        }

        if ($this->type == self::DISCOUNT) {
            self::set_session_coupon($this->code);
        }

        if ($used) {
            // Mark the coupon as used.
            $this->mark_coupon_used();
        }
    }

    /**
     * Get balance operation object according to the given area.
     *
     * @return balance_op
     */
    private function get_balance_operation() {
        if ($this->type === self::FIXED) {
            return new balance_op($this->userid);
        }
        switch ($this->area) {
            case self::AREA_ENROL:
                return balance_op::create_from_instance($this->areaid, $this->userid);
            case self::AREA_TOPUP:
                if ($this->type == self::CATEGORY) {
                    return new balance_op($this->userid, $this->category);
                } else {
                    return new balance_op($this->userid);
                }
            case self::AREA_CM:
                return balance_op::create_from_cm($this->areaid, $this->userid);
            case self::AREA_SECTION:
                return balance_op::create_from_section($this->areaid, $this->userid);
            default:
                return new balance_op($this->userid);
        }
    }

    /**
     * Called when the coupon get used and mark it as used.
     * MUSN'T be called before validation
     * @return void
     */
    public function mark_coupon_used() {
        global $DB;
        if (PHPUNIT_TEST && !isset($this->valid)) {
            $this->validate_coupon();
        }
        if (!isset($this->valid)) {
            throw new \coding_exception('cannot be called before validation');
        } else if (!$this->valid) {
            return;
        }
        // Unset the session coupon to make sure not used again.
        self::unset_session_coupon();

        if ($this->area == self::AREA_ENROL) {
            $instanceid = $this->areaid;
        } else {
            $instanceid = 0;
        }
        if ($this->source == balance::WP) {
            if ($this->type == self::DISCOUNT) {
                // It is already included in the wordpress plugin code.
                $wordpress = new \enrol_wallet\wordpress;
                $wordpress->get_coupon($this->code, $this->userid, $instanceid, true);
            }
        } else {
            $couponrecord = $this->record;
            $olduse = $DB->count_records('enrol_wallet_coupons_usage', ['code' => $this->code]);
            $usage = max($couponrecord->usetimes, $olduse) + 1;
            $couponrecord->lastuse = time();
            $couponrecord->usetimes = $usage;
            $DB->update_record('enrol_wallet_coupons', $couponrecord);
        }

        // Logging the usage in the coupon usage table.
        $logdata = [
            'code'       => $this->code,
            'type'       => $this->get_key($this->type, self::TYPES),
            'value'      => $this->value,
            'userid'     => $this->userid,
            'instanceid' => $instanceid,
            'timeused'   => time(),
        ];
        $id = $DB->insert_record('enrol_wallet_coupons_usage', (object)$logdata);

        unset($logdata['userid'], $logdata['timeused']);
        $eventdata = [
            'userid'        => $this->userid,
            'relateduserid' => $this->userid,
            'objectid'      => !empty($id) ? $id : null,
            'other'         => $logdata,
        ];

        if (!empty($instanceid)) {
            $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $instanceid], '*', MUST_EXIST);

            $eventdata['courseid'] = $instance->courseid;
            $eventdata['context'] = \context_course::instance($instance->courseid);

        } else {

            $eventdata['context'] = \context_system::instance();
        }

        $event = \enrol_wallet\event\coupon_used::create($eventdata);
        $event->trigger();
    }

    /**
     * Get the total number that the coupon has been used.
     * @return int|null
     */
    public function get_total_use() {
        if (!$this->source == balance::MOODLE) {
            return null;
        }
        global $DB;
        $count = $DB->count_records('enrol_wallet_coupons_usage', ['code' => $this->code]);
        return max($count, $this->record->usetimes);
    }

    /**
     * Get the number that this user has used the coupoun.
     * @return int|null
     */
    public function get_user_use() {
        if (!$this->source == balance::MOODLE) {
            return null;
        }
        global $DB;
        $count = $DB->count_records('enrol_wallet_coupons_usage', [
            'code' => $this->code,
            'userid' => $this->userid,
        ]);
        return $count;
    }
    /**
     * Get the coupons data, this function passed through all validation processes before return the coupon data
     * if the validation process faild, it will return a string of the error, else it returns array of the data.
     * If the parameter $instanceid passed, $cmid and $sectionid will be neglected, also if $cmid passed, $sectionid
     * will be neglected.
     * set $apply to true to apply the coupon.
     * @param string $code the coupon code
     * @param int $userid the id of the user
     * @param bool $apply Wheither or not we need to apply the coupon.
     * @param int $instanceid enrol wallet instance id if this coupon used in enrolment page.
     * @param int $cmid course module id if this coupon used for course module.
     * @param int $sectionid the course section id if the coupon used for section restriction.
     * @return string|array string in case of error, or and array of coupons data.
     */
    public static function get_coupon_value($code, $userid = 0, $apply = false, $instanceid = 0 , $cmid = 0, $sectionid = 0) {
        $coupon = new self($code, $userid);
        if (!empty($instanceid)) {
            $area = self::AREA_ENROL;
            $areaid = $instanceid;
        } else if (!empty($cmid)) {
            $area = self::AREA_CM;
            $areaid = $cmid;
        } else if (!empty($sectionid)) {
            $area = self::AREA_SECTION;
            $areaid = $sectionid;
        } else {
            $area = self::AREA_TOPUP;
            $areaid = 0;
        }
        $validation = $coupon->validate_coupon($area, $areaid);
        if (true !== $validation) {
            return $validation;
        }

        if ($apply) {
            $coupon->apply_coupon($area, $areaid);
        }

        return $coupon->get_data();
    }

    /**
     * Check if there is coupon code in session or as a parameter
     * @return string|null return the coupon code, or null if not found.
     */
    public static function check_discount_coupon() {
        $coupon = !empty($_SESSION['coupon']) ? clean_param($_SESSION['coupon'], PARAM_ALPHANUM) : null;
        return $coupon;
    }

    /**
     * Set coupon in the session.
     * @param string $code the coupon code.
     */
    public static function set_session_coupon($code) {
        $_SESSION['coupon'] = $code;
    }

    /**
     * Unset any session coupons.
     */
    public static function unset_session_coupon() {
        $_SESSION['coupon'] = null;
        unset($_SESSION['coupon']);
    }
}
