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
 * Tests for Section entity.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\entities;

use enrol_wallet\local\utils\timedate;
use stdClass;

/**
 * Tests for Section entity.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class section_test extends \advanced_testcase {
    /**
     * Test Section entity instantiation with course and section number.
     * @covers ::__construct()
     */
    public function test_section_instantiation(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $sectionid = $this->get_section_from_course($course->id);

        $section = new section($sectionid);

        $this->assertInstanceOf('enrol_wallet\local\entities\section', $section);
    }

    /**
     * Test get_course_id method.
     * @covers ::get_course_id()
     */
    public function test_get_course_id(): void {
        $this->resetAfterTest();

        $course    = $this->getDataGenerator()->create_course();
        $sectionid = $this->get_section_from_course($course->id);

        $section = new section($sectionid);

        $this->assertEquals($course->id, $section->get_course_id());
    }

    /**
     * Test get_name method.
     * @covers ::get_name()
     */
    public function test_get_name(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $sectionid = $this->get_section_from_course($course->id);

        $section = new section($sectionid);
        $name    = $section->get_name();

        // Should return a string (General or similar).
        $this->assertIsString($name);
    }

    /**
     * Test get_context method.
     * @covers ::get_context()
     */
    public function test_get_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $sectionid = $this->get_section_from_course($course->id);

        $section = new section($sectionid);

        $context = $section->get_context();

        $this->assertEquals($context->instanceid, $course->id);
        $this->assertInstanceOf('context_course', $context);
    }

    /**
     * Return a section id from a course.
     * @param  int $courseid
     * @return int
     */
    private function get_section_from_course(int $courseid): int {
        global $DB;
        $records = $DB->get_records('course_sections', ['course' => $courseid]);

        if (!empty($records)) {
            return reset($records)->id;
        }

        $section               = new stdClass();
        $section->course       = $courseid;
        $section->name         = 'New section for test';
        $section->summary      = '';
        $section->timemodified = timedate::time();

        return $DB->insert_record('course_sections', $section);
    }
}
