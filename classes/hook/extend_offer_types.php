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

use enrol_wallet\local\discounts\offer_item;

/**
 * Extend the offers available.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('wallet', 'enrol')]
#[\core\attribute\label('Add extra offers from other plugins')]
class extend_offer_types {
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
     * Add a class which must be subclass of offer_item to the list of offers.
     * This method check the duplication of the key (type) and exclude non available offers.
     * @param string $classname
     * @return void
     */
    public function add_class(string $classname) {
        if (!class_exists($classname)) {
            debugging("Class $classname does not exist. Cannot add to offer types.");
            return;
        }
        if (!is_subclass_of($classname, offer_item::class)) {
            debugging("Class $classname is not a subclass of offer_item. Cannot add to offer types.");
            return;
        }
        $key = $classname::key();
        if (isset($this->classes[$key])) {
            debugging("Offer type with key '$key' already exists. Cannot add class $classname.");
            return;
        }

        if (!$classname::is_available()) {
            return;
        }

        $this->classes[$key] = $classname;
    }

    /**
     * Bulk add for list of classes {@see ::add_class}
     * @param array $classnames
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
