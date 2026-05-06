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

namespace enrol_wallet\local\discounts\discount;

use enrol_wallet\local\discounts\offers as offers_base;
use enrol_wallet\local\entities\entity;
use enrol_wallet\local\entities\instance;

/**
 * Class offers
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offers extends discount_base {
    #[\Override()]
    public function get_percentage_discount(): float {
        $offers   = new offers_base($this->entity->instance, $this->get_userid());
        $discount = 0;

        $discount = match($this->entity->get_behavior()) {
            entity::B_SUM => $offers->get_sum_discounts(),
            entity::B_MAX => $offers->get_max_valid_discount(),
            entity::B_SEQ => $offers->get_seq_discounts(),
        };

        return max(min(100, $discount), 0);
    }

    #[\Override()]
    public static function is_available(entity $entity): bool {
        return $entity instanceof instance;
    }
}
