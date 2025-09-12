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

use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\catop as operations;
use enrol_wallet\wordpress;
use enrol_wallet\local\entities\section;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\entities\cm;

use cache;

/**
 * Class balance
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class balance {
    /**
     * Source of wallet is moodle.
     */
    public const MOODLE = 1;
    /**
     * Source of wallet is wordpress.
     */
    public const WP = 0;
    /**
     * The balance table name.
     * @var string
     */
    private const BALANCE_T = 'enrol_wallet_balance';
    /**
     * The transaction table name.
     */
    private const TRANSACTION_T = 'enrol_wallet_transactions';
    /**
     * The current source of wallet.
     * @var int
     */
    protected int $source;
    /**
     * The current userid.
     * @var int
     */
    protected $userid;
    /**
     * The current category id.
     * @var int
     */
    protected $catid;
    /**
     * category operation helper class
     * @var operations
     */
    protected $catop;
    /**
     * The whole balance details
     * @var details
     */
    public details $details;
    /**
     * The id of the record in the database.
     * @var int
     */
    protected $recordid;
    /**
     * The valid balance for the user.
     * @var float
     */
    public $valid;
    /**
     * If category balance is available in this site.
     * @var bool
     */
    public $catenabled;

    /**
     * Balance helper object to get all balance data of a given user.
     * use enrol_wallet\util\balance_op for operations like credit or debit.
     * @param int $userid
     * @param int|object $category the category id.
     */
    public function __construct($userid = 0, $category = 0) {
        global $COURSE, $USER;

        $source = get_config('enrol_wallet', 'walletsource');
        if ($source === false) {
            $source = self::MOODLE;
        }

        $this->source = $source;

        if (!empty($userid)) {
            $this->userid = $userid;
        } else {
            $this->userid = $USER->id;
        }

        $this->catenabled = (bool)get_config('enrol_wallet', 'catbalance') && ($this->source == self::MOODLE);
        if ($this->catenabled) {
            if (empty($category)) {
                $category = $COURSE->category ?? 0;
            }

            if (is_object($category)) {
                $this->catid = $category->id;
            } else if ($category > 0) {
                $this->catid = $category;
            } else {
                $this->catid = 0;
            }

        } else {
            $this->catid = 0;
        }

        $this->set_balance_details();

        $this->update();
    }

    /**
     * Get the user id at which this balance util belongs to.
     * @return int
     */
    public function get_user_id() {
        return $this->userid;
    }

    /**
     * Return the user object at which this transaction belongs to.
     * @return bool|\stdClass
     */
    public function get_user() {
        return \core_user::get_user($this->userid);
    }
    /**
     * Get the category id at which this balance util belongs to.
     * @return int
     */
    public function get_catid() {
        return $this->catid ?? 0;
    }
    /**
     * Return the valid balance as string.
     * @return string
     */
    public function __toString() {
        return format_float($this->get_valid_balance(), 2);
    }

    /**
     * Set the main balance of the given user.
     * this is called in case of using wordpress as source
     * or when first calling this class after upgrade.
     */
    protected function set_main_balance() {
        global $DB;

        if ($this->source == self::WP) {
            $wordpress = new wordpress;
            $response = $wordpress->get_user_balance($this->userid);

            if (!is_numeric($response)) {
                // This mean error or user not exist yet.
                $mainbalance = 0;
                $mainnonrefund = 0;
            } else {
                $mainbalance = (float)$response;
                $mainnonrefund = $this->get_nonrefund_from_transactions();
            }

        } else if ($this->source == self::MOODLE) {

            // Get the balance from the last transaction.
            $sort = 'timecreated DESC,id DESC';
            $params = ['userid' => $this->userid];
            $select = "userid = :userid";
            if ($DB->get_manager()->field_exists(self::TRANSACTION_T, 'category')) {
                $select .= " AND (category IS NULL OR category = 0)";
            }
            $records = $DB->get_records_select(self::TRANSACTION_T, $select, $params, $sort, 'balance, norefund', 0, 1);
            $record = reset($records);
            // Getting the balance from last transaction.
            // User with no records of any transactions means no balance yet.
            $balance = (!empty($record)) ? (float)$record->balance : 0;

            $mainbalance = (float)$balance;
            $mainnonrefund = (!empty($record)) ? (float)$record->norefund : 0;
        }

        $catids = [];
        if (!empty($this->catop)) {
            $catids = $this->catop->get_catids();
        }
        $this->details = new details(
            $mainbalance - $mainnonrefund,
            $mainnonrefund,
            0,
            $catids,
            []
        );
    }

    /**
     * Get the non-refundable balance by the legacy method
     * from the transaction table.
     * @return float
     */
    private function get_nonrefund_from_transactions(): float {
        global $DB;
        $balance = $this->get_main_balance();
        $record = $DB->get_records('enrol_wallet_transactions', ['userid' => $this->userid], 'id DESC', 'norefund', 0, 1);

        // Getting the non refundable balance from last transaction.
        if (!empty($record)) {
            $key = array_key_first($record);
            $norefund = $record[$key]->norefund;
        } else {
            $norefund = 0;
        }

        if ($balance < $norefund) {
            $norefund = $balance;
        }

        return $norefund;
    }

    /**
     * Get the record for the table enrol_wallet_balance
     * @return \stdClass
     */
    private function get_record() {
        global $DB;
        $userid = $this->userid;

        $record = $DB->get_record(self::BALANCE_T, ['userid' => $userid]);

        if (empty($record)) {
            $this->set_main_balance();

            $record = new \stdClass;
            $record->userid = $userid;
            $record->refundable = $this->details->refundable;
            $record->nonrefundable = $this->details->nonrefundable;
            $record->freegift = $this->details->mainfree;
            $record->timecreated = timedate::time();
            $record->timemodified = timedate::time();
            $record->id = $DB->insert_record(self::BALANCE_T, $record);
        }
        $this->recordid = $record->id;

        return $record;
    }

    /**
     * Update the record in the database.
     */
    protected function update_record() {
        global $DB;
        $record = new \stdClass;
        $record->id = $this->recordid;
        $record->refundable = $this->details->mainrefundable;
        $record->nonrefundable = $this->details->mainnonrefund;
        $record->freegift = $this->details->mainfree;
        $record->timemodified = timedate::time();
        $record->cat_balance = $this->format_cat_balance();

        if (empty($this->recordid)) {
            unset($record->id);
            $record->userid = $this->userid;
            $record->timecreated = timedate::time();
            $this->recordid = $DB->insert_record(self::BALANCE_T, $record);
        } else {
            $DB->update_record(self::BALANCE_T, $record);
        }
    }

    /**
     * Format the details of the category balance as json object to be saved
     * in the database
     * @return string
     */
    private function format_cat_balance() {
        $catbalance = [];
        foreach ($this->details->catbalance as $id => $obj) {
            $catbalance[$id] = $obj->get_object();
        }
        return json_encode($catbalance);
    }

    /**
     * Get all balance details for a given user
     */
    private function set_balance_details() {
        if ($this->source == self::WP) {
            return $this->set_main_balance();
        }

        $record = $this->get_record();
        $catids = [];
        if (!empty($this->catop)) {
            $catids = $this->catop->get_catids();
        }

        $catbalance = [];

        if (!empty($record->cat_balance)) {
            $catbalance = (array)json_decode($record->cat_balance);
        }
        $this->details = new details($record->refundable,
                                    $record->nonrefundable,
                                    $record->freegift ?? 0,
                                    $catids,
                                    $catbalance);

        // The id of the record to be saved in the cache.
        $this->recordid = $record->id;

        if (!empty($this->catid) && $this->catid > 0) {
            $this->catop = new operations($this->catid, $this->details);
            $this->details->catids = $this->catop->get_catids();
        }
    }

    /**
     * Recalculate totals, and then update the record and caches.
     * @param bool $recordandcache Set true to update the database record and caches too.
     */
    protected function update(bool $recordandcache = true) {
        if (!empty($this->catop)) {
            $this->details->catbalance = $this->catop->details;
        }

        if (!$recordandcache) {
            return;
        }

        $this->update_record();
    }

    /**
     * Check if the details of the balance exists first.
     * If not it will reset it.
     * Must be called at any balance inquiry request.
     */
    protected function check() {
        if (empty($this->details)) {
            $this->set_balance_details();
        }
    }

    /**
     * Must be called after any credit or debit operations.
     * It will delete the caches and unset all balance details
     * The details and caches will be set again at any balance inquiry request.
     */
    protected function reset() {
        unset($this->details);
    }

    /**
     * Return array of the balance details
     * keys are mainrefundable, mainnonrefund, mainbalance, total, total_nonrefundable, total_refundable
     * And catbalance, the last one is an array of objects keyed by category id, each object with keys as following:
     * refundable, nonrefundable, balance.
     * @return details
     */
    public function get_balance_details(): details {
        $this->check();
        return $this->details;
    }

    /**
     * Get the total balance for a user.
     * @return float
     */
    public function get_total_balance(): float {
        $this->check();
        return $this->details->total;
    }

    /**
     * Get the total non-refundable balance for a user.
     * @return float
     */
    public function get_total_nonrefundable(): float {
        $this->check();
        return $this->details->totalnonrefundable;
    }

    /**
     * Get the total refundable balance for a user.
     * @return float
     */
    public function get_total_refundable(): float {
        $this->check();
        return $this->details->totalrefundable;
    }

    /**
     * get the main balance only without category balance.
     * @return float
     */
    public function get_main_balance(): float {
        $this->check();
        if (!$this->catenabled) {
            return $this->get_total_balance();
        }

        return $this->details->mainbalance;
    }

    /**
     * Get the nonrefundable balance in the main balance.
     * @return float
     */
    public function get_main_nonrefundable(): float {
        $this->check();
        if (!$this->catenabled) {
            return $this->get_total_nonrefundable();
        }
        return $this->details->mainnonrefund;
    }

    /**
     * Get the main refundable balance.
     * @return float
     */
    public function get_main_refundable(): float {
        $this->check();
        if (!$this->catenabled) {
            return $this->get_total_refundable();
        }
        return $this->details->mainrefundable;
    }

    /**
     * Get the valid balance to be used in the specified category,
     * this includes the sum of balance in this category, parents and main balance.
     * @return float
     */
    public function get_valid_balance() {
        $this->check();
        if (!$this->catenabled) {
            return $this->get_total_balance();
        }

        return $this->details->valid;
    }

    /**
     * Get the valid nonrefundable balance, like get_valid_balance but
     * it returns the non-refundable only.
     * @return float
     */
    public function get_valid_nonrefundable(): float {
        $this->check();
        if (!$this->catenabled) {
            return $this->get_total_nonrefundable();
        }

        return $this->details->validnonrefundable;
    }

    /**
     * Return the main free balance due to gifts and so.
     * @return float
     */
    public function get_main_free(): float {
        $this->check();
        return $this->details->mainfree;
    }

    /**
     * Get the total free balance due to gifts, awards an so on
     * in all categories and main balance.
     * @return float
     */
    public function get_total_free(): float {
        $this->check();
        return $this->details->totalfree;
    }

    /**
     * Get the free balance in main and category passed in construction
     * @return float
     */
    public function get_valid_free(): float {
        $this->check();
        if (!$this->catenabled) {
            return $this->get_total_free();
        }

        return $this->details->validfree;
    }

    /**
     * Return a balance for certain category.
     * @param int $catid
     * @return float
     */
    public function get_cat_balance($catid) {
        $this->check();
        if (!isset($this->details->catbalance[$catid])) {
            return 0;
        }
        $details = $this->details->catbalance[$catid];
        return $details->balance;
    }

    /**
     * Create a balance util helper class to obtain balance data of a given user
     * by providing the enrol_wallet instance or its id.
     * @param int|\stdClass $instance
     * @param int $userid 0 means the current user.
     * @return static
     */
    public static function create_from_instance($instance, $userid = 0) {
        $util = new instance($instance, $userid);
        $category = $util->get_course_category();
        return new static($userid, $category);
    }

    /**
     * Create a balance util helper class to obtain balance data of a given user
     * by providing the course module record or its id.
     * @param int|\stdClass $cm
     * @param int $userid 0 means the current user.
     * @return static
     */
    public static function create_from_cm($cm, $userid = 0) {
        $util = new cm($cm, $userid);
        $category = $util->get_course_category();
        return new static($userid, $category);
    }

    /**
     * Create a balance util helper class to obtain balance data of a given user
     * by providing the section record or its id.
     * @param int|\stdClass $section
     * @param int $userid 0 means the current user.
     * @return static
     */
    public static function create_from_section($section, $userid = 0) {
        $util = new section($section, $userid);
        $category = $util->get_course_category();
        return new static($userid, $category);
    }
}
