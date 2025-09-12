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

namespace enrol_wallet\output;

use core_course_renderer;
use moodle_page;

/**
 * Class helper
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Get instance of enrol wallet renderer.
     * @param ?moodle_page $page
     * @return renderer
     */
    public static function get_wallet_renderer(?moodle_page $page = null): renderer {
        global $PAGE;
        if ($page === null) {
            $page = $PAGE;
        }
        return $page->get_renderer('enrol_wallet');
    }
    /**
     * Get instance of core_course_renderer.
     * @param ?moodle_page $page
     * @return core_course_renderer
     */
    public static function get_course_renderer(?moodle_page $page = null): core_course_renderer {
        global $PAGE;
        if ($page === null) {
            $page = $PAGE;
        }
        return $page->get_renderer('core', 'course');
    }
}
