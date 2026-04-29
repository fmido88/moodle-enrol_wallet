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

namespace enrol_wallet\reportbuilder\local\entities;

use core\lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use enrol_wallet\local\wallet\balance as bal;

/**
 * Class balance.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class balance extends base {
    /**
     * balances objects.
     * @var bal[]
     */
    protected static $balances = [];

    /**
     * Entity title.
     * @return \core\lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('balance', 'enrol_wallet');
    }

    /**
     * Default tables needed by this entity.
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'enrol_wallet_balance',
        ];
    }

    #[\Override()]
    public function initialise(): static {
        $this->add_all();

        return $this;
    }

    /**
     * Add columns and filters.
     * @return void
     */
    protected function add_all(): void {
        $alias = $this->get_table_alias('enrol_wallet_balance');

        $recuclaus = $this->get_valid_balance_sql_claus();
        $totalclaus = $this->get_total_balance_sql_claus();

        $properties = [
            'refundable'    => "{$alias}.refundable",
            'nonrefundable' => "{$alias}.nonrefundable",
            'balance'       => "({$alias}.refundable + {$alias}.nonrefundable)",
            'freegift'      => "{$alias}.freegift",

            'totalbalance'       => "SELECT SUM(refundable + nonrefundable) $totalclaus",
            'totalnonrefundable' => "SELECT SUM(nonrefundable) $totalclaus",
            'totalrefundable'    => "SELECT SUM(refundable) $totalclaus",
            'totalfree'          => "SELECT SUM(freegift) $totalclaus",

            'validbalance'       => "SELECT SUM(refundable + nonrefundable) $recuclaus",
            'validnonrefundable' => "SELECT SUM(nonrefundable) $recuclaus",
            'validrefundable'    => "SELECT SUM(refundable) $recuclaus",
            'validfree'          => "SELECT SUM(freegift) $recuclaus",
        ];

        foreach ($properties as $property => $field) {
            $total = strpos($property, 'total') === 0;
            $valid = strpos($property, 'valid') === 0;

            $column = (new column(
                $property,
                new lang_string($property, 'enrol_wallet'),
                $this->get_entity_name()
            ))
            ->set_type(column::TYPE_FLOAT)
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_callback(static fn ($value) => format_float($value, 2, true, true));

            if ($total || $valid) {
                $field = "({$field})";
            }

            $column->add_field($field, $property);

            $this->add_column($column);

            $filter = new filter(
                number::class,
                $property,
                new lang_string($property, 'enrol_wallet'),
                $this->get_entity_name(),
                $field
            );
            $this->add_filter($filter);
        }
    }

    /**
     * Get the total balance select sub clause to sum all balances
     * should be prefixed with SELECT SUM({fieldname}).
     * @return string
     */
    protected function get_total_balance_sql_claus(): string {
        $alias = $this->get_table_alias('enrol_wallet_balance');

        return "FROM {enrol_wallet_balance} WHERE userid = {$alias}.userid";
    }

    /**
     * Get the valid balance for the row's category select sub clause to sum all valid balances
     * up to all parent categories.
     * should be prefixed with SELECT SUM({fieldname}).
     * @return string
     */
    protected function get_valid_balance_sql_claus(): string {
        $alias = $this->get_table_alias('enrol_wallet_balance');

        return "FROM {enrol_wallet_balance}
                WHERE userid = {$alias}.userid
                AND catid IN (
                    SELECT ancestor_id
                    FROM (
                        WITH RECURSIVE cat_tree AS (
                            SELECT id AS node_id, id AS ancestor_id, parent FROM {course_categories}
                            UNION ALL
                            SELECT ct.node_id, cc.id AS ancestor_id, cc.parent
                            FROM {course_categories} cc
                            INNER JOIN cat_tree ct ON cc.id = ct.parent
                        )
                        SELECT node_id, ancestor_id FROM cat_tree
                    ) tree
                    WHERE tree.node_id = {$alias}.catid
                    UNION
                    SELECT 0
                )";
    }
}
