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
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * This is really just a display for user that he has insufficient wallet ballance to enrol.
 */
class insuf_form extends \moodleform {


    /**
     * Form definition. Abstract method - always override!
     * @return void
     */
    public function definition() {
        global $CFG, $USER, $PAGE, $OUTPUT, $DB;
        $instance = $this->_customdata->instance;

        $this->_form->addElement('header', 'walletheader', $this->_customdata->header);
        $this->_form->addElement('html', '<span style="text-align: center;"><p>'.$this->_customdata->info.'</p></span>');
    }

}
