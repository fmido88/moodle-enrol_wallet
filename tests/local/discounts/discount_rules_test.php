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

use enrol_wallet\local\config;
use enrol_wallet\local\utils\testing;
use enrol_wallet\local\utils\timedate;

/**
 * Tests for discount_rules class.
 *
 * @covers     \enrol_wallet\local\discounts\discount_rules
 * @package    enrol_wallet
 * @category   test
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class discount_rules_test extends \advanced_testcase {

    /**
     * Test get_current_discount_rules method.
     * @covers ::get_current_discount_rules()
     */
    public function test_get_current_discount_rules(): void {
        $this->resetAfterTest();
        global $DB;

        // Enable conditional discounts.
        $config = config::make();
        $config->conditionaldiscount_apply = 1;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        // Test 1: No rules exist - should return empty array.
        $rules = discount_rules::get_current_discount_rules();
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 2: Create a rule with valid time range.
        $rule1 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond'     => 100,
            'percent'  => 10,
            'timefrom' => 0,
            'timeto'   => 0,
        ]);
        $rules = discount_rules::get_current_discount_rules();
        $this->assertCount(1, $rules);
        $this->assertEquals($initialcount + 1, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 3: Create multiple rules at site level.
        $rule2 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 200,
            'percent' => 15,
            'timefrom' => 0,
            'timeto' => 0,
        ]);
        $rule3 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 500,
            'percent' => 20,
            'timefrom' => 0,
            'timeto' => 0,
        ]);
        $rules = discount_rules::get_current_discount_rules();
        $this->assertCount(3, $rules);

        // Test 4: Create a rule for a specific category.
        $category = $this->getDataGenerator()->create_category();
        $rule4 = testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 50,
            'percent' => 5,
            'timefrom' => 0,
            'timeto' => 0,
        ]);

        // Test 5: Get rules for specific category - should include category rules.
        $rules = discount_rules::get_current_discount_rules($category->id);
        $this->assertCount(1, $rules);

        // Test 6: Get rules for non-existent category - should return empty.
        $rules = discount_rules::get_current_discount_rules(9999);
        $this->assertCount(0, $rules);

        // Test 7: Create rule with future time - should not be returned.
        $future = timedate::time() + 1000;
        $rule5 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 300,
            'percent' => 25,
            'timefrom' => $future,
            'timeto' => $future + 1000,
        ]);
        $rules = discount_rules::get_current_discount_rules();
        $this->assertCount(3, $rules); // Should still be 3, excluding future rule.

        // Test 8: Create rule with past time - should not be returned.
        $past = timedate::time() - 1000;
        $rule6 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 400,
            'percent' => 30,
            'timefrom' => $past - 1000,
            'timeto' => $past,
        ]);
        $rules = discount_rules::get_current_discount_rules();
        $this->assertCount(3, $rules); // Should still be 3, excluding past rule.

        // Test 9: Create rule that's currently valid (within time range).
        $now = timedate::time();
        $rule7 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 600,
            'percent' => 35,
            'timefrom' => $now - 100,
            'timeto' => $now + 100,
        ]);
        $rules = discount_rules::get_current_discount_rules();
        $this->assertCount(4, $rules); // Should include the new valid rule.

        // Test 10: Verify rules are sorted by cond DESC, percent DESC.
        $rules = discount_rules::get_current_discount_rules(0);
        $values = array_values($rules);
        for ($i = 0; $i < count($values) - 1; $i++) {
            $this->assertTrue(
                $values[$i]->cond >= $values[$i + 1]->cond,
                "Rules should be sorted by cond DESC"
            );
        }
    }

    /**
     * Test get_all_available_discount_rules method.
     * @covers ::get_all_available_discount_rules()
     */
    public function test_get_all_available_discount_rules(): void {
        $this->resetAfterTest();
        global $DB;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        // Test 1: When disabled - should return empty array.
        $config = config::make();
        $config->conditionaldiscount_apply = 0;

        $rules = discount_rules::get_all_available_discount_rules();
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 2: When enabled but no rules - should return empty array.
        $config->conditionaldiscount_apply = 1;
        $rules = discount_rules::get_all_available_discount_rules();
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);

        // Test 3: Create rules and verify they're returned.
        $rule1 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 10,
        ]);
        $rule2 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 200,
            'percent' => 15,
        ]);
        $category = $this->getDataGenerator()->create_category();
        $rule3 = testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 50,
            'percent' => 5,
        ]);

        $rules = discount_rules::get_all_available_discount_rules();
        $this->assertCount(3, $rules);

        // Test 4: Verify sorting - category ASC, cond DESC, percent DESC.
        $values = array_values($rules);
        for ($i = 0; $i < count($values) - 1; $i++) {
            $this->assertTrue(
                $values[$i]->category <= $values[$i + 1]->category,
                "Rules should be sorted by category ASC"
            );
        }
    }

    /**
     * Test get_all_categories_with_discounts method.
     * @covers ::get_all_categories_with_discounts()
     */
    public function test_get_all_categories_with_discounts(): void {
        $this->resetAfterTest();
        global $DB;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        // Test 1: No rules - should return empty array.
        $categories = discount_rules::get_all_categories_with_discounts(true);
        $this->assertIsArray($categories);
        $this->assertEmpty($categories);
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 2: Create site-level rule (category = 0).
        $rule1 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 10,
        ]);
        $categories = discount_rules::get_all_categories_with_discounts(true);
        $this->assertCount(1, $categories);
        $this->assertArrayHasKey(0, $categories);

        // Test 3: Create rules for different categories.
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();

        $rule2 = testing::get_generator()->create_discount_rule([
            'category' => $cat1->id,
            'cond' => 50,
            'percent' => 5,
        ]);
        $rule3 = testing::get_generator()->create_discount_rule([
            'category' => $cat2->id,
            'cond' => 75,
            'percent' => 8,
        ]);

        $categories = discount_rules::get_all_categories_with_discounts(true);
        $this->assertCount(3, $categories);
        $this->assertArrayHasKey(0, $categories);
        $this->assertArrayHasKey($cat1->id, $categories);
        $this->assertArrayHasKey($cat2->id, $categories);

        // Test 4: Test with $current = false - should return all categories.
        $categories = discount_rules::get_all_categories_with_discounts(false);
        $this->assertCount(3, $categories);
    }

    /**
     * Test get_the_rest method.
     * @covers ::get_the_rest()
     */
    public function test_get_the_rest(): void {
        $this->resetAfterTest();
        global $DB;

        // Enable conditional discounts.
        $config = config::make();
        $config->conditionaldiscount_apply = 1;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        // Test 1: No rules - should return [null, null].
        $result = discount_rules::get_the_rest(100);
        $this->assertEquals([null, null], $result);
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 2: Create rules but amount doesn't meet condition.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 200,
            'percent' => 10,
        ]);
        $result = discount_rules::get_the_rest(100);
        $this->assertEquals([null, null], $result);

        // Test 3: Amount meets condition - should return rest and condition.
        $result = discount_rules::get_the_rest(250);
        $this->assertNotNull($result[0]); // Rest.
        $this->assertNotNull($result[1]); // Condition.

        // Test 4: Multiple rules - should return best discount.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 5,
        ]);
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 500,
            'percent' => 20,
        ]);
        $result = discount_rules::get_the_rest(600);
        $this->assertNotNull($result[0]);
        $this->assertEquals(500, $result[1]); // Should use the 20% discount (condition 500).

        // Test 5: Discount close to 100% (max allowed is 99.99).
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 50,
            'percent' => 99.99, // Maximum allowed value.
        ]);
        $result = discount_rules::get_the_rest(100);
        $this->assertNotNull($result[0]);

        // Test 6: Percent equals 100 - should skip (not acceptable).
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 30,
            'percent' => 99.9,
        ]);
        $result = discount_rules::get_the_rest(50);
        $this->assertNotNull($result[0]);

        // Test 7: Test with category.
        $category = $this->getDataGenerator()->create_category();
        testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 100,
            'percent' => 15,
        ]);
        $result = discount_rules::get_the_rest(150, $category->id);
        $this->assertNotNull($result[0]);

        // Test 8: When disabled - should return [null, null].
        $config->conditionaldiscount_apply = 0;
        $result = discount_rules::get_the_rest(100);
        $this->assertEquals([null, null], $result);
    }

    /**
     * Test get_the_before method.
     * @covers ::get_the_before()
     */
    public function test_get_the_before(): void {
        $this->resetAfterTest();
        global $DB;

        // Enable conditional discounts and create rules first.
        $config = config::make();
        $config->conditionaldiscount_apply = 1;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        $this->assertEquals(0, $initialcount);

        // Create rule FIRST before testing.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 10,
        ]);

        // Test 1: With discount rule - amount meets condition (100 >= 100), applies 10% discount.
        // Formula: amount * (1 - discount/100) = 100 * 0.9 = 90.
        $result = discount_rules::get_the_before(100);
        $this->assertEquals($result, 90.0);

        // Test 2: With explicit discount parameter.
        $result = discount_rules::get_the_before(100, 0, 20);
        // Formula: amount * (1 - 20/100) = 100 * 0.8 = 80.
        $this->assertEquals($result, 80.0);

        // Test 3: With higher discount.
        $result = discount_rules::get_the_before(200, 0, 50);
        // Formula: 200 * 0.5 = 100.
        $this->assertEquals($result, 100.0);

        // Test 4: With zero discount.
        $result = discount_rules::get_the_before(99, 0, 0);
        $this->assertEquals($result, 99.0);

        // Test 5: With 100% discount.
        $result = discount_rules::get_the_before(100, 0, 100);
        $this->assertEquals($result, 0.0);

        // Test 6: Test with category.
        $category = $this->getDataGenerator()->create_category();
        testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 50,
            'percent' => 25,
        ]);
        $result = discount_rules::get_the_before(100, $category->id);
        // Formula: 100 * (1 - 25/100) = 100 * 0.75 = 75.
        $this->assertEquals($result, 75.0);

        // Test 7: Large amount.
        $result = discount_rules::get_the_before(10000, 0, 33.33);
        $this->assertEquals(round($result, 2), 6667.0);

        // Test 8: Amount doesn't meet condition (50 < 100) - should return original amount.
        $result = discount_rules::get_the_before(50); // Condition is 100, discount = 0.
        $this->assertEquals($result, 50.0);

        // Verify no extra records were created.
        $this->assertEquals($initialcount + 2, $DB->count_records('enrol_wallet_cond_discount'));
    }

    /**
     * Test get_the_after method.
     * @covers ::get_the_after()
     */
    public function test_get_the_after(): void {
        $this->resetAfterTest();
        global $DB;

        // Enable conditional discounts and create rules first.
        $config = config::make();
        $config->conditionaldiscount_apply = 1;
        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        $this->assertEquals(0, $initialcount);

        // Create rule FIRST before testing.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond'     => 100,
            'percent'  => 10,
        ]);

        // Test 1: With discount rule - amount does NOT meet condition (90 < 100), discount = 0.
        // Returns original amount: 90.
        $result = discount_rules::get_the_after(90);
        $this->assertEquals($result, 90.0);

        // Test 2: With explicit discount parameter.
        $result = discount_rules::get_the_after(80, 0, 20);
        // Formula: 80 / (1 - 20/100) = 80 / 0.8 = 100.
        $this->assertEquals($result, 100.0);

        // Test 3: With higher discount.
        $result = discount_rules::get_the_after(50, 0, 50);
        // Formula: 50 / 0.5 = 100.
        $this->assertEquals($result, 100.0);

        // Test 4: With zero discount.
        $result = discount_rules::get_the_after(99, 0, 0);
        $this->assertEquals($result, 99.0);

        // Test 5: With 100% discount - special case.
        $result = discount_rules::get_the_after(0, 0, 100);
        // 0 / (1 - 100/100) = 0 / 0 = 0.
        $this->assertEquals($result, 0.0);

        // Test 6: Test with category.
        $category = $this->getDataGenerator()->create_category();
        testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 50,
            'percent' => 25,
        ]);
        $result = discount_rules::get_the_after(75, $category->id);
        // Formula: 75 / (1 - 25/100) = 75 / 0.75 = 100.
        $this->assertEquals($result, 100.0);

        // Test 7: Large amount with decimal discount.
        $result = discount_rules::get_the_after(6667, 0, 33.33);
        $this->assertEqualsWithDelta(10000.0, $result, 0.01);

        // Test 8: Amount doesn't meet condition (50 < 100) - returns original amount.
        $result = discount_rules::get_the_after(50); // Condition is 100, discount = 0.
        $this->assertEquals($result, 50.0);

        // Verify no extra records were created.
        $this->assertEquals($initialcount + 2, $DB->count_records('enrol_wallet_cond_discount'));
    }

    /**
     * Test get_applied_discount method.
     * @covers ::get_applied_discount()
     */
    public function test_get_applied_discount(): void {
        $this->resetAfterTest();
        global $DB;

        // Enable conditional discounts.
        $config = config::make();
        $config->conditionaldiscount_apply = 1;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        // Test 1: No rules - should return 0.
        $result = discount_rules::get_applied_discount(100, 0);
        $this->assertEquals(0.0, $result);
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 2: Amount doesn't meet condition.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 200,
            'percent' => 10,
        ]);
        $result = discount_rules::get_applied_discount(100, 0);
        $this->assertEquals(0.0, $result);

        // Test 3: Amount meets condition.
        $result = discount_rules::get_applied_discount(250, 0);
        $this->assertEquals(10.0, $result);

        // Test 4: Multiple rules - should return highest discount.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 5,
        ]);
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 500,
            'percent' => 20,
        ]);
        $result = discount_rules::get_applied_discount(600, 0);
        $this->assertEquals(20.0, $result);

        // Test 5: Amount meets multiple conditions - should return highest.
        $result = discount_rules::get_applied_discount(300, 0);
        $this->assertEquals(10.0, $result); // 10% (cond 200) > 5% (cond 100).

        // Test 6: Test with category.
        $category = $this->getDataGenerator()->create_category();
        testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 50,
            'percent' => 15,
        ]);
        $result = discount_rules::get_applied_discount(100, $category->id);
        $this->assertEquals(15.0, $result);

        // Test 7: Category with no rules - should return 0.
        $category2 = $this->getDataGenerator()->create_category();
        $result = discount_rules::get_applied_discount(100, $category2->id);
        $this->assertEquals(0.0, $result);

        // Test 8: Exact condition match.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 25,
        ]);
        $result = discount_rules::get_applied_discount(100, 0);
        $this->assertEquals(25.0, $result);
    }

    /**
     * Test get_the_discount_line method.
     * @covers ::get_the_discount_line()
     */
    public function test_get_the_discount_line(): void {
        $this->resetAfterTest();
        global $DB;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        // Test 1: No rules - should return empty string.
        $result = discount_rules::get_the_discount_line(0);
        $this->assertIsString($result);
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 2: With rules - should return rendered HTML.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 10,
        ]);
        $result = discount_rules::get_the_discount_line(0);
        $this->assertIsString($result);
        // The output should contain HTML elements.

        // Test 3: With category.
        $category = $this->getDataGenerator()->create_category();
        testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 50,
            'percent' => 5,
        ]);
        $result = discount_rules::get_the_discount_line($category->id);
        $this->assertIsString($result);
    }

    /**
     * Test get_bundles_records method.
     * @covers ::get_bundles_records()
     */
    public function test_get_bundles_records(): void {
        $this->resetAfterTest();
        global $DB;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_cond_discount');

        // Test 1: No bundles - should return empty array.
        $bundles = discount_rules::get_bundles_records();
        $this->assertIsArray($bundles);
        $this->assertEmpty($bundles);
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_cond_discount'));

        // Test 2: Create non-bundle rules - should not be returned.
        testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 100,
            'percent' => 10,
            'bundle' => null,
        ]);
        $bundles = discount_rules::get_bundles_records();
        $this->assertEmpty($bundles);

        // Test 3: Create bundle rules (bundle is numeric in the database).
        $bundle1 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 200,
            'percent' => 15,
            'bundle' => 1, // Numeric, not string!
        ]);
        $bundle2 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 500,
            'percent' => 20,
            'bundle' => 2,
        ]);
        $bundles = discount_rules::get_bundles_records();
        $this->assertCount(2, $bundles);

        // Test 4: Bundle with category.
        $category = $this->getDataGenerator()->create_category();
        $bundle3 = testing::get_generator()->create_discount_rule([
            'category' => $category->id,
            'cond' => 100,
            'percent' => 25,
            'bundle' => 3,
        ]);
        $bundles = discount_rules::get_bundles_records();
        $this->assertCount(3, $bundles);

        // Test 5: Bundle with time validity.
        $now = timedate::time();
        $bundle4 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 300,
            'percent' => 30,
            'bundle' => 4,
            'timefrom' => $now - 100,
            'timeto' => $now + 100,
        ]);
        $bundles = discount_rules::get_bundles_records();
        $this->assertCount(4, $bundles);

        // Test 6: Future bundle - should not be returned.
        $future = timedate::time() + 1000;
        $bundle5 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 400,
            'percent' => 35,
            'bundle' => 5,
            'timefrom' => $future,
            'timeto' => $future + 1000,
        ]);
        $bundles = discount_rules::get_bundles_records();
        $this->assertCount(4, $bundles); // Should still be 4, excluding future bundle.

        // Test 7: Past bundle - should not be returned.
        $past = timedate::time() - 1000;
        $bundle6 = testing::get_generator()->create_discount_rule([
            'category' => 0,
            'cond' => 450,
            'percent' => 40,
            'bundle' => 6,
            'timefrom' => $past - 1000,
            'timeto' => $past,
        ]);
        $bundles = discount_rules::get_bundles_records();
        $this->assertCount(4, $bundles); // Should still be 4, excluding past bundle.
    }
}
