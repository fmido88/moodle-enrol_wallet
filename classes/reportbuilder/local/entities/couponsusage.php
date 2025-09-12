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

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\user;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use enrol_wallet\local\coupons\coupons as couponshelper;
use lang_string;

/**
 * Coupons usage.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class couponsusage extends base {
    /**
     * Ger entity title.
     * @return \core\lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('coupon_usage', 'enrol_wallet');
    }

    /**
     * Get default tables for this entity.
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'enrol_wallet_coupons_usage',
        ];
    }

    /**
     * Initialise the entity, called automatically when it is added to a report
     *
     * This is where entity defines all its columns and filters by calling:
     * - {@see add_column}
     * - {@see add_filter}
     * - etc
     *
     * @return couponsusage
     */
    public function initialise(): self {
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }

        foreach ($this->get_all_filters() as $filter) {
            $this->add_filter($filter);
        }

        return $this;
    }

    /**
     * Get all columns for this entity.
     * @return column[]
     */
    public function get_all_columns() {
        $columns = [];

        $cusagealias = $this->get_table_alias('enrol_wallet_coupons_usage');
        $columns[]   = (new column(
            'area',
            new lang_string('couponarea', 'enrol_wallet'),
            $this->get_entity_name()
        ))->set_type(column::TYPE_INTEGER)
        ->add_field("{$cusagealias}.area")
        ->add_joins($this->get_joins())
        ->set_is_sortable(true)
        ->set_callback(function (int $area) {
            return couponshelper::get_area_visible_name($area);
        });

        $columns[] = (new column(
            'timeused',
            new lang_string('couponusagetime', 'enrol_wallet'),
            $this->get_entity_name()
        ))->set_type(column::TYPE_TIMESTAMP)
        ->add_field("{$cusagealias}.timeused")
        ->add_joins($this->get_joins())
        ->set_is_sortable(true)
        ->set_callback(function ($time) {
            return userdate($time);
        });

        return $columns;
    }

    /**
     * Get all filters for this entity.
     * @return filter[]
     */
    public function get_all_filters() {
        $filters     = [];
        $cusagealias = $this->get_table_alias('enrol_wallet_coupons_usage');

        $filters[] = new filter(
            user::class,
            'userid',
            new lang_string('user'),
            $this->get_entity_name(),
            "{$cusagealias}.userid"
        );

        $filters[] = new filter(
            date::class,
            'timeused',
            new lang_string('couponusagetime', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$cusagealias}.timeused"
        );

        $areaoptions = [];

        foreach (couponshelper::AREAS as $str => $int) {
            $areaoptions[$int] = couponshelper::get_area_visible_name($str);
        }

        $filters[] = (new filter(
            select::class,
            'area',
            new lang_string('couponarea', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$cusagealias}.area",
        ))->set_options($areaoptions);

        return $filters;
    }
}
