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

namespace enrol_wallet\local\utils;

use core\clock;
use core\di;

/**
 * Class timedate
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timedate {
    /**
     * Get clock instance.
     * @return clock
     */
    public static function clock(): clock {
        static $clock;
        if (isset($clock)) {
            return $clock;
        }
        $clock = di::get(clock::class);
        return $clock;
    }
    /**
     * Get the current server time.
     * Used instead of time() for tests.
     * @return int
     */
    public static function time(): int {
        return self::clock()->time();
    }
}
