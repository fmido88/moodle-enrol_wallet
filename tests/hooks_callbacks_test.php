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
 * Hooks callbacks tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet;

use enrol_wallet\hooks_callbacks;
use enrol_wallet\local\config;
use enrol_wallet\local\utils\testing;
use enrol_wallet\local\wallet\balance_op;
use moodle_page;

/**
 * Hooks callbacks tests.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hooks_callbacks_test extends \advanced_testcase {

    /**
     * Test shouldnt method.
     * @covers ::shouldnt()
     */
    public function test_shouldnt(): void {
        global $CFG;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->setUser();
        // Test shouldnt with login check.
        $this->assertTrue(hooks_callbacks::shouldnt(true));
        $this->assertFalse(hooks_callbacks::shouldnt(false));

        $this->setUser($user);
        $this->assertFalse(hooks_callbacks::shouldnt(false));
        $this->assertFalse(hooks_callbacks::shouldnt(true));

        // Need upgrade.
        $oldhash = $CFG->allversionshash;
        $CFG->allversionshash = hash('sha256', 'needupgrade');
        $this->assertTrue(hooks_callbacks::shouldnt());

        $CFG->allversionshash = $oldhash;
        $this->assertFalse(hooks_callbacks::shouldnt());
    }

    /**
     * Test show_price method.
     * @covers ::show_price()
     */
    public function test_show_price(): void {
        global $PAGE;
        $this->resetAfterTest();

        config::make()->showprice = true;
        $renderer = $PAGE->get_renderer('core');
        $hook = new \core\hook\output\before_footer_html_generation($renderer);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        $endcode = $renderer->get_page()->requires->get_end_code();
        $this->assertStringContainsStringIgnoringCase('enrol_wallet/overlyprice', $endcode);
    }

    /**
     * Test low_balance_warning method.
     * @covers ::low_balance_warning()
     */
    public function test_low_balance_warning(): void {
        global $SESSION, $PAGE;
        $this->resetAfterTest();

        $renderer = $PAGE->get_renderer('core');
        $config = config::make();
        $config->lowbalancenotice = true;
        $config->noticecondition = 50;

        $user1 = $this->getDataGenerator()->create_user();
        $balanceop = new balance_op($user1->id);
        $balanceop->credit(100);

        $user2 = $this->getDataGenerator()->create_user();
        $balanceop = new balance_op($user2->id);
        $balanceop->credit(10);

        $this->setUser($user1);
        $hook = new \core\hook\output\before_standard_top_of_body_html_generation($renderer);
        hooks_callbacks::low_balance_warning($hook);
        $this->assertTrue(empty($SESSION->notifications));

        $this->setUser($user2);
        $hook = new \core\hook\output\before_standard_top_of_body_html_generation($renderer);
        hooks_callbacks::low_balance_warning($hook);
        $this->assertNotEmpty($SESSION->notifications);
        $this->assertStringContainsStringIgnoringCase('10', reset($SESSION->notifications)->message);
    }

    /**
     * Test primary_navigation_tabs method.
     * @covers ::primary_navigation_tabs()
     */
    public function test_primary_navigation_tabs(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        config::make()->mywalletnav = true;
        config::make()->offers_nav = true;
        $page = new moodle_page();
        $primary = new \core\navigation\views\primary($page);
        $hook = new \core\hook\navigation\primary_extend($primary);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        // Reached with no errors.
    }
}
