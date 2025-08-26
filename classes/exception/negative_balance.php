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
 * Class negative_balance
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class negative_balance extends moodle_exception {
    /**
     * Negative balance exception.
     * @param float $before The balance before the operation.
     * @param float $amount The debit amount.
     */
    public function __construct($before, $amount) {
        $a = ['amount' => $amount, 'before' => $before];

        return parent::__construct('negativebalance', 'enrol_wallet', '', $a);
    }
}
