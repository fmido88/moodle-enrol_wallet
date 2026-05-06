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

use enrol_wallet\local\discounts\discount\hook;

/**
 * Before applying discount, add extra discounts if needed.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('wallet', 'enrol', 'discount')]
#[\core\attribute\label('Apply extra discount before purchase an item using wallet')]
class before_discount {

    /**
     * Constructor.
     * @param hook $discount
     */
    public function __construct(
        /** @var hook */
        protected hook $discount
    ) {
    }

    /**
     * Return the discount helper class from which extra discounts can be added.
     * @return hook
     */
    public function get_discount_class(): hook {
        return $this->discount;
    }
}
