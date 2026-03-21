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

use core\output\renderable;
use core\output\templatable;

// Todo: enable a payment account, add it to configuration then
// check that the topup by payment option existed.
// add some bundles and check that bundles option existed
// same for teller men
// Assert that the data exported correctly and these info contained
// in the rendered string.
// also merge these tests.

/**
 * Tests for Wallet enrolment.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class topup_options_test extends \advanced_testcase {

    /**
     * Test topup_options export.
     * @covers ::export_for_template()
     */
    public function test_topup_options_export(): void {
        $this->resetAfterTest();

        $renderable = new topup_options();
        $this->assertInstanceOf(renderable::class, $renderable);
        $this->assertInstanceOf(templatable::class, $renderable);

        // Create a mock renderer.
        $renderer = helper::get_wallet_renderer();

        $result = $renderable->export_for_template($renderer);
        // Todo: Assert each property of the result.
        $renderer->render($renderable);
        // Todo: Assert that contains some keywords.
    }
}
