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

use availability_profile\condition as profile_c;
use core\exception\coding_exception;
use MoodleQuickForm;
use phpunit_util;
use stdClass;
use testing_data_generator;

/**
 * Class profile_field_offer.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_offer extends offer_item {
    /** @var string Operator: field contains value */
    public const PFOP_CONTAINS = 'contains';

    /** @var string Operator: field does not contain value */
    public const PFOP_DOES_NOT_CONTAIN = 'doesnotcontain';

    /** @var string Operator: field equals value */
    public const PFOP_IS_EQUAL_TO = 'isequalto';

    /** @var string Operator: field starts with value */
    public const PFOP_STARTS_WITH = 'startswith';

    /** @var string Operator: field ends with value */
    public const PFOP_ENDS_WITH = 'endswith';

    /** @var string Operator: field is empty */
    public const PFOP_IS_EMPTY = 'isempty';

    /** @var string Operator: field is not empty */
    public const PFOP_IS_NOT_EMPTY = 'isnotempty';

    /**
     * Custom field shortname.
     * @var ?string
     */
    protected ?string $cf;

    /**
     * Standard field name.
     * @var ?string
     */
    protected ?string $sf;

    /**
     * Operator.
     * @var string
     */
    protected string $op;

    /**
     * The condition value.
     * @var ?string
     */
    protected ?string $value;

    /**
     * {@inheritDoc}
     * @param stdClass $offer
     * @param int      $courseid
     * @param int      $userid
     * @param bool     $subcondition
     */
    public function __construct(\stdClass $offer, int $courseid, int $userid = 0, bool $subcondition = false) {
        parent::__construct($offer, $courseid, $userid, $subcondition);
        $this->cf = $offer->cf ?? null;
        $this->sf = $offer->sf ?? null;
        $this->op = $offer->op;
        $this->value = $offer->value ?? null;
    }

    #[\Override()]
    public static function is_valid_structure(stdClass $offer): bool {
        $cf = $offer->cf ?? null;
        $sf = $offer->sf ?? null;
        $op = $offer->op;
        $value = $offer->value ?? null;
        $valid = !empty($cf) || !empty($sf);
        $valid = $valid && \in_array($op, [
            self::PFOP_CONTAINS,
            self::PFOP_DOES_NOT_CONTAIN,
            self::PFOP_IS_EQUAL_TO,
            self::PFOP_STARTS_WITH,
            self::PFOP_ENDS_WITH,
            self::PFOP_IS_EMPTY,
            self::PFOP_IS_NOT_EMPTY,
        ]);
        $valid = $valid && (isset($value) || in_array($op, [self::PFOP_IS_NOT_EMPTY, self::PFOP_IS_EMPTY]));

        return $valid;
    }

    #[\Override()]
    public static function is_available(): bool {
        global $CFG;

        if (file_exists($CFG->dirroot . '/availability/condition/profile/classes/condition.php')) {
            require_once($CFG->dirroot . '/availability/condition/profile/classes/condition.php');

            return true;
        }

        return false;
    }

    #[\Override()]
    public static function key(): string {
        return 'pf';
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        global $DB;

        if (!$this->is_available()) {
            return null;
        }

        if (isset($this->sf)) {
            $fieldname = get_string($this->sf);
        } else {
            $name = $DB->get_field('user_info_field', 'name', ['shortname' => $this->cf]);
            $fieldname = format_string($name);
        }
        $a = [
            'op'       => get_string('offers_pfop_' . $this->op, 'enrol_wallet'),
            'discount' => $this->get_formatted_discount(),
            'field'    => $fieldname,
            'value'    => $this->value,
        ];

        return get_string('offers_pf_desc', 'enrol_wallet', $a);
    }

    #[\Override()]
    public function is_hidden(): bool {
        global $DB;

        if (parent::is_hidden()) {
            return true;
        }

        if (!isset($this->cf) && isset($this->sf)) {
            return false;
        }

        // May be this field is deleted.
        return !$DB->record_exists('user_info_field', ['shortname' => $this->cf]);
    }

    /**
     * Get the structure needed for availability.
     * @return object
     */
    protected function get_structure(): stdClass {
        $structure = (object)[
            'op' => $this->op,
            'v'  => $this->value ?? null,
        ];

        if (isset($this->sf)) {
            $structure->sf = $this->sf;
        } else {
            $structure->cf = $this->cf;
        }

        switch ($this->op) {
            case self::PFOP_IS_EMPTY:
            case self::PFOP_IS_NOT_EMPTY:
                unset($structure->v);
                break;

            default:
        }

        return $structure;
    }

    #[\Override()]
    public function validate_offer(): bool {
        if (!$this->is_available()) {
            return false;
        }

        $structure = $this->get_structure();

        try {
            $av = new profile_c($structure);

            $fake = new fake_info();

            $available = $av->is_available(false, $fake, false, $this->userid);

            if ($available) {
                return true;
            }
        } catch (\Throwable $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());

            return false;
        }

        return false;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_profile_field_based', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(
        MoodleQuickForm $mform,
        int $i,
        int $courseid,
        ?stdClass $offer = null,
        ?callable $wrapper = null
    ): void {
        global $CFG;
        $context = \core\context\course::instance($courseid);
        $fields = [
            'firstname', 'lastname', 'email',
            'city', 'country', 'idnumber',
            'institution', 'department',
            'phone1', 'phone2', 'address',
        ];
        $stfields = [];

        foreach ($fields as $field) {
            $stfields['sf' . $field] = get_string($field);
        }
        \core_collator::asort($stfields);
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $fields = profile_get_custom_fields(true);
        $custom = [];

        foreach ($fields as $field) {
            $custom['cf' . $field->shortname] = format_string($field->name, true, ['context' => $context]);
        }
        \core_collator::asort($custom);
        $options = array_merge(['' => get_string('choosedots')], $stfields, $custom);
        $group = [];
        $label = get_string('offers_profile_field', 'enrol_wallet');
        $group[] = $mform->createElement('select', static::fname('field', $i, $wrapper), $label, $options);
        $operations = [
            self::PFOP_CONTAINS         => get_string('offers_pfop_contains', 'enrol_wallet'),
            self::PFOP_DOES_NOT_CONTAIN => get_string('offers_pfop_doesnotcontain', 'enrol_wallet'),
            self::PFOP_IS_EQUAL_TO      => get_string('offers_pfop_isequalto', 'enrol_wallet'),
            self::PFOP_IS_EMPTY         => get_string('offers_pfop_isempty', 'enrol_wallet'),
            self::PFOP_IS_NOT_EMPTY     => get_string('offers_pfop_isnotempty', 'enrol_wallet'),
            self::PFOP_STARTS_WITH      => get_string('offers_pfop_startswith', 'enrol_wallet'),
            self::PFOP_ENDS_WITH        => get_string('offers_pfop_endswith', 'enrol_wallet'),
        ];
        $group[] = $mform->createElement('select', static::fname('op', $i, $wrapper), '', $operations);
        $group[] = $mform->createElement('text', static::fname('value', $i, $wrapper), '');
        $mform->setType(static::fname('value', $i, $wrapper), PARAM_TEXT);
        $mform->addGroup(
            $group,
            static::fname('', $i, $wrapper),
            get_string('offers_profile_field_based', 'enrol_wallet'),
            null,
            false
        );
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors, ?callable $wrapper = null): void {
        if (empty($offer->cf) && empty($offer->sf)) {
            $errors[static::fname('', $i, $wrapper)] = get_string('offers_error_pfselect', 'enrol_wallet');
        } else if (!\in_array($offer->op, [self::PFOP_IS_EMPTY, self::PFOP_IS_NOT_EMPTY])) {
            if (empty($offer->value)) {
                $errors[static::fname('', $i, $wrapper)] = get_string('offers_error_pfnovalue', 'enrol_wallet');
            }
        }
    }

    #[\Override()]
    public static function after_edit_form_definition(
        MoodleQuickForm $mform,
        stdClass $offer,
        int $i,
        ?callable $wrapper = null
    ): void {
        foreach ($offer as $key => $value) {
            if ($key == 'type') {
                continue;
            }

            if (\in_array($key, ['cf', 'sf'])) {
                if (empty($value)) {
                    continue;
                }
                $value = $key . $value;
                $key = 'field';
            }
            $mform->setDefault(static::fname($key, $i, $wrapper), $value);
        }
    }

    #[\Override()]
    public static function pre_save_submitted_data(array &$offers, int $i, string $name, mixed $value): void {
        if ($name == 'field') {
            if (strpos($value, 'sf') === 0) {
                $offers[$i]->sf = substr($value, 2);
            } else if (strpos($value, 'cf') === 0) {
                $offers[$i]->cf = substr($value, 2);
            }
        } else {
            $offers[$i]->$name = $value;
        }
    }

    /**
     * Mock an offer object of this type for testing.
     * @param  ?testing_data_generator $gen
     * @param  ?float                  $discount
     * @param  ?string                 $cf
     * @param  ?string                 $sf
     * @param  ?string                 $op
     * @param  ?string|float|int       $value
     * @return stdClass
     */
    public static function mock_offer(
        ?testing_data_generator $gen = null,
        ?float $discount = null,
        ?string $cf = null,
        ?string $sf = null,
        ?string $op = null,
        string|float|int|null $value = null
    ): stdClass {
        global $DB;

        if (null === $gen) {
            $gen = phpunit_util::get_data_generator();
        }
        static $inc = 0;
        $offer = new stdClass();
        $offer->type = static::key();
        $offer->discount = $discount ?? random_int(1, 99);

        if ($sf === null && $cf === null) {
            $fields = ['shortname', 'email', 'firstname', 'lastname', 'country', 'city', 'address'];
            $randkey = array_rand($fields);
            $sf = $fields[$randkey];
        }
        $offer->cf = $cf;
        $offer->sf = $sf;
        $ops = [
            self::PFOP_CONTAINS,
            self::PFOP_DOES_NOT_CONTAIN,
            self::PFOP_IS_EQUAL_TO,
            self::PFOP_STARTS_WITH,
            self::PFOP_ENDS_WITH,
            self::PFOP_IS_EMPTY,
            self::PFOP_IS_NOT_EMPTY,
        ];

        if ($op === null) {
            $op = $ops[rand(0, 6)];
        }

        if (!\in_array($op, $ops)) {
            throw new coding_exception("Invalid ->op $op");
        }
        $offer->op = $op;

        if ($value === null && !\in_array($op, [self::PFOP_IS_EMPTY, self::PFOP_IS_NOT_EMPTY])) {
            $value = random_string(5);
        }
        $offer->value = $value;

        if ($cf !== null && !$DB->record_exists('user_info_field', ['shortname' => $cf])) {
            // Check the existence of the field.
            $record = [
                'shortname' => $cf,
                'datatype'  => 'text',
                'name'      => 'Offer test field ' . $inc++,
            ];
            $gen->create_custom_profile_field($record);
        }

        return $offer;
    }
}
