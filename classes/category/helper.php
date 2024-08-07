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

namespace enrol_wallet\category;

use core_course_category;
use enrol_wallet\util\instance;

/**
 * Class helper
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * The category object.
     * @var core_course_category $category
     */
    protected $category;

    /**
     * The id of the category.
     * @var int $catid
     */
    protected $catid;

    /**
     * @var array[int] the parents ids.
     */
    protected $parents = [];
    /**
     * Create a category helper object.
     *
     * @param object|int $categoryorid the category or its id.
     */
    public function __construct($categoryorid) {

        if (is_number($categoryorid)) {
            $this->catid = $categoryorid;
            $this->category = core_course_category::get($categoryorid, IGNORE_MISSING, true);
        } else if ($categoryorid instanceof core_course_category) {
            $this->catid = $categoryorid->id;
            $this->category = $categoryorid;
        } else if (is_object($categoryorid)) {
            $this->catid = $categoryorid->id;
            $this->category = core_course_category::get($categoryorid->id, IGNORE_MISSING, true);
        }

        if (!empty($this->category)) {
            $this->parents = $this->category->get_parents();
            // Include the catid with the parents array for easy search.
            $this->parents[$this->catid] = $this->catid;
        }
    }

    /**
     * Get the core_course_category object.
     * @return core_course_category
     */
    public function get_category() {
        return $this->category ?? null;
    }
    /**
     * Get the parents of the current category INCLUDING the category itself.
     * @return array of names of the categories keyed by the category id.
     */
    public function get_parents() {
        $all = [];
        foreach ($this->parents as $catid) {
            $catname = core_course_category::get($catid)->get_formatted_name();
            $all[$catid] = $catname;
        }
        return $all;
    }

    /**
     * Check if the passed category id is this category itself or one of its children.
     * @param int $catid the category id to check with this one.
     * @return bool
     */
    public function is_belong_to_this($catid) {
        if ($catid == $this->catid) {
            return true;
        }

        if (empty($this->category)) {
            return false;
        }

        $ids = $this->category->get_all_children_ids();
        if (in_array($catid, $ids)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the given course module is belonging to this category.
     * @param int $cmid
     * @return bool
     */
    public function is_child_cm($cmid) {
        global $DB;
        $sql = "SELECT cm.id, c.category
                FROM {course_modules} cm
                JOIN {course} c ON cm.course = c.id
                WHERE cm.id = :cmid";
        $params = ['cmid' => $cmid];
        if ($record = $DB->get_record_sql($sql, $params)) {
            if ($this->is_belong_to_this($record->category)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this course section is belonging to this category.
     * @param int $sectionid
     * @return bool
     */
    public function is_child_section($sectionid) {
        global $DB;
        $sql = "SELECT cs.id, c.category
                FROM {course_sections} cs
                JOIN {course} c ON cs.course = c.id
                WHERE cs.id = :sectionid";
        $params = ['sectionid' => $sectionid];
        if ($record = $DB->get_record_sql($sql, $params)) {
            if ($this->is_belong_to_this($record->category)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the enrol wallet instance is belonging to this category
     * @param int $instanceid
     * @return bool
     */
    public function is_child_instance($instanceid) {
        global $DB;
        $sql = "SELECT e.id, c.category
                FROM {enrol} e
                JOIN {course} c ON e.courseid = c.id
                WHERE e.id = :instanceid";
        $params = ['instanceid' => $instanceid];
        if ($record = $DB->get_record_sql($sql, $params)) {
            if ($this->is_belong_to_this($record->category)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create category balance operations class from enrol wallet instance
     * @param int|\stdClass $instanceorid the enrol wallet instance of its id.
     * @return self
     */
    public static function create_from_instance($instanceorid) {
        $helper = new instance($instanceorid);
        $category = $helper->get_course_category();
        return new self($category);
    }

    /**
     * Create an instance of category balance operation from course object or its id.
     * @param int|\stdClass $courseorid
     * @return self
     */
    public static function create_from_course($courseorid) {
        if (is_number($courseorid)) {
            $course = get_course($courseorid);
        } else if (is_object($courseorid)) {
            $course = $courseorid;
        } else {
            throw new \moodle_exception('invalidcourseid');
        }
        return new self($course->category);
    }
}
