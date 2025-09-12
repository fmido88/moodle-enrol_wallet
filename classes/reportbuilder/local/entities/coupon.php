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

use core\output\pix_icon;
use core_course_category;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\category;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\action;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use enrol_wallet\local\coupons\coupons as couponshelper;
use enrol_wallet\local\urls\actions;
use enrol_wallet\local\urls\manage;
use enrol_wallet\reportbuilder\local\filters\coupons_course_selector;
use lang_string;

/**
 * Coupons entities for report.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupon extends base {
    /**
     * Cache courses data.
     * @var array
     */
    protected static $courses = [];

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
        $columns = $this->get_all_columns();

        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();

        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        return $this;
    }

    /**
     * Get all columns for the report.
     * @return column[]
     */
    public function get_all_columns() {
        $columns      = [];
        $canviewcode  = has_capability('enrol/wallet:viewcoupon', \context_system::instance());
        $couponsalias = $this->get_table_alias('enrol_wallet_coupons');

        $columns[] = (new column(
            'id',
            new lang_string('couponid', 'enrol_wallet'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_type(column::TYPE_INTEGER)
        ->add_field("{$couponsalias}.id")
        ->set_is_sortable(true);

        $columns[] = (new column(
            'code',
            new lang_string('coupon_code', 'enrol_wallet'),
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
        ->set_callback(function ($type) {
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
        ->set_callback(function ($catid) {
            if (empty($catid)) {
                return '';
            }

            $category = core_course_category::get($catid, IGNORE_MISSING, true);

            if (!$category) {
                return get_string('deleted') . " ($catid)";
            }

            return $category->get_nested_name(false);
        });

        $columns[] = (new column(
            'courses',
            new lang_string('courses'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_LONGTEXT)
        ->add_field("{$couponsalias}.courses")
        ->set_is_sortable(false)
        ->set_callback(function ($courses) {
            global $DB;
            if (empty($courses)) {
                return '';
            }

            $courses = array_filter(array_map('trim', explode(',', $courses)));
            $list    = [];

            foreach ($courses as $courseid) {
                if (!isset(self::$courses[$courseid])) {
                    $course = $DB->get_record('course', ['id' => $courseid], 'id,shortname,fullname');

                    if ($course) {
                        self::$courses[$courseid] = format_string($course->fullname);
                    } else {
                        self::$courses[$courseid] = get_string('deleted') . " ($courseid)";
                    }
                }
                $list[] = self::$courses[$courseid];
            }

            return implode('<br>', $list);
        });

        $columns[] = (new column(
            'maxusage',
            new lang_string('coupons_maxusage', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_INTEGER)
        ->add_field("{$couponsalias}.maxusage")
        ->set_is_sortable(true)
        ->set_callback(function ($maxusage) {
            if ($maxusage == 0) {
                return new lang_string('unlimited', 'enrol_wallet');
            }

            return $maxusage;
        });

        $columns[] = (new column(
            'maxperuser',
            new lang_string('coupons_maxperuser', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_INTEGER)
        ->add_field("{$couponsalias}.maxperuser")
        ->set_is_sortable(true)
        ->set_callback(function ($maxperuser) {
            if ($maxperuser == 0) {
                return new lang_string('maxavailable', 'enrol_wallet');
            }

            return $maxperuser;
        });

        $columns[] = (new column(
            'usetimes',
            new lang_string('coupon_t_usage', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_INTEGER)
        ->add_field("{$couponsalias}.usetimes")
        ->set_is_sortable(true);

        $columns[] = (new column(
            'validfrom',
            new lang_string('validfrom', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_field("{$couponsalias}.validfrom")
        ->set_is_sortable(true)
        ->set_callback(function ($validfrom, $row) {
            if ($validfrom == 0) {
                return new lang_string('any');
            }

            return format::userdate($validfrom, $row);
        });

        $columns[] = (new column(
            'validto',
            new lang_string('validto', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_field("{$couponsalias}.validto")
        ->set_is_sortable(true)
        ->set_callback(function ($validto, $row) {
            if ($validto == 0) {
                return new lang_string('any');
            }

            return format::userdate($validto, $row);
        });

        $columns[] = (new column(
            'lastuse',
            new lang_string('coupon_t_lastuse', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_field("{$couponsalias}.lastuse")
        ->set_is_sortable(true)
        ->set_callback(function ($lastuse, $row) {
            if ($lastuse == 0) {
                return new lang_string('never');
            }

            return format::userdate($lastuse, $row);
        });

        $columns[] = (new column(
            'timecreated',
            new lang_string('coupon_t_timecreated', 'enrol_wallet'),
            $this->get_entity_name()
        ))->add_joins($this->get_joins())
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_field("{$couponsalias}.timecreated")
        ->set_is_sortable(true)
        ->set_callback(function ($timecreated, $row) {
            return format::userdate($timecreated, $row);
        });

        return $columns;
    }

    /**
     * Get all available filters.
     * @return array<filter>
     */
    protected function get_all_filters() {
        $filters = [];
        $calias  = $this->get_table_alias('enrol_wallet_coupons');

        $filters[] = new filter(
            text::class,
            'code',
            new lang_string('coupon_code', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.code",
        );

        $filters[] = new filter(
            number::class,
            'value',
            new lang_string('coupon_value', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.value"
        );

        $filters[] = new filter(
            category::class,
            'category',
            new lang_string('category'),
            $this->get_entity_name(),
            "{$calias}.category"
        );

        $options = [];
        $coupontypes = array_flip(couponshelper::TYPES);
        foreach (couponshelper::get_coupons_options() as $type => $name) {
            $options[$coupontypes[$type]] = $name;
        }

        $filters[] = (new filter(
            select::class,
            'type',
            new lang_string('coupon_type', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.type"
        ))
        ->set_options($options);

        $filters[] = new filter(
            coupons_course_selector::class,
            'course',
            new lang_string('courses'),
            $this->get_entity_name(),
            "{$calias}.courses"
        );

        $filters[] = (new filter(
            number::class,
            'id',
            new lang_string('couponid', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.id"
        ))->set_limited_operators([number::ANY_VALUE, number::EQUAL_TO, number::RANGE]);

        $filters[] = new filter(
            number::class,
            'maxusage',
            new lang_string('coupons_maxusage', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.maxusage"
        );

        $filters[] = new filter(
            number::class,
            'maxperuser',
            new lang_string('coupons_maxperuser', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.maxperuser"
        );

        $filters[] = new filter(
            number::class,
            'usetimes',
            new lang_string('coupon_t_usage', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.usetimes"
        );

        $filters[] = new filter(
            date::class,
            'validfrom',
            new lang_string('validfrom', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.validfrom"
        );

        $filters[] = new filter(
            date::class,
            'validto',
            new lang_string('validto', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.validto"
        );

        $filters[] = new filter(
            date::class,
            'lastuse',
            new lang_string('coupon_t_lastuse', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.lastuse"
        );

        $filters[] = new filter(
            date::class,
            'timecreated',
            new lang_string('coupon_t_timecreated', 'enrol_wallet'),
            $this->get_entity_name(),
            "{$calias}.timecreated"
        );

        return $filters;
    }

    /**
     * Get all available action for this entity.
     * @return action[]
     */
    public function get_all_actions() {
        $actions = [];

        $systemcontext = \core\context\system::instance();
        if (has_capability('enrol/wallet:editcoupon', $systemcontext)) {
            $actions[] = new action(
                url: manage::EDIT_COUPON->url(['id' => ':id', 'sesskey' => sesskey()]),
                icon: new pix_icon('i/edit', get_string('edit')),
                title: new lang_string('edit')
            );
        }

        if (has_capability('enrol/wallet:deletecoupon', $systemcontext)) {
            $actions[] = new action(
                url: actions::DELETE_COUPON->url(['id' => ':id', 'sesskey' => sesskey()]),
                icon: new pix_icon('i/delete', get_string('delete')),
                title: new lang_string('delete')
            );
        }

        return $actions;
    }
}
