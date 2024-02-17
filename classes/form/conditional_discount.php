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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

use moodleform;
use enrol_wallet\category\options;
/**
 * Class conditional_discount
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class conditional_discount extends moodleform {
    /**
     * definition
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $data = $this->_customdata;

        $mform->addElement('header', 'conditionaldiscount', get_string('conditionaldiscount', 'enrol_wallet'));

        $options = options::get_all_categories_options();
        $mform->addElement('select', 'category', get_string('category'), $options);

        $mform->addElement('text', 'cond', get_string('conditionaldiscount_condition', 'enrol_wallet'));
        $mform->setType('cond', PARAM_FLOAT);
        $mform->addHelpButton('cond', 'conditionaldiscount_condition', 'enrol_wallet');

        $mform->addElement('text', 'percent', get_string('conditionaldiscount_percent', 'enrol_wallet'));
        $mform->setType('percent', PARAM_FLOAT);
        $mform->addHelpButton('percent', 'conditionaldiscount_percent', 'enrol_wallet');

        $mform->addElement('date_time_selector', 'timefrom',
                            get_string('conditionaldiscount_timefrom', 'enrol_wallet'), ['optional' => true]);
        $mform->addHelpButton('timefrom', 'conditionaldiscount_timefrom', 'enrol_wallet');

        $mform->addElement('date_time_selector', 'timeto',
                            get_string('conditionaldiscount_timeto', 'enrol_wallet'), ['optional' => true]);
        $mform->addHelpButton('timefrom', 'conditionaldiscount_timeto', 'enrol_wallet');

        $mform->addElement('checkbox', 'have_bundle', get_string('addbundle', 'enrol_wallet'));
        $mform->addHelpButton('have_bundle', 'addbundle', 'enrol_wallet');

        $mform->addElement('float', 'bundle_value', get_string('bundle_value', 'enrol_wallet'));
        $mform->addHelpButton('bundle_value', 'bundle_value', 'enrol_wallet');
        $mform->hideIf('bundle_value', 'have_bundle');

        $options = [
            'enable_filemanagement' => false,
        ];
        $mform->addElement('editor', 'bundle_desc', get_string('bundle_desc', 'enrol_wallet'), '', $options);
        $mform->addHelpButton('bundle_desc', 'bundle_desc', 'enrol_wallet');
        $mform->setType('bundle_desc', PARAM_CLEANHTML);
        $mform->hideIf('bundle_desc', 'have_bundle');

        if (!empty($data['id'])) {
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
        }

        if ($data['edit']) {
            $mform->addElement('hidden', 'edit');
            $mform->setType('edit', PARAM_BOOL);

            $mform->setDefaults($_GET);

            $mform->addElement('submit', 'confirmedit', get_string('confirmedit', 'enrol_wallet'));
        } else {
            $mform->addElement('submit', 'add', get_string('add'));
        }
    }

    /**
     * Validation
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        if ($data['percent'] > 100 || $data['percent'] < 0) {
            $errors['percent'] = get_string('percent_error', 'enrol_wallet');
        }
        if (!empty($data['have_bundle'])) {
            if (empty($data['bundle_value']) || $data['bundle_value'] < $data['cond']) {
                $errors['bundle_value'] = get_string('bundle_value_error', 'enrol_wallet');
            }
        }
        return $errors;
    }
}
