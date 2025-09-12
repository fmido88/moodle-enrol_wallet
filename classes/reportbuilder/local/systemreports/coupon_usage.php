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

use core\context\system;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use enrol_wallet\reportbuilder\local\entities\coupon;
use enrol_wallet\reportbuilder\local\entities\couponsusage;

/**
 * Coupons usage report.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupon_usage extends system_report {
    /**
     * Anyone can view his own usage history, but only with capability
     * enrol/wallet:viewcoupon could view all.
     * @return bool
     */
    protected function can_view(): bool {
        return isloggedin() && !isguestuser();
    }

    /**
     * Initialise the report.
     * @return void
     */
    protected function initialise(): void {
        global $USER;
        $usageentity = new couponsusage();
        $usagealias  = $usageentity->get_table_alias('enrol_wallet_coupons_usage');
        $this->set_main_table('enrol_wallet_coupons_usage', $usagealias);

        $this->add_entity($usageentity);

        $this->add_base_fields("{$usagealias}.id, {$usagealias}.userid");

        $couponsentity = new coupon();
        $couponsalias  = $couponsentity->get_table_alias('enrol_wallet_coupons');

        $couponsentity->add_join("JOIN {enrol_wallet_coupons} {$couponsalias} ON {$couponsalias}.code = {$usagealias}.code");
        $this->add_entity($couponsentity);

        $userentity = new user();
        $useralias  = $userentity->get_table_alias('user');
        $userentity->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$usagealias}.userid");
        $this->add_entity($userentity);

        $systemcontext = system::instance();
        $canviewall    = has_capability('enrol/wallet:viewcoupon', $systemcontext);

        if (!$canviewall) {
            // User can view only his usage.
            $this->add_base_condition_simple("{$usagealias}.userid", $USER->id);
        }

        $columns = [
            'coupon:code',
            'user:fullnamewithlink',
            'coupon:type',
            'coupon:value',
            'coupon:category',
            'coupon:courses',
            'couponsusage:area',
            'couponsusage:timeused',
        ];
        $this->add_columns_from_entities($columns);

        if ($canviewall) {
            $this->add_filters_from_entities([
                'user:fullname',
            ]);
            $this->add_filters_from_entity($usageentity->get_entity_name());
            $this->add_filters_from_entity($couponsentity->get_entity_name());
        } else {
            $filters = [
                'coupon:code',
                'coupon:type',
                'coupon:value',
                'coupon:category',
                'coupon:courses',
                'couponsusage:area',
                'couponsusage:timeused',
            ];
            $this->add_filters_from_entities($filters);
        }

        $this->set_downloadable(true);
    }
}
