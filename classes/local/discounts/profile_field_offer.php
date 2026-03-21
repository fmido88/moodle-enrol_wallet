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
use MoodleQuickForm;
use stdClass;

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
     */
    public function __construct(\stdClass $offer, int $courseid, int $userid = 0) {
        parent::__construct($offer, $courseid, $userid);
        $this->cf    = $offer->cf ?? null;
        $this->sf    = $offer->sf ?? null;
        $this->op    = $offer->op;
        $this->value = $offer->value ?? null;
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

        if (isset($offer->sf)) {
            $fieldname = get_string($this->sf);
        } else {
            $name      = $DB->get_field('user_info_field', 'name', ['shortname' => $this->cf]);
            $fieldname = format_string($name);
        }
        $a = [
            'op'       => get_string('offers_pfop_' . $this->op, 'enrol_wallet'),
            'discount' => $this->discount,
            'field'    => $fieldname,
            'value'    => $this->value,
        ];

        return get_string('offers_pf_desc', 'enrol_wallet', $a);
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
        } catch (\coding_exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER);

            return false;
        }

        return false;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_profile_field_based', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(MoodleQuickForm $mform, int $i, int $courseid): void {
        global $CFG;
        $context = \core\context\course::instance($courseid);
        $fields  = [
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
        $options    = array_merge(['' => get_string('choosedots')], $stfields, $custom);
        $group      = [];
        $label      = get_string('offers_profile_field', 'enrol_wallet');
        $group[]    = $mform->createElement('select', 'offer_pf_field_' . $inc, $label, $options);
        $operations = [
            self::PFOP_CONTAINS         => get_string('offers_pfop_contains', 'enrol_wallet'),
            self::PFOP_DOES_NOT_CONTAIN => get_string('offers_pfop_doesnotcontain', 'enrol_wallet'),
            self::PFOP_IS_EQUAL_TO      => get_string('offers_pfop_isequalto', 'enrol_wallet'),
            self::PFOP_IS_EMPTY         => get_string('offers_pfop_isempty', 'enrol_wallet'),
            self::PFOP_IS_NOT_EMPTY     => get_string('offers_pfop_isnotempty', 'enrol_wallet'),
            self::PFOP_STARTS_WITH      => get_string('offers_pfop_startswith', 'enrol_wallet'),
            self::PFOP_ENDS_WITH        => get_string('offers_pfop_endswith', 'enrol_wallet'),
        ];
        $group[] = $mform->createElement('select', 'offer_pf_op_' . $inc, '', $operations);
        $group[] = $mform->createElement('text', 'offer_pf_value_' . $inc, '');
        $mform->setType('offer_pf_value_' . $inc, PARAM_TEXT);
        $mform->addGroup($group, 'offer_pf_' . $inc, get_string('offers_profile_field_based', 'enrol_wallet'), null, false);
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors): void {
        if (empty($offer->cf) && empty($offer->sf)) {
            $errors[offers::fname(self::key(), '', $i)] = get_string('offers_error_pfselect', 'enrol_wallet');
        } else if (!in_array($offer->op, [self::PFOP_IS_EMPTY, self::PFOP_IS_NOT_EMPTY])) {
            if (empty($offer->value)) {
                $errors[offers::fname(self::key(), '', $i)] = get_string('offers_error_pfnovalue', 'enrol_wallet');
            }
        }
    }

    #[\Override()]
    public static function after_edit_form_definition(MoodleQuickForm $mform, stdClass $offer, int $i): void {
        $type = $offer->type;

        foreach ($offer as $key => $value) {
            if ($key == 'type') {
                continue;
            }

            if (\in_array($key, ['cf', 'sf'])) {
                if (empty($value)) {
                    continue;
                }
                $value = $key . $value;
                $key   = 'field';
            }
            $mform->setDefault(offers::fname($type, $key, $i), $value);
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
}
