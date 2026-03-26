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
use enrol_wallet\local\utils\timedate;
use MoodleQuickForm;
use stdClass;

/**
 * Class other_category_courses_offer
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class other_category_courses_offer extends offer_item {
    /**
     * Category id at which the offer belongs to.
     * @var int
     */
    protected int $cat;
    /**
     * Number of enrolled courses required.
     * @var int
     */
    protected int $number;

    /**
     * Active enrolment only.
     * @var bool
     */
    protected bool $activeonly = false;
    /**
     * {@inheritDoc}
     * @param stdClass $offer
     * @param int $courseid
     * @param int $userid
     */
    public function __construct(stdClass $offer, int $courseid, int $userid = 0) {
        parent::__construct($offer, $courseid, $userid);
        $this->cat = (int)$offer->cat;
        $this->number = $offer->number ?? $offer->courses;
        $this->activeonly = $offer->activeonly ?? false;
    }

    #[\Override()]
    public static function key(): string {
        return 'otherc';
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        $category = core_course_category::get($this->cat, IGNORE_MISSING, false, $this->userid);

        if (!$category) {
            return null;
        }
        $a = [
            'catname'  => $category->get_nested_name(),
            'number'   => $this->number,
            'discount' => format_float($this->discount, 2),
        ];
        return get_string('offers_nc_desc', 'enrol_wallet', $a);
    }

    #[\Override()]
    public function validate_offer(): bool {
        global $DB;
        $number = $this->number;
        $catid  = $this->cat;
        if (empty($number)) {
            return false;
        }

        $category = core_course_category::get($catid, IGNORE_MISSING, false, $this->userid);

        if (!$category) {
            return false;
        }

        $ids = [$catid, ...$category->get_all_children_ids()];

        [$in, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params = $inparams + [
            'thiscourse' => $this->courseid,
            'userid'     => $this->userid,
        ];

        $sql = "SELECT ue.id
                FROM {user_enrolments} ue
                JOIN {enrol} e ON ue.enrolid = e.id
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category $in
                  AND c.id <> :thiscourse
                  AND ue.userid = :userid";

        if ($this->activeonly) {
            $sql .= " AND ue.status = :active
                      AND (ue.timeend >= :now1 OR ue.timeend = 0)
                      AND (ue.timestart <= :now2 OR ue.timestart = 0)";
            $params += [
                'active' => ENROL_USER_ACTIVE,
                'now1' => timedate::time(),
                'now2' => timedate::time(),
            ];
        }

        $records = $DB->get_records_sql($sql, $params, 0, $number + 1);

        if (\count($records) >= $number) {
            return true;
        }

        return false;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_other_category_courses_based', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(MoodleQuickForm $mform, int $i, int $courseid): void {
        $thiscourse = get_course($courseid);
        $cetegories = core_course_category::get_all();
        $options    = [];
        $max        = 0;
        $inc = $i;

        foreach ($cetegories as $category) {
            if ($category->id == $thiscourse->category) {
                continue;
            }
            $count = $category->get_courses_count(['recursive' => true]);

            if (empty($count)) {
                continue;
            }
            $options[$category->id] = $category->get_nested_name(false) . " ($count)";
            $max                    = max($max, $count);
        }

        $group   = [];
        $group[] = $mform->createElement('select', 'offer_otherc_cat_' . $inc, get_string('categories'), $options);
        $options = ['' => get_string('choosedots')];

        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = $i;
        }

        $group[] = $mform->createElement('select', 'offer_otherc_courses_' . $inc, get_string('courses'), $options);
        $label   = get_string('offers_other_category_courses_based', 'enrol_wallet');
        $mform->addGroup($group, 'offer_otherc_' . $inc, $label, null, false);

        $mform->addElement('advcheckbox', 'offer_otherc_activeonly_' . $inc, get_string('activeonly', 'enrol_wallet'));
    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors): void {
        if (!$category = core_course_category::get($offer->cat, IGNORE_MISSING)) {
            $errors[offers::fname(self::key(), '', $i)] = get_string('offers_error_othercnotexist', 'enrol_wallet');
            return;
        }

        if (empty($offer->courses)) {
            $errors[offers::fname(self::key(), '', $i)] = get_string('offers_error_othercnocourses', 'enrol_wallet');
        } else if ($category->get_courses_count(['recursive' => true]) < $offer->courses) {
            $errors[offers::fname(self::key(), '', $i)] = get_string('offers_error_otherccoursesexceed', 'enrol_wallet');
        }
    }

    #[\Override()]
    public static function clean_submitted_value(string $name, mixed &$value): void {
        if (\in_array($name, ['cat', 'courses', 'number'])) {
            $value = clean_param($value, PARAM_INT);
        } else {
            parent::clean_submitted_value($name, $value);
        }
    }
}
