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

/**
 * Functions to handle category balance operations.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet\category;

use enrol_wallet\util\instance as helper;
use enrol_wallet\category\helper as cathelper;
use enrol_wallet\util\balance;
use stdClass;

/**
 * Functions to handle category balance and operations.
 *
 */
class operations extends cathelper {

    /**
     * @var int
     */
    private $userid;

    /**
     * the refundable category balance.
     * @var float $refundable
     */
    public $refundable;

    /**
     * The nonrefundable category balance;
     * @var float $nonrefundable
     */
    public $nonrefundable;

    /**
     * The total balance.
     * @var float
     */
    public $balance;

    /**
     * The details array
     * @var array[object]
     */
    public $details;
    /**
     * How much of the free gift amount has been cut.
     * @var float
     */
    private $freecut = 0;
    /**
     * The free gifted balance
     * @var float
     */
    private $free = 0;

    /**
     * Create a category operation object which will store the balance data.
     *
     * @param object|int $categoryorid the category or its id.
     * @param object|int $userorid The user or userid, leave it empty mean the current user.
     */
    public function __construct($categoryorid, $userorid = 0) {

        parent::__construct($categoryorid);

        if (empty($userorid)) {
            global $USER;
            $this->userid = $USER->id;
        } else if (is_number($userorid)) {
            $this->userid = $userorid;
        } else if (is_object($userorid)) {
            $this->userid = $userorid->id;
        }

        if (empty($this->category) || empty($this->userid)) {
            throw new \moodle_exception('error');
        }

        $balancehelper = new balance($this->userid, -1);
        $this->details = $balancehelper->get_balance_details()['catbalance'] ?? [];
        unset($balancehelper);

        $this->compute_cat_balance();
    }

    /**
     * Compute the category balance for the required user.
     */
    private function compute_cat_balance() {

        $details = $this->details;

        $ids = array_keys($details);
        $commons = array_intersect($ids, $this->parents);

        $refund = 0;
        $norefund = 0;
        $free = 0;
        foreach ($commons as $id) {
            $refund += $details[$id]->refundable;
            $norefund += $details[$id]->nonrefundable;
            $free += $details[$id]->free ?? 0;
        }

        $this->refundable = $refund;
        $this->nonrefundable = $norefund;
        $this->free = min($this->nonrefundable, $free);
        $this->balance = $refund + $norefund;
    }

    /**
     * Return the nonrefundable balance for the given user in the given category.
     * @return float the non refundable balance
     */
    public function get_non_refundable_balance() {
        return $this->nonrefundable;
    }

    /**
     * Return the refundable balance for the given user in the given category.
     * @return float the refundable balance
     */
    public function get_refundable_balance() {
        return $this->refundable;
    }

    /**
     * Return the balance for the given user in the given category.
     * @return float the total category balance
     */
    public function get_balance() {
        if (!isset($this->balance)) {
            $this->balance = $this->nonrefundable + $this->refundable;
        }
        return $this->balance;
    }

    /**
     * Return the cut amount from free balance.
     * @param bool $reset If to reset the amount to 0
     * @return float
     */
    public function get_free_cut($reset = true) {
        $free = $this->freecut;
        if ($reset) {
            $this->freecut = 0;
        }
        return $free;
    }

    /**
     * Get the free balance in these categories.
     * @return float
     */
    public function get_free_balance() {
        $free = 0;
        foreach ($this->parents as $id) {
            $free += $this->details[$id]->free ?? 0;
        }
        return $free;
    }
    /**
     * Add balance for the given user for the current category.
     * @param float $amount the amount to be added.
     * @param bool $refundable If the amount is refundable or not.
     * @param bool $free If this amount is due to a free gift or so.
     */
    public function add($amount, $refundable = true, $free = false) {
        $catobj = (object) [
            'refundable' => $this->details[$this->catid]->refundable ?? 0,
            'nonrefundable' => $this->details[$this->catid]->nonrefundable ?? 0,
            'free' => $this->details[$this->catid]->free ?? 0,
        ];
        $catobj->balance = $this->details[$this->catid]->balance
                            ?? $catobj->refundable + $catobj->nonrefundable;
        if ($refundable) {
            $this->refundable += $amount;
            $catobj->refundable += $amount;
        } else {
            $this->nonrefundable += $amount;
            $catobj->nonrefundable += $amount;
            if ($free) {
                $this->free += $amount;
                $catobj->free += $amount;
            }
        }
        $this->balance += $amount;
        $catobj->balance += $amount;

        $this->details[$this->catid] = $catobj;
    }

    /**
     * deduct balance for the given user for the current category.
     *
     * @param float $amount the amount to be deducted.
     * @return float The remained value to be cut.
     */
    public function deduct($amount) {
        $parents = $this->parents;
        usort($parents, function($a, $b) {
            return $a <=> $b;
        });

        $remain = $amount;
        foreach ($parents as $id) {

            $remain = $this->single_cut($remain, $id);

            if ($remain == 0) {
                break;
            }
        }

        return $remain;
    }

    /**
     * Cut balance from single category only.
     * @param float $amount amount to be cut.
     * @param int $id the id of the category.
     * @return float the remain amount to be deducted.
     */
    private function single_cut($amount, $id) {
        if (!isset($this->details[$id])) {
            return $amount;
        }
        $refundable = $this->details[$id]->refundable;
        $nonrefundable = $this->details[$id]->nonrefundable;
        $free = $this->details[$id]->free ?? 0;
        if ($refundable >= $amount) {
            $refundable = $refundable - $amount;
            $remain = 0;
        } else {
            $nonrefundable = $nonrefundable - $amount + $refundable;
            $refundable = 0;
            $free = $this->details[$id]->free ?? 0;
            if ($nonrefundable >= 0) {
                $remain = 0;
            } else {
                $remain = abs($nonrefundable);
                $nonrefundable = 0;
            }
            $newfree = max($free - $remain, 0);
            $this->freecut += max($free - $newfree, 0);
        }
        $this->details[$id] = (object)[
            'refundable' => $refundable,
            'nonrefundable' => $nonrefundable,
            'free' => $newfree ?? $free,
            'balance' => $refundable + $nonrefundable,
        ];

        return $remain;
    }

    /**
     * Create category balance operations class from enrol wallet instance
     * @param int|stdClass $instanceorid the enrol wallet instance of its id.
     * @param int $userid if 0 it will refer to the current user.
     * @return operations
     */
    public static function create_from_instance($instanceorid, $userid = 0) {
        $helper = new helper($instanceorid, $userid);
        $category = $helper->get_course_category();
        return new self($category, $userid);
    }

    /**
     * Create an instance of category balance operation from course object or its id.
     * @param int|stdClass $courseorid
     * @param int $userid if 0 it will set the current user.
     * @return operations
     */
    public static function create_from_course($courseorid, $userid = 0) {
        if (is_number($courseorid)) {
            $course = get_course($courseorid);
        } else if (is_object($courseorid)) {
            $course = $courseorid;
        } else {
            throw new \moodle_exception('invalidcourseid');
        }
        return new self($course->category, $userid);
    }
}
