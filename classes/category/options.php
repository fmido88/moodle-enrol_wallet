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
 * Retrieve the categories options to be used in forms.
 *
 * @package   enrol_wallet
 * @copyright 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet\category;

use enrol_wallet\util\instance;
use core_course_category;

/**
 * Class methods to get all the categories options to be displayed in forms
 * @package enrol_wallet
 */
class options {
    /**
     * The course category object.
     * @var core_course_category|null
     */
    protected $category = null;
    /**
     * The category id
     * @var int
     */
    protected $catid = 0;
    /**
     * The parents of the passed category
     * @var array[int]
     */
    public $parents = [];
    /**
     * Initialize the options with an optional parameter $category or $categoryid
     * it wil be helpful to get all categories corresponding to the passed one
     * parent, categories with discount and other methods.
     *
     * @param int|object $categoryorid 0 means get all options in the site
     */
    public function __construct($categoryorid = 0) {
        if (is_number($categoryorid)) {
            $this->catid = $categoryorid;
            $this->category = core_course_category::get($categoryorid);
        } else if ($categoryorid instanceof core_course_category) {
            $this->catid = $categoryorid->id;
            $this->category = $categoryorid;
        } else if (is_object($categoryorid)) {
            $this->catid = $categoryorid->id;
            $this->category = core_course_category::get($categoryorid->id);
        }
        $this->set_parents();
    }

    /**
     * Called by constructor to set the parents of the current category.
     */
    protected function set_parents() {
        if (!empty($this->category)) {
            $this->parents = $this->category->get_parents();
            // Include the catid with the parents array for easy search.
            $this->parents[] = $this->catid;
        }
    }

    /**
     * return the ids of parents of the current category including itself
     * empty array given if no category specified in the constructor.
     * @return array[int] of categories ids.
     */
    public function get_parents_ids() {
        return $this->parents;
    }

    /**
     * Return an array on parents names keyed with their ids
     * including the category itself
     * @return array[string]
     */
    public function get_parents_options() {
        $catoptions = [0 => get_string('any')];
        foreach ($this->parents as $catid) {
            $catname = core_course_category::get($catid)->get_formatted_name();
            $catoptions[$catid] = $catname;
        }

        return $catoptions;
    }

    /**
     * Get all the categories in the site in nested name form
     * return array keyed with categories ids
     * @return array[string]
     */
    public static function get_all_categories_options() {
        $catoptions = [0 => get_string('any')];
        $allcats = \core_course_category::get_all();
        foreach ($allcats as $catid => $cat) {
            $catoptions[$catid] = $cat->get_nested_name(false);
        }
        asort($catoptions, SORT_STRING | SORT_FLAG_CASE);
        return $catoptions;
    }

    /**
     * Check if the current category or one of its parents has at least one conditional discount rule.
     * if no category specified, it will check for the site discounts.
     * @return bool
     */
    public function has_discount() {
        global $DB;
        $now = time();
        $params = [
            'time1' => $now,
            'time2' => $now,
        ];
        $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        if (!empty($this->catid)) {
            list($in, $catparams) = $DB->get_in_or_equal($this->get_parents_ids(), SQL_PARAMS_NAMED);
            $select .= " AND category $in";
            $params += $catparams;
        } else {
            $select .= ' AND (category IS NULL OR category = 0)';
        }
        $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params, '', 'id', 0, 1);

        return !empty($records);
    }

    /**
     * Get categories options with discounts only.
     * It will be filtered to contain only the current one, its parents and site level
     *
     * @return array[string] array of categories nested name keyed with their ids.
     */
    public function get_local_options_with_discounts() {
        global $DB;
        $now = time();
        $params = [
            'time1' => $now,
            'time2' => $now,
        ];
        $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        if (!empty($this->catid)) {
            list($in, $catparams) = $DB->get_in_or_equal($this->get_parents_ids(), SQL_PARAMS_NAMED);
            $select .= " AND category $in";
            $params += $catparams;
        }
        $select .= ' AND (category IS NULL OR category = 0)';
        $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params, 'category ASC', 'id, category');
        $options = [];
        foreach ($records as $record) {
            if (empty($record->category)) {
                if (isset($options[0])) {
                    continue;
                }
                $options[0] = get_string('any');
                continue;
            }
            if (isset($options[$record->category])) {
                continue;
            }
            if ($record->category == $this->catid) {
                $options[$record->category] = $this->category->get_nested_name(false);
            } else {
                $cat = core_course_category::get($record->category);
                $options[$record->category] = $cat->get_nested_name(false);
            }
        }
        ksort($options, SORT_NUMERIC);
        return $options;
    }

    /**
     * Get all category options with conditional discounts only
     * return array of categories nested name keyed with categories ids.
     * @return array[string]
     */
    public static function get_all_options_with_discount() {
        global $DB;
        $now = time();
        $params = [
            'time1' => $now,
            'time2' => $now,
        ];
        $select = '(timefrom <= :time1 OR timefrom = 0) AND (timeto >= :time2 OR timeto = 0)';
        $records = $DB->get_records_select('enrol_wallet_cond_discount', $select, $params, 'category ASC', 'id, category');
        $options = [];
        foreach ($records as $record) {
            if (empty($record->category)) {
                if (isset($options[0])) {
                    continue;
                }
                $options[0] = get_string('any');
                continue;
            }
            if (isset($options[$record->category])) {
                continue;
            }
            $cat = core_course_category::get($record->category);
            $options[$record->category] = $cat->get_nested_name(false);
        }
        ksort($options, SORT_NUMERIC);
        return $options;
    }
    /**
     * Construct the class from enrol wallet instance id.
     * @param int $instanceid
     * @return options
     */
    public static function create_from_instance_id($instanceid = 0) {
        if (empty($instanceid)) {
            return new self();
        } else {
            $instancehelper = new instance($instanceid);
            return new self($instancehelper->get_course_category());
        }
    }
}
