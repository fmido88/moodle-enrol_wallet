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

namespace enrol_wallet\util;
use enrol_wallet\category\operations;
use enrol_wallet\wordpress;
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
    protected $source;
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
    protected operations $catop;
    /**
     * The main balance for the user
     * @var float
     */
    public $balance;
    /**
     * The whole balance details
     * @var array
     */
    public $details;
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
     * Balance helper object to get all balance data of a given user.
     * use enrol_wallet\util\balance_op for operations like credit or debit.
     * @param int $userid
     * @param int|object $category the category id.
     */
    public function __construct($userid = 0, $category = 0) {
        $this->source = get_config('enrol_wallet', 'walletsource');
        if (!empty($userid)) {
            $this->userid = $userid;
        } else {
            global $USER;
            $this->userid = $USER->id;
        }

        if (empty($category)) {
            global $COURSE;
            $category = $COURSE->category ?? 0;
        }

        if (is_object($category)) {
            $this->catid = $category->id;
        } else if ($category > 0) {
            $this->catid = $category;
        } else {
            $this->catid = 0;
        }

        if (!empty($this->catid) && $this->catid > 0) {
            $this->catop = new operations($this->catid, $this->userid);
        }

        $this->set_details_from_cache();
    }

    /**
     * Set the main balance of the given user.
     */
    protected function set_main_balance() {
        global $DB;

        $balancedata = [];

        if ($this->source == self::WP) {
            $wordpress = new wordpress;
            $response = $wordpress->get_user_balance($this->userid);

            if (!is_numeric($response)) {
                // This mean error or user not exist yet.
                $this->balance = 0;
                return;
            }
            $balancedata['mainbalance'] = $response;
            $this->details = $balancedata;
            $this->balance = $response;
            return;
        } else if ($this->source == self::MOODLE) {

            // Get the balance from the last transaction.
            $sort = 'timecreated DESC,id DESC';
            $params = ['userid' => $this->userid];
            $select = "userid = :userid AND (category IS NULL OR category = 0)";
            $records = $DB->get_records_select(self::TRANSACTION_T, $select, $params, $sort, 'balance, norefund', 0, 1);
            $record = reset($records);
            // Getting the balance from last transaction.
            // User with no records of any transactions means no balance yet.
            $balance = (!empty($record)) ? $record->balance : 0;

            $balancedata['mainbalance'] = $balance;
            $balancedata['mainnonrefund'] = (!empty($record)) ? $record->norefund : 0;
            $balancedata['mainfree'] = 0;
            $this->details = $balancedata;
            $this->balance = (float)$balance;
        }
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
            $balance = $this->details['mainbalance'];
            $norefund = $this->details['mainnonrefund'];
            $record = new \stdClass;
            $record->userid = $userid;
            $record->refundable = $balance - $norefund;
            $record->nonrefundable = $norefund;
            $record->freegift = $this->details['mainfree'];
            $record->timecreated = time();
            $record->timemodified = time();
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
        $record->refundable = $this->details['mainrefundable'];
        $record->nonrefundable = $this->details['mainnonrefund'];
        $record->freegift = $this->details['mainfree'] ?? 0;
        $record->cat_balance = $this->format_cat_balance();
        $record->timemodified = time();
        $DB->update_record(self::BALANCE_T, $record);
    }

    /**
     * Format the details of the category balance as json object to be saved
     * in the database
     * @return string
     */
    private function format_cat_balance() {
        $catbalance = [];
        foreach ($this->details['catbalance'] as $id => $obj) {
            unset($obj->balance);
            $catbalance[$id] = $obj;
        }
        return json_encode($catbalance);
    }

    /**
     * Get all balance details for a given user
     */
    private function set_balance_details() {
        $record = $this->get_record();
        // Main.
        $details = [
            'mainrefundable' => $record->refundable,
            'mainnonrefund' => $record->nonrefundable,
            'mainbalance' => $record->refundable + $record->nonrefundable,
            'mainfree'    => $record->freegift ?? 0,
        ];

        // The id of the record to be saved in the cache.
        $details['recordid'] = $record->id;

        // Totals.
        $details['total'] = $details['mainbalance'];
        $details['total_nonrefundable'] = $details['mainnonrefund'];
        $details['total_refundable'] = $details['mainrefundable'];

        // Categories.
        $details['catbalance'] = [];
        if (!empty($record->cat_balance)) {
            $cats = json_decode($record->cat_balance);
            foreach ($cats as $id => $obj) {
                $details['catbalance'][$id] = new \stdClass;
                $details['catbalance'][$id]->refundable = $obj->refundable;
                $details['catbalance'][$id]->nonrefundable = $obj->nonrefundable;
                $details['catbalance'][$id]->free = $obj->free ?? 0;
                $details['catbalance'][$id]->balance = $obj->refundable + $obj->nonrefundable;

                $details['total'] += $obj->refundable + $obj->nonrefundable;
                $details['total_nonrefundable'] += $obj->nonrefundable;
                $details['total_refundable'] += $obj->refundable;
            }
        }
        $this->details = $details;

        $this->balance = $details['mainbalance'];

        $this->valid = $this->balance;
        if (!empty($this->catop)) {
            $this->valid += $this->catop->get_balance();
        }
        $this->update_cache();
    }

    /**
     * Setting the balance details from the caches.
     * If the details not exist it will set it from the database.
     */
    private function set_details_from_cache() {
        $cashed = cache::make('enrol_wallet', 'balance');
        if ($details = $cashed->get($this->userid)) {
            $this->details = $details;
            $this->recordid = $details['recordid'];
            $this->balance = $details['mainbalance'];

            $this->valid = $this->balance;
            if (!empty($this->catop)) {
                $this->valid += $this->catop->get_balance();
            }
        } else {
            $this->set_balance_details();
        }
    }

    /**
     * Delete the balance cache.
     */
    private function delete_cache() {
        $cashed = cache::make('enrol_wallet', 'balance');
        $cashed->delete($this->userid);
    }

    /**
     * Update the balance cache.
     */
    private function update_cache() {
        $cashed = cache::make('enrol_wallet', 'balance');
        $cashed->delete($this->userid);
        $cashed->set($this->userid, $this->details);
    }

    /**
     * Recalculate totals, and then update the record and caches.
     */
    protected function update() {
        $details = $this->details;
        // Main.
        $details['mainbalance'] = $details['mainrefundable'] + $details['mainnonrefund'];

        // Totals.
        $details['total'] = $details['mainbalance'];
        $details['total_nonrefundable'] = $details['mainnonrefund'];
        $details['total_refundable'] = $details['mainrefundable'];
        foreach ($details['catbalance'] as $id => $obj) {
            $details['catbalance'][$id]->balance = $obj->refundable + $obj->nonrefundable;

            $details['total'] += $obj->refundable + $obj->nonrefundable;
            $details['total_nonrefundable'] += $obj->nonrefundable;
            $details['total_refundable'] += $obj->refundable;
        }
        $this->details = $details;
        $this->balance = $details['mainbalance'];
        $this->valid = $this->balance;

        $this->update_record();
        $this->update_cache();

        if (!empty($this->catid)) {
            unset($this->catop);
            $this->catop = new operations($this->catid, $this->userid);
            $this->valid += $this->catop->get_balance();
        }
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
        $this->delete_cache();
        unset($this->details);
    }

    /**
     * Return array of the balance details
     * keys area mainrefundable, mainnonrefund, mainbalance, total, total_nonrefundable, total_refundable
     * And catbalance, the last one is an array of objects keyed by category id, each object with keys as following:
     * refundable, nonrefundable, balance.
     * @return array
     */
    public function get_balance_details() {
        $this->check();
        return $this->details;
    }

    /**
     * Get the total balance for a user.
     * @return float
     */
    public function get_total_balance() {
        $this->check();
        return $this->details['total'];
    }

    /**
     * Get the total non-refundable balance for a user.
     * @return float
     */
    public function get_total_nonrefundable() {
        $this->check();
        return $this->details['total_nonrefundable'];
    }

    /**
     * Get the total refundable balance for a user.
     * @return float
     */
    public function get_total_refundable() {
        $this->check();
        return $this->details['total_refundable'];
    }

    /**
     * get the main balance only without category balance.
     * @return float
     */
    public function get_main_balance() {
        $this->check();
        return $this->details['mainbalance'];
    }

    /**
     * Get the nonrefundable balance in the main balance.
     * @return float
     */
    public function get_main_nonrefundable() {
        $this->check();
        return $this->details['mainnonrefund'];
    }

    /**
     * Get the main refundable balance.
     * @return float
     */
    public function get_main_refundable() {
        $this->check();
        return $this->details['mainrefundable'];
    }
    /**
     * Get the valid balance to be used in the specified category,
     * this includes the sum of balance in this category, parents and main balance.
     * @return float
     */
    public function get_valid_balance() {
        $this->check();
        return $this->valid;
    }

    /**
     * Get the valid nonrefundable balance, like get_valid_balance but
     * it returns the non-refundable only.
     * @return float
     */
    public function get_valid_nonrefundable() {
        $this->check();
        $nonrefundable = $this->details['mainnonrefund'];
        if (!empty($this->catop)) {
            $nonrefundable += @$this->catop->get_non_refundable_balance() ?? 0;
        }
        return  $nonrefundable;
    }
    /**
     * Return the main free balance due to gifts and so.
     * @return float
     */
    public function get_main_free() {
        $this->check();
        return $this->details['mainfree'] ?? 0;
    }

    /**
     * Get the total free balance due to gifts, awards an so on
     * in all categories and main balance.
     * @return float
     */
    public function get_total_free() {
        $total = $this->get_main_free();
        if (!empty($this->details['catbalance'])) {
            foreach ($this->details['catbalance'] as $obj) {
                $total += $obj->free ?? 0;
            }
        }
        return $total;
    }
    /**
     * Get the free balance in main and category passed in construction
     * @return float
     */
    public function get_valid_free() {
        $valid = $this->get_main_free();
        if (!empty($this->catop)) {
            $valid += $this->catop->get_free_balance();
        }
        return $valid;
    }
    /**
     * Return a balance for certain category.
     * @param int $catid
     * @return float
     */
    public function get_cat_balance($catid) {
        $this->check();
        return $this->details['catbalance']->balance ?? 0;
    }

    /**
     * Create a balance util helper class to obtain balance data of a given user
     * by providing the enrol_wallet instance or its id.
     * @param int|\stdClass $instance
     * @param int $userid 0 means the current user.
     * @return self
     */
    public static function create_from_instance($instance, $userid = 0) {
        $util = new instance($instance, $userid);
        $category = $util->get_course_category();
        return new self($userid, $category);
    }
    /**
     * Create a balance util helper class to obtain balance data of a given user
     * by providing the course module record or its id.
     * @param int|\stdClass $cm
     * @param int $userid 0 means the current user.
     * @return self
     */
    public static function create_from_cm($cm, $userid = 0) {
        $util = new cm($cm, $userid);
        $category = $util->get_course_category();
        return new self($userid, $category);
    }
    /**
     * Create a balance util helper class to obtain balance data of a given user
     * by providing the section record or its id.
     * @param int|\stdClass $section
     * @param int $userid 0 means the current user.
     * @return self
     */
    public static function create_from_section($section, $userid = 0) {
        $util = new section($section, $userid);
        $category = $util->get_course_category();
        return new self($userid, $category);
    }
}
