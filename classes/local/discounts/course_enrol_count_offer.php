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
use core_course_category;
use MoodleQuickForm;
use phpunit_util;
use stdClass;
use testing_data_generator;

/**
 * Class course_enrol_count_offer.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_enrol_count_offer extends offer_item {
    /**
     * Number of courses in the condition.
     * @var int
     */
    protected int $number;

    /**
     * If the user has active enrolment in these course.
     * @var bool
     */
    protected bool $activeonly = false;

    /**
     * {@inheritDoc}
     * @param stdClass $offer
     * @param int      $courseid
     * @param int      $userid
     * @param bool     $subcondition
     */
    public function __construct(stdClass $offer, int $courseid, int $userid = 0, bool $subcondition = false) {
        parent::__construct($offer, $courseid, $userid, $subcondition);
        $this->number = $offer->number ?? $offer->courses;
        $this->activeonly = $offer->activeonly ?? false;
    }

    #[\Override()]
    public static function is_valid_structure(stdClass $offer): bool {
        $number = $offer->number ?? $offer->courses;
        $activeonly = $offer->activeonly ?? false;

        return is_number($number) && (bool)$activeonly == $activeonly;
    }

    #[\Override()]
    public static function key(): string {
        return 'nc';
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        $course = get_course($this->courseid);
        $category = core_course_category::get($course->category, IGNORE_MISSING, false, $this->userid);

        if (!$category) {
            return null;
        }

        $usercount = other_category_courses_offer::get_user_courses_count(
            $course->category,
            $this->userid,
            $this->activeonly,
            [$this->courseid]
        );
        $remain = $this->number - $usercount;
        $remain = max($remain, 0);
        $a = [
            'catname'  => $category->get_nested_name(),
            'number'   => $remain,
            'discount' => $this->get_formatted_discount(),
        ];

        return get_string('offers_nc_desc', 'enrol_wallet', $a);
    }

    /**
     * Hide if the category is not exist of is hidden for the student
     * or the number of courses available less than the number required.
     * @return bool
     */
    public function is_hidden(): bool {
        global $DB;
        if (parent::is_hidden()) {
            return true;
        }

        $catid = $DB->get_field('course', 'category', ['id' => $this->courseid]);
        if (!$catid) {
            // Shouldn't happen at all.
            return true;
        }

        $category = core_course_category::get($catid, IGNORE_MISSING, false, $this->userid);
        if (!$category) {
            return false;
        }

        $count = $category->get_courses_count(['recursive' => true]);
        // Exclude this course.
        return $count - 1 < $this->number;
    }
    #[\Override()]
    public function validate_offer(): bool {
        global $DB;
        $catid = $DB->get_field('course', 'category', ['id' => $this->courseid]);

        if (!$catid) {
            return false;
        }

        $offer = fullclone($this->offer);
        $offer->cat = $catid;
        $offer->type = other_category_courses_offer::key();
        $enrolcount = new other_category_courses_offer($offer, $this->courseid, $this->userid, $this->subcondition);

        return $enrolcount->validate_offer();
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_number_courses_base', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(
        MoodleQuickForm $mform,
        int $i,
        int $courseid,
        ?stdClass $offer = null,
        ?callable $wrapper = null
    ): void {
        $thiscourse = get_course($courseid);
        $category = core_course_category::get($thiscourse->category, IGNORE_MISSING);

        $inc = $i;

        if (!$category) {
            return;
        }
        $count = $category->get_courses_count(['recursive' => true]);
        $options = ['' => get_string('choosedots')];

        for ($i = 1; $i <= $count; $i++) {
            $options[$i] = $i;
        }

        $element = $mform->addElement('select', static::fname('number', $inc, $wrapper), get_string('courses'), $options);
        $element->setMultiple(false);

        $mform->addElement('advcheckbox', static::fname('activeonly', $inc, $wrapper), get_string('activeonly', 'enrol_wallet'));
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors, ?callable $wrapper = null): void {
        if (empty($offer->number) || $offer->number <= 0) {
            $errors[static::fname('number', $i, $wrapper)] = get_string('offers_error_ncnumber', 'enrol_wallet');
        }
    }

    #[\Override()]
    public static function clean_submitted_value(string $name, mixed &$value): void {
        if (\in_array($name, ['courses', 'number'])) {
            $value = clean_param($value, PARAM_INT);
        } else {
            parent::clean_submitted_value($name, $value);
        }
    }

    /**
     * Mock an offer object of this type for testing.
     * @param  ?testing_data_generator $gen
     * @param  ?float                 $discount
     * @param  ?int                   $number
     * @param  ?bool                  $activeonly
     * @return stdClass
     */
    public static function mock_offer(
        ?testing_data_generator $gen = null,
        ?float $discount = null,
        ?int $number = null,
        ?bool $activeonly = null
    ) {
        global $DB;
        $offer = new stdClass();
        $offer->type = static::key();
        $offer->discount = $discount ?? (random_int(100, 9900) / 100);

        if ($number === null) {
            global $PAGE;
            $number = rand(1, 6);
            if ($PAGE->course->id === SITEID) {
                $categoryid = $gen->create_category()->id;
                $offer->gen_course = $gen->create_course(['category' => $categoryid]);
            } else {
                $categoryid = $PAGE->course->category;
            }

            // Ensure not hidden.
            for ($i = 0; $i < $number; $i++) {
                $gen->create_course(['category' => $categoryid]);
            }
        }
        $offer->number = $number;

        if ($activeonly === null) {
            $activeonly = (bool)rand(0, 1);
        }
        $offer->activeonly = $activeonly;

        return $offer;
    }
}
