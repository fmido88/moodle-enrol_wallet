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

namespace enrol_wallet\local\discounts;

use MoodleQuickForm;
use stdClass;

/**
 * Class geo_location_offer
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class geo_location_offer extends offer_item {
    #[\Override()]
    public static function key(): string {
        return 'geo';
    }

    #[\Override()]
    public function get_description(bool $availableonly = false): ?string {
        return null;
    }

    #[\Override()]
    public function validate_offer(): bool {
        return true;
    }

    #[\Override()]
    public static function get_visible_name(): string {
        return get_string('offers_location_based', 'enrol_wallet');
    }

    #[\Override()]
    public static function add_form_element(MoodleQuickForm $mform, int $i, int $courseid): void {

    }

    #[\Override()]
    public static function validate_submitted_offer(stdClass $offer, int $i, array &$errors): void {

    }

    #[\Override()]
    public static function is_available(): bool {
        return false;
    }
}
