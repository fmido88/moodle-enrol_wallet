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

namespace enrol_wallet\local\wallet;

use core_course_category;
use enrol_wallet\exception\negative_amount;

/**
 * Functions to handle category balance and operations.
 */
class catop {
    /**
     * Category id.
     * @var int
     */
    protected int $catid;

    /**
     * Parent categories ids including this one.
     * @var int[]
     */
    protected array $parents = [];

    /**
     * @var int
     */
    private $userid;

    /**
     * the refundable category balance.
     * @var float
     */
    public $refundable;

    /**
     * The nonrefundable category balance;.
     * @var float
     */
    public $nonrefundable;

    /**
     * The total balance.
     * @var float
     */
    public $balance;

    /**
     * The details array.
     * @var catdetails[]
     */
    public $details;

    /**
     * How much of the free gift amount has been cut.
     * @var float
     */
    private $freecut = 0;

    /**
     * The free gifted balance.
     * @var float
     */
    private $free = 0;

    /**
     * Create a category operation object which will store the balance data.
     *
     * @param object|int $categoryorid the category or its id.
     * @param details    $details
     */
    public function __construct(int|object $categoryorid, details $details) {
        if (is_object($categoryorid)) {
            $this->catid = $categoryorid->id;

            if ($categoryorid instanceof core_course_category) {
                $category = $categoryorid;
            }
        } else {
            $this->catid = $categoryorid;
        }

        if (!isset($category)) {
            $category = core_course_category::get($this->catid);
        }

        $this->parents = $category->get_parents();
        // Include the catid with the parents array for easy search.
        $this->parents[$this->catid] = $this->catid;

        if (empty($this->catid)) {
            throw new \moodle_exception('error');
        }

        $this->details = $details->catbalance;

        $this->compute_cat_balance();
    }

    /**
     * Return categories ids (this category id and parents).
     * @return int[]
     */
    public function get_catids(): array {
        return $this->parents;
    }

    /**
     * Compute the category balance for the required user.
     */
    public function compute_cat_balance() {
        $details = $this->details;

        $ids     = array_keys($details);
        $commons = array_intersect($ids, $this->parents);

        $refund   = 0;
        $norefund = 0;
        $free     = 0;

        foreach ($commons as $id) {
            $refund   += $details[$id]->refundable;
            $norefund += $details[$id]->nonrefundable;
            $free     += $details[$id]->free ?? 0;
        }

        $this->refundable    = $refund;
        $this->nonrefundable = $norefund;
        $this->free          = min($this->nonrefundable, $free);
        $this->balance       = $refund + $norefund;
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
     * @param  bool  $reset If to reset the amount to 0
     * @return float
     */
    public function get_free_cut($reset = true): float {
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
    public function get_free_balance(): float {
        $free = 0;

        foreach ($this->parents as $id) {
            $free += $this->details[$id]->free ?? 0;
        }

        return $free;
    }

    /**
     * Add balance for the given user for the current category.
     * @param float $amount     the amount to be added.
     * @param bool  $refundable If the amount is refundable or not.
     * @param bool  $free       If this amount is due to a free gift or so.
     */
    public function add($amount, $refundable = true, $free = false): void {
        $catobj = $this->details[$this->catid] ?? new catdetails(0, 0, 0);

        if ($refundable) {
            $this->refundable   += $amount;
            $catobj->refundable += $amount;
        } else {
            $this->nonrefundable   += $amount;
            $catobj->nonrefundable += $amount;

            if ($free) {
                $this->free   += $amount;
                $catobj->free += $amount;
            }
        }
        $this->balance += $amount;

        $this->details[$this->catid] = $catobj;
    }

    /**
     * deduct balance for the given user for the current category.
     *
     * @param  float $amount the amount to be deducted.
     * @return float The remained value to be cut.
     */
    public function deduct($amount): float {
        negative_amount::check($amount);
        $parents = $this->parents;

        $remain = $amount;

        foreach ($parents as $id) {
            $remain = $this->single_cut($remain, $id);

            if ($remain <= 0) {
                break;
            }
        }

        return $remain;
    }

    /**
     * Cut balance from single category only.
     * @param  float $amount amount to be cut.
     * @param  int   $id     the id of the category.
     * @return float the remain amount to be deducted.
     */
    private function single_cut($amount, $id): float {
        negative_amount::check($amount);

        if (!isset($this->details[$id])) {
            return $amount;
        }

        $refundable    = $this->details[$id]->refundable;
        $nonrefundable = $this->details[$id]->nonrefundable;

        $free = $this->details[$id]->free;

        if ($refundable >= $amount) {
            $refundable       -= $amount;
            $this->refundable -= $amount;
            $remain = 0;
        } else {
            $nonrefundable = $nonrefundable - $amount + $refundable;
            $newfree       = $free - $amount + $refundable;

            $this->nonrefundable -= $amount - $refundable;

            $this->refundable -= $refundable;
            $refundable = 0;

            if ($nonrefundable >= 0) {
                $remain = 0;
            } else {
                $remain        = abs($nonrefundable);
                $nonrefundable = 0;
            }

            $newfree = max($newfree, 0);
            $freecut = max($free - $newfree, 0);

            $this->free -= $freecut;
            $this->freecut += $freecut;
        }

        $this->details[$id] = new catdetails($refundable, $nonrefundable, $newfree ?? $free ?? 0);

        return $remain;
    }
}
