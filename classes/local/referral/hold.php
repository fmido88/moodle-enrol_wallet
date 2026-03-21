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

namespace enrol_wallet\local\referral;

use coding_exception;
use core\persistent;

/**
 * Class hold
 *
 * @property int $id
 * @property int $referrer
 * @property string $referred
 * @property int $courseid
 * @property float $amount
 * @property bool $released
 * @property int $timemodified
 * @property int $timecreated
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hold extends persistent {
    /**
     * Table name.
     * @var string
     */
    public const TABLE = 'enrol_wallet_hold_gift';
    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * The result of this method is cached internally for the whole request.
     *
     * The 'default' value can be a Closure when its value may change during a single request.
     * For example if the default value is based on a $CFG property, then it should be wrapped in a closure
     * to avoid running into scenarios where the true value of $CFG is not reflected in the definition.
     * Do not abuse closures as they obviously add some overhead.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'referrer' => [
                'type'    => PARAM_INT,
            ],
            'referred' => [
                'type'    => PARAM_USERNAME,
            ],
            'courseid' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'amount' => [
                'type' => PARAM_FLOAT,
            ],
            'released' => [
                'type'    => PARAM_BOOL,
                'default' => false,
            ],
        ];
    }
    /**
     * Get records by referrer userid.
     * @param int $userid
     * @return hold[]
     */
    public static function get_by_referrer(int $userid) {
        return static::get_records(['referrer' => $userid]);
    }
    /**
     * Get records by the referred username.
     * @param string $username
     * @return bool|hold
     */
    public static function get_by_referred(string $username) {
        return static::get_record(['referred' => $username]);
    }

    /**
     * Easier than get() :)
     * @param string $name
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * Magic setter.
     * @param string $name
     * @param mixed $value
     *
     * @throws coding_exception
     * @return void
     */
    public function __set($name, $value) {
        if (!\in_array($name, ['released', 'courseid', 'timemodified'])) {
            throw new coding_exception("Cannot modify the property $name");
        }
        $this->set($name, $value);
    }

    /**
     * Magic isset
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return $this->has_property($name) && $this->get($name) !== null;
    }
}
