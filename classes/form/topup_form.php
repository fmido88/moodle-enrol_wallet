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

/** The form that able the user to topup their wallet using payment gateways.
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * The form that able the user to topup their wallet using payment gateways.
 */
class topup_form extends \moodleform {

    /**
     * Form definition. Abstract method - always override!
     * @return void
     */
    public function definition() {

        $instance = $this->_customdata->instance;
        $mform = $this->_form;
        $mform->addElement('text', 'value', get_string('topupvalue', 'enrol_wallet'));
        $mform->setType('value', PARAM_NUMBER);
        $mform->addHelpButton('value', 'topupvalue', 'enrol_wallet');

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $instance->courseid);

        $mform->addElement('hidden', 'currency');
        $mform->setType('currency', PARAM_INT);
        $mform->setDefault('currency', $instance->currency);

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setDefault('instanceid', $instance->id);

        $mform->addElement('hidden', 'account');
        $mform->setType('account', PARAM_INT);
        $mform->setDefault('account', $instance->customint1);

        $this->add_action_buttons(false, 'Apply');
    }

}
