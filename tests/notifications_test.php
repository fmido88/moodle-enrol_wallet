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
 * Wallet transactions notifications test.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;

use enrol_wallet\notifications;

/**
 * Wallet transactions notifications test.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications_test extends \advanced_testcase {
    /**
     * Test transaction_notifications
     * @covers ::transaction_notify()
     */
    public function test_transaction_notifications() {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        if (!enrol_is_enabled('wallet')) {
            $class = \core_plugin_manager::resolve_plugininfo_class('enrol');
            $class::enable_plugin('wallet', true);
        }
        $this->assertTrue(enrol_is_enabled('wallet'));

        $user = $this->getDataGenerator()->create_user();

        $data = [
            'userid' => $user->id,
            'type' => 'credit',
            'amount' => 150,
            'balbefore' => 200,
            'balance' => 350,
            'descripe' => '',
            'timecreated' => time(),
        ];
        $sink = $this->redirectMessages();
        notifications::transaction_notify($data);
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
        $a = (object)[
            'type' => 'credit',
            'amount' => 150,
            'before' => 200,
            'balance' => 350,
            'desc' => '',
            'time' => userdate($data['timecreated']),
        ];
        $messagebody = get_string('messagebody_credit', 'enrol_wallet', $a);
        $this->assertStringContainsString($messagebody, $messages[0]->fullmessage);
    }
}
