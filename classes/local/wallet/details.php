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
 * Balance details object.
 *
 * @property float $mainrefundable Alias to main refundable balance
 * @property float $refund Alias to main refundable balance
 * @property float $mainnonrefundable Alias to main non-refundable balance
 * @property float $mainnonrefund Alias to main non-refundable balance
 * @property float $norefund Alias to main non-refundable balance
 * @property float $mainbalance Main balance
 * @property float $balance Main balance
 * @property float $mainfree Alias to free gift (main free)
 * @property float $main_free  Alias to free gift (main free)
 * @property float $free Alias to free gift (main free)
 * @property float $total Total balance
 * @property float $total_nonrefundable Alias to total non-refundable balance
 * @property float $totalnonrefundable Total non-refundable balance
 * @property float $total_refundable Alias to total refundable balance
 * @property float $totalrefundable Total refundable balance
 * @property float $total_free Total free points
 * @property float $totalfree Total free points
 * @property float $valid Valid balance can be used from the main and category balance.
 * @property float $valid_nonrefundable  Valid non-refundable balance
 * @property float $validnonrefundable Valid non-refundable balance
 * @property float $valid_refundable Valid refundable balance
 * @property float $validrefundable Valid refundable balance
 * @property float $valid_free Valid free points
 * @property float $validfree Valid free points
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class details {
    /**
     * Cat balances objects.
     * @var catdetails[]
     */
    public array $catbalance = [];
    /**
     * Helper to calculate balances.
     * @param float $refundable
     * @param float $nonrefundable
     * @param float $freegift
     * @param int[] $catids
     * @param catdetails[] $catbalance
     */
    public function __construct(
        /** @var float the main refundable value. */
        public float $refundable,
        /** @var float the main non-refundable value. */
        public float $nonrefundable,
        /** @var float the main free points. */
        public float $freegift,
        /** @var int[] categories ids that this object belongs to. */
        public array $catids = [],
        array $catbalance = []
    ) {
        foreach ($catbalance as $id => $obj) {
            $this->catbalance[$id] = new catdetails(
                $obj->refundable,
                $obj->nonrefundable,
                $obj->free ?? 0,
            );
        }
    }

    /**
     * Calculate the total (valid) type of balance balance
     * @param string $name balance, nonrefundable, refundable, free
     * @param bool $total true to get the total, false for only valid
     * @throws coding_exception
     * @return float
     */
    protected function calculate(string $name, bool $total): float {
        if (!isset($this->$name)) {
            throw new coding_exception("The property $name not exists in the class " . self::class);
        }

        $value = $this->$name;
        foreach ($this->catbalance as $id => $obj) {
            if (!isset($obj->$name)) {
                throw new coding_exception("The property $name not exists in the class " . $obj::class);
            }
            if ($total || in_array($id, $this->catids)) {
                $value += $obj->$name;
            }
        }

        return $value;
    }
    /**
     * Magic getter.
     * @param string $name
     * @throws coding_exception
     * @return float
     */
    public function __get($name) {
        switch ($name) {
            case 'mainrefundable':
            case 'refund':
                return $this->refundable;

            case 'mainnonrefundable':
            case 'mainnonrefund':
            case 'norefund':
                return $this->nonrefundable;

            case 'mainbalance':
            case 'balance':
                return  $this->refundable + $this->nonrefundable;

            case 'mainfree':
            case 'main_free':
            case 'free':
                return $this->freegift;

            case 'total':
            case 'totalbalance':
            case 'total_balance':
                return $this->calculate('balance', true);

            case 'total_nonrefundable':
            case 'totalnonrefundable':
                return $this->calculate('nonrefundable', true);

            case 'total_refundable':
            case 'totalrefundable':
                return $this->calculate('refundable', true);

            case 'total_free':
            case 'totalfree':
                return $this->calculate('free', true);

            case 'valid':
            case 'validbalance':
            case 'valid_balance':
                return $this->calculate('balance', false);

            case 'valid_nonrefundable':
            case 'validnonrefundable':
                return $this->calculate('nonrefundable', false);

            case 'valid_refundable':
            case 'validrefundable':
                return $this->calculate('refundable', false);

            case 'valid_free':
            case 'validfree':
                return $this->calculate('free', false);

            default:
                throw new coding_exception("The property $name not exists in the class " . self::class);
        }
    }
    /**
     * Check if a magic property is existed.
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        switch ($name) {
            case 'mainrefundable':
            case 'refund':

            case 'mainnonrefundable':
            case 'mainnonrefund':
            case 'norefund':

            case 'mainbalance':
            case 'balance':

            case 'mainfree':
            case 'main_free':
            case 'free':

            case 'total':
            case 'totalbalance':
            case 'total_balance':

            case 'total_nonrefundable':
            case 'totalnonrefundable':

            case 'total_refundable':
            case 'totalrefundable':

            case 'total_free':
            case 'totalfree':

            case 'valid':
            case 'validbalance':
            case 'valid_balance':

            case 'valid_nonrefundable':
            case 'validnonrefundable':

            case 'valid_refundable':
            case 'validrefundable':

            case 'valid_free':
            case 'validfree':
                return true;
            default:
                return false;
        }
    }
    /**
     * Cannot set a calculated value.
     * @param string $name
     * @param string $value
     * @throws coding_exception
     * @return never
     */
    public function __set($name, $value) {
        throw new coding_exception("Cannot set a value to property $name");
    }
}
