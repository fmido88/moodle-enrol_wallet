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

namespace enrol_wallet\local;

use stdClass;

/**
 * Configuration getters and setters.
 * This class mainly a wrapper for get_config and set_config functions for easy documenting each config value as property.
 *
 * @property string $allowmultipleinstances Number of allowed enrol instances per course (0 means unlimited).
 * @property string $availability_plugins Availability plugins used to restrict enrolment imploded by ',' separator.
 * @property string $awardcreteria
 * @property string $awards
 * @property string $awardssite
 * @property string $awardvalue
 * @property string $borrowenable
 * @property string $borrowperiod
 * @property string $borrowtrans
 * @property string $cashback If cashback is enabled or not on site level (bool).
 * @property string $cashbackpercent Percentage of cashback.
 * @property string $catbalance If category balance is enabled or not (bool).
 * @property string $conditionaldiscount_apply If the conditional discount is enabled or not (bool).
 * @property string $coupons Coupons types enabled on the site imploded by ',' separator.
 * @property string $currency The wallet currency.
 * @property string $customcurrency
 * @property string $customcurrencycode
 * @property string $defaultenrol
 * @property string $discount_behavior
 * @property string $discount_field
 * @property string $enablerefund
 * @property string $enrolperiod
 * @property string $expiredaction
 * @property string $expirynotify
 * @property string $expirynotifyhour
 * @property string $expirynotifylast
 * @property string $expirythreshold
 * @property string $frontpageoffers
 * @property string $longtimenosee
 * @property string $lowbalancenotice
 * @property string $maxenrolled
 * @property string $mintransfer
 * @property string $mywalletnav
 * @property string $newenrols
 * @property string $newusergift
 * @property string $newusergiftvalue
 * @property string $noticecondition
 * @property string $offers_nav Add offers to main navigation bar (bool).
 * @property string $paymentaccount Payment account id used to topup the wallet (int).
 * @property string $referral_amount
 * @property string $referral_enabled
 * @property string $referral_max
 * @property string $referral_plugins
 * @property string $refundperiod
 * @property string $refundpolicy
 * @property string $repurchase
 * @property string $repurchase_firstdis
 * @property string $repurchase_seconddis
 * @property string $restrictionenabled
 * @property string $roleid
 * @property string $sendcoursewelcomemessage
 * @property string $showprice
 * @property string $status
 * @property string $tellermen
 * @property string $transfer_enabled
 * @property string $transferfee_from
 * @property string $transferpercent
 * @property string $unenrollimitafter
 * @property string $unenrollimitbefor
 * @property string $unenrolrefund
 * @property string $unenrolrefundfee
 * @property string $unenrolrefundperiod
 * @property string $unenrolrefundpolicy
 * @property string $unenrolselfenabled
 * @property string $version
 * @property string $walletsource The wallet source (wordpress or moodle).
 * @property string $wordpress_secretkey
 * @property string $wordpress_url
 * @property string $wordpressloggins
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {
    /**
     * A singleton instance of config class.
     * @var config
     */
    protected static config $singleton;

    /**
     * Local store for config values.
     * @var stdClass
     */
    protected \stdClass $store;

    /**
     * Constructor.
     */
    protected function __construct() {
        $this->init_store();
    }

    /**
     * Initialize the local store.
     * @return void
     */
    private function init_store() {
        global $SESSION;
        if (PHPUNIT_TEST && !isset($SESSION->enrol_wallet_teststore)) {
            // In PHPUnit tests we need the store to be cleared when resetting the data.
            $SESSION->enrol_wallet_teststore = new stdClass();
            $this->store =& $SESSION->enrol_wallet_teststore;
        } else if (!isset($this->store)) {
            $this->store = new stdClass();
        }
    }
    /**
     * Check if the value is stored locally.
     * @param string $name
     * @return string|null
     */
    protected function get_from_store($name) {
        $this->init_store();
        if (property_exists($this->store, $name)) {
            return $this->store->$name;
        }

        return null;
    }
    /**
     * Set a value to the local store.
     * @param string $name
     * @param string|int|float|bool|null $value
     * @return void
     */
    protected function set_to_store($name, $value) {
        $this->init_store();
        $this->store->$name = $value;
    }
    /**
     * Get a config value.
     * @param string $name
     */
    public function __get($name) {
        $value = $this->get_from_store($name);
        if ($value !== null) {
            return $value;
        }

        // Just to display debugging if not exist.
        if (!$this->exists($name)) {
            return null;
        }

        $value = get_config('enrol_wallet', $name);

        if ($value !== false) {
            $this->set_to_store($name, $value);
        }

        return $value;
    }
    /**
     * Set a config value.
     * @param string $name
     * @param string|int|bool|null $value
     */
    public function __set($name, $value) {
        set_config($name, $value, 'enrol_wallet');
        $this->set_to_store($name, $value);
    }

    /**
     * Unset a config value.
     * @param string $name
     * @return void
     */
    public function __unset($name) {
        unset_config($name, 'enrol_wallet');
    }

    /**
     * Check if the config value is set.
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        if (isset($this->store->$name)) {
            return true;
        }

        $value = get_config('enrol_wallet', $name);
        if ($value !== false) {
            $this->set_to_store($name, $value);
            return true;
        }
        return false;
    }

    /**
     * Check if a config value exists and display debug message if not.
     * @param string $name
     * @return bool
     */
    protected function exists($name) {
        global $CFG;
        $exists = isset($this->$name);
        if (!$exists && !during_initial_install() && empty($CFG->upgraderunning)) {
            debugging("Trying to get undefined config value $name in enrol_wallet", DEBUG_DEVELOPER);
        }
        return $exists;
    }
    /**
     * Static getter.
     * @param string $name
     * @return string|int|bool|null
     */
    public static function get($name) {
        $config = self::instance();
        $config->exists($name);
        return $config->$name;
    }

    /**
     * Set a config value statically.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function set($name, $value) {
        $config = self::instance();
        $config->exists($name);
        $config->$name = $value;
    }

    /**
     * Get instance of the config class.
     * @return config
     */
    public static function make(): config {
        if (!isset(self::$singleton)) {
            self::$singleton = new static();
        }
        return self::$singleton;
    }

    /**
     * Alias for make method.
     * @return config
     */
    public static function instance(): config {
        return self::make();
    }
}
