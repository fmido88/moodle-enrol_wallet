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

namespace enrol_wallet;

use context_course;
use enrol_wallet\local\config;
use moodle_page;
use moodle_url;
use settings_navigation;

/**
 * Tests for Wallet enrolment
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    /**
     * Test callback
     *
     * @covers ::enrol_wallet_myprofile_navigation()
     */
    public function test_enrol_wallet_myprofile_navigation(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/user/profile/lib.php");
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $tree = \core_user\output\myprofile\manager::build_tree($user, true);
        // For now no errors thrown.
    }

    /**
     * Test callback
     *
     * @covers ::enrol_wallet_extend_navigation_frontpage()
     * @return void
     */
    public function test_enrol_wallet_extend_navigation_frontpage(): void {
        global $PAGE, $SITE;
        $this->resetAfterTest();
        config::make()->frontpageoffers = true;

        $this->setAdminUser();
        // $page = new moodle_page();
        $PAGE->set_context(context_course::instance(SITEID));
        $PAGE->set_course($SITE);
        $PAGE->set_url(new moodle_url('/'));

        $nav = (new settings_navigation($PAGE));
        $nav->initialise();
        $node = $nav->find('extrawallet', null);
        $this->assertNotEmpty($node !== false);

        $node = $nav->find('enrol-wallet-offers', null);
        $this->assertNotEmpty($node);
    }
}
