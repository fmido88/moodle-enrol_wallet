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
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\timedate;
use moodle_url;
use MoodleQuickForm;
use phpunit_util;
use stdClass;
use testing_data_generator;

/**
 * Class courses_enrol_same_cat_offer.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses_enrol_same_cat_offer extends offer_item {
    /**
     * Any of the courses.
     * @var string
     */
    public const COND_ANY = 'any';
    /**
     * All of the courses.
     * @var string
     */
    public const COND_ALL = 'all';

    /**
     * List of course ids in the condition.
     * @var int[]
     */
    protected array $courses;

    /**
     * Condition rule (any, all).
     * @var string
     */
    protected string $condition;

    /**
     * Active enrollments only.
     * @var bool
     */
    protected bool $activeonly = false;

    /**
     * Wallet enrollments only.
     * @var bool
     */
    protected bool $walletonly = false;

    /**
     * {@inheritDoc}
     * @param stdClass $offer
     * @param int      $courseid
     * @param int      $userid
     * @param bool     $subcondition
     */
    public function __construct(stdClass $offer, int $courseid, int $userid = 0, bool $subcondition = false) {
        parent::__construct($offer, $courseid, $userid, $subcondition);
        $this->condition = $offer->condition;
        $this->courses = $offer->courses;
        $this->walletonly = $offer->walletonly ?? false;
        $this->activeonly = $offer->activeonly ?? false;
    }

    #[\Override()]
    public static function is_valid_structure(stdClass $offer): bool {
        $courses = $offer->courses;
        $condition = $offer->condition;
        $walletonly = $offer->walletonly ?? false;
        $activeonly = $offer->activeonly ?? false;
        $valid = \is_array($courses);
        foreach ($courses as $id) {
            $valid = $valid && is_number($id);
        }
        $valid = $valid && \is_string($condition) && \in_array($condition, [static::COND_ALL, static::COND_ANY]);
        $valid = $valid && $walletonly == (bool)$walletonly;
        $valid = $valid && $activeonly == (bool)$activeonly;

        return $valid;
    }

    #[\Override()]
    public static function key(): string {
        return 'ce';
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        $courseslist = html_writer::start_tag('ul');

        foreach ($this->courses as $id) {
            $course = new core_course_list_element(get_course($id));
            $context = $course->get_context();

            $enrolled = $this->walletonly
                    ? instance::is_enrolled_by_wallet($id, $this->userid, $this->activeonly)
                    : is_enrolled($context, $this->userid, '', $this->activeonly);

            if ($enrolled) {
                if ($this->condition == 'any') {
                    return '';
                }
                continue;
            }

            $coursename = $course->get_formatted_fullname();
            $courseurl = new moodle_url('/course/view.php', ['id' => $id]);
            $courselink = html_writer::link($courseurl, $coursename);

            $courseslist .= html_writer::tag('li', $courselink);
        }

        $courseslist .= html_writer::end_tag('ul');
        $a = [
            'courses'   => $courseslist,
            'discount'  => $this->get_formatted_discount(),
            'condition' => $this->condition == 'any' ? get_string('any') : get_string('all'),
        ];

        // Todo: create a template is more convenient here.
        return get_string('offers_ce_desc', 'enrol_wallet', $a);
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
            return true;
        }

        $catcourses = $category->get_courses(['recursive' => true, 'idonly' => true]);
        $and = $this->condition === self::COND_ALL;
        foreach ($this->courses as $cid) {
            $exist = \in_array($cid, $catcourses);
            if ($and && !$exist) {
                return true;
            }
            if (!$and && $exist) {
                return false;
            }
        }
        return false;
    }
    #[\Override()]
    public function validate_offer(): bool {
        global $DB;
        $ids = $this->courses;
        $condition = $this->condition;

        [$in, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);

        $sql = "SELECT ue.id, e.enrol
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid $in
                   AND ue.userid = :userid";

        $params = $inparams + [
            'userid' => $this->userid,
        ];

        if ($this->walletonly) {
            $sql .= ' AND e.enrol = :wallet';
            $params['wallet'] = 'wallet';
        }

        if ($this->activeonly) {
            $sql .= ' AND ue.status = :stat
                   AND (ue.timeend >= :now1 OR ue.timeend = 0)
                   AND (ue.timestart <= :now2 OR ue.timeend = 0)';
            $params['now1'] = timedate::time();
            $params['now2'] = timedate::time();
            $params['stat'] = ENROL_USER_ACTIVE;
        }

        $records = $DB->get_records_sql($sql, $params);

        if (empty($records)) {
            return false;
        }

        if ($condition == 'any') {
            return true;
        }

        if ($condition == 'all' && \count($records) >= \count($ids)) {
            return true;
        }

        return false;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_course_enrol_based', 'enrol_wallet');
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

        $element = $mform->addElement('select', static::fname('courses', $i, $wrapper), get_string('courses'), $options);
        $element->setMultiple(true);
        $options = [
            self::COND_ALL => get_string('all'),
            self::COND_ANY => get_string('any'),
        ];
        $mform->addElement('select', static::fname('condition', $i, $wrapper), '', $options);

        $mform->addElement('advcheckbox', static::fname('aciveonly', $i, $wrapper), get_string('activeonly', 'enrol_wallet'));
        $mform->addElement('advcheckbox', static::fname('walletonly', $i, $wrapper), get_string('walletonly', 'enrol_wallet'));
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors, ?callable $wrapper = null): void {
        if (empty($offer->courses)) {
            $errors[static::fname('courses', $i, $wrapper)] = get_string('offers_error_ce', 'enrol_wallet');
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

    /**
     * Mock an offer object of this type for testing.
     * @param  ?testing_data_generator $gen
     * @param  ?float             $discount
     * @param  ?array             $courses
     * @param  ?string            $condition
     * @param  ?bool              $activeonly
     * @param  ?bool              $walletonly
     * @return stdClass
     */
    public static function mock_offer(
        ?testing_data_generator $gen = null,
        ?float $discount = null,
        ?array $courses = null,
        ?string $condition = null,
        ?bool $activeonly = false,
        ?bool $walletonly = false
    ): stdClass {
        global $DB, $PAGE;
        if (null === $gen) {
            $gen = phpunit_util::get_data_generator();
        }
        $offer = new stdClass();
        $offer->type = static::key();
        $offer->discount = $discount ?? random_int(1, 99);

        if ($courses === null) {
            $courses = [];
            // To be used in test.
            if ($PAGE->course->id = SITEID) {
                $offer->gen_cat = $gen->create_category();
                $cat = $offer->gen_cat->id;
            } else {
                $cat = $PAGE->course->id;
            }

            for ($i = 0; $i < 7; $i++) {
                $courses[] = $gen->create_course(['category' => $cat])->id;
            }
        }
        $offer->courses = $courses;

        if ($condition === null) {
            $condition = [self::COND_ANY, self::COND_ALL][rand(0, 1)];
        }

        $offer->condition = $condition;

        if ($activeonly === null) {
            $activeonly = (bool)rand(0, 1);
        }
        $offer->activeonly = $activeonly;

        if ($walletonly === null) {
            $walletonly = (bool)rand(0, 1);
        }
        $offer->walletonly = $walletonly;

        return $offer;
    }
}
