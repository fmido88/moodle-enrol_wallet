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

use core_course_category;
use MoodleQuickForm;
use stdClass;

/**
 * Class course_enrol_count_offer
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
     * {@inheritDoc}
     * @param stdClass $offer
     * @param int $courseid
     * @param int $userid
     */
    public function __construct(stdClass $offer, int $courseid, int $userid = 0) {
        parent::__construct($offer, $courseid, $userid);
        $this->number = $offer->number ?? $offer->courses;
    }
    #[\Override()]
    public static function key(): string {
        return 'nc';
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        $course   = get_course($this->courseid);
        $category = core_course_category::get($course->category, IGNORE_MISSING, false, $this->userid);

        if (!$category) {
            return null;
        }

        $a = [
            'catname'  => $category->get_nested_name(),
            'number'   => $this->number,
            'discount' => $this->get_formatted_discount(),
        ];

        return get_string('offers_nc_desc', 'enrol_wallet', $a);
    }

    #[\Override()]
    public function validate_offer(): bool {
        global $DB;
        $courseid = $this->courseid;
        $catid    = $DB->get_field('course', 'category', ['id' => $courseid]);

        if (!$catid) {
            return false;
        }

        $offer = fullclone($this->offer);
        $offer->cat = $catid;
        $offer->type = other_category_courses_offer::key();
        $enrolcount = new other_category_courses_offer($offer, $courseid, $this->userid);
        return $enrolcount->validate_offer();
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_number_courses_base', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(MoodleQuickForm $mform, int $i, int $courseid): void {
        $thiscourse = get_course($courseid);
        $category   = core_course_category::get($thiscourse->category, IGNORE_MISSING);

        $inc = $i;
        if (!$category) {
            return;
        }
        $count   = $category->get_courses_count(['recursive' => true]);
        $options = ['' => get_string('choosedots')];

        for ($i = 1; $i <= $count; $i++) {
            $options[$i] = $i;
        }
        $element = $mform->addElement('select', 'offer_nc_number_' . $inc, get_string('courses'), $options);
        $element->setMultiple(false);
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors): void {
        if (empty($offer->number) || $offer->number <= 0) {
            $errors[offers::fname(self::key(), 'number', $i)] = get_string('offers_error_ncnumber', 'enrol_wallet');
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
}
