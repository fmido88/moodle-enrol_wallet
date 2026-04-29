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

namespace enrol_wallet\reportbuilder\local\systemreports;

use core\lang_string;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use enrol_wallet\reportbuilder\local\entities\transaction;

/**
 * Class transactions.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transactions extends system_report {
    #[\Override()]
    protected function can_view(): bool {
        return isloggedin() && !isguestuser();
    }

    #[\Override()]
    protected function initialise(): void {
        global $USER;
        $tentity = new transaction();
        $talias = $tentity->get_table_alias('enrol_wallet_transactions');
        $this->set_main_table('enrol_wallet_transactions', $talias);

        $this->add_entity($tentity);

        $this->add_base_fields("{$talias}.id, {$talias}.userid, {$talias}.category");

        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $userentity->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$talias}.userid");
        $this->add_entity($userentity);

        $canviewall = has_capability('enrol/wallet:transaction', \core\context\system::instance());

        // User can view only his usage.
        $canviewall || $this->add_base_condition_simple("{$talias}.userid", $USER->id);

        $categoryentity = new \core_course\reportbuilder\local\entities\course_category();
        $catalias = $categoryentity->get_table_alias('course_categories');
        $categoryentity->add_join("LEFT JOIN {course_categories} {$catalias} ON {$catalias}.id = {$talias}.category");
        $this->add_entity($categoryentity);

        $categoryentity->get_column('namewithlink')->add_callback(function ($name) {
            if (empty($name)) {
                return new lang_string('site');
            }

            return $name;
        });

        $this->add_column_from_entity($tentity->get_entity_name() . ':id');
        !$canviewall || $this->add_column_from_entity($userentity->get_entity_name() . ':fullnamewithlink');
        $this->add_column_from_entity($categoryentity->get_entity_name() . ':namewithlink');
        $this->add_columns_from_entity($tentity->get_entity_name(), [], ['id']);

        !$canviewall || $this->add_filter_from_entity($userentity->get_entity_name() . ':userselect');
        $this->add_filter_from_entity($categoryentity->get_entity_name() . ':name');
        $this->add_filters_from_entity($tentity->get_entity_name());

        $this->add_condition_from_entity($userentity->get_entity_name() . ':userselect');

        $this->set_downloadable(true);
        $this->set_initial_sort_column($tentity->get_entity_name() . ':id', SORT_DESC);
    }
}
