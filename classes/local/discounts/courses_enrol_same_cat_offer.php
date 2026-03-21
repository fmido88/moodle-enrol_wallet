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

use core\output\html_writer;
use core_course_category;
use core_course_list_element;
use moodle_url;
use MoodleQuickForm;
use stdClass;

/**
 * Class courses_enrol_same_cat_offer
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses_enrol_same_cat_offer extends offer_item {
    /**
     * List of course ids in the condition.
     * @var int[]
     */
    protected array $courses;
    /**
     * Condition rule (any, all)
     * @var string
     */
    protected string $condition;
    /**
     * {@inheritDoc}
     * @param stdClass $offer
     * @param int $courseid
     * @param int $userid
     */
    public function __construct(stdClass $offer, int $courseid, int $userid = 0) {
        parent::__construct($offer, $courseid, $userid);
        $this->condition = $offer->condition;
        $this->courses = $offer->courses;
    }

    #[\Override()]
    public static function key(): string {
        return 'ce';
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        $courseslist = html_writer::start_tag('ul');

        foreach ($this->courses as $id) {
            $course     = new core_course_list_element(get_course($id));
            $context    = $course->get_context();

            $enrolled   = is_enrolled($context, $this->userid);

            $coursename = $course->get_formatted_fullname();
            $courseurl  = new moodle_url('/course/view.php', ['id' => $id]);
            $courselink = html_writer::link($courseurl, $coursename);

            $courseslist .= html_writer::tag('li', $courselink);
        }

        $courseslist .= html_writer::end_tag('ul');
        $a = [
            'courses'   => $courseslist,
            'discount'  => format_float($this->discount, 2),
            'condition' => $this->condition == 'any' ? get_string('any') : get_string('all'),
        ];
        return get_string('offers_ce_desc', 'enrol_wallet', $a);
    }

    #[\Override()]
    public function validate_offer(): bool {
        global $DB;
        $ids = $this->courses;
        $condition = $this->condition;

        [$in, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);

        $sql = "SELECT ue.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid $in
                   AND ue.userid = :userid";

        $params  = $inparams + ['userid' => $this->userid];
        $records = $DB->get_records_sql($sql, $params);

        if (empty($records)) {
            return false;
        } else if ($condition == 'any') {
            return true;
        } else if ($condition == 'all' && \count($records) >= \count($ids)) {
            return true;
        }

        return false;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_course_enrol_based', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(MoodleQuickForm $mform, int $i, int $courseid): void {
        $thiscourse = get_course($courseid);
        $category   = core_course_category::get($thiscourse->category, IGNORE_MISSING);

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
        $element = $mform->addElement('select', 'offer_ce_courses_' . $i, get_string('courses'), $options);
        $element->setMultiple(true);
        $mform->addElement('select', 'offer_ce_condition_' . $i, '', ['all' => get_string('all'), 'any' => get_string('any')]);
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors): void {
        if (empty($offer->courses)) {
            $errors[offers::fname(self::key(), 'courses', $i)] = get_string('offers_error_ce', 'enrol_wallet');
        }
    }

    #[\Override()]
    public static function clean_submitted_value(string $name, mixed &$value): void {
        if (\is_array($value)) {
            $value = clean_param_array($value, PARAM_INT);
        } else if ($name == 'number') {
            $value = clean_param($value, PARAM_INT);
        } else if ($name == 'condition') {
            $value = clean_param($value, PARAM_ALPHANUM);
        } else {
            parent::clean_submitted_value($name, $value);
        }
    }
}
