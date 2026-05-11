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

use enrol_wallet\local\entities\entity;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/enrol/wallet/lib.php');

/**
 * discount_base_test.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class discount_base_test extends \advanced_testcase {
    /**
     * Test original and discounted cost calculations.
     */
    public function test_get_original_and_discounted_cost(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $entity = new class ($course->id, 100, $user->id) extends entity {
            #[\Override()]
            public function get_name(): string {
                return 'Stub';
            }

            #[\Override()]
            public function get_context(): \context {
                return \context_course::instance($this->courseid);
            }

            #[\Override()]
            public static function get_coupon_area(): int {
                return 0;
            }
        };

        $discount = new class ($entity, 200.0) extends discount_base {
            #[\Override()]
            public static function is_available(entity $entity): bool {
                return true;
            }

            #[\Override()]
            public function get_percentage_discount(): float {
                return 25.0;
            }
        };

        $this->assertSame(200.0, $discount->get_original_cost());
        $this->assertSame(150.0, $discount->get_discounted_cost());
        $this->assertSame(50.0, $discount->get_absolute_discount());
        $this->assertSame($entity, $discount->get_entity());
        $this->assertSame((int)$user->id, $discount->get_userid());
    }

    /**
     * Test discounted cost remains original when discount is unavailable.
     */
    public function test_discount_not_available_returns_original_cost(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $entity = new class ($course->id, 101, $user->id) extends entity {
            #[\Override()]
            public function get_name(): string {
                return 'Stub Not Available';
            }

            #[\Override()]
            public function get_context(): \context {
                return \context_course::instance($this->courseid);
            }

            #[\Override()]
            public static function get_coupon_area(): int {
                return 0;
            }
        };

        $discount = new class ($entity, 120.0) extends discount_base {
            #[\Override()]
            public static function is_available(entity $entity): bool {
                return false;
            }

            #[\Override()]
            public function get_percentage_discount(): float {
                return 50.0;
            }
        };

        $this->assertSame(120.0, $discount->get_discounted_cost());
        $this->assertSame(0.0, $discount->get_absolute_discount());
    }

    /**
     * Test after_process is a no-op by default.
     */
    public function test_after_process_is_noop_by_default(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $entity = new class ($course->id, 102, $user->id) extends entity {
            #[\Override()]
            public function get_name(): string {
                return 'Stub Noop';
            }

            #[\Override()]
            public function get_context(): \context {
                return \context_course::instance($this->courseid);
            }

            #[\Override()]
            public static function get_coupon_area(): int {
                return 0;
            }
        };

        $discount = new class ($entity, 10.0) extends discount_base {
            #[\Override()]
            public static function is_available(entity $entity): bool {
                return true;
            }

            #[\Override()]
            public function get_percentage_discount(): float {
                return 0.0;
            }
        };

        $discount->after_process();
        $this->assertTrue(true);
    }
}
