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

use enrol_wallet\hook\before_discount;
use enrol_wallet\local\utils\discount;
use Throwable;

/**
 * Discounts added by hook before calculation of cost.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook extends discount_base {
    use discount;

    /**
     * The values of discounts added by different components.
     * @var array
     */
    protected array $values = [];

    /**
     * Array of post process actions.
     * @var callable[]
     */
    protected array $postprocess = [];

    #[\Override()]
    public function get_percentage_discount(): float {
        $hook = new before_discount($this);

        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        return match ($this->entity->get_behavior()) {
            self::const('max') => $this->calculate_max_discount($this->values, true),
            self::const('sum') => $this->calculate_sum_discount($this->values, true),
            self::const('seq') => $this->calculate_sequential_discount($this->values, true),
        };
    }

    #[\Override()]
    public static function is_available(\enrol_wallet\local\entities\entity $entity): bool {
        return true;
    }

    /**
     * Add a single discount value to the list of discounts.
     * @param  float $discount
     * @return void
     */
    public function add_discount(float $discount): void {
        if ($discount >= 100 || $discount < 0) {
            PHPUNIT_TEST || debugging("Invalid discount value $discount it should be between 0 and 100");

            return;
        }
        $this->values[] = $discount;
    }

    /**
     * Return the current discounts values added by the hook.
     * @return array
     */
    public function get_discounts(): array {
        return $this->values;
    }

    /**
     * Override the discounts values with a new list.
     * @param  array $values
     * @return void
     */
    public function set_discounts(array $values): void {
        $this->values = [];

        foreach ($values as $value) {
            $this->add_discount($value);
        }
    }

    /**
     * Add a callback to be called after purchase is done to display notice or check
     * coupon is used or something.
     * The callback function with no arguments and void return.
     * @param  callable $callback
     * @return void
     */
    public function add_post_purchase_callback(callable $callback): void {
        $this->postprocess[] = $callback;
    }

    /**
     * Process callback added by purchases.
     * @return void
     */
    public function after_process(): void {
        foreach ($this->postprocess as $callback) {
            try {
                \call_user_func($callback);
            } catch (Throwable $e) {
                PHPUNIT_TEST || debugging($e->getMessage());
            }
        }
    }
}
