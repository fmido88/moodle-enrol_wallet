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

namespace enrol_wallet\form;

use enrol_wallet\util\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * Class override
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class override extends moodleform {
    /**
     * Form definition.
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $data = $this->_customdata;
        $instanceid = $data['instanceid'];

        form::add_user_auto_complete_selection($mform, 'users', multi: true);

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setConstant('instanceid', $instanceid);

        $this->add_action_buttons();
        $this->set_display_vertical();
    }

    /**
     * Validate override form
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        return $errors;
    }
}
