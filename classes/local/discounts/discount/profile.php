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

use enrol_wallet\local\config;
use enrol_wallet\local\entities\entity;

/**
 * Class profile.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile extends discount_base {
    #[\Override()]
    public function get_percentage_discount(): float {
        global $DB;
        $discount = 0;

        // Check if the discount according to custom profile field in enabled.
        if (!$fieldid = config::make()->discount_field) {
            return $discount;
        }

        // Check the data in the discount field.
        $data = $DB->get_field('user_info_data', 'data', ['userid' => $this->get_userid(), 'fieldid' => $fieldid]);

        if (empty($data)) {
            return $discount;
        }

        // If the user has free access to courses return 0 cost.
        if (stripos(strtolower($data), 'free') !== false) {
            $discount = 1;
            // If there is a word no in the data means no discount.
        } else if (stripos(strtolower($data), 'no') !== false) {
            $discount = 0;
        } else {
            // Get the integer from the data.
            preg_match('/\d+/', $data, $matches);

            if (isset($matches[0]) && \intval($matches[0]) <= 100) {
                // Cannot allow discount more than 100%.
                $discount = \intval($matches[0]) / 100;
            }
        }

        return max(min(1, $discount), 0) * 100;
    }

    #[\Override()]
    public static function is_available(entity $entity): bool {
        return !empty(config::make()->discount_field);
    }
}
