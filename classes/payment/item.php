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

namespace enrol_wallet\payment;

use core\exception\coding_exception;
use core\persistent;
use core_payment\helper;

/**
 * Enrol wallet payment item.
 *
 * @property int    $id
 * @property int    $userid
 * @property float  $cost
 * @property string $currency
 * @property int    $instanceid
 * @property int    $category
 * @property int    $timecreated
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item extends persistent {
    /**
     * Table name.
     * @var string
     */
    public const TABLE = 'enrol_wallet_items';

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
            'cost' => [
                'type'    => PARAM_FLOAT,
                'default' => 0,
            ],
            'currency' => [
                'type'    => PARAM_ALPHA,
                'choices' => helper::get_supported_currencies(),
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'instanceid' => [
                'type'    => PARAM_INT,
                'default' => 0,
            ],
            'category' => [
                'type'    => PARAM_INT,
                'null'    => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Create a new wallet item.
     * @param  float  $cost
     * @param  string $currency
     * @param  ?int   $userid
     * @param  int    $instanceid
     * @param  ?int   $category
     * @return static
     */
    public static function create_item(
        float $cost,
        string $currency,
        ?int $userid = null,
        int $instanceid = 0,
        ?int $category = null
    ): static {
        global $USER;
        $data = [
            'cost'       => $cost,
            'currency'   => $currency,
            'userid'     => $userid ?? $USER->id,
            'instanceid' => $instanceid,
            'category'   => $category,
        ];
        $item = static::get_record($data);

        if (!$item) {
            $item = (new static(0, (object)$data))->create();
        }

        return $item;
    }

    /**
     * Mock a payment item for testing.
     * @param  array            $defaults
     * @throws coding_exception
     * @return item
     */
    public static function mock_item(array $defaults = []): self {
        global $USER;

        if (!PHPUNIT_TEST && !BEHAT_TEST) {
            throw new coding_exception('Cannot use ::mock_item() outside testing.');
        }

        $record = (object)[
            'cost'       => $defaults['cost'] ?? random_int(1000, 9000) / 100,
            'currency'   => $defaults['currency'] ?? 'EUR',
            'userid'     => $defaults['userid'] ?? $USER->id,
            'instanceid' => $defaults['instanceid'] ?? 0,
            'category'   => $defaults['category'] ?? null,
        ];
        $item = new self(0, $record);

        return $item->create();
    }

    /**
     * Magic getter.
     * @param string $name
     */
    public function __get(string $name) {
        return $this->get($name);
    }

    /**
     * Check if the property isset.
     * @param  string $name
     * @return bool
     */
    public function __isset(string $name) {
        return $this->has_property($name) && ($this->get($name) !== null);
    }

    /**
     * Magic setter.
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value) {
        $this->set($name, $value);
    }
}
