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
use html_writer;
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
     * @param  bool             $subcondition Is part of another set condition.
     * @throws coding_exception
     */
    public function __construct(
        /** @var stdClass the offer data object. */
        public readonly stdClass $offer,
        /** @var int The course id. */
        public readonly int $courseid,
        /** @var int The user id to validate offer for. */
        protected int $userid = 0,
        /** @var bool If this is a part of another condition. */
        public readonly bool $subcondition = false,
    ) {
        global $USER;

        static::check_same_type($offer);
        static::check_structure_validation($offer, true || PHPUNIT_TEST);

        if (!$subcondition && !isset($offer->discount)) {
            ob_start();
            var_dump(compact('offer', 'subcondition'));
            throw new coding_exception("No discount for offer which not a sub-offer ", ob_get_clean());
        }
        $this->discount = $subcondition ? 0 : $offer->discount;

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
     * Getter for userid.
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Get formatted discount value.
     * @return string something like 15.2% DISCOUNT
     */
    public function get_formatted_discount(): string {
        if ($this->subcondition || $this->is_hidden()) {
            return '';
        }
        $discount = format_float($this->discount, 2, true, true);

        return get_string('offers_discountpercent', 'enrol_wallet', $discount);
    }

    /**
     * Check if this should be hidden from display
     * and calculating the maximum un-validated offer.
     * @return bool
     */
    public function is_hidden(): bool {
        return !static::is_available();
    }
    /**
     * Return the type of this offer item.
     * @return string
     */
    abstract public static function key(): string;

    // Todo: display a different description if the offer granted.
    /**
     * Get description explaining to the user how to get this offer
     * return null if it is not available.
     *
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
     * @param  stdClass|null   $offer    The existing offer configuration (for update).
     * @param  callable|null   $wrapper  Wrapper for the form element name.
     * @return void
     */
    abstract public static function add_form_element(
        MoodleQuickForm $mform,
        int $i,
        int $courseid,
        ?stdClass $offer = null,
        ?callable $wrapper = null
    ): void;

    /**
     * Format an offer form element name.
     * @param string    $key     the key of the element
     * @param int       $inc     increment
     * @param ?callable $wrapper
     */
    public static function fname(string $key, int $inc, ?callable $wrapper = null): string {
        $type = static::key();
        $name = "offer_{$type}";

        if (!empty($key)) {
            $name .= "_{$key}";
        }

        $elementname = "{$name}_{$inc}";

        if ($wrapper !== null) {
            return \call_user_func($wrapper, $elementname);
        }

        return $elementname;
    }

    /**
     * Validate the submitted data from the edit form.
     * @param  stdClass      $offer   Offer data structure.
     * @param  int           $i       Offer index.
     * @param  array         $errors  Reference to validation error messages.
     * @param  callable|null $wrapper Element name generator wrapper.
     * @return void
     */
    abstract public static function validate_submitted_offer(
        stdClass $offer,
        int $i,
        array &$errors,
        ?callable $wrapper = null
    ): void;

    /**
     * Check that this offer is the same type as the current class.
     * @param  stdClass $offer
     * @param  bool     $throw
     * @return bool
     */
    final protected static function check_same_type(stdClass $offer, bool $throw = true): bool {
        if (empty($offer->type) || $offer->type !== static::key()) {
            $err = "Mismatch between offer type and class. Offer type: {$offer->type}, Class type: " . static::key();
            $throw ? throw new coding_exception($err) : debugging($err);
        }

        return true;
    }

    /**
     * Should be used to validate the structure of the offer object
     * that is passed to the constructor and any static method.
     * @param  stdClass $offer
     * @param  bool     $throw
     * @return bool
     */
    final protected static function check_structure_validation(stdClass $offer, bool $throw = true): bool {
        $valid = static::check_same_type($offer, $throw) && static::is_valid_structure($offer);

        if (!$valid) {
            $msg = 'Invalid offer object structure for the offer type: ' . static::get_visible_name();

            if ($throw) {
                ob_start();
                var_dump($offer);
                throw new coding_exception($msg, ob_get_clean());
            }
            debugging($msg);
        }

        return $valid;
    }

    /**
     * Checks if this offer object is matching the structure of the current
     * class.
     * @param  stdClass $offer
     * @return void
     */
    abstract public static function is_valid_structure(stdClass $offer): bool;

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
     * @param  ?callable       $wrapper
     * @return void
     */
    public static function after_edit_form_definition(
        MoodleQuickForm $mform,
        stdClass $offer,
        int $i,
        ?callable $wrapper = null
    ): void {
        static::check_structure_validation($offer);

        foreach ($offer as $key => $value) {
            if ($key == 'type') {
                continue;
            }
            $mform->setDefault(static::fname($key, $i, $wrapper), $value);
        }
    }

    /**
     * Clean the submitted values.
     * @param  string $name  parameter name.
     * @param  mixed  $value
     * @return void
     */
    public static function clean_submitted_value(string $name, mixed &$value): void {
        $value = ($name == 'discount') ? clean_param($value, PARAM_FLOAT) : clean_param($value, PARAM_TEXT);
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
        if (!isset($offers[$i])) {
            $offers[$i]       = new stdClass();
            $offers[$i]->type = static::key();
        }
        $offers[$i]->$name = $value;
    }

    /**
     * Add heading to the form fragment contain the offer type name.
     * @param  MoodleQuickForm $mform
     * @param  ?callable       $wrapper
     * @return void
     */
    protected static function add_offer_form_heading(MoodleQuickForm $mform, ?callable $wrapper = null): void {
        global $OUTPUT;
        $name    = static::get_visible_name();
        $heading = $OUTPUT->heading($name, 5);
        $mform->addElement('html', $heading);
    }

    /**
     * Return a callable to wrap the offer element name.
     * @param int $i the current increment
     * @param ?callable $wrapper the parent wrapper
     *
     * @throws coding_exception
     * @return callable
     */
    public static function get_wrapper(int $i, ?callable $wrapper = null): callable {
        throw new coding_exception('The method ::get_wrapper() is not implanted in ' . static::class);
    }

    /**
     * Add a form fragment directly for certain type of offers.
     * Used in server side directly.
     *
     * @param int             $i            increment number
     * @param int             $courseid
     * @param MoodleQuickForm $mform
     * @param stdClass|null   $offer
     * @param bool            $issuboffer   set to false if this is a sub offer.
     * @param ?callable       $wrapper      the wrapper for form element name.
     * @return void
     */
    public static function add_form_fragment(
        int $i,
        int $courseid,
        MoodleQuickForm $mform,
        ?stdClass $offer = null,
        bool $issuboffer = true,
        ?callable $wrapper = null
    ): void {
        $style = 'border: 3px groove gray;'
               . 'border-radius: 15px;'
               . 'padding: 0.5rem;'
               . 'margin: 0.5rem;';
        $out = html_writer::start_div('', [
            'style'       => $style,
            'class'       => 'enrol-wallet-offerset',
            'data-action' => 'offer-set',
        ]);
        $mform->addElement('html', $out);

        static::add_offer_form_heading($mform, $wrapper);

        static::add_form_element($mform, $i, $courseid, $offer, $wrapper);

        static::add_form_footer($mform, $i, $wrapper, $issuboffer);

        $mform->addElement('html', html_writer::end_div());
    }

    /**
     * Add footer for the form fragment contain the discount value and delete button.
     * @param  MoodleQuickForm $mform
     * @param  int             $i
     * @param  ?callable       $wrapper
     * @param  bool            $withdiscount
     * @return void
     */
    protected static function add_form_footer(
        MoodleQuickForm $mform, int $i, ?callable $wrapper = null, bool $withdiscount = true): void {
        if ($withdiscount) {
            $name = static::fname('discount', $i, $wrapper);
            $mform->addElement('text', $name, get_string('discount', 'enrol_wallet'));
            $mform->setType($name, PARAM_FLOAT);
        }

        $attributes = [
            'data-action-delete' => $i,
            'data-action'        => 'deleteoffer',
        ];
        $delname = 'offer_delete_' . $i;
        $delname = $wrapper ? \call_user_func($wrapper, $delname) : $delname;
        $mform->addElement('button', $delname, get_string('delete'), $attributes);
    }
}
