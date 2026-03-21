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

namespace enrol_wallet\local\discounts;

use core\exception\coding_exception;
use MoodleQuickForm;
use stdClass;

/**
 * Class offer_item.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class offer_item {
    /**
     * The discount value for this offer.
     * @var float
     */
    protected float $discount;

    /**
     * Initiate an offer item helper class.
     * @param  stdClass         $offer    The offer data.
     * @param  int              $courseid The course id.
     * @param  int              $userid   The user id to validate the offer for.
     * @throws coding_exception
     */
    public function __construct(
        /** @var stdClass the offer data object. */
        protected stdClass $offer,
        /** @var int The course id. */
        protected int $courseid,
        /** @var int The user id to validate offer for. */
        protected int $userid = 0
    ) {
        global $USER;

        if ($this->key() !== $offer->type) {
            $err = "Mismatch between offer type and class. Offer type: {$offer->type}, Class type: " . $this->key();
            throw new coding_exception($err);
        }

        $this->discount = $offer->discount;

        if ($this->userid === 0) {
            $this->userid = $USER->id;
        }
    }

    /**
     * Get the discount value.
     * @return float
     */
    public function get_discount(): float {
        return $this->discount;
    }

    /**
     * Get formatted discount value.
     * @return string
     */
    public function get_formatted_discount(): string {
        return format_float($this->discount);
    }

    /**
     * Return the type of this offer item.
     * @return string
     */
    abstract public static function key(): string;

    /**
     * Get description explaining to the user how to get this offer
     * return null if it is not available.
     * @param  bool    $availableonly
     * @return ?string
     */
    abstract public function get_description(bool $availableonly = false): ?string;

    /**
     * Validate the offer for the current user.
     * @return bool
     */
    abstract public function validate_offer(): bool;

    /**
     * Get the label of the offer.
     * @return string
     */
    abstract public static function get_visible_name(): string;

    /**
     * Add special field for this offer to the moodle form.
     * @param  MoodleQuickForm $mform
     * @param  int             $i        increment.
     * @param  int             $courseid the course id.
     * @return void
     */
    abstract public static function add_form_element(MoodleQuickForm $mform, int $i, int $courseid): void;

    /**
     * Validate the submitted data from the edit form.
     * @param  stdClass $offer
     * @param  int      $i
     * @param  array    $errors
     * @return void
     */
    abstract public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors): void;

    /**
     * Check if this type is available for use.
     * @return bool
     */
    public static function is_available(): bool {
        return true;
    }

    /**
     * Add default values for the form element after adding them.
     * @param  MoodleQuickForm $mform
     * @param  stdClass        $offer
     * @param  int             $i
     * @return void
     */
    public static function after_edit_form_definition(MoodleQuickForm $mform, stdClass $offer, int $i): void {
        $type = $offer->type;

        foreach ($offer as $key => $value) {
            if ($key == 'type') {
                continue;
            }
            $mform->setDefault(offers::fname($type, $key, $i), $value);
        }
    }

    /**
     * Clean the submitted values.
     * @param  string $name  parameter name.
     * @param  mixed  $value
     * @return void
     */
    public static function clean_submitted_value(string $name, mixed &$value): void {
        if ($name == 'discount') {
            $value = clean_param($value, PARAM_FLOAT);
        } else {
            $value = clean_param($value, PARAM_TEXT);
        }
    }

    /**
     * Add the submitted value after cleaning to the list of offers.
     * @param  array  $offers
     * @param  int    $i
     * @param  string $name
     * @param  mixed  $value
     * @return void
     */
    public static function pre_save_submitted_data(array &$offers, int $i, string $name, mixed $value): void {
        $offers[$i]->$name = $value;
    }
}
