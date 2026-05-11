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

use enrol_wallet\local\coupons\areas\base as area_base;

/**
 * Extend the coupons areas.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('wallet', 'enrol', 'coupons')]
#[\core\attribute\label('Add extra coupons areas to apply coupons definite by other plugins')]
class extend_coupons_areas {
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
     * Add a class which must be subclass of enrol_wallet\local\coupons\areas\base
     * to the list of available areas.
     * This method check the duplication of the key (area) and exclude non available coupon areas.
     * @param  string $classname
     * @return void
     */
    public function add_class(string $classname) {
        if (!class_exists($classname)) {
            debugging("Class $classname does not exist. Cannot add to offer areas.");

            return;
        }

        if (!is_subclass_of($classname, area_base::class)) {
            debugging("Class $classname is not a subclass of enrol_wallet\local\coupons\areas\base. Cannot add to areas.");

            return;
        }
        $area = $classname::get_area();

        if (isset($this->classes[$area])) {
            debugging("Coupon area '$area' already exists. Cannot add class $classname.");

            return;
        }

        if ((!$areacode = ($classname::AREA ?? null)) || !\is_int($areacode)) {
            debugging("The class $classname not defined the integer constant AREA");

            return;
        }

        foreach ($this->classes as $class) {
            if ($class::AREA == $areacode) {
                debugging("The const AREA '$areacode' in class $classname already used before and cannot be added.");

                return;
            }
        }

        $this->classes[$area] = $classname;
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
     * @return string[]|area_base[]
     */
    public function get_classes(): array {
        return $this->classes;
    }
}
