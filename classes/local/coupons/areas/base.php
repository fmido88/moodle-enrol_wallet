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

namespace enrol_wallet\local\coupons\areas;

use core\exception\coding_exception;
use enrol_wallet\hook\extend_coupons_areas;
use enrol_wallet\local\coupons\types\base as type_base;
use enrol_wallet\local\entities\category as cat_entity;
use enrol_wallet\local\entities\entity;
use enrol_wallet\local\wallet\balance_op;
use stdClass;

/**
 * Base class for areas of applying coupons.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * The applying area.
     * Must override.
     * @var int
     */
    public const AREA = null;

    /**
     * Cached category entities.
     * @var array
     */
    protected static $catentities = [];

    /**
     * Constructor.
     * @param int $areaid
     */
    protected function __construct(
        /** @var int The area id. */
        public readonly int $areaid
    ) {
    }

    /**
     * Create instance of coupon area.
     * @param  int|string       $area
     * @param  int              $areaid
     * @throws coding_exception
     * @return base|object
     */
    public static function make(int|string $area, int $areaid = 0): static {
        if (!is_number($area)) {
            $class = __NAMESPACE__ . "\\$area";

            return new $class($areaid);
        }
        $classes = self::get_classes();

        foreach ($classes as $class) {
            if ($class::AREA == $area) {
                return new $class($areaid);
            }
        }

        throw new coding_exception("Cannot find the class for the area {$area}");
    }

    /**
     * Get type classes.
     * @return string[]|static[]
     */
    final protected static function get_classes(): array {
        static $classes;

        if (isset($classes)) {
            return $classes;
        }

        $hook = new extend_coupons_areas();
        $areas = [
            'topup',
            'enrol',
            'cm',
            'section',
        ];

        foreach ($areas as $area) {
            $class = __NAMESPACE__ . "\\$area";
            $hook->add_class($class);
        }

        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $classes = $hook->get_classes();

        return $classes;
    }

    /**
     * Return list of areas.
     * @return int[]
     */
    final public static function get_areas(): array {
        $areas = [];
        $classes = self::get_classes();

        foreach ($classes as $class) {
            $areas[$class::get_area()] = $class::AREA;
        }

        return $areas;
    }

    /**
     * Get list of all areas as options.
     * @return string[]
     */
    final public static function get_area_list(): array {
        $classes = self::get_classes();
        $list = [];

        foreach ($classes as $class) {
            if (!$key = (@$class::get_area() ?? null)) {
                continue;
            }
            $list[$key] = $class::get_visible_name();
        }

        return $list;
    }

    /**
     * Get the context for the current area.
     * @return \core\context
     */
    public function get_context(): \core\context {
        if ($entity = $this->get_entity()) {
            return $entity->get_context();
        }

        return \context_system::instance();
    }

    /**
     * Get enrolment instance id if this coupon used in enrol form.
     * @return int
     */
    public function get_instance_id(): int {
        return 0;
    }

    /**
     * Get the course category entity as helper class.
     * @param  int              $catid
     * @return cat_entity|mixed
     */
    final public function get_cat_entity(int $catid) {
        if (!isset(self::$catentities[$catid])) {
            self::$catentities[$catid] = new cat_entity($catid);
        }

        return self::$catentities[$catid];
    }

    /**
     * Return the area as string.
     * Important to be override if the area added by hook.
     * @return string
     */
    public static function get_area(): string {
        $parts = explode('\\', static::class);

        return end($parts);
    }

    /**
     * Get the class name from given area code.
     * @param int $area
     *
     * @return base|string|null
     */
    final public static function get_class_from_area_code(int $area): ?string {
        $classes = self::get_classes();

        foreach ($classes as $class) {
            if ($class::AREA === $area) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Get the class from the submitted data.
     * @param  array|stdClass $data
     * @return string|static
     */
    final public static function get_class_from_data(array|stdClass $data): string {
        $found = [];

        foreach (self::get_classes() as $class) {
            if (null !== ($id = $class::get_id_from_data($data))) {
                if (!empty($id)) {
                    return $class;
                }
                $found[] = $class;
            }
        }

        return reset($found);
    }

    /**
     * Get a modified redirect url after submitting a coupon.
     * @param  \core\url  $url
     * @param  ?type_base $coupon
     * @return \core\url
     */
    public function get_redirect_url(\core\url $url, ?type_base $coupon): \core\url {
        return $url;
    }

    /**
     * Check if the applying area is within the same category in property category.
     * @param  int  $catid
     * @return bool
     */
    abstract public function is_same_category(int $catid): bool;

    /**
     * Return instance of balance operation class.
     * @param  int       $userid
     * @param  type_base $type
     * @return void
     */
    abstract public function get_balance_operation(int $userid, type_base $type): balance_op;

    /**
     * Get the visible name of the current type.
     * @return void
     */
    abstract public static function get_visible_name(): string;

    /**
     * Get the name of the used area or context.
     * @param  bool $withlink
     * @return void
     */
    abstract public function get_name(bool $withlink = false): string;

    /**
     * Check if the record exists for this area (example: the enrol instance).
     * @return bool
     */
    abstract public function record_exists(): bool;

    /**
     * Check if this area can be used for a given type of coupons.
     * @param  type_base $type
     * @return void
     */
    abstract public function is_valid_for_type(type_base $type): bool;

    /**
     * Return entity instance for calculating costs.
     * @param  int  $userid
     * @return void
     */
    abstract public function get_entity(int $userid = 0): ?entity;

    /**
     * Return area id from submitted data.
     * @param  array|stdClass $data
     * @return void
     */
    abstract public static function get_id_from_data(array|stdClass $data): ?int;
}
