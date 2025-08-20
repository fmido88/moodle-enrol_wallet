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

namespace enrol_wallet\local\restriction;

use core_availability\result;

/**
 * Class that holds a tree of availability conditions.
 *
 * override to make it suitable for enrol wallet instance.
 *
 * The structure of this tree in JSON input data is:
 *
 * { op:'&', c:[] }
 *
 * where 'op' is one of the OP_xx constants and 'c' is an array of children.
 *
 * At the root level one of the following additional values must be included:
 *
 * op '|' or '!&'
 *   show:true
 *   Boolean value controlling whether a failed match causes the item to
 *   display to students with information, or be completely hidden.
 * op '&' or '!|'
 *   showc:[]
 *   Array of same length as c with booleans corresponding to each child; you
 *   can make it be hidden or shown depending on which one they fail. (Anything
 *   with false takes precedence.)
 *
 * @package enrol_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tree extends \core_availability\tree {
    /**
     * @var \stdClass $instance the enrol wallet instance
     */
    private $instance;
    /**
     * Decodes availability structure.
     *
     * This function also validates the retrieved data as follows:
     * 1. Data that does not meet the API-defined structure causes a
     *    coding_exception (this should be impossible unless there is
     *    a system bug or somebody manually hacks the database).
     * 2. Data that meets the structure but cannot be implemented (e.g.
     *    reference to missing plugin or to module that doesn't exist) is
     *    either silently discarded (if $lax is true) or causes a
     *    coding_exception (if $lax is false).
     *
     * @see decode_availability
     * @param \stdClass $structure Structure (decoded from JSON)
     * @param boolean $lax If true, throw exceptions only for invalid structure
     * @param boolean $root If true, this is the root tree
     * @param \stdClass $instance the enrol wallet instance.
     * @throws \coding_exception If data is not valid structure
     */
    public function __construct(\stdClass $structure, $lax = false, $root = true, $instance = null) {
        parent::__construct($structure, $lax, $root);
        $this->instance = $instance;
    }

    /**
     * Determines whether this particular item is currently available
     * according to the availability criteria.
     *
     * - This does not include the 'visible' setting (i.e. this might return
     *   true even if visible is false); visible is handled independently.
     * - This does not take account of the viewhiddenactivities capability.
     *   That should apply later.
     *
     * The $not option is potentially confusing. This option always indicates
     * the 'real' value of NOT. For example, a condition inside a 'NOT AND'
     * group will get this called with $not = true, but if you put another
     * 'NOT OR' group inside the first group, then a condition inside that will
     * be called with $not = false. We need to use the real values, rather than
     * the more natural use of the current value at this point inside the tree,
     * so that the information displayed to users makes sense.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return result Availability check result
     */
    public function check_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;
        $oldinfo = $info;
        unset($info);
        // If there are no children in this group, we just treat it as available.
        if (!$this->children) {
            return new result(true);
        }

        // Get logic flags from operator.
        list($innernot, $andoperator) = $this->get_logic_flags($not);

        if ($andoperator) {
            $allow = true;
        } else {
            $allow = false;
        }
        $failedchildren = [];
        $totallyhide = !$this->show;
        $decoded = json_decode($this->instance->customtext2);
        $enabled = get_config('enrol_wallet', 'availability_plugins');
        $enabled = explode(',', $enabled);
        $additional = '';
        foreach ($this->children as $index => $child) {
            if (!in_array($decoded->c[$index]->type, $enabled)) {
                continue;
            }
            if ($child instanceof \availability_completion\condition) {
                $cm = $DB->get_record('course_modules', ['id' => $decoded->c[$index]->cm]);
                $info = new info($this->instance, get_course($cm->course));
            } else if ($child instanceof \availability_grade\condition) {
                $gradeitem = $DB->get_record('grade_items', ['id' => $decoded->c[$index]->id]);
                $gradecourse = get_course($gradeitem->courseid);
                $info = new info($this->instance, $gradecourse);
                $additional = ($gradeitem->itemname ?? 'Course total') . ': ' .$gradecourse->fullname;
            } else {
                $info = $oldinfo;
            }

            // Check available and get info.
            $childresult = $child->check_available(
                    $innernot, $info, $grabthelot, $userid);
            $childyes = $childresult->is_available();
            if (!$childyes) {
                $failedchildren[] = $childresult;
                if (!is_null($this->showchildren) && !$this->showchildren[$index]) {
                    $totallyhide = true;
                }
            }

            if ($andoperator && !$childyes) {
                $allow = false;
                // Do not exit loop at this point, as we will still include other info.
            } else if (!$andoperator && $childyes) {
                // Exit loop since we are going to allow access (from this tree at least).
                $allow = true;
                break;
            }
        }

        if ($allow) {
            return new result(true);
        } else if ($totallyhide) {
            return new result(false);
        } else {
            return new result(false, $this, $failedchildren);
        }
    }
}

