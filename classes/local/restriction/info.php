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
 * Class handles conditional availability information for a section.
 *
 * @package enrol_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\restriction;

/**
 * Class handles conditional availability information for a wallet enrol instance.
 *
 * @package enrol_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class info extends \core_availability\info {
    /** @var \stdClass instance. */
    protected $instance;

    /** @var \enrol_wallet_plugin enrol wallet class */
    protected $wallet;

    /**
     * Constructs with item details.
     * @param \stdClass $instance the enrol wallet instance.
     * @param \stdClass $course the course object.
     */
    public function __construct($instance, $course = null) {
        $this->wallet = enrol_get_plugin('wallet');
        $this->instance = $instance;
        if (empty($course)) {
            $course = $this->wallet->get_course_by_instance_id($instance->id);
        }

        parent::__construct($course, true, $instance->customtext2);

        $this->instance = $instance;
    }

    /**
     * Obtains the name of the item (enrol instance name, at present) that
     * this is controlling availability of. Name should be formatted ready
     * for on-screen display.
     *
     * @return string Name of item
     */
    protected function get_thing_name() {
        return $this->wallet->get_instance_name($this->instance);
    }

    /**
     * Gets context used for checking capabilities for this item.
     *
     * @return \context Context for this item
     */
    public function get_context() {
        return \context_course::instance($this->get_course()->id);
    }

    /**
     * Gets the capability used to view hidden activities/sections (as
     * appropriate).
     *
     * @return string Name of capability used to view hidden items of this type
     */
    protected function get_view_hidden_capability() {
        return 'enrol/wallet:enrolself';
    }

    /**
     * Stores an updated availability tree JSON structure into the relevant
     * database table.
     *
     * @param string $availability New JSON value
     */
    protected function set_in_database($availability) {
        global $DB;

        $instance = new \stdClass();
        $instance->id = $this->instance->id;
        $instance->customtext2 = $availability;
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);
    }

    /**
     * Gets the section object. Intended for use by conditions.
     *
     * @return null
     */
    public function get_section() {
        return null;
    }

    /**
     * Obtains the modinfo associated with this availability information.
     *
     * Note: This field is available ONLY for use by conditions when calculating
     * availability or information.
     *
     * We override it to avoid exceptions as its called in a non-check time.
     * @return \course_modinfo Modinfo
     */
    public function get_modinfo() {
        if (!empty($this->modinfo)) {
            return $this->modinfo;
        }
        return get_fast_modinfo($this->course);
    }
    /**
     * Gets the cm object. Intended for use by conditions.
     *
     * @return null
     */
    public function get_course_module() {
        return null;
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
     * Depending on options selected, a description of the restrictions which
     * mean the student can't view it (in HTML format) may be stored in
     * $information. If there is nothing in $information and this function
     * returns false, then the activity should not be displayed at all.
     *
     * This function displays debugging() messages if the availability
     * information is invalid.
     *
     * @param string $information String describing restrictions in HTML format
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid If set, specifies a different user ID to check availability for
     * @param \course_modinfo|null $modinfo Usually leave as null for default. Specify when
     *   calling recursively from inside get_fast_modinfo()
     * @return bool True if this item is available to the user, false otherwise
     */
    public function is_available(&$information, $grabthelot = false, $userid = 0,
                                $modinfo = null) {
        global $USER, $OUTPUT, $DB;

        // Default to no information.
        $information = '';

        // Do nothing if there are no availability restrictions.
        if (is_null($this->availability)) {
            return true;
        }

        // Resolve optional parameters.
        if (!$userid) {
            $userid = $USER->id;
        }

        $isavaliable = true;

        $this->modinfo = get_fast_modinfo($this->course, $userid);

        // Get availability from tree.
        try {
            $tree = $this->get_availability_tree();
            $result = $tree->check_available(false, $this, true, $userid);
        } catch (\coding_exception $e) {
            $this->warn_about_invalid_availability($e);
            $this->modinfo = null;
            return false;
        }

        // See if there are any messages.
        if (!$result->is_available()) {
            // If the item is marked as 'not visible' then we don't change the available
            // flag (visible/available are treated distinctly), but we remove any
            // availability info. If the item is hidden with the eye icon, it doesn't
            // make sense to show 'Available from <date>' or similar, because even
            // when that date arrives it will still not be available unless somebody
            // toggles the eye icon.
            if ($this->visible) {
                $inf = $tree->get_result_information($this, $result);
                $information .= self::format_info($inf, $this->course);
            }
            $isavaliable = false;
        }

        $this->modinfo = null;
        return $isavaliable;
    }

    /**
     * Formats the $cm->availableinfo string for display. This includes
     * filling in the names of any course-modules that might be mentioned.
     * Should be called immediately prior to display, or at least somewhere
     * that we can guarantee does not happen from within building the modinfo
     * object.
     *
     * @param \core_availability_multiple_messages|string $inforenderable Info string or renderable
     * @param int|\stdClass $courseorid
     * @return string Correctly formatted info string
     */
    public static function format_info($inforenderable, $courseorid) {
        global $PAGE, $OUTPUT;

        // Use renderer if required.
        if (is_string($inforenderable)) {
            $info = $inforenderable;
        } else {
            $renderable = new \core_availability\output\availability_info($inforenderable);
            $info = $OUTPUT->render($renderable);
        }

        // Don't waste time if there are no special tags.
        if (strpos($info, '<AVAILABILITY_') === false) {
            return $info;
        }

        // Handle CMNAME tags.
        $modinfo = get_fast_modinfo($courseorid);
        $context = \context_course::instance($modinfo->courseid);
        $info = preg_replace_callback('~<AVAILABILITY_CMNAME_([0-9]+)/>~',
                function($matches) use($modinfo, $context) {
                    $cm = $modinfo->get_cm($matches[1]);
                    $coursename = $cm->get_course()->fullname;
                    $modname = $coursename . ': '. $cm->get_name();
                    if ($cm->has_view() && $cm->get_user_visible()) {
                        // Help student by providing a link to the module which is preventing availability.
                        return \html_writer::link($cm->get_url(), format_string($modname, true, ['context' => $context]));
                    } else {
                        return format_string($modname, true, ['context' => $context]);
                    }
                }, $info);
        $info = preg_replace_callback('~<AVAILABILITY_FORMAT_STRING>(.*?)</AVAILABILITY_FORMAT_STRING>~s',
                function($matches) use ($context) {
                    $decoded = htmlspecialchars_decode($matches[1], ENT_NOQUOTES);
                    return format_string($decoded, true, ['context' => $context]);
                }, $info);
        $info = preg_replace_callback('~<AVAILABILITY_CALLBACK type="([a-z0-9_]+)">(.*?)</AVAILABILITY_CALLBACK>~s',
                function($matches) use ($modinfo, $context) {

                    // Find the class, it must have already been loaded by now.
                    $fullclassname = 'availability_' . $matches[1] . '\condition';
                    if (!class_exists($fullclassname, false)) {
                        return '<!-- Error finding class ' . $fullclassname .' -->';
                    }
                    // Load the parameters.
                    $params = [];
                    $encodedparams = preg_split('~<P/>~', $matches[2], 0);
                    foreach ($encodedparams as $encodedparam) {
                        $params[] = htmlspecialchars_decode($encodedparam, ENT_NOQUOTES);
                    }
                    $formatedname = $fullclassname::get_description_callback_value($modinfo, $context, $params);
                    if ($matches[1] !== 'grade') {
                        return $formatedname;
                    } else {
                        global $DB;
                        $gradeitem = $DB->get_record('grade_items', ['id' => $matches[2]]);
                        $coursename = get_course($gradeitem->courseid)->fullname;
                        return $formatedname . ' - ' . $coursename;

                    }
                }, $info);

        return $info;
    }

    /**
     * Decodes availability data from JSON format.
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
     * @param string $availability Availability string in JSON format
     * @param boolean $lax If true, throw exceptions only for invalid structure
     * @return tree Availability tree
     * @throws \coding_exception If data is not valid JSON format
     */
    protected function decode_availability($availability, $lax) {
        // Decode JSON data.
        $structure = json_decode($availability);
        if (is_null($structure)) {
            throw new \coding_exception('Invalid availability text', $availability);
        }

        // Recursively decode tree.
        return new tree($structure, $lax, true, $this->instance);
    }
}
