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

use enrol_wallet\local\restriction\overrides;
use enrol_wallet\local\utils\testing;

/**
 * Tests for Wallet enrolment.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class overrides_test extends \advanced_testcase {
    /**
     * Test override an instance.
     * @covers ::override_instance()
     * @return void
     */
    public function test_override_instance(): void {
        $this->resetAfterTest();
        $instance = testing::get_generator()->create_instance();
        $user     = $this->getDataGenerator()->create_user();
        $cohort   = $this->getDataGenerator()->create_cohort();

        $overridden = overrides::override_instance($instance->id, $user->id);
        $this->assertTrue($overridden);

        $overridden = overrides::override_instance($instance->id, $cohort->id, true);
        $this->assertTrue($overridden);
    }
}
