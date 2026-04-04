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

use enrol_wallet\local\utils\timedate;
use MoodleQuickForm;
use stdClass;
use testing_data_generator;

/**
 * Class time_offer.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class time_offer extends offer_item {
    /**
     * Date from.
     * @var int
     */
    protected int $from;

    /**
     * Date to.
     * @var int
     */
    protected int $to;

    /**
     * {@inheritDoc}
     * @param stdClass $offer
     * @param int      $courseid
     * @param int      $userid
     * @param bool     $subcondition
     */
    public function __construct(stdClass $offer, int $courseid, int $userid = 0, bool $subcondition = false) {
        parent::__construct($offer, $courseid, $userid, $subcondition);
        $this->from = $offer->from;
        $this->to = $offer->to;
    }

    #[\Override()]
    public static function is_valid_structure(stdClass $offer): bool {
        $from = $offer->from;
        $to = $offer->to;
        $valid = is_number($from) && $from >= 0;
        $valid = $valid && is_number($to) && $to >= $from;

        return $valid;
    }

    #[\Override()]
    public static function key(): string {
        return 'time';
    }

    /**
     * Hide if the offer is in the past and cannot be obtained anymore.
     * @return bool
     */
    public function is_hidden(): bool {
        return parent::is_hidden() || timedate::time() >= $this->to;
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        if ($availableonly && !$this->validate_offer() || !$this->is_available()) {
            return null;
        }

        $a = [
            'to'       => userdate($this->to),
            'from'     => userdate($this->from),
            'discount' => $this->get_formatted_discount(),
        ];

        return get_string('offers_time_desc', 'enrol_wallet', $a);
    }

    #[\Override()]
    public function validate_offer(): bool {
        $now = timedate::time();

        if ($now < $this->to && $now > $this->from) {
            return true;
        }

        return false;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_time_based', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(
        MoodleQuickForm $mform,
        int $i,
        int $courseid,
        ?stdClass $offer = null,
        ?callable $wrapper = null
    ): void {
        $mform->addElement('date_time_selector', static::fname('from', $i, $wrapper), get_string('fromdate'));
        $mform->addElement('date_time_selector', static::fname('to', $i, $wrapper), get_string('todate'));
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors, ?callable $wrapper = null): void {
        if ($offer->to < timedate::time() - DAYSECS) {
            $errors[static::fname('to', $i, $wrapper)] = get_string('offers_error_timeto', 'enrol_wallet');
        }

        if ($offer->from > $offer->to) {
            $errors[static::fname('from', $i, $wrapper)] = get_string('offers_error_timefrom', 'enrol_wallet');
        }
    }

    #[\Override()]
    public static function clean_submitted_value(string $name, mixed &$value): void {
        if (\is_array($value)) {
            $value = clean_param_array($value, PARAM_INT);
            $value = mktime($value['hour'], $value['minute'], 0, $value['month'], $value['day'], $value['year']);
        } else {
            parent::clean_submitted_value($name, $value);
        }
    }

    /**
     * Mock an offer object of this type for testing.
     * @param  ?testing_data_generator $gen
     * @param  ?float                  $discount
     * @param  ?int                    $to
     * @param  ?int                    $from
     * @return stdClass
     */
    public static function mock_offer(
        ?testing_data_generator $gen = null,
        ?float $discount = null,
        ?int $to = null,
        ?int $from = null
    ): stdClass {
        $offer = new stdClass();
        $offer->type = static::key();
        $offer->discount = $discount ?? random_int(1, 99);
        $offer->from = $from ?? timedate::time() - rand(1, 5) * DAYSECS;
        $offer->to = $to ?? timedate::time() + rand(1, 5) * DAYSECS;

        return $offer;
    }
}
