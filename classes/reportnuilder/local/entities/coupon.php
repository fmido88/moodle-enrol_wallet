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

namespace enrol_wallet\reportnuilder\local\entities;

use core_course_category;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter, action};
use core_reportbuilder\local\filters\{date, select, text, autocomplete, number, user};
use enrol_wallet\coupons as couponshelper;
use lang_string;

/**
 * Class coupon.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupon extends base {
    /**
     * Entity title.
     * @return \core\lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('coupons', 'enrol_wallet');
    }

    /**
     * Default tables needed by this entity.
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'enrol_wallet_coupons',
        ];
    }

    /**
     * Initialize columns, filters and actions.
     * @return coupon
     */
    public function initialise(): self {
        // Todo.
        return $this;
    }

    /**
     * Get all columns for the report.
     * @return column[]
     */
    public function get_all_columns() {
        $columns      = [];
        $canviewcode  = has_capability('enrol/wallet:viewcoupon', \context_system::instance());
        $couponsalias = $this->get_table_alias('enrol_wallet_coupon');
        $columns[]    = (new column(
            'id',
            new lang_string('coupon_id', 'enrol_wallet'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_type(column::TYPE_INTEGER)
        ->add_field("{$couponsalias}.id")
        ->set_is_sortable(true);

        $columns[] = (new column(
            'code',
            new lang_string('couponcode', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_TEXT)
        ->add_field("{$couponsalias}.code")
        ->set_is_sortable(true)
        ->set_callback(function ($code) use ($canviewcode): string {
            if ($canviewcode) {
                return $code;
            }
            // Mask the coupon code if the user has no capability to view it.
            $length = strlen($code);
            $remain = substr($code, -1 * ceil($length / 3));

            return str_repeat('*', $length - strlen($remain)) . $remain;
        });

        $columns[] = (new column(
            'type',
            new lang_string('coupon_type', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_TEXT)
        ->add_field("{$couponsalias}.type")
        ->set_is_sortable(true)
        ->set_callback(function($type) {
            return couponshelper::get_type_visible_name($type);
        });

        $columns[] = (new column(
            'value',
            new lang_string('coupon_t_value', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_FLOAT)
        ->add_field("{$couponsalias}.value")
        ->set_is_sortable(true);

        $columns[] = (new column(
            'category',
            new lang_string('category'),
            $this->get_entity_name(),
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_INTEGER)
        ->add_field("{$couponsalias}.category")
        ->set_is_sortable(false)
        ->set_callback(function($catid) {
            if (empty($catid)) {
                return '';
            }

            $category = core_course_category::get($catid, IGNORE_MISSING, true);
            if (!$category) {
                return get_string('deletedcategory');
            }

            return $category->get_nested_name(false);
        });

        $columns[] = (new column(
            'courses',
            new lang_string('courses'),
            $this->get_entity_name()
        ));

        return $columns;
    }
}
