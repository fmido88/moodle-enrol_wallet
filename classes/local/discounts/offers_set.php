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

use core_text;
use html_writer;
use MoodleQuickForm;
use phpunit_util;
use stdClass;
use testing_data_generator;

/**
 * Class offers_set.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offers_set extends offer_item {
    /**
     * All of the conditions must met.
     * @var string
     */
    public const OP_AND = 'AND';

    /**
     * Any of the conditions must met.
     * @var string
     */
    public const OP_OR = 'OR';

    /**
     * Sub offers as conditions.
     * @var offer_item[]
     */
    protected array $suboffers = [];

    /**
     * Operator 'AND' or 'OR'.
     * @var string
     */
    protected string $op;

    /**
     * Constructor for sub offer container.
     * @param stdClass $offer
     * @param int      $courseid
     * @param int      $userid
     * @param bool     $subcondition
     */
    public function __construct(stdClass $offer, int $courseid, int $userid = 0, bool $subcondition = false) {
        parent::__construct($offer, $courseid, $userid, $subcondition);

        foreach ($offer->sub as $suboffer) {
            $subofferclass = offers::get_offer_class_name($suboffer->type);

            if ($subofferclass) {
                unset($suboffer->discount);
                $this->suboffers[] = new $subofferclass($suboffer, $courseid, $userid, true);
            }
        }

        $this->op = $offer->op;
    }

    #[\Override()]
    public static function is_valid_structure(stdClass $offer): bool {
        $sub = $offer->sub;
        $op = $offer->op;
        $valid = \in_array($op, [self::OP_OR, self::OP_AND]);

        if ($valid && $sub instanceof stdClass) {
            foreach ($sub as $key => $value) {
                if (!is_number($key)) {
                    $valid = false;
                    break;
                }
            }
            $sub = (array)$sub;
        }
        $valid = $valid && \is_array($sub);

        foreach ($sub as $v) {
            $valid = $valid && \is_object($v);
        }

        foreach ($sub as $suboffer) {
            $type = $suboffer->type;
            $class = offers::get_offer_class_name($type);

            if (!$class) {
                continue;
            }

            $valid = $valid && $class::is_valid_structure($suboffer);
        }

        return $valid;
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        global $OUTPUT;

        $list = [];
        foreach ($this->suboffers as $offer) {
            $desc = $offer->get_description($availableonly);

            if ($desc) {
                $list[] = [
                    'description' => $desc,
                    'valid' => $offer->validate_offer(),
                ];
            }
        }

        if (empty($list)) {
            return null;
        }

        $listintro = \count($list) > 1 ?
                    ($this->op === self::OP_AND
                    ? get_string('list_and', 'core_availability')
                    : get_string('list_or', 'core_availability'))
                    : '';

        $strid = $this->subcondition ? 'offer_subset_desc' : 'offer_set_desc';
        $intro = get_string($strid, 'enrol_wallet', [
            'discount' => $this->get_formatted_discount(),
            'op'       => $listintro,
        ]);

        $join = match(true) {
            \count($list) <= 1         => '',
            $this->op === self::OP_AND => get_string('operator_and'),
            default                    => get_string('operator_or'),
        };

        $finallist = [];

        for ($i = 0; $i < 2 * (\count($list)) - 1; $i++) {
            if ($i % 2 === 0) {
                $finallist[] = [
                    'offer' => $list[$i / 2],
                    'join' => false,
                ];
            } else {
                $finallist[] = ['join' => $join];
            }
        }
        $contextdata = [
            'issubset' => $this->subcondition,
            'intro'    => $intro,
            'list'     => $finallist,
            'join'     => $join,
        ];

        return $OUTPUT->render_from_template('enrol_wallet/offer_set_desc', $contextdata);
    }

    #[\Override()]
    public function is_hidden(): bool {
        if (parent::is_hidden()) {
            return true;
        }

        $and = $this->op === static::OP_AND;

        $hidden = !$and;

        foreach ($this->suboffers as $offer) {
            $hidden = match($and) {
                // Any one of sub offers is hidden while the operation is 'AND' it
                // means that the whole set is useless and cannot be validated
                // what ever the user do so the whole set should be hidden,
                // one hidden make all set hidden (we start with false).
                true => $hidden || $offer->is_hidden(),
                // Start with false because only one offer is visible make it
                // possible for the user to get the offer (we start with true).
                false => $hidden && $offer->is_hidden(),
            };
        }

        return $hidden;
    }

    #[\Override()]
    public static function key(): string {
        return 'set';
    }

    #[\Override()]
    public function validate_offer(): bool {
        foreach ($this->suboffers as $offer) {
            $valid = $offer->validate_offer();

            if ($valid && $this->op == self::OP_OR) {
                return true;
            }

            if (!$valid && $this->op == self::OP_AND) {
                return false;
            }
        }

        return $this->op == self::OP_AND;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offer_set', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(
        MoodleQuickForm $mform,
        int $i,
        int $courseid,
        ?stdClass $offer = null,
        ?callable $wrapper = null
    ): void {
        // Operation selector (AND / OR).
        $opname = static::fname('op', $i, $wrapper);
        $group[] = $mform->createElement('select', $opname, '', [
            self::OP_AND => get_string('listheader_multi_and', 'core_availability'),
            self::OP_OR  => get_string('listheader_multi_or', 'core_availability'),
        ]);
        $mform->setType($opname, PARAM_ALPHA);
        $mform->setDefault($opname, self::OP_AND);

        $inc = 0;

        if (!empty($offer->sub)) {
            $offer->sub = array_values((array)$offer->sub);
            $inc = \count($offer->sub);
        }

        $groupname = static::fname('', $i, $wrapper);
        $attributes = [
            'data-set-increment'   => $inc,
            'data-group-increment' => $i,
            'data-action'          => 'add-sub-offer',
            'data-group-name'      => $groupname,
        ];
        $offersoptions = offers::get_offer_options();
        $group[] = $mform->createElement(
            'select',
            "add_sub_offer_$i",
            get_string('suboffer', 'enrol_wallet'),
            $offersoptions,
            $attributes
        );

        $mform->addGroup($group, $groupname, appendName: false);

        // Suboffers container - dynamic content loaded by JS.
        $subdiv = html_writer::start_div('offer-set-container mt-3', [
            'data-action'          => 'sub-offer-placeholder',
            'data-group-increment' => $i,
            'data-group-name'      => $groupname,
        ]);
        $mform->addElement('html', $subdiv);

        foreach ($offer->sub ?? [] as $inc => $suboffer) {
            if (!$class = offers::get_offer_class_name($suboffer->type)) {
                continue;
            }

            $class::add_form_fragment($inc, $courseid, $mform, $suboffer, false, static::get_wrapper($i, $wrapper));
        }

        $mform->addElement('html', html_writer::end_div());
    }

    #[\Override()]
    public static function after_edit_form_definition(
        MoodleQuickForm $mform,
        stdClass $offer,
        int $i,
        ?callable $wrapper = null
    ): void {
        static::check_structure_validation($offer);

        // Set operation default.
        $mform->setDefault(static::fname('op', $i, $wrapper), $offer->op ?? static::OP_AND);
        $mform->setDefault(static::fname('discount', $i, $wrapper), $offer->discount ?? 0);

        // Recursively add existing suboffers.
        if (!empty($offer->sub)) {
            $offer->sub = array_values((array)$offer->sub);

            foreach ($offer->sub as $inc => $suboffer) {
                $class = offers::get_offer_class_name($suboffer->type);

                if ($class) {
                    // Use wrapper for nested naming.
                    $subwrapper = static::get_wrapper($i, $wrapper);
                    $class::after_edit_form_definition($mform, $suboffer, $inc, $subwrapper);
                }
            }
        }
    }

    #[\Override()]
    public static function get_wrapper(int $i, ?callable $wrapper = null): callable {
        return fn ($name) => static::fname("{$name}", $i, $wrapper);
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors, ?callable $wrapper = null): void {
        // Validate operation.
        if (empty($offer->op) || !\in_array($offer->op, [self::OP_AND, self::OP_OR])) {
            $errors[static::fname('op', $i, $wrapper)] = get_string('invalidoffersetop', 'enrol_wallet');
        }

        if (empty($offer->sub)) {
            return;
        }

        $offer->sub = array_values((array)$offer->sub);

        // Recursively validate suboffers.
        foreach (($offer->sub ?? []) as $inc => $suboffer) {
            $subofferclass = offers::get_offer_class_name($suboffer->type ?? '');

            if ($subofferclass) {
                $subwrapper = static::get_wrapper($i, $wrapper);
                $subofferclass::validate_submitted_offer($suboffer, $inc, $errors, $subwrapper);
            }
        }
    }

    #[\Override()]
    public static function clean_submitted_value(string $name, mixed &$value): void {
        if ($name === 'op') {
            $value = clean_param($value, PARAM_ALPHA);

            return;
        }

        if (strpos($name, 'offer_') !== 0) {
            parent::clean_submitted_value($name, $value);

            return;
        }

        [$k, $i, $type] = offers::analyze_element_key($name);

        if (!$class = offers::get_offer_class_name($type)) {
            return;
        }
        $class::clean_submitted_value($k, $value);
    }

    #[\Override()]
    public static function pre_save_submitted_data(array &$offers, int $i, string $name, mixed $value): void {
        if (!isset($offers[$i])) {
            $offers[$i] = new stdClass();
            $offers[$i]->type = static::key();
            $offers[$i]->sub = [];
        }

        if (strpos($name, 'offer_') !== 0) {
            parent::pre_save_submitted_data($offers, $i, $name, $value);

            return;
        }

        [$k, $inc, $type] = offers::analyze_element_key($name);

        if (!isset($offers[$i]->sub[$inc])) {
            $offers[$i]->sub[$inc] = new stdClass();
            $offers[$i]->sub[$inc]->type = $type;
        }
        $class = offers::get_offer_class_name($type);
        $class::pre_save_submitted_data($offers[$i]->sub, $inc, $k, $value);
    }

    /**
     * Mock an offer object of this type for testing.
     * @param  ?testing_data_generator $gen
     * @param  ?float                  $discount
     * @param  ?array                  $sub
     * @param  ?string                 $op
     * @return stdClass
     */
    public static function mock_offer(
        ?testing_data_generator $gen = null,
        ?float $discount = null,
        ?array $sub = null,
        ?string $op = null
    ): stdClass {
        global $DB;

        if (null === $gen) {
            $gen = phpunit_util::get_data_generator();
        }
        $offer = new stdClass();
        $offer->type = static::key();
        $offer->discount = $discount ?? random_int(1, 99);

        if ($sub === null) {
            $subsub = [];
            $subsub[] = time_offer::mock_offer($gen);
            $subsub[] = profile_field_offer::mock_offer($gen);

            foreach ($subsub as &$suboffer1) {
                $suboffer1->discount = null;
            }

            $sub = [];
            $sub[] = self::mock_offer($gen, null, $subsub);
            $sub[] = time_offer::mock_offer($gen);
            $sub[] = profile_field_offer::mock_offer($gen);

            foreach ($sub as &$suboffer2) {
                $suboffer2->discount = null;
            }
        }
        $offer->sub = $sub;

        $offer->op = $op ?? [self::OP_OR, self::OP_AND][rand(0, 1)];

        return $offer;
    }
}
