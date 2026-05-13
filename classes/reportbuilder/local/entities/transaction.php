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
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;

/**
 * Transaction entity.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction extends base {
    #[\Override()]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('transactions', 'enrol_wallet');
    }

    #[\Override()]
    protected function get_default_tables(): array {
        return ['enrol_wallet_transactions'];
    }

    #[\Override()]
    public function initialise(): self {
        global $DB;

        $fields = $this->get_fields();
        $alias = $this->get_table_alias('enrol_wallet_transactions');

        // Almost hidden column for sorting only.
        $column = (new column(
            'id',
            null,
            $this->get_entity_name()
        ))
            ->add_field("{$alias}.id")
            ->set_type(column::TYPE_INTEGER)
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->set_callback(fn () => '');
        $this->add_column($column);

        foreach ($fields as $field => $info) {
            $fieldsql = "{$alias}.{$field}";

            $column = (new column(
                $field,
                new lang_string(...$info['title']),
                $this->get_entity_name()
            ))
            ->add_field($fieldsql)
            ->set_type($info['type'])
            ->add_joins($this->get_joins())
            ->set_is_sortable($field !== 'descripe')
            ->add_callback($info['callback']);

            $this->add_column($column);

            $fieldsql = match ($field) {
                'descripe' => $DB->sql_compare_text($fieldsql),
                default    => $fieldsql
            };
            $filter = (new filter(
                $info['filter'],
                $field,
                new lang_string(...$info['title']),
                $this->get_entity_name(),
                $fieldsql
            ));

            if ($field === 'type') {
                $filter->set_options([
                    'credit' => new lang_string('credit', 'enrol_wallet'),
                    'debit'  => new lang_string('debit', 'enrol_wallet'),
                ]);
            }

            $this->add_filter($filter)->add_condition($filter);
        }

        return $this;
    }

    /**
     * Get the fields needed to be added in the report.
     * @return array{
     *  array{
     *      title: array,
     *      type: int,
     *      callback: callable,
     *      filter: string,
     *  },
     * }
     */
    protected function get_fields(): array {
        return [
            'timecreated' => [
                'title'    => ['time', ''],
                'type'     => column::TYPE_TIMESTAMP,
                'callback' => format::userdate(...),
                'filter'   => date::class,
            ],
            'amount'      => [
                'title'    => ['amount', 'enrol_wallet'],
                'type'     => column::TYPE_FLOAT,
                'callback' => self::format_float(...),
                'filter'   => number::class,
            ],
            'type'        => [
                'title'    => ['transaction_type', 'enrol_wallet'],
                'type'     => column::TYPE_TEXT,
                'callback' => static fn ($value) => new lang_string($value, 'enrol_wallet'),
                'filter'   => select::class,
            ],
            'balbefore'   => [
                'title'    => ['balance_before', 'enrol_wallet'],
                'type'     => column::TYPE_FLOAT,
                'callback' => self::format_float(...),
                'filter'   => number::class,
            ],
            'balance'     => [
                'title'    => ['balance_after', 'enrol_wallet'],
                'type'     => column::TYPE_FLOAT,
                'callback' => self::format_float(...),
                'filter'   => number::class,
            ],
            'norefund'    => [
                'title'    => ['nonrefundable', 'enrol_wallet'],
                'type'     => column::TYPE_FLOAT,
                'callback' => self::format_float(...),
                'filter'   => number::class,
            ],
            'descripe'    => [
                'title'    => ['description', ''],
                'type'     => column::TYPE_LONGTEXT,
                'callback' => fn ($value) => format_text($value),
                'filter'   => text::class,
            ],
        ];
    }

    /**
     * Just helper function to format float values used as callback.
     * @param  float  $value
     * @return string
     */
    protected static function format_float(float $value): string {
        return format_float($value, 2, true, true);
    }
}
