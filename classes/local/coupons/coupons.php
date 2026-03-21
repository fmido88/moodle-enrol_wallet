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

namespace enrol_wallet\local\coupons;

use context;
use context_module;
use core\exception\coding_exception;
use core_course_category;
use core_course_list_element;
use enrol_wallet\local\config;
use enrol_wallet\local\entities\category as cat_helper;
use enrol_wallet\local\entities\cm;
use enrol_wallet\local\entities\entity;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\entities\section;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet_plugin as wallet;
use stdClass;

/**
 * Class to handle coupons operations.
 */
class coupons {
    /**
     * Coupons disabled.
     */
    public const NOCOUPONS = 0;

    /**
     * Fixed type coupons.
     */
    public const FIXED = 1;

    /**
     * Percentage discount type coupons.
     */
    public const DISCOUNT = 2;

    /**
     * Enrol type coupons.
     */
    public const ENROL = 3;

    /**
     * Category type coupons.
     */
    public const CATEGORY = 4;

    /**
     * All types enabled.
     */
    public const ALL = 5;

    /**
     * Fixed discount type coupons.
     */
    public const FIXEDDISCOUNT = 6;

    /**
     * Coupons types
     * keys according to how it stored in database.
     */
    public const TYPES = [
        'fixed'         => self::FIXED,
        'percent'       => self::DISCOUNT,
        'category'      => self::CATEGORY,
        'enrol'         => self::ENROL,
        'fixeddis'      => self::FIXEDDISCOUNT,
    ];

    /**
     * Applying coupon on enrol area.
     */
    public const AREA_ENROL = 5;

    /**
     * Applying coupon on cm (for availability_wallet).
     */
    public const AREA_CM = 7;

    /**
     * Applying coupon on section (for availability_wallet).
     */
    public const AREA_SECTION = 9;

    /**
     * Applying coupon in topping up form.
     */
    public const AREA_TOPUP = 11;

    /**
     * Array of aras codes.
     */
    public const AREAS = [
        'enrol'   => self::AREA_ENROL,
        'cm'      => self::AREA_CM,
        'section' => self::AREA_SECTION,
        'topup'   => self::AREA_TOPUP,
    ];

    /**
     * The coupon code.
     * @var string
     */
    protected string $code;

    /**
     * The coupon type
     * Should be integer as one of the types constant but it starts initialization
     * as string first as the type saved in database table.
     * @var int
     */
    protected int $type = 0;

    /**
     * The coupon value.
     * @var float
     */
    protected float $value = 0;

    /**
     * The user id.
     * @var int
     */
    protected int $userid;

    /**
     * Is the coupon valid?
     * @var bool
     */
    protected bool $valid;

    /**
     * The area code at which the coupon applied.
     * @var int
     */
    protected int $area = self::AREA_TOPUP;

    /**
     * The id of the area at which the coupon applied
     * enrol_wallet instance id, cm id, section id or 0 for toping up.
     * @var int
     */
    protected int $areaid = 0;

    /**
     * Array of courses ids at which the coupon can be used.
     * @var array
     */
    protected array $courses = [];

    /**
     * category id at which the coupon could be used.
     * @var int
     */
    protected int $category = 0;

    /**
     * The record of the coupon in the database.
     * @var \stdClass
     */
    protected stdClass $record;

    /**
     * Error string.
     * @var string
     */
    protected string $error;

    /**
     * The coupons source.
     * @var int
     */
    private int $source;

    /**
     * Constructor function for coupons operations, This will retrieve the coupon data and validate it.
     * @param string $code   the coupon code.
     * @param int    $userid The id of the user checking for the coupon, 0 means the current user.
     */
    public function __construct($code, $userid = 0) {
        global $USER;
        $this->source = config::make()->walletsource;
        $this->code   = $code;
        $this->userid = empty($userid) ? $USER->id : $userid;

        $this->set_coupon_data($code, $this->userid);
    }

    /**
     * Check the coupon's data and cashing it.
     * @param string $coupon the coupon code to check.
     * @param int    $userid
     */
    protected function set_coupon_data($coupon, $userid): void {
        global $DB;

        if (!$this->is_enabled()) {
            // This means that coupons is disabled in the site.
            $this->valid = false;
            $this->error = 'Coupons are disabled in this site.';

            return;
        }

        if ($this->source === balance::WP) {
            $wordpress  = new \enrol_wallet\wordpress();
            $coupondata = $wordpress->get_coupon($coupon, $userid, 0, false);

            if (!\is_array($coupondata)) {
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
            if ($key == 'type') {
                $value = self::type_to_int($value);
            }
            $this->$key = $value;
        }
    }

    /**
     * Get the coupons data.
     * @return array
     */
    public function get_data() {
        if ($this->has_error()) {
            return [];
        }
        $data = [
            'code'     => $this->code,
            'value'    => $this->value,
            'type'     => $this->get_type(),
            'category' => $this->category ?? null,
            'courses'  => $this->courses ?? null,
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
     * Check if the current coupon type is enabled.
     * @return bool
     */
    protected function check_enabled(): bool {
        // First check if this type is enabled in the website.
        if (!$this->is_enabled_type()) {
            $identifier  = $this->get_type() . 'coupondisabled';
            $this->error = get_string($identifier, 'enrol_wallet');
            $this->valid = false;

            return false;
        }

        return true;
    }

    /**
     * Check if the current type is enabled or not.
     * @return bool
     */
    public function is_enabled_type(): bool {
        $type    = $this->type;
        $enabled = $this->get_enabled();

        if (\in_array(self::NOCOUPONS, $enabled)) {
            return false;
        }

        if (!is_number($type)) {
            $type = self::TYPES[$type];
        }

        if (\in_array(self::ALL, $enabled) && $this->source === balance::MOODLE) {
            return true;
        }
        $wpcoupons = [self::FIXED, self::DISCOUNT];

        if ($this->source === balance::WP && !\in_array($type, $wpcoupons)) {
            return false;
        }

        if (\in_array($type, $enabled)) {
            return true;
        }

        return false;
    }

    /**
     * Get an array of enabled coupons types.
     * @return array
     */
    public static function get_enabled() {
        $config = config::instance()->coupons;

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
     * Return an array of enabled coupons options keyed with the type code.
     * @return array
     */
    public static function get_enabled_options() {
        $options = [];

        if (!$config = config::instance()->coupons) {
            return $options;
        }
        $enabled = explode(',', $config);

        if (\in_array(self::NOCOUPONS, $enabled)) {
            return $options;
        }
        $names = self::get_coupons_options();

        if (\in_array(self::ALL, $enabled)) {
            return $names;
        }

        foreach ($enabled as $key) {
            $options[$key] = $names[$key];
        }

        return $options;
    }

    /**
     * Check if coupons is enabled on this site or not.
     * @return bool
     */
    public static function is_enabled() {
        if (\in_array(self::NOCOUPONS, self::get_enabled())) {
            return false;
        }

        return true;
    }

    /**
     * Check if the coupon is used for discount only.
     * @return bool
     */
    public function is_discount_coupon(): bool {
        return \in_array($this->type, [self::DISCOUNT, self::FIXEDDISCOUNT]);
    }

    /**
     * Check if the coupon type could be used to topup the wallet.
     * @return bool
     */
    public function is_topup_coupon(): bool {
        return \in_array($this->type, [self::FIXED, self::CATEGORY]);
    }

    /**
     * Check if the coupon could be used directly for enrolment.
     * @return bool
     */
    public function is_enrol_coupon(): bool {
        $true = \in_array($this->type, [self::ENROL, self::FIXED]);
        $true = $true || ($this->type === self::CATEGORY && $this->is_same_category());

        return $true;
    }

    /**
     * Set the area of applying the coupon and its id.
     * @param int $area the area code {@see self::AREAS}
     * @param int $id   The instance, cm or section id.
     */
    protected function set_area($area, $id = 0) {
        $this->area   = $area;
        $this->areaid = $id;
    }

    /**
     * Return all options of coupons types and their names to be used in plugin settings.
     * @return array
     */
    public static function get_coupons_options() {
        return [
            self::FIXED         => get_string('fixedvaluecoupon', 'enrol_wallet'),
            self::DISCOUNT      => get_string('percentdiscountcoupon', 'enrol_wallet'),
            self::CATEGORY      => get_string('categorycoupon', 'enrol_wallet'),
            self::ENROL         => get_string('enrolcoupon', 'enrol_wallet'),
            self::FIXEDDISCOUNT => get_string('fixeddiscountcoupon', 'enrol_wallet'),
        ];
    }

    /**
     * Get the coupon type in form of one of the integer type constants.
     * @param string|int $type
     */
    public static function type_to_int(string|int $type): ?int {
        if (\is_string($type) && \array_key_exists($type, self::TYPES)) {
            return self::TYPES[$type];
        }

        if (\is_number($type) && \in_array($type, self::TYPES)) {
            return $type;
        }

        return null;
    }

    /**
     * Get the coupon type in form of one of the string type constants.
     * @param  string|int  $type
     * @return string|null
     */
    public static function type_to_string(string|int $type): ?string {
        if (\is_number($type)) {
            return self::get_key($type, self::TYPES);
        }

        if (\is_string($type) && \array_key_exists($type, self::TYPES)) {
            return $type;
        }

        return null;
    }

    /**
     * Return the type as string as to be stored in database.
     * @param bool $string
     * @return string|int
     */
    public function get_type($string = true): string|int {
        if ($string) {
            return self::get_key($this->type, self::TYPES);
        }
        return $this->type;
    }

    /**
     * Get the key of a value in the given array.
     * @param mixed $value
     * @param array $array
     */
    protected static function get_key($value, $array) {
        $fliped = array_flip($array);

        return $fliped[$value] ?? null;
    }

    /**
     * Return a human readable name of the coupon type.
     * @param int|string $type
     */
    public static function get_type_visible_name($type) {
        if ($type = self::type_to_int($type)) {
            return self::get_coupons_options()[$type];
        }

        return null;
    }

    /**
     * Get the visible name of the area.
     * @param  int|string $area
     * @return string
     */
    public static function get_area_visible_name(int|string $area) {
        if (is_number($area)) {
            $areas = array_flip(self::AREAS);
            $area  = $areas[(int)$area] ?? '';
        }

        if (empty($area) || !\array_key_exists($area, self::AREAS)) {
            debugging("The coupon area $area not exists.");

            return '';
        }

        return get_string("couponarea_{$area}", 'enrol_wallet');
    }

    /**
     * Get the area name at which the coupon used for.
     * Instance, cm or section name.
     * @param  int    $area
     * @param  int    $areaid
     * @return string
     */
    public static function get_used_area_name(int $area, int $areaid) {
        switch ($area) {
            case self::AREA_TOPUP:
                return get_string('topupbycoupon', 'enrol_wallet');

            case self::AREA_ENROL:
                $instance = new instance($areaid);

                $name       = $instance->get_name();
                $coursename = $instance->get_course_context()->get_context_name(false);

                return "{$name} ({$coursename})";

            case self::AREA_CM:
                return context_module::instance($areaid)->get_context_name(false);

            case self::AREA_SECTION:
                $section = new section($areaid);

                return $section->get_name();

            default:
                throw new coding_exception("Invalid coupon area $area passed to get_used_area_name()");
        }
    }

    /**
     * Validate the coupon's record (time usage, number of usage ...).
     * @return bool
     */
    protected function validate_record(): bool {
        if (!empty($this->error)) {
            $this->valid = false;

            return false;
        }

        if (!is_numeric($this->value) || ($this->value <= 0 && $this->type !== self::ENROL)) {
            $this->valid = false;
            $this->error = get_string('coupon_invalidrecord', 'enrol_wallet');

            return false;
        }

        if ($this->source === balance::MOODLE) {
            // If it is on moodle website.
            $couponrecord = $this->record;

            // Make sure that the coupon didn't exceed the max usage (0 mean unlimited).
            if (!empty($couponrecord->maxusage) && $couponrecord->maxusage <= $this->get_total_use()) {
                $this->valid = false;
                $this->error = get_string('coupon_exceedusage', 'enrol_wallet');

                return false;
            }

            // Make sure that this coupon is within validation time (0 mean any time).
            if (!empty($couponrecord->validfrom) && $couponrecord->validfrom > timedate::time()) {
                $this->valid = false;
                $date        = userdate($couponrecord->validfrom);
                $this->error = get_string('coupon_notvalidyet', 'enrol_wallet', $date);

                return false;
            }

            if (!empty($couponrecord->validto) && $couponrecord->validto < timedate::time()) {
                $this->valid = false;
                $this->error = get_string('coupon_expired', 'enrol_wallet');

                return false;
            }

            // Check the maximum limit per each user has not been reached.
            if (!empty($couponrecord->maxperuser) && $this->get_user_use() >= $couponrecord->maxperuser) {
                $this->valid = false;
                $this->error = get_string('coupon_exceedusage', 'enrol_wallet');

                return false;
            }
        }

        return true;
    }

    /**
     * Check if the area is valid for applying this coupon.
     * MUST CALL ::set_area before using this method.
     * @return bool
     */
    protected function validate_area(): bool {
        if (!$this->check_area_record_exists()) {
            $area        = $this->get_area_visible_name($this->area);
            $this->error = get_string('couponareanotexist', 'enrol_wallet', ['area' => $area, 'id' => $this->areaid]);
            $this->valid = false;

            return false;
        }

        if (empty($this->type) || !empty($this->error) || empty($this->area)) {
            $this->valid = false;
        } else {
            switch ($this->area) {
                // For toping up the wallet.
                case self::AREA_TOPUP:
                    if (!\in_array($this->type, [self::FIXED, self::CATEGORY])) {
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

        if ($this->valid === false) {
            $this->error = get_string('coupon_applynothere', 'enrol_wallet');

            return false;
        }

        return true;
    }

    /**
     * Check if the area to be validated is the same stored here.
     * @param  ?int             $area
     * @param  int              $areaid
     * @throws coding_exception
     * @return bool
     */
    protected function is_same_area_input(?int $area, int $areaid): bool {
        global $DB;

        if ($area === null) {
            return true;
        }

        if (!\in_array($area, self::AREAS)) {
            throw new coding_exception("Non recognized coupon apply area $area");
        }

        if ($area === $this->area && $area === self::AREA_TOPUP || $areaid === $this->areaid) {
            return true;
        }

        return false;
    }

    /**
     * Check if the record exists for this area (example: the enrol instance).
     * @return bool
     */
    protected function check_area_record_exists() {
        global $DB;
        $conditions = ['id' => $this->areaid];

        return match ($this->area) {
            self::AREA_TOPUP   => true,
            self::AREA_ENROL   => $DB->record_exists('enrol', $conditions),
            self::AREA_CM      => $DB->record_exists('course_modules', $conditions),
            self::AREA_SECTION => $DB->record_exists('course_sections', $conditions),
            default            => false,
        };
    }

    /**
     * Validate if the coupon is restricted to be used in certain category or courses.
     * @return bool
     */
    private function validate_area_category_and_courses() {
        if (!empty($this->category) && !$this->is_same_category()) {
            $category    = core_course_category::get($this->category, IGNORE_MISSING, false, $this->userid);
            $this->error = get_string('coupon_applynothere_category', 'enrol_wallet', $category->get_nested_name());
            $this->valid = false;

            return false;
        }

        if (!empty($this->courses) && ($entity = $this->get_entity_helper_class())) {
            if (!\in_array($entity->get_course_id(), $this->courses)) {
                $available = '';

                foreach ($this->courses as $courseid) {
                    try {
                        $course = @get_course($courseid);
                    } catch (\Throwable $e) {
                        $course = null;
                    }

                    if ($course) {
                        $course     = new core_course_list_element($course);
                        $coursename = $course->get_formatted_fullname();
                        $available .= '- ' . $coursename . '<br>';
                    }
                }

                $this->valid = false;
                $this->error = get_string('coupon_applynothere_course', 'enrol_wallet', $available);

                return false;
            }
        }

        return true;
    }

    /**
     * Check if the applying area is within the same category in property category.
     * @param  cat_helper $catop
     * @return bool
     */
    private function is_same_category($catop = null): bool {
        if (empty($catop)) {
            $catid = $this->category;
            $catop = new cat_helper($catid);
        }

        return match($this->area) {
            self::AREA_ENROL   => $catop->is_child_instance($this->areaid),
            self::AREA_CM      => $catop->is_child_cm($this->areaid),
            self::AREA_SECTION => $catop->is_child_section($this->areaid),
            self::AREA_TOPUP   => true,
            default            => false,
        };
    }

    /**
     * Validate a percentage discount coupon.
     * @return bool
     */
    private function validate_discount_coupon(): bool {
        if ($this->value > 100 || $this->value <= 0) {
            $this->error = get_string('invalidpercentcoupon', 'enrol_wallet');
            $this->valid = false;

            return false;
        }

        return $this->validate_area_category_and_courses();
    }

    /**
     * Validate a fixed discount coupon.
     * @return bool
     */
    private function validate_fixed_discount_coupon(): bool {
        if ($this->value <= 0) {
            $this->error = get_string('invalidfixeddiscountcoupon', 'enrol_wallet');
            $this->valid = false;

            return false;
        }

        return $this->validate_area_category_and_courses();
    }

    /**
     * Check if the category coupon if valid to be used here.
     * @return bool
     */
    private function validate_category_coupon(): bool {
        if (!$this->type == self::CATEGORY) {
            return false;
        }
        $catenabled = (bool)config::instance()->catbalance && $this->source === balance::MOODLE;

        if (empty($this->category) || (!$catenabled && $this->area == self::AREA_TOPUP)) {
            $this->error = get_string('invalidcouponcategory', 'enrol_wallet');
            $this->valid = false;

            return false;
        }
        $catid = $this->category;
        $catop = new cat_helper($catid);

        // This type of coupons is restricted to be used in certain category and its children.
        if (!$this->is_same_category($catop)) {
            $this->valid  = false;
            $categoryname = $catop->get_category()->get_nested_name(false);
            $this->error  = get_string('coupon_categoryfail', 'enrol_wallet', $categoryname);

            return false;
        }

        return true;
    }

    /**
     * Validate enrol coupon.
     * @return bool
     */
    private function validate_enrol_coupon(): bool {
        global $DB;

        if (empty($this->courses) && empty($this->category)) {
            $this->valid = false;
            $this->error = get_string('invalidcouponcourse', 'enrol_wallet');

            return false;
        }

        return $this->validate_area_category_and_courses();
    }

    /**
     * Check if the coupon is valid to be used in this area.
     * returns string on error and true if valid.
     * @param  ?int        $area   code, value, type, courses, category
     * @param  int         $areaid the area at which the coupon applied (instanceid, cmid, sectionid)
     * @return bool|string
     */
    public function validate_coupon(?int $area = null, int $areaid = 0): true|string {
        if ($this->is_same_area_input($area, $areaid) && !empty($this->error)) {
            return $this->error;
        }
        unset($this->error);

        if (isguestuser($this->userid) || empty($this->userid)) {
            $this->valid = false;
            $this->error = get_string('guestnousecoupons', 'enrol_wallet');

            return $this->error;
        }

        $this->valid = true;

        if ($area !== null) {
            $this->set_area($area, $areaid);
        }

        if (!$this->validate_record() || !$this->check_enabled()) {
            return $this->error;
        }

        $this->validate_area();

        if (!empty($this->error)) {
            return $this->error;
        }

        switch ($this->type) {
            case self::DISCOUNT:
                $this->validate_discount_coupon();
                break;

            case self::FIXED:
                break;

            case self::CATEGORY:
                $this->validate_category_coupon();
                break;

            case self::ENROL:
                $this->validate_enrol_coupon();
                break;

            case self::FIXEDDISCOUNT:
                $this->validate_fixed_discount_coupon();
                break;

            default:
        }

        if (!empty($this->error)) {
            return $this->error;
        }

        return $this->valid;
    }

    /**
     * Return the value of this coupon.
     * @return float
     */
    public function get_value(): float {
        return $this->value;
    }

    /**
     * Get the code of the coupon.
     * @return string
     */
    public function get_code(): string {
        return $this->code;
    }

    /**
     * Return the error due to validation process.
     * false if there is none and the coupon is valid.
     * @return bool
     */
    public function has_error(): bool {
        $this->check_validation();
        return !empty($this->error) || !$this->valid;
    }

    /**
     * Return the last error when applying the coupon if
     * any existed.
     * @return string|null
     */
    public function get_error(): ?string {
        $this->check_validation();
        return $this->error ?? null;
    }


    /**
     * Is the coupon valid or not?
     * @return bool
     */
    public function is_valid(): bool {
        $this->check_validation();
        return $this->valid;
    }
    /**
     * Check if the coupon has been validated or not.
     * @throws coding_exception
     * @return void
     */
    protected function check_validation(): void {
        if (!isset($this->valid)) {
            throw new coding_exception('Cannot check error or valid status before call ::validate_coupoun()');
        }
    }
    /**
     * Apply the coupon for enrolment or topping up the wallet.
     *
     * @param  string $area
     * @param  int    $areaid
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
        $op   = $this->get_balance_operation();

        // Check if we applying the coupon (fixed value coupons) charge the wallet directly.
        if (\in_array($this->type, [self::FIXED, self::CATEGORY])) {
            $desc = get_string('topupcoupon_desc', 'enrol_wallet', $this->code);
            $op->credit($this->value, $op::C_COUPON, $this->record->id, $desc, false);
            $used = true;
        }

        // After we get the coupon data now we check if this coupon used from enrolment page.
        // If true and the value >= the fee, save time for student and enrol directly.
        if ($this->area == self::AREA_ENROL) {
            $balance  = $op->get_valid_balance();
            $instance = new instance($this->areaid, $this->userid);
            $user     = \core_user::get_user($this->userid);
            $fee      = (float)$instance->get_cost_after_discount();
            $plugin   = new wallet();

            if ($this->type === self::ENROL || $this->is_topup_coupon() && $balance >= $fee) {
                $used = true;

                // Check if the coupon value is grater than or equal the fee.
                // Enrol the user in the course.
                if (!$instance->is_enrolled(true)) {
                    $charge = ($this->type !== self::ENROL);
                    $plugin->enrol_self($instance, $user, $charge);
                } else if ($this->type === self::ENROL) {
                    $used = false;
                }
            } else if ($this->type === self::CATEGORY && $balance < $fee) {
                $error = get_string('coupon_cat_notsufficient', 'enrol_wallet');
                \core\notification::error($error);
            }
        }

        if ($this->is_discount_coupon()) {
            self::set_session_coupon($this->code);
        }

        if ($used) {
            // Mark the coupon as used.
            $this->mark_coupon_used();
        }
    }

    /**
     * Called when the coupon get used and mark it as used.
     * MUSN'T be called before validation.
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
            if (PHPUNIT_TEST) {
                debugging('Cannot mark an invalid coupon as used.');
            }

            return;
        }
        // Unset the session coupon to make sure not used again.
        self::unset_session_coupon();

        if ($this->area == self::AREA_ENROL) {
            $instanceid = $this->areaid;
        } else {
            $instanceid = 0;
        }

        if ($this->source === balance::WP) {
            if ($this->type === self::DISCOUNT) {
                // It is already included in the wordpress plugin code.
                $wordpress = new \enrol_wallet\wordpress();
                $wordpress->get_coupon($this->code, $this->userid, $instanceid, true);
            }
        } else {
            $couponrecord           = $this->record;
            $olduse                 = $DB->count_records('enrol_wallet_coupons_usage', ['code' => $this->code]);
            $usage                  = max($couponrecord->usetimes, $olduse) + 1;
            $couponrecord->lastuse  = timedate::time();
            $couponrecord->usetimes = $usage;
            $DB->update_record('enrol_wallet_coupons', $couponrecord);
        }

        // Logging the usage in the coupon usage table.
        $logdata = [
            'code'       => $this->code,
            'type'       => $this->get_type(),
            'value'      => $this->value,
            'userid'     => $this->userid,
            'area'       => $this->area,
            'areaid'     => $this->areaid,
            'instanceid' => $instanceid,
            'timeused'   => timedate::time(),
        ];
        $id = $DB->insert_record('enrol_wallet_coupons_usage', (object)$logdata);

        unset($logdata['userid'], $logdata['timeused']);
        $eventdata = [
            'userid'        => $this->userid,
            'relateduserid' => $this->userid,
            'objectid'      => !empty($id) ? $id : null,
            'other'         => $logdata,
        ];

        if ($entity = $this->get_entity_helper_class()) {
            $eventdata['context']  = $entity->get_context();
            $eventdata['courseid'] = $entity->get_course_id();
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
    public function get_total_use(): ?int {
        global $DB;

        if (!$this->source === balance::MOODLE) {
            return null;
        }
        $count = $DB->count_records('enrol_wallet_coupons_usage', ['code' => $this->code]);

        return max($count, $this->record->usetimes);
    }

    /**
     * Get the number that this user has used the coupoun.
     * @return int|null
     */
    public function get_user_use() {
        if (!$this->source === balance::MOODLE) {
            return null;
        }
        global $DB;
        $count = $DB->count_records('enrol_wallet_coupons_usage', [
            'code'   => $this->code,
            'userid' => $this->userid,
        ]);

        return $count;
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
                if ($this->type === self::CATEGORY) {
                    return new balance_op($this->userid, $this->category);
                }

                return new balance_op($this->userid);

            case self::AREA_CM:
                return balance_op::create_from_cm($this->areaid, $this->userid);

            case self::AREA_SECTION:
                return balance_op::create_from_section($this->areaid, $this->userid);

            default:
                return new balance_op($this->userid);
        }
    }

    /**
     * Get the entity class helper for the current area.
     * @return cm|instance|section|null
     */
    public function get_entity_helper_class(): ?entity {
        return match ($this->area) {
            self::AREA_TOPUP   => null,
            self::AREA_ENROL   => new instance($this->areaid, $this->userid),
            self::AREA_CM      => new cm($this->areaid, $this->userid),
            self::AREA_SECTION => new section($this->areaid, $this->userid),
        };
    }

    /**
     * Get the context for the current area.
     * @return context
     */
    public function get_area_context(): context {
        if ($entity = $this->get_entity_helper_class()) {
            return $entity->get_context();
        }

        return \context_system::instance();
    }

    /**
     * Get the coupons data, this function passed through all validation processes before return the coupon data
     * if the validation process faild, it will return a string of the error, else it returns array of the data.
     * If the parameter $instanceid passed, $cmid and $sectionid will be neglected, also if $cmid passed, $sectionid
     * will be neglected.
     * set $apply to true to apply the coupon.
     * @param  string       $code       the coupon code
     * @param  int          $userid     the id of the user
     * @param  bool         $apply      Wheither or not we need to apply the coupon.
     * @param  int          $instanceid enrol wallet instance id if this coupon used in enrolment page.
     * @param  int          $cmid       course module id if this coupon used for course module.
     * @param  int          $sectionid  the course section id if the coupon used for section restriction.
     * @return string|array string in case of error, or and array of coupons data.
     */
    public static function get_coupon_value($code, $userid = 0, $apply = false, $instanceid = 0, $cmid = 0, $sectionid = 0) {
        $coupon = new self($code, $userid);

        if (!empty($instanceid)) {
            $area   = self::AREA_ENROL;
            $areaid = $instanceid;
        } else if (!empty($cmid)) {
            $area   = self::AREA_CM;
            $areaid = $cmid;
        } else if (!empty($sectionid)) {
            $area   = self::AREA_SECTION;
            $areaid = $sectionid;
        } else {
            $area   = self::AREA_TOPUP;
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
     * Check if there is coupon code in session or as a parameter.
     * @return string|null return the coupon code, or null if not found.
     */
    public static function check_discount_coupon(): ?string {
        global $SESSION;
        $coupon = !empty($SESSION->enrol_wallet_coupon)
                  ? clean_param($SESSION->enrol_wallet_coupon, PARAM_ALPHANUM)
                  : null;

        return $coupon;
    }

    /**
     * Set coupon in the session.
     * @param string $code the coupon code.
     */
    public static function set_session_coupon($code) {
        global $SESSION;
        $SESSION->enrol_wallet_coupon = $code;
        instance::reset_static_cache();
    }

    /**
     * Unset any session coupons.
     */
    public static function unset_session_coupon() {
        global $SESSION;
        $SESSION->enrol_wallet_coupon = null;
        instance::reset_static_cache();
    }
}
