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

namespace enrol_wallet\exception;

use core\exception\moodle_exception;

/**
 * Class negative_amount
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class negative_amount extends moodle_exception {
    /**
     * Check if an amount is negative (for debit or credit).
     * @param float $amount
     */
    public function __construct(float $amount) {
        parent::__construct('nonegativeallowed', 'enrol_wallet', '', $amount);
    }

    /**
     * Check an amount to be positive float and throw exception other wise.
     * @param float $amount
     * @throws static
     * @return void
     */
    public static function check(float $amount) {
        if ($amount < 0) {
            throw new static($amount);
        }
    }
}
