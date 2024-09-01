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

namespace enrol_wallet\util;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

use core_course_category;
use context_system;
use MoodleQuickForm;
use html_writer;
use context_course;
use moodle_url;

/**
 * Class offers
 *
 * Contain all methods that handles offers rules, validation and it's edit
 * form elements.
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offers {
    /** @var string Operator: field contains value */
    const PFOP_CONTAINS = 'contains';

    /** @var string Operator: field does not contain value */
    const PFOP_DOES_NOT_CONTAIN = 'doesnotcontain';

    /** @var string Operator: field equals value */
    const PFOP_IS_EQUAL_TO = 'isequalto';

    /** @var string Operator: field starts with value */
    const PFOP_STARTS_WITH = 'startswith';

    /** @var string Operator: field ends with value */
    const PFOP_ENDS_WITH = 'endswith';

    /** @var string Operator: field is empty */
    const PFOP_IS_EMPTY = 'isempty';

    /** @var string Operator: field is not empty */
    const PFOP_IS_NOT_EMPTY = 'isnotempty';

    /**
     * Offers codes
     * time - time based offers
     * geo - geolocation based offers
     * pf - profiled field data based offer
     * ce - another course enrolment based offer
     * nc - number of enrolment in same category
     * otherc - number of courses in another category
     */

    /**
     * Time based type
     * @var string
     */
    protected const TIME = 'time';
    /**
     * Profile field based offer
     * @var string
     */
    protected const PROFILE_FIELD = 'pf';
    /**
     * Geo location based offer
     * @var string
     */
    protected const GEO_LOCATION = 'geo';
    /**
     * Other category enrollment count offer
     * @var string
     */
    protected const OTHER_CATEGORY_COURSES = 'otherc';
    /**
     * Other course enrolment required
     * @var string
     */
    protected const COURSES_ENROL_SAME_CAT = 'ce';
    /**
     * Number of courses in same category base offer
     * @var string
     */
    protected const COURSE_ENROL_COUNT = 'nc';

    /** @var \stdClass|array */
    private $offers;
    /**
     * The enrol wallet instance
     * @var \stdClass
     */
    public $instance;
    /**
     * The user id
     * @var int
     */
    protected $userid;
    /**
     * All calculated discounts.
     * @var array
     */
    protected $discounts = [];

    /**
     * create an instance of offer helper class to get, calculate and validate the
     * offers rules.
     * @param \stdClass $instance
     * @param int $userid
     */
    public function __construct($instance, $userid = 0) {
        $this->instance = $instance;
        if (!empty($userid)) {
            $this->userid = $userid;
        } else {
            global $USER;
            $this->userid = $USER->id;
        }

        if (!empty($instance->customtext3)) {
            $this->offers = (array)json_decode($instance->customtext3);
        } else {
            $this->offers = [];
        }
    }

    /**
     * Get the raw discount rules,
     * @return array|\stdClass of objects
     */
    public function get_raw_offers() {
        return $this->offers;
    }

    /**
     * Return array of offers rules in a certain instance with description.
     * @return array
     */
    public function get_detailed_offers() {
        $descriptions = [];
        if (empty($this->offers)) {
            return $descriptions;
        }

        foreach ($this->offers as $key => $offer) {
            $formatteddiscount = format_float($offer->discount, 2);
            switch($offer->type) {
                case self::TIME:
                    $a = [
                        'to'       => userdate($offer->to),
                        'from'     => userdate($offer->from),
                        'discount' => $formatteddiscount,
                    ];
                    $descriptions[$key] = get_string('offers_time_desc', 'enrol_wallet', $a);
                    break;
                case self::COURSE_ENROL_COUNT:
                    $course = get_course($this->instance->courseid);
                    $category = core_course_category::get($course->category, IGNORE_MISSING, false, $this->userid);
                    if (!$category) {
                        continue 2;
                    }
                    $a = [
                        'catname'  => $category->get_nested_name(),
                        'number'   => $offer->number ?? $offer->courses,
                        'discount' => $formatteddiscount,
                    ];
                    $descriptions[$key] = get_string('offers_nc_desc', 'enrol_wallet', $a);
                    break;
                case self::OTHER_CATEGORY_COURSES:
                    $category = core_course_category::get($offer->cat, IGNORE_MISSING, false, $this->userid);
                    if (!$category) {
                        continue 2;
                    }
                    $a = [
                        'catname'  => $category->get_nested_name(),
                        'number'   => $offer->number ?? $offer->courses,
                        'discount' => $formatteddiscount,
                    ];
                    $descriptions[$key] = get_string('offers_nc_desc', 'enrol_wallet', $a);
                    break;
                case self::COURSES_ENROL_SAME_CAT:
                    $courseslist = html_writer::start_tag('ul');
                    foreach ($offer->courses as $id) {
                        $course = get_course($id);
                        $context = context_course::instance($id);
                        $enrolled = is_enrolled($context, $this->userid);
                        $coursename = format_string($course->fullname, true, ['context' => $context]);
                        $courseurl = new moodle_url('/course/view.php', ['id' => $id]);
                        $courselink = html_writer::link($courseurl, $coursename);
                        $courseslist .= html_writer::tag('li', $courselink);
                    }
                    $courseslist .= html_writer::end_tag('ul');
                    $a = [
                        'courses' => $courseslist,
                        'discount' => $formatteddiscount,
                        'condition' => $offer->condition == 'any' ? get_string('any') : get_string('all'),
                    ];
                    $descriptions[$key] = get_string('offers_ce_desc', 'enrol_wallet', $a);
                    break;
                case self::PROFILE_FIELD:
                    if (isset($offer->sf)) {
                        $fieldname = get_string($offer->sf);
                    } else {
                        global $DB;
                        $name = $DB->get_field('user_info_field', 'name', ['shortname' => $offer->cf]);
                        $fieldname = format_string($name);
                    }
                    $a = [
                        'op' => get_string('offers_pfop_'.$offer->op, 'enrol_wallet'),
                        'discount' => $offer->discount,
                        'field' => $fieldname,
                        'value' => $offer->value,
                    ];
                    $descriptions[$key] = get_string('offers_pf_desc', 'enrol_wallet', $a);
                    break;
                case self::GEO_LOCATION:
                default:
            }
        }
        return $descriptions;
    }

    /**
     * Get a formatted description for this instance to be displayed.
     * @return string
     */
    public function format_offers_descriptions() {
        global $OUTPUT;
        $output = '';
        $descriptions = $this->get_detailed_offers();
        if (empty($descriptions)) {
            return $output;
        }
        $output .= $OUTPUT->heading(get_string('offers', 'enrol_wallet'), 5);
        $output .= html_writer::start_tag('ul');
        foreach ($descriptions as $key => $description) {
            if (!array_key_exists($key, (array)$this->offers)) {
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
    public function get_max_discount() {
        if (empty($this->offers)) {
            return 0;
        }

        $discounts = [];
        foreach ($this->offers as $obj) {
            $discounts[] = (float)$obj->discount;
        }

        $behavior = (int)get_config('enrol_wallet', 'discount_behavior');
        if ($behavior === instance::B_MAX) {
            return max($discounts);
        } else if ($behavior === instance::B_SUM) {
            return min(array_sum($discounts), 100);
        }

        $max = 0;
        \core_collator::asort($discounts, \core_collator::SORT_NUMERIC);
        $discounts = array_reverse($discounts);

        foreach ($discounts as $d) {
            $d = $d / 100;
            $max = 1 - (1 - $max) * (1 - $d);
        }

        return min(1, $max) * 100;
    }

    /**
     * Return the max discount valid for the passed user.
     * @return float
     */
    public function get_max_valid_discount() {
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
    public function get_sum_discounts() {
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
     * @return array[float]
     */
    public function get_available_discounts() {
        $discounts = [];
        if (empty($this->offers)) {
            return $discounts;
        } else if (!empty($this->discounts)) {
            return $this->discounts;
        }

        foreach ($this->offers as $key => $offer) {
            switch ($offer->type) {
                case self::TIME:
                    if ($this->validate_time_offer($offer)) {
                        $discounts[$key] = $offer->discount;
                    }
                    break;
                case self::COURSE_ENROL_COUNT:
                    if ($this->validate_course_enrol_count($offer)) {
                        $discounts[$key] = $offer->discount;
                    }
                    break;
                case self::OTHER_CATEGORY_COURSES:
                    if ($this->validate_category_enrol_count($offer, $offer->cat)) {
                        $discounts[$key] = $offer->discount;
                    }
                    break;
                case self::COURSES_ENROL_SAME_CAT:
                    if ($this->validate_courses_enrollments_same_cat($offer)) {
                        $discounts[$key] = $offer->discount;
                    }
                    break;
                case self::PROFILE_FIELD:
                    if ($this->validate_profile_field_offer($offer)) {
                        $discounts[$key] = $offer->discount;
                    }
                    break;
                case self::GEO_LOCATION:
                default:
            }
        }
        $this->discounts = $discounts;
        return $discounts;
    }

    /**
     * Validate profile field offers using availability_profile plugin
     * @param \stdClass $offer
     * @return bool
     */
    private function validate_profile_field_offer($offer) {
        global $CFG;
        if (file_exists($CFG->dirroot.'/availability/condition/profile/classes/condition.php')) {
            require_once($CFG->dirroot.'/availability/condition/profile/classes/condition.php');
            $structure = (object) [
                'op' => $offer->op,
                'v'  => $offer->value ?? null,
            ];
            if (isset($offer->sf)) {
                $structure->sf = $offer->sf;
            } else {
                $structure->cf = $offer->cf;
            }
            switch ($offer->op) {
                case self::PFOP_IS_EMPTY:
                case self::PFOP_IS_NOT_EMPTY:
                    unset($structure->v);
                    break;
                default;
            }
            try {
                $av = new \availability_profile\condition($structure);

                $fake = new fake_info();
                if ($av->is_available(false, $fake, false, $this->userid)) {
                    return true;
                }
            } catch (\coding_exception $e) {
                debugging($e->getMessage(), DEBUG_DEVELOPER);
                return false;
            }
        }
        return false;
    }

    /**
     * Validate the enrolment of the user in a list of courses passed in $offer object
     * @param \stdClass $offer
     * @return bool
     */
    private function validate_courses_enrollments_same_cat($offer) {
        global $DB;
        $ids = $offer->courses;
        $condition = $offer->condition;
        list($in, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $sql = "SELECT ue.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid $in
                   AND ue.userid = :userid";
        $params = array_merge($inparams, ['userid' => $this->userid]);
        $records = $DB->get_records_sql($sql, $params);
        if (empty($records)) {
            return false;
        } else if ($condition == 'any') {
            return true;
        } else if ($condition == 'all' && count($records) >= count($ids)) {
            return true;
        }

        return false;
    }
    /**
     * Validate the number of required courses for enrolment in a certain category.
     * @param \stdClass $offer
     * @param int $catid
     * @return bool
     */
    private function validate_category_enrol_count($offer, $catid) {
        global $DB;
        $courseid = $this->instance->courseid;
        $number = $offer->number ?? $offer->courses;
        $number = (int)$number;
        if (empty($number)) {
            return false;
        }
        $ids = [$catid];
        $category = core_course_category::get($catid, IGNORE_MISSING, false, $this->userid);
        if (!$category) {
            return false;
        }

        $ids = array_merge($ids, $category->get_all_children_ids());

        list($in, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $sql = "SELECT ue.id
                FROM {user_enrolments} ue
                JOIN {enrol} e ON ue.enrolid = e.id
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category $in
                  AND c.id != :thiscourse
                  AND ue.userid = :userid";
        $params = ['thiscourse' => $courseid, 'userid' => $this->userid];
        $params = array_merge($inparams, $params);

        $records = $DB->get_records_sql($sql, $params, 0, $number + 1);
        if (count($records) >= $number) {
            return true;
        }

        return false;
    }
    /**
     * Check if the user enrolled in the required number of course in this category
     * @param \stdClass $offer
     * @return bool
     */
    private function validate_course_enrol_count($offer) {
        global $DB;
        $courseid = $this->instance->courseid;
        $catid = $DB->get_field('course', 'category', ['id' => $courseid]);
        if (!$catid) {
            return false;
        }
        return $this->validate_category_enrol_count($offer, $catid);
    }
    /**
     * Check if the time based offer is available to this user.
     * @param \stdClass $offer
     * @return bool
     */
    private function validate_time_offer($offer) {
        if (time() < $offer->to && time() > $offer->from) {
            return true;
        }
        return false;
    }
    /**
     * Get offers options.
     * @return array[string]
     */
    private static function get_offer_options() {
        return [
            ''                           => get_string('offers_please_select', 'enrol_wallet'),
            self::TIME                   => get_string('offers_time_based', 'enrol_wallet'),
            self::PROFILE_FIELD          => get_string('offers_profile_field_based', 'enrol_wallet'),
            self::COURSES_ENROL_SAME_CAT => get_string('offers_course_enrol_based', 'enrol_wallet'),
            self::COURSE_ENROL_COUNT     => get_string('offers_number_courses_base', 'enrol_wallet'),
            self::OTHER_CATEGORY_COURSES => get_string('offers_other_category_courses_based', 'enrol_wallet'),
            self::GEO_LOCATION           => get_string('offers_location_based', 'enrol_wallet'),
        ];
    }
    /**
     * Add forms elements for offers.
     * @param MoodleQuickForm $mform
     */
    public function get_form_offers_elements(&$mform) {
        global $PAGE, $CFG;
        $mform->addElement('header', 'wallet_offers', get_string('offers', 'enrol_wallet'));

        $options = self::get_offer_options();
        // Not implanted yet.
        unset($options['geo']);
        if (!file_exists($CFG->dirroot.'/availability/condition/profile/classes/condition.php')) {
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
                $inc = max($inc, $i);
                $type = $offer->type;
                foreach ($offer as $key => $value) {
                    if ($key == 'type') {
                        continue;
                    }
                    $mform->setDefault(self::fname($type, $key, $i), $value);
                }
            }
        }

        $PAGE->requires->js_call_amd('enrol_wallet/offers', 'init', ['cid' => $courseid, 'inc' => $inc]);
    }

    /**
     * Add heading to the form fragment contain the offer type name.
     * @param MoodleQuickForm $mform
     * @param string $type
     * @return void
     */
    private static function add_offer_form_heading($mform, $type) {
        global $OUTPUT;
        $name = self::get_offer_options()[$type];
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
     * @param string $type time - geo - pf - ce - nc
     * @param int $i increment number
     * @param int $courseid
     * @return string
     */
    public static function render_form_fragment($type, $i, $courseid) {

        $mform = new MoodleQuickForm('tempName', 'get', '');

        self::add_offer_form_heading($mform, $type);

        $function = 'add_elements_for_'.$type;
        self::$function($mform, $i, $courseid);

        $mform->addElement('text', 'offer_'.$type.'_discount_'.$i, get_string('discount', 'enrol_wallet'));
        $mform->setType('offer_'.$type.'_discount_'.$i, PARAM_FLOAT);

        $mform->addElement('button', 'offer_delete_'.$i, get_string('delete'), ['data-action-delete' => $i]);
        ob_start();
        $mform->display();
        $out = ob_get_clean();

        // Remove the <form> tags from the form output.
        $out = preg_replace('/<form[^>]*>|<\/form>/', '', $out);
        $style = "border: 3px groove gray;"
               . "border-radius: 15px;"
               . "padding: 5px;";
        $out = html_writer::div($out, '', ['id' => 'offer_group_'.$i, 'style' => $style]);
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
     * @param string $type time - geo - pf - ce - nc
     * @param int $i increment number
     * @param int $courseid
     * @param MoodleQuickForm $mform
     */
    public static function add_form_fragment($type, $i, $courseid, $mform) {
        $style = "border: 3px groove gray;"
               . "border-radius: 15px;"
               . "padding: 5px;";
        $out = html_writer::start_div('', ['id' => 'offer_group_'.$i, 'style' => $style]);
        $mform->addElement('html', $out);

        self::add_offer_form_heading($mform, $type);

        $function = 'add_elements_for_'.$type;
        self::$function($mform, $i, $courseid);

        $name = self::fname($type, 'discount', $i);
        $mform->addElement('text', $name, get_string('discount', 'enrol_wallet'));
        $mform->setType($name, PARAM_FLOAT);

        $mform->addElement('button', 'offer_delete_'.$i, get_string('delete'), ['data-action-delete' => $i]);
        $mform->addElement('html', html_writer::end_div());
    }

    /**
     * Format an offer form element name.
     * @param string $type the type of offer
     * @param string $key the key of the element
     * @param int $inc increment
     */
    private static function fname($type, $key, $inc) {
        $name = "offer_$type";
        if (!empty($key)) {
            $name .= "_$key";
        }
        return $name."_$inc";
    }

    /**
     * Add elements for time based offers
     * @param MoodleQuickForm $mform
     * @param int $i
     * @param int $courseid
     * @return void
     */
    protected static function add_elements_for_time(&$mform, $i, $courseid) {
        $mform->addElement('date_time_selector', 'offer_time_from_'.$i, get_string('from'));
        $mform->addElement('date_time_selector', 'offer_time_to_'.$i, get_string('to'));
    }

    /**
     * Add elements for offers according to another category enrollments.
     *
     * @param MoodleQuickForm $mform
     * @param int $inc increment
     * @param int $courseid
     * @return void
     */
    protected static function add_elements_for_otherc(&$mform, $inc, $courseid) {
        $thiscourse = get_course($courseid);
        $cetegories = core_course_category::get_all();
        $options = [];
        $max = 0;
        foreach ($cetegories as $category) {
            if ($category->id == $thiscourse->category) {
                continue;
            }
            $count = $category->get_courses_count(['recursive' => true]);
            if (empty($count)) {
                continue;
            }
            $options[$category->id] = $category->get_nested_name(false) . " ($count)";
            $max = max($max, $count);
        }
        $group = [];
        $group[] = $mform->createElement('select', 'offer_otherc_cat_'.$inc, get_string('categories'), $options);
        $options = ['' => get_string('choosedots')];
        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = $i;
        }
        $group[] = $mform->createElement('select', 'offer_otherc_courses_'.$inc, get_string('courses'), $options);
        $label = get_string('offers_other_category_courses_based', 'enrol_wallet');
        $mform->addGroup($group, 'offer_otherc_'.$inc, $label, null, false);
    }

    /**
     * Add elements for number of courses in same category offer.
     * @param MoodleQuickForm $mform
     * @param int $inc increment
     * @param int $courseid
     * @return void
     */
    protected static function add_elements_for_nc(&$mform, $inc, $courseid) {
        $thiscourse = get_course($courseid);
        $category = core_course_category::get($thiscourse->category, IGNORE_MISSING);
        if (!$category) {
            return;
        }
        $count = $category->get_courses_count(['recursive' => true]);
        $options = ['' => get_string('choosedots')];
        for ($i = 1; $i <= $count; $i++) {
            $options[$i] = $i;
        }
        $element = $mform->addElement('select', 'offer_nc_number_'.$inc, get_string('courses'), $options);
        $element->setMultiple(false);
    }

    /**
     * Add elements for course enrolment based offers
     * @param MoodleQuickForm $mform
     * @param int $i increment
     * @param int $courseid
     * @return void
     */
    protected static function add_elements_for_ce(&$mform, $i, $courseid) {
        $thiscourse = get_course($courseid);
        $category = core_course_category::get($thiscourse->category, IGNORE_MISSING);
        if (!$category) {
            return;
        }
        $courses = $category->get_courses(['recursive' => true]);
        $options = [];
        foreach ($courses as $course) {
            if ($course->id == $courseid) {
                continue;
            }
            $options[$course->id] = $course->fullname;
        }
        $element = $mform->addElement('select', 'offer_ce_courses_'.$i, get_string('courses'), $options);
        $element->setMultiple(true);
        $mform->addElement('select', 'offer_ce_condition_'.$i, '', ['all' => get_string('all'), 'any' => get_string('any')]);
    }

    /**
     * Add elements for profile field based offers
     * @param MoodleQuickForm $mform
     * @param int $inc increment
     * @param int $courseid
     * @return void
     */
    protected static function add_elements_for_pf(&$mform, $inc, $courseid) {
        global $CFG;
        $context = context_course::instance($courseid);
        $fields = [
            'firstname', 'lastname', 'email',
            'city', 'country', 'idnumber',
            'institution', 'department',
            'phone1', 'phone2', 'address',
        ];
        $stfields = [];
        foreach ($fields as $field) {
            $stfields['sf'.$field] = get_string($field);
        }
        \core_collator::asort($stfields);
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $fields = profile_get_custom_fields(true);
        $custom = [];
        foreach ($fields as $field) {
            $custom['cf'.$field->shortname] = format_string($field->name, true, ['context' => $context]);
        }
        \core_collator::asort($custom);
        $options = array_merge(['' => get_string('choosedots')], $stfields, $custom);
        $group = [];
        $label = get_string('offers_profile_field', 'enrol_wallet');
        $group[] = $mform->createElement('select', 'offer_pf_field_'.$inc, $label, $options);
        $operations = [
            self::PFOP_CONTAINS         => get_string('offers_pfop_contains', 'enrol_wallet'),
            self::PFOP_DOES_NOT_CONTAIN => get_string('offers_pfop_doesnotcontain', 'enrol_wallet'),
            self::PFOP_IS_EQUAL_TO      => get_string('offers_pfop_isequalto', 'enrol_wallet'),
            self::PFOP_IS_EMPTY         => get_string('offers_pfop_isempty', 'enrol_wallet'),
            self::PFOP_IS_NOT_EMPTY     => get_string('offers_pfop_isnotempty', 'enrol_wallet'),
            self::PFOP_STARTS_WITH      => get_string('offers_pfop_startswith', 'enrol_wallet'),
            self::PFOP_ENDS_WITH        => get_string('offers_pfop_endswith', 'enrol_wallet'),
        ];
        $group[] = $mform->createElement('select', 'offer_pf_op_'.$inc, '', $operations);
        $group[] = $mform->createElement('text', 'offer_pf_value_'.$inc, '');
        $mform->setType('offer_pf_value_'.$inc, PARAM_TEXT);
        $mform->addGroup($group, 'offer_pf_'.$inc, get_string('offers_profile_field_based', 'enrol_wallet'), null, false);
    }

    /**
     * parse the submitted data in form of json to be saved
     * in the data base.
     * @param \stdClass|array $data
     */
    public static function parse_data(&$data) {

        $isarray = is_array($data) ? true : false;

        $offers = self::get_offers_from_submitted_data($data);

        if ($isarray) {
            $data = (array)$data;
            $data['customtext3'] = json_encode($offers);
        } else {
            $data->customtext3 = json_encode($offers);
        }
    }

    /**
     * Validate submitted offers values from the form.
     *
     * @param array $data the submitted data
     * @return array[string]
     */
    public static function validate($data) {

        $errors = [];
        $offers = self::get_offers_from_submitted_data($data);
        foreach ($offers as $i => $offer) {
            $discount = $offer->discount ?? null;
            $type = $offer->type;
            if (empty($discount)
                || !is_numeric($discount)
                || $discount < 0 || $discount > 100
                ) {
                $n = self::fname($type, 'discount', $i);
                $errors[$n] = get_string('offers_error_discountvalue', 'enrol_wallet');
            }

            switch ($type) {
                case self::TIME:
                    if ($offer->to < time() - DAYSECS) {
                        $errors[self::fname($type, 'to', $i)] = get_string('offers_error_timeto', 'enrol_wallet');
                    }
                    if ($offer->from > $offer->to) {
                        $errors[self::fname($type, 'from', $i)] = get_string('offers_error_timefrom', 'enrol_wallet');
                    }
                    break;
                case self::COURSE_ENROL_COUNT:
                    if (empty($offer->number) || $offer->number <= 0) {
                        $errors[self::fname($type, 'number', $i)] = get_string('offers_error_ncnumber', 'enrol_wallet');
                    }
                    break;
                case self::OTHER_CATEGORY_COURSES:
                    if (!$category = core_course_category::get($offer->cat, IGNORE_MISSING)) {
                        $errors[self::fname($type, '', $i)] = get_string('offers_error_othercnotexist', 'enrol_wallet');
                        break;
                    }
                    if (empty($offer->courses)) {
                        $errors[self::fname($type, '', $i)] = get_string('offers_error_othercnocourses', 'enrol_wallet');
                    } else if ($category->get_courses_count(['recursive' => true]) < $offer->courses) {
                        $errors[self::fname($type, '', $i)] = get_string('offers_error_otherccoursesexceed', 'enrol_wallet');
                    }

                    break;
                case self::PROFILE_FIELD:
                    if (empty($offer->cf) || empty($offer->sf)) {
                        $errors[self::fname($type, '', $i)] = get_string('offers_error_pfselect', 'enrol_wallet');
                    } else if (!in_array($offer->op, [self::PFOP_IS_EMPTY, self::PFOP_IS_NOT_EMPTY])) {
                        if (empty($offer->value)) {
                            $errors[self::fname($type, '', $i)] = get_string('offers_error_pfnovalue', 'enrol_wallet');
                        }
                    }
                    break;
                case self::COURSES_ENROL_SAME_CAT:
                    if (empty($offer->courses)) {
                        $errors[self::fname($type, 'courses', $i)] = get_string('offers_error_ce', 'enrol_wallet');
                    }
                    break;
                case self::GEO_LOCATION:
                default:
            }
        }

        return $errors;
    }

    /**
     * Extract the offers from the submitted form data.
     * @param \stdClass|array $data
     * @return array[\stdClass]
     */
    protected static function get_offers_from_submitted_data($data) {

        $data = (object)$data;
        $offers = [];
        foreach ($_POST as $key => $value) {
            if (isset($data->$key)) {
                // Already included and cleaned from the submitted form data.
                continue;
            }

            if (strpos($key, 'offer_') !== 0) {
                // Not ours.
                continue;
            }

            // Will be cleaned later after determine its type.
            $data->$key = $value;
        }

        foreach ($data as $key => $value) {
            if (strpos($key, 'offer_') !== 0) {
                continue;
            }
            // ... offer_<type>_<key>_<increment>.
            $chars = explode('_', $key);

            $i = (int)array_pop($chars);
            $type = $chars[1];
            if (!isset($offers[$i])) {
                $offers[$i] = new \stdClass;
                $offers[$i]->type = $type;
            }
            $k = $chars[2];

            // Cleaning the values.
            if ($type == self::TIME && is_array($value)) {

                $value = clean_param_array($value, PARAM_INT);
                $value = mktime($value['hour'], $value['minute'], 0, $value['month'], $value['day'], $value['year']);

            } else if ($type == self::COURSES_ENROL_SAME_CAT && is_array($value)) {

                $value = clean_param_array($value, PARAM_INT);

            } else if (in_array($k, ['cat', 'courses', 'number'])) {

                $value = clean_param($value, PARAM_INT);

            } else if ($k == 'discount') {

                $value = clean_param($value, PARAM_FLOAT);

            } else {

                $value = clean_param($value, PARAM_TEXT);
            }

            if ($type == self::PROFILE_FIELD && $k == 'field') {
                if (strpos($value, 'sf') === 0) {
                    $offers[$i]->sf = substr($value, 2);
                } else if (strpos($value, 'cf') === 0) {
                    $offers[$i]->cf = substr($value, 2);
                }
            } else {
                $offers[$i]->$k = $value;
            }
        }

        return $offers;
    }

    /**
     * Get stored offers from database, submitted data or temporary
     * stored ones from session
     * @return array
     */
    private function get_stored_offers() {
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
                $return = $return + (array)$offers;
                $this->offers = $return;
            }
        }

        return $return;
    }

    /**
     * Get all courses with offers and add it to course object $course->offers as array keyed with
     * instance id and each contain array with offers details.
     * @param int $categoryid
     * @return array
     */
    public static function get_courses_with_offers($categoryid = 0) {
        global $DB;
        $notempty = $DB->sql_isnotempty('enrol', 'e.customtext3', true, true);

        $sql = "SELECT e.id as instanceid, c.*, e.customtext3, e.cost
                From {course} c
                JOIN {enrol} e ON e.courseid = c.id
                WHERE e.status = :stat
                  AND (e.enrolstartdate < :time1 OR e.enrolstartdate = 0)
                  AND (e.enrolenddate > :time2 OR e.enrolenddate = 0)
                  AND e.enrol = :wallet
                  AND c.visible = 1
                  AND (e.cost = 0 OR $notempty)
                ORDER BY c.timecreated DESC";
        $params = [
            'stat' => ENROL_INSTANCE_ENABLED,
            'time1' => time(),
            'time2' => time(),
            'wallet' => 'wallet',
        ];
        if (!empty($categoryid)) {
            $category = core_course_category::get($categoryid, IGNORE_MISSING);
            $catids = [];
            if ($category) {
                $catids = $category->get_all_children_ids();
            }
            $catids[] = $categoryid;
            list($in, $inparams) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
            $sql .= " AND c.category $in";
            $params = $params + $inparams;
        }
        $courses = $DB->get_records_sql($sql, $params);

        $final = [];
        foreach ($courses as $instanceid => $course) {
            $instance = new stdClass;
            $instance->id = $instanceid;
            $instance->courseid = $course->id;
            $instance->customtext3 = $course->customtext3;

            $zero = is_number($course->cost) && $course->cost == 0;
            $class = new self($instance);
            if (empty($class->get_raw_offers()) && !$zero) {
                continue;
            }
            if (!isset($final[$course->id])) {
                $final[$course->id] = $course;
                $final[$course->id]->free = $zero;
                $final[$course->id]->hasoffer = !$zero;
                $final[$course->id]->offers = [];
                unset($final[$course->id]->instanceid);
                unset($final[$course->id]->customtext3);
                unset($final[$course->id]->cost);
            }
            $final[$course->id]->offers[$instanceid] = $class->format_offers_descriptions();
        }
        return $final;
    }
}

/**
 * Fake availability info class.
 *
 * Fake availability info class used to check the availability
 * of profile field base offer by availability_field
 * @package enrol_wallet
 */
class fake_info extends \core_availability\info {
    /**
     * Override parent construct and don't set any data
     * to create a fake info class just to avoid errors.
     */
    public function __construct() {
    }
    /**
     * Not used but return some context.
     * @return \context
     */
    public function get_context() {
        return context_system::instance();
    }
    /**
     * Not used
     * @return string
     */
    protected function get_thing_name() {
        return '';
    }
    /**
     * Not used
     * @return string
     */
    protected function get_view_hidden_capability() {
        return 'enrol/wallet:manage';
    }
    /**
     * Not used
     * @param string $availability
     * @return void
     */
    protected function set_in_database($availability) {
        return;
    }
}
