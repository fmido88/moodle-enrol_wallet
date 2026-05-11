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
 * Class timedate.
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

    /**
     * Get the order of the week in a single month.
     * @param  int    $time the timestamp
     * @return string something like 2nd week
     */
    public static function week_of_month(int $time = -1) {
        if ($time < 0) {
            $time = self::time();
        }

        $cal = \core_calendar\type_factory::get_calendar_instance();
        $date = $cal->timestamp_to_date_array($time);
        $numweek = $cal->get_num_weekdays();
        $week = ceil($date['mday'] / $numweek);
        $map = [
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            4 => '4th',
            5 => '5th',
        ];

        return $map[$week] . ' ' . get_string('week');
    }

    /**
     * Get the start of a week for the given timestamp.
     * @param  int      $time
     * @return bool|int
     */
    public static function start_of_week(int $time = -1) {
        if ($time < 0) {
            $time = self::time();
        }

        $cal = \core_calendar\type_factory::get_calendar_instance();
        $weekdays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $starting = $weekdays[$cal->get_starting_weekday()];
        $week = strtotime("$starting this week", $time);

        while ($week > $time) {
            $week -= WEEKSECS;
        }

        return $week;
    }
}
