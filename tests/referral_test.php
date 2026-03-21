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
 * Tests for referral program functionality.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet;

use enrol_wallet\local\config;
use enrol_wallet\local\referral\code;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet\output\pages;
use enrol_wallet_plugin;

/**
 * Tests for referral program.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class referral_test extends \advanced_testcase {
    // Test for release referral gift is already existed in observer_test.

    /**
     * Test referral code generation and validation.
     * @covers \enrol_wallet\local\referral\code::get_code_record()
     * @covers \enrol_wallet\output\pages::process_referral_page()
     */
    public function test_generate_referral_code(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        ob_start();
        pages::process_referral_page($user->id);
        $output = ob_get_clean();

        $this->assertStringContainsStringIgnoringCase('not enabled', $output);
        $this->assertFalse($DB->record_exists(code::TABLE, ['userid' => $user->id]));

        $this->enable_referral();

        ob_start();
        pages::process_referral_page($user->id);
        $output = ob_get_clean();

        $this->assertStringContainsStringIgnoringCase('no past referrals', $output);
        $this->assertTrue($DB->record_exists(code::TABLE, ['userid' => $user->id]));

        $codes[] = code::get_code_record($user->id)->code;

        for ($i = 0; $i < 1000; $i++) {
            // Test that there is a unique code for each user.
            $user = $this->getDataGenerator()->create_user();
            $code = code::get_code_record($user->id)->code;
            $this->assertFalse(\in_array($code, $codes));
            $codes[] = $code;
        }
    }

    /**
     * Test user deletion cleans up referral data.
     * @covers ::pre_user_delete()
     */
    public function test_user_delete_cleans_referral(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        // Create referral record.
        $record = [
            'userid'      => $user->id,
            'code'        => 'TESTCODE',
            'usetimes'    => 0,
            'timecreated' => timedate::time(),
        ];
        $DB->insert_record('enrol_wallet_referral', $record);

        // Create hold gift.
        $hold = [
            'referrer'    => $user->id,
            'referred'    => 'someuser',
            'amount'      => 50,
            'timecreated' => timedate::time(),
            'released'    => 0,
        ];
        $DB->insert_record('enrol_wallet_hold_gift', $hold);

        // Verify records exist.
        $this->assertTrue($DB->record_exists('enrol_wallet_referral', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('enrol_wallet_hold_gift', ['referrer' => $user->id]));

        // Delete user - this should trigger enrol_wallet_pre_user_delete.
        delete_user($user);

        // Verify referral record is deleted.
        $this->assertFalse($DB->record_exists('enrol_wallet_referral', ['userid' => $user->id]));
    }

    /**
     * Helper function to enable referral.
     * @param  int   $maxusage
     * @param  float $amount
     * @param  array $plugins
     * @param  bool  $enable
     * @return void
     */
    protected function enable_referral(
        int $maxusage = 2,
        float $amount = 50,
        array $plugins = ['wallet'],
        bool $enable = true
    ) {
        $config = config::make();

        $config->referral_enabled = $enable;
        $config->referral_amount  = $amount;
        $config->referral_max     = $maxusage;
        $config->referral_plugins = implode(',', $plugins);
    }
}
