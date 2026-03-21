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

namespace enrol_wallet\hook;

use MoodleQuickForm;

/**
 * Extend the charger form to add extra elements or rules.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('wallet', 'enrol')]
#[\core\attribute\label('Adding elements in the credit form')]
class before_charger_form_definition {
    /**
     * Constructor for the hook.
     * @param MoodleQuickForm $mform
     * @param mixed $customdata
     */
    public function __construct(
        /** @var MoodleQuickForm The charger form. */
        public \MoodleQuickForm $mform,
        /** @var mixed The form custom data, usually array. */
        protected mixed $customdata = null
    ) {
    }
    /**
     * The charger form.
     * @return MoodleQuickForm
     */
    public function get_form(): \MoodleQuickForm {
        return $this->mform;
    }
    /**
     * Getter for form custom data.
     * @return mixed
     */
    public function get_custom_data(): mixed {
        return $this->customdata;
    }
}
