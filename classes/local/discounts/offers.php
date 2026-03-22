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
 * Enrol wallet offers.
 *
 * Contains classes and methods that handles offers rules.
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\discounts;

use core\exception\coding_exception;
use enrol_wallet\hook\extend_offer_types;
use enrol_wallet\local\config;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\timedate;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

use core_course_category;
use html_writer;
use MoodleQuickForm;

/**
 * Class offers.
 *
 * Contain all methods that handles offers rules, validation and it's edit
 * form elements.
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offers {
    /**
     * Offers codes
     * time - time based offers
     * geo - geolocation based offers
     * pf - profiled field data based offer
     * ce - another course enrolment based offer
     * nc - number of enrolment in same category
     * otherc - number of courses in another category.
     */

    /**
     * Time based type.
     * @var string
     */
    public const TIME = 'time';

    /**
     * Profile field based offer.
     * @var string
     */
    public const PROFILE_FIELD = 'pf';

    /**
     * Geo location based offer.
     * @var string
     */
    public const GEO_LOCATION = 'geo';

    /**
     * Other category enrollment count offer.
     * @var string
     */
    public const OTHER_CATEGORY_COURSES = 'otherc';

    /**
     * Other course enrolment required.
     * @var string
     */
    public const COURSES_ENROL_SAME_CAT = 'ce';

    /**
     * Number of courses in same category base offer.
     * @var string
     */
    public const COURSE_ENROL_COUNT = 'nc';

    /**
     * The raw offers data for the instance.
     * @var array
     * */
    private array $offers;

    /**
     * The enrol wallet instance.
     * @var \stdClass
     */
    public stdClass $instance;

    /**
     * The user id.
     * @var int
     */
    protected int $userid;

    /**
     * All calculated discounts.
     * @var float[]
     */
    protected array $discounts = [];

    /**
     * create an instance of offer helper class to get, calculate and validate the
     * offers rules.
     * @param \stdClass $instance
     * @param int       $userid
     */
    public function __construct(stdClass $instance, int $userid = 0) {
        global $USER;
        $this->instance = $instance;

        $this->userid = match(true) {
            !empty($userid) => $userid,
            default         => $USER->id,
        };

        $this->offers = match(true) {
            !empty($instance->customtext3) => (array)json_decode($instance->customtext3),
            default                        => []
        };
    }

    /**
     * Magic call.
     * It's used for testing for now. Todo: remove in the future.
     * @param string $method
     * @param array $args
     * @throws coding_exception
     * @return bool
     */
    public function __call($method, $args) {
        if (preg_match('/validate_([\w]+)_offer/', $method, $matches)) {
            $class = $this->get_offer_item($args[0]);

            return $class->validate_offer();
        }

        throw new coding_exception("The method $method not exist.");
    }

    /**
     * Get list of all available offer item classes.
     * @return array
     */
    public static function get_offer_classes(): array {
        static $singleton;

        if (isset($singleton)) {
            return $singleton;
        }
        $classes = [
            time_offer::class,
            course_enrol_count_offer::class,
            other_category_courses_offer::class,
            courses_enrol_same_cat_offer::class,
            profile_field_offer::class,
            geo_location_offer::class,
        ];
        $hook = new extend_offer_types();
        $hook->add_classes($classes);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        $singleton = $hook->get_classes();

        return $singleton;
    }

    /**
     * Get the offer item class name.
     * @param  string      $type
     * @return ?offer_item
     */
    public static function get_offer_class_name(string $type): ?string {
        $classes = self::get_offer_classes();

        if (!\array_key_exists($type, $classes)) {
            return null;
        }

        return $classes[$type];
    }

    /**
     * Get instance of offer item.
     * @param  stdClass        $offer
     * @return offer_item|null
     */
    public function get_offer_item(stdClass $offer): ?offer_item {
        $class = $this->get_offer_class_name($offer->type);

        if (!$class) {
            return null;
        }

        return new $class($offer, $this->instance->courseid, $this->userid);
    }

    /**
     * Get the raw discount rules,.
     * @return array of objects
     */
    public function get_raw_offers(): array {
        return $this->offers;
    }

    /**
     * Return array of offers rules in a certain instance with description.
     * @param  bool  $availableonly
     * @return array
     */
    public function get_detailed_offers(bool $availableonly = false): array {
        $descriptions = [];

        if (empty($this->offers)) {
            return $descriptions;
        }

        foreach ($this->offers as $key => $offer) {
            $class = $this->get_offer_item($offer);

            if (!$class) {
                continue;
            }

            if (!$discription = $class->get_description($availableonly)) {
                continue;
            }
            $descriptions[$key] = $discription;
        }

        return $descriptions;
    }

    /**
     * Get a formatted description for this instance to be displayed.
     * @param  bool   $availableonly
     * @return string
     */
    public function format_offers_descriptions(bool $availableonly = false): string {
        global $OUTPUT;
        $output       = '';
        $descriptions = $this->get_detailed_offers($availableonly);

        if (empty($descriptions)) {
            return $output;
        }

        $output .= $OUTPUT->heading(get_string('offers', 'enrol_wallet'), 5);
        $output .= html_writer::start_tag('ul');

        foreach ($descriptions as $key => $description) {
            if (!\array_key_exists($key, (array)$this->offers)) {
                continue;
            }
            $output .= html_writer::tag('ul', $description);
        }
        $output .= html_writer::end_tag('ul');

        return $output;
    }

    /**
     * Return the max discount at all no matter if it is valid
     * for the user or not.
     * @return float
     */
    public function get_max_discount(): float {
        if (empty($this->offers)) {
            return 0;
        }

        $discounts = [];

        foreach ($this->offers as $obj) {
            $discounts[] = (float)$obj->discount;
        }

        $behavior = (int)config::instance()->discount_behavior;

        if ($behavior === instance::B_MAX) {
            return max($discounts);
        } else if ($behavior === instance::B_SUM) {
            return min(array_sum($discounts), 100);
        }

        $max = 0;
        \core_collator::asort($discounts, \core_collator::SORT_NUMERIC);
        $discounts = array_reverse($discounts);

        foreach ($discounts as $d) {
            $d /= 100;
            $max = 1 - (1 - $max) * (1 - $d);
        }

        return min(1, $max) * 100;
    }

    /**
     * Return the max discount valid for the passed user.
     * @return float
     */
    public function get_max_valid_discount(): float {
        if (empty($this->discounts)) {
            $this->discounts = $this->get_available_discounts();
        }

        if (empty($this->discounts)) {
            return 0;
        }

        return max($this->discounts);
    }

    /**
     * Get the over all discount after check all offers conditions.
     * @return float
     */
    public function get_sum_discounts(): float {
        if (empty($this->discounts)) {
            $this->discounts = $this->get_available_discounts();

            if (empty($this->discounts)) {
                return 0;
            }
        }
        $sum = 0;

        foreach ($this->discounts as $d) {
            $sum += $d;
        }

        return min($sum, 100);
    }

    /**
     * Return array with available valid discounts for the passed user.
     * @return float[]
     */
    public function get_available_discounts(): array {
        $discounts = [];

        if (empty($this->offers)) {
            return $discounts;
        } else if (!empty($this->discounts)) {
            return $this->discounts;
        }

        foreach ($this->offers as $key => $offer) {
            $class = $this->get_offer_item($offer);

            if (!$class) {
                continue;
            }

            if (!$class->validate_offer()) {
                continue;
            }
            $discounts[$key] = $class->get_discount();
        }
        $this->discounts = $discounts;

        return $this->discounts;
    }

    /**
     * Get offers options.
     * @return array[string]
     */
    private static function get_offer_options(): array {
        $classes = self::get_offer_classes();

        if (empty($classes)) {
            return [];
        }

        $options = [
            '' => get_string('offers_please_select', 'enrol_wallet'),
        ];

        foreach ($classes as $key => $class) {
            $options[$key] = $class::get_visible_name();
        }

        return $options;
    }

    /**
     * Add forms elements for offers.
     * @param MoodleQuickForm $mform
     */
    public function get_form_offers_elements(MoodleQuickForm &$mform): void {
        global $PAGE, $CFG;
        $mform->addElement('header', 'wallet_offers', get_string('offers', 'enrol_wallet'));

        $options = self::get_offer_options();
        // Not implanted yet.
        unset($options['geo']);

        if (!file_exists($CFG->dirroot . '/availability/condition/profile/classes/condition.php')) {
            // If some how the availability_profile not exist as we used it to assist in validation.
            unset($options['pf']);
        }
        $mform->addElement('select', 'add_offer', get_string('add'), $options);

        $courseid = $this->instance->courseid;

        $offers = $this->get_stored_offers();

        $inc = 0;

        if (!empty($offers)) {
            foreach ($offers as $i => $offer) {
                self::add_form_fragment($offer->type, $i, $courseid, $mform);
                $inc  = max($inc, $i);
                $type = $offer->type;

                if ($class = self::get_offer_class_name($type)) {
                    $class::after_edit_form_definition($mform, $offer, $i);
                }
            }
        }

        $PAGE->requires->js_call_amd('enrol_wallet/offers', 'init', ['cid' => $courseid, 'inc' => $inc]);
    }

    /**
     * Add heading to the form fragment contain the offer type name.
     * @param  MoodleQuickForm $mform
     * @param  string          $type
     * @return void
     */
    private static function add_offer_form_heading(MoodleQuickForm $mform, string $type): void {
        global $OUTPUT;
        $types = self::get_offer_options();
        unset($types['']);

        if (!\array_key_exists($type, $types)) {
            debugging("The offer type $type not exist.", DEBUG_DEVELOPER);

            return;
        }
        $name    = $types[$type];
        $heading = $OUTPUT->heading($name, 5);
        $mform->addElement('html', $heading);
    }

    /**
     * Render a form fragment for certain type of offers.
     * used by ajax to inject the elements in the user side.
     *
     * time - time based offers
     * geo - geolocation based offers
     * pf - profiled field data based offer
     * ce - another course enrolment based offer
     * nc - number of enrolment in same category
     * otherc - number of courses in another category
     * @param  string $type     time - geo - pf - ce - nc
     * @param  int    $i        increment number
     * @param  int    $courseid
     * @return string
     */
    public static function render_form_fragment(string $type, int $i, int $courseid): string {
        $mform = new MoodleQuickForm('tempName', 'get', '');

        $class = self::get_offer_class_name($type);

        if (!$class) {
            return '';
        }
        self::add_offer_form_heading($mform, $type);

        $class::add_form_element($mform, $i, $courseid);

        $mform->addElement('text', 'offer_' . $type . '_discount_' . $i, get_string('discount', 'enrol_wallet'));
        $mform->setType('offer_' . $type . '_discount_' . $i, PARAM_FLOAT);

        $mform->addElement('button', 'offer_delete_' . $i, get_string('delete'), [
            'data-action-delete' => $i,
            'data-action'        => 'deleteoffer',
        ]);
        ob_start();
        $mform->display();
        $out = ob_get_clean();

        // Remove the <form> tags from the form output.
        $out   = preg_replace('/<form[^>]*>|<\/form>/', '', $out);
        $style = 'border: 3px groove gray;'
               . 'border-radius: 15px;'
               . 'padding: 5px;';
        $out = html_writer::div($out, '', ['id' => 'offer_group_' . $i, 'style' => $style]);

        return $out;
    }

    /**
     * Add a form fragment directly for certain type of offers.
     * Used in server side directly.
     *
     * time - time based offers
     * geo - geolocation based offers
     * pf - profiled field data based offer
     * ce - another course enrolment based offer
     * nc - number of enrolment in same category
     * otherc - number of courses in another category
     * @param string          $type     time - geo - pf - ce - nc
     * @param int             $i        increment number
     * @param int             $courseid
     * @param MoodleQuickForm $mform
     */
    public static function add_form_fragment(string $type, int $i, int $courseid, MoodleQuickForm $mform): void {
        $class = self::get_offer_class_name($type);

        if (!$class) {
            return;
        }
        $style = 'border: 3px groove gray;'
               . 'border-radius: 15px;'
               . 'padding: 5px;';
        $out = html_writer::start_div('', ['id' => 'offer_group_' . $i, 'style' => $style]);
        $mform->addElement('html', $out);

        self::add_offer_form_heading($mform, $type);

        $class::add_form_element($mform, $i, $courseid);

        $name = self::fname($type, 'discount', $i);
        $mform->addElement('text', $name, get_string('discount', 'enrol_wallet'));
        $mform->setType($name, PARAM_FLOAT);

        $attributes = [
            'data-action-delete' => $i,
            'data-action'        => 'deleteoffer',
        ];
        $mform->addElement('button', 'offer_delete_' . $i, get_string('delete'), $attributes);
        $mform->addElement('html', html_writer::end_div());
    }

    /**
     * Format an offer form element name.
     * @param string $type the type of offer
     * @param string $key  the key of the element
     * @param int    $inc  increment
     */
    public static function fname(string $type, string $key, int $inc): string {
        $name = "offer_$type";

        if (!empty($key)) {
            $name .= "_$key";
        }

        return "{$name}_{$inc}";
    }

    /**
     * parse the submitted data in form of json to be saved
     * in the data base.
     * @param \stdClass|array $data
     */
    public static function parse_data(stdClass|array &$data): void {
        $isarray = \is_array($data) ? true : false;

        $offers = self::get_offers_from_submitted_data($data, $hasoffersdata);

        if (!$hasoffersdata
            && (($isarray && !isset($data['customtext3']))
            || (!$isarray && !isset($data->add_offer)))) {
            return; // No offers parsed.
        }

        if ($isarray) {
            $data['customtext3'] = json_encode($offers);
            unset($data['add_offer']);
        } else {
            $data->customtext3 = json_encode($offers);
            unset($data->add_offer);
        }
    }

    /**
     * Validate submitted offers values from the form.
     *
     * @param array $data the submitted data
     * @return array[string]
     */
    public static function validate(array $data): array {
        $errors = [];
        $offers = self::get_offers_from_submitted_data($data);

        foreach ($offers as $i => $offer) {
            $discount = $offer->discount ?? null;
            $type     = $offer->type;

            if (empty($discount)
                || !is_numeric($discount)
                || $discount < 0 || $discount > 100
            ) {
                $n          = self::fname($type, 'discount', $i);
                $errors[$n] = get_string('offers_error_discountvalue', 'enrol_wallet');
            }

            $class = self::get_offer_class_name($type);

            if (!$class) {
                $errors[self::fname($type, 'discount', $i)] = get_string('offer_type_not_available', 'enrol_wallet', $type);
                continue;
            }
            $class::validate_submitted_offer($offer, $i, $errors);
        }

        return $errors;
    }

    /**
     * Extract the offers from the submitted form data.
     * @param \stdClass|array $data
     * @param ?bool           $hasoffersdata
     * @return array[\stdClass]
     */
    protected static function get_offers_from_submitted_data(\stdClass|array $data, ?bool &$hasoffersdata = null): array {
        $data   = (object)(array)$data;
        $offers = [];

        $hasoffersdata = false;

        // Remove all keys not starting with 'offer_' because they may be belong to deleted offer.
        foreach ($data as $key => $value) {
            if (strpos($key, 'offer_') === 0) {
                unset($data->$key);
                $hasoffersdata = true;
            }
        }

        foreach ($_POST as $key => $value) {
            if (isset($data->$key)) {
                // Already included and cleaned from the submitted form data.
                continue;
            }

            if (strpos($key, 'offer_') !== 0) {
                // Not ours.
                continue;
            }

            $hasoffersdata = true;
            // Will be cleaned later after determine its type.
            $data->$key = $value;
        }

        foreach ($data as $key => $value) {
            if (strpos($key, 'offer_') !== 0) {
                continue;
            }
            // ... offer_<type>_<key>_<increment>.
            $chars = explode('_', $key);

            $i    = (int)array_pop($chars);
            $type = $chars[1];

            if (!isset($offers[$i])) {
                $offers[$i]       = new stdClass();
                $offers[$i]->type = $type;
            }
            $k = $chars[2];

            // Cleaning the values.
            $class = self::get_offer_class_name($type);

            if (!$class) {
                continue;
            }
            $class::clean_submitted_value($k, $value);
            $class::pre_save_submitted_data($offers, $i, $k, $value);
        }

        return array_values($offers);
    }

    /**
     * Get stored offers from database, submitted data or temporary
     * stored ones from session.
     * @return array
     */
    private function get_stored_offers(): array {
        $return = [];

        $offers = (array)self::get_offers_from_submitted_data([]);

        if (!empty($offers)) {
            return $offers;
        }

        if (!empty($this->offers) && count((array)$this->offers) > 0) {
            $offers = (array)$this->offers;

            if (!empty($offers)) {
                $return = $offers;
            }
        }

        if (!empty($this->instance->customtext3)) {
            $offers = (array)json_decode($this->instance->customtext3);

            if (!empty($offers)) {
                $return       = $return + (array)$offers;
                $this->offers = $return;
            }
        }

        return $return;
    }

    /**
     * Get all courses with offers and add it to course object $course->offers as array keyed with
     * instance id and each contain array with offers details.
     * @param  int   $categoryid
     * @return array
     */
    public static function get_courses_with_offers($categoryid = 0): array {
        global $DB;
        $notempty = $DB->sql_isnotempty('enrol', 'e.customtext3', true, true);

        $costfield = $DB->sql_cast_char2real('e.cost');
        $notemptycost = '(' . $DB->sql_isnotempty('enrol', 'e.cost', true, false);
        $notemptycost .= " AND e.cost IS NOT NULL)";
    
        $sql = "SELECT e.id as instanceid, c.*, e.customtext3, e.cost
                From {course} c
                JOIN {enrol} e ON e.courseid = c.id
                WHERE e.status = :stat
                  AND (e.enrolstartdate < :time1 OR e.enrolstartdate = 0)
                  AND (e.enrolenddate > :time2 OR e.enrolenddate = 0)
                  AND e.enrol = :wallet
                  AND c.visible = 1
                  AND (($notemptycost AND $costfield = :zero) OR $notempty)";
        $order = " ORDER BY c.timecreated DESC";
        $params = [
            'stat'   => ENROL_INSTANCE_ENABLED,
            'time1'  => timedate::time(),
            'time2'  => timedate::time(),
            'wallet' => 'wallet',
            'zero'   => $DB->sql_cast_char2real('0'),
        ];

        if (!empty($categoryid)) {
            $category = core_course_category::get($categoryid, IGNORE_MISSING);
            $catids   = [];

            if ($category) {
                $catids = $category->get_all_children_ids();
            }
            $catids[] = $categoryid;

            [$in, $inparams] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
            $sql .= " AND c.category $in";
            $params = $params + $inparams;
        }
        $courses = $DB->get_records_sql($sql . $order, $params);

        $final = [];

        foreach ($courses as $instanceid => $course) {
            $instance              = new stdClass();
            $instance->id          = $instanceid;
            $instance->courseid    = $course->id;
            $instance->customtext3 = $course->customtext3;

            $zero  = is_number($course->cost) && $course->cost == 0;
            $class = new static($instance);

            $rawoffers = (array)@$class->get_raw_offers();

            foreach ($rawoffers as $k => $offer) {
                if ($offer->type == self::TIME) {
                    if (timedate::time() > $offer->to) { // Expired offer.
                        unset($rawoffers[$k]);
                    }
                }
            }

            if (empty($rawoffers) && !$zero) {
                continue;
            }

            if (!isset($final[$course->id])) {
                $final[$course->id]           = $course;
                $final[$course->id]->free     = $zero;
                $final[$course->id]->hasoffer = !$zero;
                $final[$course->id]->offers   = [];
                unset($final[$course->id]->instanceid, $final[$course->id]->customtext3, $final[$course->id]->cost);
            }
            $final[$course->id]->offers[$instanceid] = $class->format_offers_descriptions(true);
        }

        return $final;
    }
}
