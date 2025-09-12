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

use core\exception\coding_exception;

/**
 * If the debit process misbehaved.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debit_exception extends coding_exception {
    /**
     * Thrown when the debit misbehave due to some miscalculation which should
     * never happen.
     * @param float $amount The amount suppose to be deducted.
     * @param float $before The balance before debit.
     * @param float $after The balance after debit.
     * @param string $debuginfo
     */
    public function __construct(float $amount, float $before, float $after, $debuginfo = null) {
        $hint = "Wrong debit process the amount to be deduct '$amount' not match the calculated ";
        $hint .= "amount before: '$before' - after: '$after'";
        parent::__construct($hint, $debuginfo);
    }
}
