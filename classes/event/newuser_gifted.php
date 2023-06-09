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
class newuser_gifted extends \core\event\base {

    // For more information about the Events API, please visit:
    // https://docs.moodle.org/dev/Event_2.
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'enrol_wallet_transactions';
    }

    /**
     * Returns localized general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_transactions', 'enrol_wallet');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $a = new \stdClass;
        $a->relateduserid = $this->relateduserid;
        $a->charger = $this->userid;
        $a->amount = $this->other['amount'];
        $a->refundable = $this->other['refundable'];
        $a->reason = $this->other['desc'];
        $type = $this->other['type'];
        if ($type == 'debit') {
            return get_string('event_transaction_debit_description', 'enrol_wallet', $a);
        } else if ($type == 'credit') {
            return get_string('event_transaction_credit_description', 'enrol_wallet', $a);
        } else {
            return null;
        }
    }
}
