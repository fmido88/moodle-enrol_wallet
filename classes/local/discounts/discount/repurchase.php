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
use enrol_wallet\local\entities\instance;

/**
 * Class repurchase.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repurchase extends discount_base {
    #[\Override()]
    public function get_percentage_discount(): float {
        global $DB;
        $userid = $this->get_userid();
        $instanceid = $this->entity->id;
        $discount = 0;

        if ($ue = $DB->get_record('user_enrolments', ['enrolid' => $instanceid, 'userid' => $userid])) {
            $config = config::make();

            if (!empty($ue->timeend) && $config->repurchase) {
                if ($first = $config->repurchase_firstdis) {
                    $discount = min($first / 100, 1);
                    $second = $config->repurchase_seconddis;
                    $timepassed = $ue->timemodified > $ue->timecreated + $ue->timeend - $ue->timestart;

                    if ($second && $ue->modifierid == $userid && $timepassed) {
                        $discount = max($second / 100, $discount);
                    }
                }
            }
        }

        return max(min($discount, 1), 0) * 100;
    }

    #[\Override()]
    public static function is_available(entity $entity): bool {
        return ($entity instanceof instance) && !empty(config::make()->repurchase);
    }
}
