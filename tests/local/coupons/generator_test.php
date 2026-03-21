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

namespace enrol_wallet\local\coupons;

use ReflectionClass;
use enrol_wallet\local\utils\timedate;

/**
 * Tests for Wallet enrolment coupon generator
 *
 * @covers     \enrol_wallet\local\coupons\generator
 * @package    enrol_wallet
 * @category   test
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generator_test extends \advanced_testcase {

    /**
     * Test generate_random_coupon method with all scenarios.
     * @covers ::generate_random_coupon()
     */
    public function test_generate_random_coupon(): void {
        $this->resetAfterTest();
        global $DB;

        // Record initial count for later comparison.
        $initialcount = $DB->count_records('enrol_wallet_coupons');

        // Test 1: All character types enabled.
        $options = [
            'upper' => true,
            'lower' => true,
            'digits' => true,
        ];
        $codes = [];
        for ($i = 0; $i < 50; $i++) {
            $codes[] = generator::generate_random_coupon(12, $options);
        }
        foreach ($codes as $code) {
            $this->assertEquals(12, strlen($code), "Code length should be 12");
            // Must not contain similar characters when all types are used.
            $this->assertFalse(str_contains($code, 'l'));
            $this->assertFalse(str_contains($code, '1'));
            $this->assertFalse(str_contains($code, 'I'));
            $this->assertFalse(str_contains($code, 'O'));
            $this->assertFalse(str_contains($code, '0'));
        }
        // Verify all character types are present.
        $this->assertTrue(preg_match('/[0-9]/', implode('', $codes)) === 1);
        $this->assertTrue(preg_match('/[a-z]/', implode('', $codes)) === 1);
        $this->assertTrue(preg_match('/[A-Z]/', implode('', $codes)) === 1);

        // Test 2: Uppercase and lowercase only (no digits).
        $options = ['upper' => true, 'lower' => true, 'digits' => false];
        for ($i = 0; $i < 50; $i++) {
            $code = generator::generate_random_coupon(25, $options);
            $this->assertEquals(25, strlen($code));
            $this->assertFalse(preg_match('/[0-9]/', $code) === 1, "No digits expected");
            // ... l and I should be removed when both lower and upper exist.
            $this->assertFalse(str_contains($code, 'l'));
            $this->assertFalse(str_contains($code, 'I'));
        }

        // Test 3: Lowercase and digits only (no uppercase).
        $options = ['upper' => false, 'lower' => true, 'digits' => true];
        for ($i = 0; $i < 50; $i++) {
            $code = generator::generate_random_coupon(8, $options);
            $this->assertEquals(8, strlen($code));
            $this->assertFalse(preg_match('/[A-Z]/', $code) === 1, "No uppercase expected");
            // ...'l' and '1' should be removed when both lower and digits exist.
            $this->assertFalse(str_contains($code, 'l'));
            $this->assertFalse(str_contains($code, '1'));
        }

        // Test 4: Only uppercase.
        $options = ['upper' => true, 'lower' => false, 'digits' => false];
        for ($i = 0; $i < 50; $i++) {
            $code = generator::generate_random_coupon(10, $options);
            $this->assertEquals(10, strlen($code));
            $this->assertFalse(preg_match('/[0-9]/', $code) === 1, "No digits expected");
            $this->assertFalse(preg_match('/[a-z]/', $code) === 1, "No lowercase expected");
        }

        // Test 5: Only digits.
        $options = ['upper' => false, 'lower' => false, 'digits' => true];
        for ($i = 0; $i < 50; $i++) {
            $code = generator::generate_random_coupon(6, $options);
            $this->assertEquals(6, strlen($code));
            $this->assertFalse(preg_match('/[a-zA-Z]/', $code) === 1, "No letters expected");
        }

        // Test 6: Short lengths.
        $options = ['upper' => true, 'lower' => true, 'digits' => true];
        $this->assertEquals(1, strlen(generator::generate_random_coupon(1, $options)));
        $this->assertEquals(2, strlen(generator::generate_random_coupon(2, $options)));
        $this->assertEquals(3, strlen(generator::generate_random_coupon(3, $options)));

        // Test 7: Long lengths.
        $this->assertEquals(100, strlen(generator::generate_random_coupon(100, $options)));
        $this->assertEquals(200, strlen(generator::generate_random_coupon(200, $options)));

        // Test 8: Randomness - all codes should be unique.
        $randomcodes = [];
        for ($i = 0; $i < 100; $i++) {
            $randomcodes[] = generator::generate_random_coupon(10, $options);
        }
        $this->assertCount(100, array_unique($randomcodes), "All generated codes should be unique");

        // Verify no new records were created (this method doesn't write to DB).
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_coupons'));
    }

    /**
     * Test remove_like_characters method with all scenarios.
     * @covers ::remove_like_characters()
     */
    public function test_remove_like_characters(): void {
        $this->resetAfterTest();
        global $DB;

        $initialcount = $DB->count_records('enrol_wallet_coupons');

        $class = new ReflectionClass(generator::class);
        $method = $class->getMethod('remove_like_characters');
        $method->setAccessible(true);

        // Test 1: All character sets combined.
        $charset = generator::LOWERCASE_CHARSET . generator::UPPERCASE_CHARSET . generator::NUMBERS_CHARSET;
        $result = $method->invoke(null, $charset);
        // All similar characters should be removed.
        $this->assertFalse(str_contains($result, 'l'));
        $this->assertFalse(str_contains($result, '1'));
        $this->assertFalse(str_contains($result, 'I'));
        $this->assertFalse(str_contains($result, 'O'));
        $this->assertFalse(str_contains($result, '0'));

        // Test 2: Uppercase and numbers only.
        $charset = generator::UPPERCASE_CHARSET . generator::NUMBERS_CHARSET;
        $result = $method->invoke(null, $charset);
        $this->assertFalse(str_contains($result, 'l'));
        $this->assertFalse(str_contains($result, '1'));
        $this->assertFalse(str_contains($result, 'I'));
        $this->assertFalse(str_contains($result, 'O'));
        $this->assertFalse(str_contains($result, '0'));

        // Test 3: Lowercase and numbers only.
        $charset = generator::LOWERCASE_CHARSET . generator::NUMBERS_CHARSET;
        $result = $method->invoke(null, $charset);
        $this->assertFalse(str_contains($result, 'l'));
        $this->assertFalse(str_contains($result, '1'));
        $this->assertFalse(str_contains($result, 'I'));
        $this->assertFalse(str_contains($result, 'O'));
        $this->assertTrue(str_contains($result, '0'), "0 should remain when only lowercase and numbers");

        // Test 4: Only lowercase.
        $charset = generator::LOWERCASE_CHARSET;
        $result = $method->invoke(null, $charset);
        $this->assertTrue(str_contains($result, 'l'), "l should remain when only lowercase");
        $this->assertFalse(str_contains($result, '1'));
        $this->assertFalse(str_contains($result, 'I'));
        $this->assertFalse(str_contains($result, 'O'));
        $this->assertFalse(str_contains($result, '0'));

        // Test 5: Only uppercase.
        $charset = generator::UPPERCASE_CHARSET;
        $result = $method->invoke(null, $charset);
        $this->assertFalse(str_contains($result, 'l'));
        $this->assertFalse(str_contains($result, '1'));
        $this->assertTrue(str_contains($result, 'I'), "I should remain when only uppercase");
        $this->assertTrue(str_contains($result, 'O'), "O should remain when only uppercase");
        $this->assertFalse(str_contains($result, '0'));

        // Test 6: Only numbers.
        $charset = generator::NUMBERS_CHARSET;
        $result = $method->invoke(null, $charset);
        $this->assertFalse(str_contains($result, 'l'));
        $this->assertTrue(str_contains($result, '1'), "1 should remain when only numbers");
        $this->assertFalse(str_contains($result, 'I'));
        $this->assertFalse(str_contains($result, 'O'));
        $this->assertTrue(str_contains($result, '0'), "0 should remain when only numbers");

        // Verify no new records were created.
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_coupons'));
    }

    /**
     * Test create_coupon_record method with all scenarios.
     * @covers ::create_coupon_record()
     */
    public function test_create_coupon_record(): void {
        $this->resetAfterTest();
        global $DB;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_coupons');

        // Test 1: Default values.
        $record = generator::create_coupon_record();
        $this->assertNotNull($record);
        $this->assertNotNull($record->id);
        $this->assertEquals('fixed', $record->type);
        $this->assertEquals(0, $record->value);
        $this->assertEquals(0, $record->category);
        $this->assertEquals('', $record->courses);
        $this->assertEquals(0, $record->maxusage);
        $this->assertEquals(0, $record->maxperuser);
        $this->assertEquals(0, $record->validfrom);
        $this->assertEquals(0, $record->validto);
        $this->assertNotEmpty($record->code);
        $this->assertEquals(10, strlen($record->code));

        // Verify in database.
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record->id]);
        $this->assertNotNull($dbrecord);
        $this->assertEquals($initialcount + 1, $DB->count_records('enrol_wallet_coupons'));

        // Test 2: Custom code.
        $record = generator::create_coupon_record('TESTCODE123');
        $this->assertEquals('TESTCODE123', $record->code);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['code' => 'TESTCODE123']);
        $this->assertNotNull($dbrecord);
        $this->assertEquals($record->id, $dbrecord->id);
        $this->assertEquals($initialcount + 2, $DB->count_records('enrol_wallet_coupons'));

        // Test 3: Max usage.
        $record = generator::create_coupon_record(maxusage: 5);
        $this->assertEquals(5, $record->maxusage);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record->id]);
        $this->assertEquals(5, $dbrecord->maxusage);
        $this->assertEquals($initialcount + 3, $DB->count_records('enrol_wallet_coupons'));

        // Test 4: Max per user.
        $record = generator::create_coupon_record(maxperuser: 2);
        $this->assertEquals(2, $record->maxperuser);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record->id]);
        $this->assertEquals(2, $dbrecord->maxperuser);
        $this->assertEquals($initialcount + 4, $DB->count_records('enrol_wallet_coupons'));

        // Test 5: Value.
        $record = generator::create_coupon_record(value: 50.5);
        $this->assertEquals(50.5, $record->value);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record->id]);
        $this->assertEquals(50.5, $dbrecord->value);
        $this->assertEquals($initialcount + 5, $DB->count_records('enrol_wallet_coupons'));

        // Test 6: Courses.
        $courses = [5, 12, 20];
        $record = generator::create_coupon_record(courses: $courses);
        $this->assertEquals('5,12,20', $record->courses);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record->id]);
        $this->assertEquals('5,12,20', $dbrecord->courses);
        $this->assertEquals($initialcount + 6, $DB->count_records('enrol_wallet_coupons'));

        // Test 7: Category.
        $record = generator::create_coupon_record(category: 10);
        $this->assertEquals(10, $record->category);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record->id]);
        $this->assertEquals(10, $dbrecord->category);
        $this->assertEquals($initialcount + 7, $DB->count_records('enrol_wallet_coupons'));

        // Test 8: Validity dates.
        $now = timedate::time();
        $oneweek = 7 * 24 * 60 * 60;
        $record = generator::create_coupon_record(validfrom: $now, validto: $now + $oneweek);
        $this->assertEquals($now, $record->validfrom);
        $this->assertEquals($now + $oneweek, $record->validto);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record->id]);
        $this->assertEquals($now, $dbrecord->validfrom);
        $this->assertEquals($now + $oneweek, $dbrecord->validto);
        $this->assertEquals($initialcount + 8, $DB->count_records('enrol_wallet_coupons'));

        // Test 9: Different types.
        $record1 = generator::create_coupon_record(type: 'fixed', value: 100);
        $this->assertEquals('fixed', $record1->type);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record1->id]);
        $this->assertEquals('fixed', $dbrecord->type);

        $record2 = generator::create_coupon_record(type: 'percent', value: 20);
        $this->assertEquals('percent', $record2->type);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record2->id]);
        $this->assertEquals('percent', $dbrecord->type);

        $record3 = generator::create_coupon_record(type: 'enrol', value: 0);
        $this->assertEquals('enrol', $record3->type);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record3->id]);
        $this->assertEquals('enrol', $dbrecord->type);

        $record4 = generator::create_coupon_record(type: 'category', value: 50, category: 5);
        $this->assertEquals('category', $record4->type);
        $this->assertEquals(5, $record4->category);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record4->id]);
        $this->assertEquals('category', $dbrecord->type);
        $this->assertEquals(5, $dbrecord->category);

        $record5 = generator::create_coupon_record(type: 'fixeddis', value: 25);
        $this->assertEquals('fixeddis', $record5->type);
        $dbrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $record5->id]);
        $this->assertEquals('fixeddis', $dbrecord->type);

        // Verify final count.
        $this->assertEquals($initialcount + 13, $DB->count_records('enrol_wallet_coupons'));
    }

    /**
     * Test create_coupons method with all scenarios.
     * @covers ::create_coupons()
     */
    public function test_create_coupons(): void {
        $this->resetAfterTest();
        global $DB;

        // Record initial count.
        $initialcount = $DB->count_records('enrol_wallet_coupons');

        // Test 1: Single coupon with predefined code.
        $options = (object)[
            'number' => 1,
            'code' => 'SINGLECODE',
            'type' => 'fixed',
            'value' => 50,
            'maxusage' => 0,
            'maxperuser' => 0,
            'from' => 0,
            'to' => 0,
        ];
        $result = generator::create_coupons($options);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        // Verify in database.
        $record = $DB->get_record('enrol_wallet_coupons', ['code' => 'SINGLECODE']);
        $this->assertNotNull($record);
        $this->assertEquals($record->type, 'fixed');
        $this->assertEquals($record->value, 50);
        $this->assertEquals($initialcount + 1, $DB->count_records('enrol_wallet_coupons'));

        // Test 2: Multiple generated codes.
        $options = (object)[
            'number' => 10,
            'code' => '',
            'type' => 'fixed',
            'value' => 25,
            'maxusage' => 5,
            'maxperuser' => 1,
            'from' => 0,
            'to' => 0,
            'length' => 15,
            'lower' => true,
            'upper' => true,
            'digits' => true,
        ];
        $result = generator::create_coupons($options);
        $this->assertIsArray($result);
        $this->assertCount(10, $result);

        // Verify all codes are unique in database by checking only the newly created IDs.
        $newrecords = $DB->get_records_list('enrol_wallet_coupons', 'id', $result);
        $codes = array_column($newrecords, 'code');
        $this->assertCount(10, array_unique($codes));

        // Verify codes have correct length.
        foreach ($codes as $code) {
            $this->assertEquals(15, strlen($code));
        }
        $this->assertEquals($initialcount + 11, $DB->count_records('enrol_wallet_coupons'));

        // Test 3: Unique codes within batch.
        $options = (object)[
            'number' => 5,
            'code' => '',
            'type' => 'percent',
            'value' => 10,
            'maxusage' => 0,
            'maxperuser' => 0,
            'from' => 0,
            'to' => 0,
            'length' => 10,
            'lower' => true,
            'upper' => false,
            'digits' => true,
        ];
        $result = generator::create_coupons($options);
        $this->assertIsArray($result);
        $this->assertCount(5, $result);

        // Verify all codes within the batch are unique.
        $allcodes = array_values($DB->get_records_menu('enrol_wallet_coupons', ['type' => 'percent'], '', 'id,code'));
        $this->assertCount(5, array_unique($allcodes), "All generated codes in batch should be unique");
        $this->assertEquals($initialcount + 16, $DB->count_records('enrol_wallet_coupons'));

        // Test 4: With validity period.
        $now = timedate::time();
        $onemonth = 30 * 24 * 60 * 60;
        $options = (object)[
            'number' => 3,
            'code' => '',
            'type' => 'percent',
            'value' => 15,
            'maxusage' => 10,
            'maxperuser' => 2,
            'from' => $now,
            'to' => $now + $onemonth,
            'length' => 8,
            'lower' => false,
            'upper' => true,
            'digits' => true,
        ];
        $result = generator::create_coupons($options);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Verify validity in database.
        foreach ($result as $id) {
            $record = $DB->get_record('enrol_wallet_coupons', ['id' => $id]);
            $this->assertEquals($now, $record->validfrom);
            $this->assertEquals($now + $onemonth, $record->validto);
            $this->assertEquals(10, $record->maxusage);
            $this->assertEquals(2, $record->maxperuser);
        }
        $this->assertEquals($initialcount + 19, $DB->count_records('enrol_wallet_coupons'));

        // Test 5: With courses restriction.
        $options = (object)[
            'number' => 2,
            'code' => '',
            'type' => 'enrol',
            'value' => 0,
            'maxusage' => 0,
            'maxperuser' => 0,
            'from' => 0,
            'to' => 0,
            'courses' => '5,10,15',
            'length' => 12,
            'lower' => true,
            'upper' => true,
            'digits' => false,
        ];
        $result = generator::create_coupons($options);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Verify courses in database.
        foreach ($result as $id) {
            $record = $DB->get_record('enrol_wallet_coupons', ['id' => $id]);
            $this->assertEquals('5,10,15', $record->courses);
        }
        $this->assertEquals($initialcount + 21, $DB->count_records('enrol_wallet_coupons'));

        // Test 6: With category restriction.
        $options = (object)[
            'number' => 2,
            'code' => '',
            'type' => 'category',
            'value' => 100,
            'maxusage' => 0,
            'maxperuser' => 0,
            'from' => 0,
            'to' => 0,
            'category' => 7,
            'length' => 10,
            'lower' => true,
            'upper' => false,
            'digits' => true,
        ];
        $result = generator::create_coupons($options);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Verify category in database.
        foreach ($result as $id) {
            $record = $DB->get_record('enrol_wallet_coupons', ['id' => $id]);
            $this->assertEquals(7, $record->category);
        }
        $this->assertEquals($initialcount + 23, $DB->count_records('enrol_wallet_coupons'));

        // Test 7: With progress trace.
        $trace = new \null_progress_trace();
        $options = (object)[
            'number' => 5,
            'code' => '',
            'type' => 'fixed',
            'value' => 50,
            'maxusage' => 0,
            'maxperuser' => 0,
            'from' => 0,
            'to' => 0,
            'length' => 10,
            'lower' => true,
            'upper' => true,
            'digits' => true,
        ];
        $result = generator::create_coupons($options, $trace);
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals($initialcount + 28, $DB->count_records('enrol_wallet_coupons'));
    }

    /**
     * Test charset constants.
     * @covers ::UPPERCASE_CHARSET
     * @covers ::LOWERCASE_CHARSET
     * @covers ::NUMBERS_CHARSET
     */
    public function test_charset_constants(): void {
        $this->resetAfterTest();
        global $DB;

        $initialcount = $DB->count_records('enrol_wallet_coupons');

        // Verify charset constants are strings.
        $this->assertIsString(generator::UPPERCASE_CHARSET);
        $this->assertIsString(generator::LOWERCASE_CHARSET);
        $this->assertIsString(generator::NUMBERS_CHARSET);

        // Verify they contain expected characters.
        $this->assertEquals(26, strlen(generator::UPPERCASE_CHARSET));
        $this->assertEquals(26, strlen(generator::LOWERCASE_CHARSET));
        $this->assertEquals(10, strlen(generator::NUMBERS_CHARSET));

        // Verify no records were created.
        $this->assertEquals($initialcount, $DB->count_records('enrol_wallet_coupons'));
    }
}
