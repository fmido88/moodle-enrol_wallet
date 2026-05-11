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

namespace enrol_wallet\local\discounts\discount;

use enrol_wallet\local\entities\entity;

/**
 * Discount calculator.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class discount_base {
    /**
     * Construct a discount type object to get discounts for certain entity
     * and user.
     *
     * @param entity $entity
     * @param float  $cost
     */
    public function __construct(
        /** @var entity The entity to apply the discount for. */
        protected entity $entity,
        /** @var float The original cost before discount. */
        protected float $cost
    ) {
    }

    /**
     * Getter for the original cost before any discount.
     * @return float
     */
    final public function get_original_cost(): float {
        return $this->cost;
    }

    /**
     * Get the cost after discounting.
     * @return float
     */
    final public function get_discounted_cost(): float {
        if (!static::is_available($this->entity)) {
            return $this->get_original_cost();
        }
        $original = $this->get_original_cost();
        $percent = $this->get_percentage_discount();

        return $original - ($original * $percent / 100);
    }

    /**
     * Get the absolute discount (value to be subtracted from the original cost).
     * @return float|int
     */
    final public function get_absolute_discount(): float {
        if (!static::is_available($this->entity)) {
            return 0;
        }

        $original = $this->get_original_cost();
        $percent = $this->get_percentage_discount();

        return $original * $percent / 100;
    }

    /**
     * Get the entity to calculate the discount for.
     * @return entity
     */
    final public function get_entity(): entity {
        return $this->entity;
    }

    /**
     * The id of the user receiving the discount.
     * @return int
     */
    final public function get_userid(): int {
        return $this->entity->get_userid();
    }

    /**
     * If this discount is available for the given entity or not.
     * @param  entity $entity
     * @return void
     */
    abstract public static function is_available(entity $entity): bool;

    /**
     * Calculate and return the percentage discount value.
     * @return void
     */
    abstract public function get_percentage_discount(): float;

    /**
     * Post purchase action.
     * @return void
     */
    public function after_process(): void {
        // Override if there is something need to be done after purchasing.
    }
}
