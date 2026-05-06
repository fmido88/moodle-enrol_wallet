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

namespace enrol_wallet\local\coupons\types;

use core\exception\coding_exception;
use core_collator;
use core_course_category;
use core_course_list_element;
use enrol_wallet\hook\extend_coupons_types;
use enrol_wallet\local\config;
use enrol_wallet\local\coupons\areas\base as area_base;
use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet_plugin;
use ReflectionClass;
use stdClass;

/**
 * Coupons type base class.
 *
 * @property int $id
 * @property-read string $code
 * @property-read float $value
 * @property-read string $type
 * @property-read int $category
 * @property-read int $maxusage
 * @property-read int $maxperuser
 * @property int $usetimes
 * @property-read int $validfrom
 * @property-read int $validto
 * @property-read int $timecreated
 * @property int $lastuse
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Array of courses ids at which the coupon can be used.
     * @var int[]
     */
    public readonly array $courses;

    /**
     * No coupons enabled in the site.
     * @var int
     */
    public const NOCOUPONS = 0;

    /**
     * If the wallet source is wordpress.
     * @var bool
     */
    protected bool $wordpress;

    /**
     * Constructor.
     * @param  stdClass         $record
     * @throws coding_exception
     */
    protected function __construct(
        /** @var stdClass The coupon record. */
        protected stdClass $record
    ) {
        $this->wordpress = (config::make()->walletsource == balance::WP);

        if ($record->type !== static::get_type()) {
            throw new coding_exception("Mismatch coupon type constructor {$record->type} " . static::get_type());
        }

        if (!(new ReflectionClass(static::class))->hasConstant('TYPE')) {
            throw new coding_exception('The class ' . static::class . ' has not defined the TYPE');
        }

        $courses = $record->courses ?? '';
        $courses = array_map(fn ($id) => (int)trim($id), explode(',', $courses));
        $this->courses = array_filter($courses);
    }

    /**
     * Magic getter.
     * @param  string           $name
     * @throws coding_exception
     * @return mixed
     */
    public function __get(string $name): mixed {
        if (isset($this->record->$name)) {
            return $this->record->$name;
        }

        throw new coding_exception("The property $name not exist in the coupon record");
    }

    /**
     * Magic setter.
     * @param  string           $name
     * @param  mixed            $value
     * @throws coding_exception
     * @return void
     */
    public function __set(string $name, mixed $value): void {
        if (!\in_array($name, ['lastuse', 'usetimes']) && isset($this->record->$name)) {
            throw new coding_exception("Cannot modify the property $name");
        }
        $this->record->$name = $value;
    }

    /**
     * Magic isset.
     * @param  string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        return isset($this->record->$name);
    }

    /**
     * Return the coupon record.
     * @return stdClass
     */
    public function get_record(): stdClass {
        return $this->record;
    }

    /**
     * Get instance of coupon type class.
     * @param  stdClass         $record
     * @throws coding_exception
     * @return static
     */
    public static function make(stdClass $record): static {
        $class = __NAMESPACE__ . "\\{$record->type}";

        if (!class_exists($class)) {
            throw new coding_exception("The class $class for coupon type {$record->type} not exists");
        }

        return new $class($record);
    }

    /**
     * Check if the record is valid.
     * @param  int     $userid
     * @param  ?string $error
     * @return bool
     */
    public function is_valid_record(int $userid, ?string &$error = null): bool {
        if (!is_numeric($this->value) || ($this->value <= 0 && self::get_type() !== 'enrol')) {
            $error = get_string('coupon_invalidrecord', 'enrol_wallet') . " ({$this->code}: {$this->value})";

            return false;
        }

        if ($this->wordpress) {
            return true;
        }

        // Make sure that the coupon didn't exceed the max usage (0 mean unlimited).
        if (!empty($this->maxusage) && $this->maxusage <= $this->get_total_use()) {
            $error = get_string('coupon_exceedusage', 'enrol_wallet');

            return false;
        }

        // Make sure that this coupon is within validation time (0 mean any time).
        if (!empty($this->validfrom) && $this->validfrom > timedate::time()) {
            $date = userdate($this->validfrom);
            $error = get_string('coupon_notvalidyet', 'enrol_wallet', $date);

            return false;
        }

        if (!empty($this->validto) && $this->validto < timedate::time()) {
            $error = get_string('coupon_expired', 'enrol_wallet');

            return false;
        }

        // Check the maximum limit per each user has not been reached.
        if (!empty($this->maxperuser) && $this->get_user_use($userid) >= $this->maxperuser) {
            $error = get_string('coupon_exceedusage', 'enrol_wallet');

            return false;
        }

        return true;
    }

    /**
     * Get the total number that the coupon has been used.
     * @return int|null
     */
    public function get_total_use(): ?int {
        global $DB;

        if (config::make()->walletsource != balance::MOODLE) {
            return null;
        }

        $count = $DB->count_records('enrol_wallet_coupons_usage', ['code' => $this->code]);

        return max($count, $this->record->usetimes);
    }

    /**
     * Get the number that this user has used the coupon.
     * @param  ?int     $userid
     * @return int|null
     */
    public function get_user_use(?int $userid = null) {
        global $DB, $USER;

        if (config::make()->walletsource != balance::MOODLE) {
            return null;
        }

        $userid ??= $USER->id;
        $count = $DB->count_records('enrol_wallet_coupons_usage', [
            'code'   => $this->code,
            'userid' => $userid,
        ]);

        return $count;
    }

    /**
     * Get the type of this as string.
     * @return string
     */
    public static function get_type(): string {
        $class = static::class;
        if ($class === self::class) {
            throw new coding_exception("Cannot get the type from base abstract class.");
        }
        $parts = explode('\\', $class);

        return end($parts);
    }

    /**
     * Get the class name for given type string.
     * @param  string $type
     *
     * @return base|string|null
     */
    final public static function get_class_name_from_type(string $type): ?string {
        $classes = self::get_classes();

        foreach ($classes as $class) {
            if (strtolower($class::get_type()) === strtolower($type)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * If this type can be used for discount.
     * @return bool
     */
    public static function is_discount_coupon(): bool {
        return false;
    }

    /**
     * If this type can be used for direct enrol.
     * @return bool
     */
    public static function is_enrol_coupon(): bool {
        return false;
    }

    /**
     * Check if the coupon type could be used to topup the wallet.
     * @return bool
     */
    public static function is_topup_coupon(): bool {
        return false;
    }

    /**
     * Check if this coupon is saved in session as part of its appliance
     * Discount coupons used as session coupon to recalculate cost.
     * @return bool
     */
    public static function in_session_coupon(): bool {
        return static::is_discount_coupon();
    }

    /**
     * If this coupon type should have numerical value or not.
     * @return bool
     */
    public static function has_value(): bool {
        return true;
    }
    /**
     * Validate if this coupon can directly enrol.
     * @param  float $fee
     * @param  float $balance
     * @return bool
     */
    public function can_enrol(float $fee, float $balance): bool {
        if (!$this->is_enrol_coupon()) {
            return false;
        }

        if (!$this->is_topup_coupon()) {
            return true;
        }

        return $balance >= $fee;
    }

    /**
     * Topup the wallet by coupon.
     * @param balance_op $op
     * @param area_base $area
     * @return bool true if the wallet topped up by the coupon.
     */
    final protected function apply_topup(balance_op $op, area_base $area): bool {
        if ($this->is_topup_coupon()) {
            $desc = get_string('topupcoupon_desc', 'enrol_wallet', $this->code);
            return $op->credit($this->value, $op::C_COUPON, $this->id, $desc, false);
        }
        return false;
    }

    /**
     * Check and apply the coupon for enrolment.
     * @param balance_op $op
     * @param area_base $area
     * @return bool true if the coupon used successfully for enrollment.
     */
    final protected function apply_enrol(balance_op $op, area_base $area): bool {
        // Check if this coupon can be used from enrolment page.
        // If true and the value >= the fee, save time for student and enrol directly.

        if ($this->is_enrol_coupon()) {
            $userid = $op->get_user_id();
            $instance = $area->get_entity($userid);
            if (!($instance instanceof instance)) {
                // Enrollments only apply for enrol instance.
                return false;
            }

            $balance = $op->get_valid_balance();
            $user = $op->get_user();
            $fee = (float)$instance->get_cost_after_discount();
            $plugin = new enrol_wallet_plugin();

            if ($this->can_enrol($fee, $balance)) {
                // Check if the coupon value is grater than or equal the fee.
                // Enrol the user in the course.
                if (!$instance->is_enrolled(true)) {
                    return true === $plugin->enrol_self($instance, $user, $this->is_topup_coupon());
                }
            } else if (static::TYPE === category::TYPE && $balance < $fee) {
                $error = get_string('coupon_cat_notsufficient', 'enrol_wallet');
                \core\notification::error($error);
            }
        }
        return false;
    }

    /**
     * Apply the coupon.
     * Override this if the coupon type has different behavior.
     * @param area_base $area
     * @param int $userid
     * @return bool true if the coupon used successfully and must be marked as used.
     */
    public function apply_coupon(area_base $area, int $userid): bool {
        $op = $area->get_balance_operation($userid, $this);

        // Check if we applying the coupon (fixed value coupons) charge the wallet directly.
        $used = $this->apply_topup($op, $area);

        // Apply enrolment if the area has instance id and the coupon could be used for
        // enrolment.
        $used = $this->apply_enrol($op, $area) || $used;

        // Store coupon in session usually if it is a discount coupon.
        if ($this->in_session_coupon()) {
            coupons::set_session_coupon($this->code);
        }

        return $used;
    }

    /**
     * Get an array of enabled coupons types.
     * @return array
     */
    final public static function get_enabled_types() {
        $config = config::instance()->coupons;

        if (empty($config)) {
            return [];
        }

        $types = explode(',', $config);
        $types = array_map(fn ($type) => (int)$type, $types);

        return array_filter($types);
    }

    /**
     * Enable all coupons types for testing.
     * @param bool $set
     * @throws coding_exception
     * @return string
     */
    final public static function enable_all_types(bool $set = true): string {
        if (!PHPUNIT_TEST) {
            throw new coding_exception("Enable all types only used in phpunit tests.");
        }
        $types = self::get_types();
        $value = implode(',', $types);
        if ($set) {
            config::make()->coupons = $value;
        }
        return $value;
    }
    /**
     * Return all options of coupons types and their names to be used in plugin settings.
     * @param bool $stringkey use the type name 'fixed, percent, .. ' as the keys of the array,
     *                        if false the TYPE integer constant will be used.
     * @return array
     */
    final public static function get_coupons_options(bool $stringkey = false) {
        $options = [];

        foreach (self::get_classes() as $class) {
            $key = $stringkey ? $class::get_type() : $class::TYPE;
            $options[$key] = $class::get_visible_name();
        }

        core_collator::ksort($options);

        return $options;
    }

    /**
     * Return an array of enabled coupons options keyed with the type code.
     * @param bool $stringkey
     * @return array
     */
    final public static function get_enabled_options(bool $stringkey = false) {
        $options = [];

        $classes = self::get_enabled_classes();

        foreach ($classes as $class) {

            $key = $stringkey ? $class::get_type() : $class::TYPE;
            $options[$key] = $class::get_visible_name();
        }

        return $options;
    }

    /**
     * Get type classes.
     * @return string[]|static[]
     */
    final public static function get_classes(): array {
        static $classes;
        if (isset($classes)) {
            return $classes;
        }

        $hook = new extend_coupons_types();
        $types = [
            'fixed',
            'fixeddis',
            'category',
            'enrol',
            'percent',
        ];

        foreach ($types as $type) {
            $class = __NAMESPACE__ . "\\$type";
            $hook->add_class($class);
        }

        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $classes = $hook->get_classes();
        return $classes;
    }

    /**
     * Return array of all types.
     * @return array
     */
    final public static function get_types(): array {
        $classes = self::get_classes();
        $types = [];

        foreach ($classes as $class) {
            $types[$class::get_type()] = $class::TYPE;
        }

        return $types;
    }

    /**
     * Get enabled type classes.
     * @return string[]|static[]
     */
    final public static function get_enabled_classes(): array {
        $enabled = [];
        $classes = self::get_classes();

        foreach ($classes as $class) {
            if ($class::is_enabled()) {
                $enabled[$class::TYPE] = $class;
            }
        }

        return $enabled;
    }

    /**
     * Check if this type is enabled.
     * @return bool
     */
    public static function is_enabled(): bool {
        return \in_array(static::TYPE, self::get_enabled_types());
    }

    /**
     * Validate if this coupon could be used in this area regarding it category and courses.
     * @param  area_base $area
     * @param  int       $userid
     * @param  ?string   $error
     * @return bool
     */
    public function validate_area_category_and_courses(area_base $area, int $userid, ?string &$error = null) {
        if (!empty($this->category) && !$area->is_same_category($this->category)) {
            $category = core_course_category::get($this->category, IGNORE_MISSING, false, $userid);
            $error = get_string('coupon_applynothere_category', 'enrol_wallet', $category->get_nested_name());

            return false;
        }

        if (!empty($this->courses) && ($entity = $area->get_entity())) {
            if (!\in_array($entity->get_course_id(), $this->courses)) {
                $available = '';

                foreach ($this->courses as $courseid) {
                    try {
                        $course = @get_course($courseid);
                    } catch (\Throwable $e) {
                        $course = null;
                    }

                    if ($course) {
                        $course = new core_course_list_element($course);
                        $coursename = $course->get_formatted_fullname();
                        $available .= '- ' . $coursename . '<br>';
                    }
                }

                $error = get_string('coupon_applynothere_course', 'enrol_wallet', $available);

                return false;
            }
        }

        return true;
    }

    /**
     * Override if this coupon can specify courses in their appliance.
     * This is used to either hide courses selection options in the generator form or not.
     * @return bool
     */
    public static function can_specify_courses(): bool {
        return false;
    }

    /**
     * Override if this coupon can be limited for certain category.
     * This is used to either hide course category selection options in the generator form or not.
     * @return bool
     */
    public static function can_specify_category(): bool {
        return false;
    }

    /**
     * Override if special validation needed for this coupon type.
     * @param array $data
     * @param array $errors
     * @return void
     */
    protected static function validate_type_generator_form(array $data, array &$errors): void {
    }

    /**
     * Validate the generate form submitted data.
     * @param array $data
     * @param array $errors
     * @return void
     */
    final public static function validate_generator_form(array $data, array &$errors): void {
        global $DB;
        if ($data['method'] === 'single') {
            if (empty($data['code'])) {
                $errors['code'] = get_string('coupon_code_error', 'enrol_wallet');
            } else if ($DB->record_exists('enrol_wallet_coupons', ['code' => $data['code']])) {
                $errors['code'] = get_string('coupon_exist', 'enrol_wallet');
            }
        }

        if ($data['method'] === 'random' && empty($data['number'])) {
            $errors['number'] = get_string('coupon_generator_nonumber', 'enrol_wallet');
        }

        if (!empty($data['maxperuser']) && $data['maxperuser'] > $data['maxusage']) {
            $errors['maxperuser'] = get_string('coupon_generator_peruser_gt_max', 'enrol_wallet');
            $errors['maxusage'] = get_string('coupon_generator_peruser_gt_max', 'enrol_wallet');
        }

        if (!$childclass = self::get_class_name_from_type($data['type'])) {
            $errors['type'] = get_string('coupon_generator_invalidtype', 'enrol_wallet');
        }

        if (empty($data['value']) && $childclass::has_value()) {
            $errors['value'] = get_string('coupons_valueerror', 'enrol_wallet');
        }

        $childclass::validate_type_generator_form($data, $errors);
    }
    /**
     * Get applying coupon success notification message.
     * @param  area_base                            $area
     * @return array{message: string, type: string} message and notification type.
     */
    public function get_submission_message(area_base $area): array {
        return [
            'message' => get_string('coupon_applyerror', 'enrol_wallet', ''),
            'type'    => 'error',
        ];
    }

    /**
     * Get this type visible name.
     * @return void
     */
    abstract public static function get_visible_name(): string;

    /**
     * Get the value after discount by using this coupon.
     * @param  float $cost
     * @return void
     */
    abstract public function get_discounted_value(float $cost): float;

    /**
     * Validate this coupon.
     * @param  area_base $area
     * @param  int       $userid
     * @param  ?string   $error
     * @return void
     */
    abstract public function validate_coupon(area_base $area, int $userid, ?string &$error = null): bool;
}
