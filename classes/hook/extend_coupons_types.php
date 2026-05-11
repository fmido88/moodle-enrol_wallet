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

namespace enrol_wallet\hook;

use enrol_wallet\local\coupons\types\base as type_base;

/**
 * Extend the coupons types.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('wallet', 'enrol', 'coupons')]
#[\core\attribute\label('Add extra coupons type definite by other plugins')]
class extend_coupons_types {
    /**
     * List of offer items classes.
     * @var array
     */
    protected $classes = [];

    /**
     * Constructor.
     */
    public function __construct() {
    }

    /**
     * Add a class which must be subclass of enrol_wallet\local\coupons\types\base
     * to the list of available types.
     * This method check the duplication of the key (type) and exclude non available coupon types.
     * @param  string $classname
     * @return void
     */
    public function add_class(string $classname) {
        if (!class_exists($classname)) {
            debugging("Class $classname does not exist. Cannot add to offer types.");

            return;
        }

        if (!is_subclass_of($classname, type_base::class)) {
            debugging("Class $classname is not a subclass of offer_item. Cannot add to offer types.");

            return;
        }
        $type = $classname::get_type();

        if (isset($this->classes[$type])) {
            debugging("Coupon type '$type' already exists. Cannot add class $classname.");

            return;
        }

        if ((!$typecode = ($classname::TYPE ?? null)) || !\is_int($typecode)) {
            debugging("The class $classname not defined the integer constant TYPE");

            return;
        }

        foreach ($this->classes as $class) {
            if ($class::TYPE == $typecode) {
                debugging("The const TYPE in class $classname already used before and cannot be added.");

                return;
            }
        }

        $this->classes[$type] = $classname;
    }

    /**
     * Bulk add for list of classes {@see ::add_class}.
     * @param  array $classnames
     * @return void
     */
    public function add_classes(array $classnames) {
        foreach ($classnames as $classname) {
            $this->add_class($classname);
        }
    }

    /**
     * Get the current list of classes.
     * @return array
     */
    public function get_classes(): array {
        return $this->classes;
    }
}
