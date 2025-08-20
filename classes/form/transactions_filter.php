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

/** A filtration for the transaction table.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

use enrol_wallet\local\utils\catoptions;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Enrollment form.
 * @package enrol_wallet
 */
class transactions_filter extends \moodleform {

    /**
     * definition
     * @return void
     */
    public function definition() {
        global $CFG;
        $viewall = $this->_customdata['viewall'];
        $context = $this->_customdata['context'];
        $mform = $this->_form;

        $mform->addElement('header', 'filter', get_string('filter_transaction', 'enrol_wallet'));

        if ($viewall) {
            // Borrow potential users selectors from enrol_manual.
            $options = [
                'ajax'              => 'enrol_manual/form-potential-user-selector',
                'multiple'          => false,
                'courseid'          => SITEID,
                'enrolid'           => 0,
                'perpage'           => $CFG->maxusersperpage,
                'noselectionstring' => get_string('allusers', 'enrol_wallet'),
            ];

            if (class_exists('core_user/fields')) {
                $options['userfields'] = implode(',', \core_user\fields::get_identity_fields($context, true));
            } else {
                $options['userfields'] = implode(',', enrol_wallet_get_identity_fields($context, true));
            }

            $mform->addElement('autocomplete', 'userid', get_string('selectusers', 'enrol_manual'), [], $options);
        }

        // Adding starting and ending dates for transactions.
        $mform->addElement('date_time_selector', 'datefrom', get_string('datefrom', 'enrol_wallet'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'dateto', get_string('dateto', 'enrol_wallet'), ['optional' => true]);

        // Select specific type of transaction.
        $options = [
            ''       => 'All',
            'debit'  => 'debit',
            'credit' => 'credit',
        ];
        $mform->addElement('select', 'ttype', get_string('transaction_type', 'enrol_wallet'), $options);

        // Select specific value.
        $mform->addElement('text', 'value', get_string('value', 'enrol_wallet'));
        $mform->setType('value', PARAM_FLOAT);

        $catoptions = catoptions::get_all_categories_options();
        $mform->addElement('select', 'category', get_string('category'), $catoptions);

        // Transaction per page.
        $limits = [];
        for ($i = 25; $i <= 1000; $i = $i + 25) {
            $limits[$i] = $i;
        }

        $mform->addElement('select', 'pagesize', get_string('transaction_perpage', 'enrol_wallet'), $limits);

        $mform->addElement('submit', 'submit', get_string('submit'));
        $types = [
            'userid'   => PARAM_INT,
            'datefrom' => PARAM_INT,
            'dateto'   => PARAM_INT,
            'ttype'    => PARAM_TEXT,
            'value'    => PARAM_FLOAT,
            'category' => PARAM_INT,
            'pagesize' => PARAM_INT,
        ];
        foreach ($_GET as $key => $value) {
            if ($mform->elementExists($key) && isset($types[$key])) {
                if (is_array($value)) {
                    $array = clean_param_array($value, $types[$key]);
                    $time = make_timestamp($array['year'],
                                           $array['month'],
                                           $array['day'] ?? 1,
                                           $array['hour'] ?? 0,
                                           $array['minute'] ?? 0);
                    $mform->setDefault($key, $time);
                } else {
                    $mform->setDefault($key, clean_param($value, $types[$key]));
                }
            }
        }
    }
}
