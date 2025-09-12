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
use core\lang_string;
use core_reportbuilder\system_report;
use enrol_wallet\reportbuilder\local\entities\coupon;
use stdClass;

/**
 * Class coupons.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupons extends system_report {
    /**
     * Validates access to view this report.
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('enrol/wallet:viewcoupon', system::instance());
    }

    /**
     * Initialise report.
     * @return void
     */
    protected function initialise(): void {
        $couponsentity = new coupon();
        $couponsentity->set_entity_name('coupon')
            ->set_entity_title(new lang_string('coupons', 'enrol_wallet'));

        $couponsalias = $couponsentity->get_table_alias('enrol_wallet_coupons');

        $this->set_main_table('enrol_wallet_coupons', $couponsalias);
        $this->add_entity($couponsentity);

        $this->add_base_fields("{$couponsalias}.id");
        $this->add_columns_from_entity($couponsentity->get_entity_name());
        $this->add_filters_from_entity($couponsentity->get_entity_name());

        $this->set_checkbox_toggleall(static function(stdClass $row) {
            return [$row->id, $row->id];
        });
        $actions = $couponsentity->get_all_actions();
        foreach ($actions as $action) {
            $this->add_action($action);
        }

        $this->set_downloadable(true);
    }
}
