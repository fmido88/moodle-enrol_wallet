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
 * Plugin event classes are defined here.
 *
 * @package     enrol_wallet
 * @copyright   2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\event;

/**
 * The view event class.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolpage_viewed extends \core\event\base {

    // For more information about the Events API, please visit:
    // https://docs.moodle.org/dev/Event_2.
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localized general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('enrolpage_viewed_event', 'enrol_wallet');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {

        $a = new \stdClass;
        $a->userid = $this->userid;
        $a->courseid = $this->courseid;

        return get_string('enrolpage_viewed_desc', 'enrol_wallet', $a);
    }

    /**
     * Create and trigger from enrol instance.
     * @param \stdClass $instance
     */
    public static function create_and_trigger($instance) {
        global $USER;
        static $viewed = false;
        if (!$viewed) {
            $context = \context_course::instance($instance->courseid);
            $data['contextid'] = $context->id;
            $data['courseid'] = $instance->courseid;
            $data['userid'] = $USER->id;
            $event = self::create($data);
            $event->trigger();
            $viewed = true;
        }
    }
}
