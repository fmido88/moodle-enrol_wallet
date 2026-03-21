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

use core\exception\coding_exception;
use core\persistent;

/**
 * Referral code.
 *
 * @property int $id
 * @property int $userid
 * @property string $code
 * @property int $usetimes
 * @property string $users
 * @property int $timemodified
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class code extends persistent {
    /**
     * Table name.
     * @var string
     */
    public const TABLE = 'enrol_wallet_referral';

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
            'code' => [
                'type'    => PARAM_ALPHANUM,
            ],
            'usetimes' => [
                'type'    => PARAM_INT,
                'default' => 0,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'users' => [
                'type'    => PARAM_TEXT,
                'default' => '',
            ],
        ];
    }
    /**
     * Get the referral code record for this user.
     * creates one if non-existed.
     * @param int $userid
     * @return bool|code
     */
    public static function get_code_record(int $userid = 0) {
        global $USER, $DB;
        if (!$userid) {
            $userid = $USER->id;
        }

        if ($record = self::get_record(['userid' => $userid])) {
            return $record;
        }

        do {
            $code = random_string();
        } while ($DB->record_exists(static::TABLE, ['code' => $code]));

        $record = (object)[
            'userid' => $userid,
            'code'   => $code,
        ];
        return (new static(0, $record))->create();
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
        if (!\in_array($name, ['usetimes', 'users', 'timemodified'])) {
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
