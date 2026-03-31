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

namespace enrol_wallet\reportbuilder\local\filters;

use core_reportbuilder\local\filters\course_selector;
use core_reportbuilder\local\helpers\database;

/**
 * Filter for coupons courses.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupons_course_selector extends course_selector {
    /**
     * return the filter sql.
     * @param array $values
     * @return array<array|string> [$sql, $params]
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $fieldsql = $this->filter->get_field_sql();
        $courseids = $values["{$this->name}_values"] ?? [];
        if (empty($courseids)) {
            return ['', []];
        }

        $clauses = [];
        $params = [];
        foreach ($courseids as $cid) {
            $param = database::generate_param_name();
            if ($DB->sql_regex_supported()) {
                $clauses[] = "$fieldsql " . $DB->sql_regex() . " :$param";
                $params[$param] = "(^|,)" . preg_quote($cid) . "(,|$)";
            } else {
                $fieldname = $DB->sql_concat("','", $fieldsql, "','");
                $clauses[] = $DB->sql_like($fieldname, ":$param", false);
                $params[$param] = "%,$cid,%";
            }
        }

        return ['(' . implode(' AND ', $clauses) . ')', $params];
    }
}
