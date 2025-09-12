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

namespace enrol_wallet\local\wallet;

use core\exception\coding_exception;

/**
 * Single category balance details.
 * @property float $balance the category balance (refundable and non-refundable)
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catdetails {

    /**
     * A single category balance details object.
     * @param float $refundable
     * @param float $nonrefundable
     * @param float $free
     */
    public function __construct(
        /** @var float the category refundable balance. */
        public float $refundable,
        /** @var float the category non-refundable balance. */
        public float $nonrefundable,
        /** @var float the category free points. */
        public float $free
    ) {
    }

    /**
     * Get a simple object of this class with only fields to be saved in database.
     * @return object{refundable: float, nonrefundable: float, free: float}
     */
    public function get_object(): object {
        return (object)[
            'refundable'    => $this->refundable,
            'nonrefundable' => $this->nonrefundable,
            'free'          => $this->free,
        ];
    }
    /**
     * Magic getter.
     * @param string $name
     * @throws coding_exception
     * @return float
     */
    public function __get($name): float {
        if ($name === 'balance') {
            return $this->refundable + $this->nonrefundable;
        }
        throw new coding_exception("Non recognized property $name");
    }

    /**
     * Check if a magic property is existed.
     * @param string $name
     * @return bool
     */
    public function __isset($name): bool {
        return $name === 'balance';
    }
    /**
     * Cannot set a calculated value.
     * @param string $name
     * @param string $value
     * @throws coding_exception
     * @return never
     */
    public function __set($name, $value): never {
        throw new coding_exception("Cannot set a value to property $name");
    }
}
