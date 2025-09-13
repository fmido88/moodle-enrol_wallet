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

namespace enrol_wallet;

use context_course;
use enrol_wallet\local\config;
use enrol_wallet\local\coupons\coupons;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\wallet\balance_op;
use enrol_wallet_plugin;

/**
 * Tests for coupons operations.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class coupons_test extends \advanced_testcase {
    /**
     * Category 1.
     * @var object
     */
    private $cat1;

    /**
     * Category 2.
     * @var object
     */
    private $cat2;

    /**
     * Category 3.
     * @var object
     */
    private $cat3;

    /**
     * Category 4.
     * @var object
     */
    private $cat4;

    /**
     * Course 1.
     * @var \stdClass
     */
    private $c1;

    /**
     * Course 1.
     * @var \stdClass
     */
    private $c2;

    /**
     * Course 2.
     * @var \stdClass
     */
    private $c3;

    /**
     * Course 4.
     * @var \stdClass
     */
    private $c4;

    /**
     * Course 5.
     * @var \stdClass
     */
    private $c5;

    /**
     * Course 6.
     * @var \stdClass
     */
    private $c6;

    /**
     * Course 7.
     * @var \stdClass
     */
    private $c7;

    /**
     * User 1.
     * @var \stdClass
     */
    private $u1;

    /**
     * User 2.
     * @var \stdClass
     */
    private $u2;

    /**
     * User 3.
     * @var \stdClass
     */
    private $u3;

    /**
     * User 4.
     * @var \stdClass
     */
    private $u4;

    /**
     * User 4.
     * @var \stdClass
     */
    private $u5;

    /**
     * User 6.
     * @var \stdClass
     */
    private $u6;

    /**
     * Enrol wallet instance 1.
     * @var \stdClass
     */
    private $inst1;

    /**
     * Enrol wallet instance 2.
     * @var \stdClass
     */
    private $inst2;

    /**
     * Enrol wallet instance 3.
     * @var \stdClass
     */
    private $inst3;

    /**
     * Enrol wallet instance 4.
     * @var \stdClass
     */
    private $inst4;

    /**
     * Enrol wallet instance 5.
     * @var \stdClass
     */
    private $inst5;

    /**
     * Enrol wallet instance 6.
     * @var \stdClass
     */
    private $inst6;

    /**
     * Enrol wallet instance 7.
     * @var \stdClass
     */
    private $inst7;

    /**
     * Course section 1.
     * @var \stdClass
     */
    private $sec1;

    /**
     * Course section 2.
     * @var \stdClass
     */
    private $sec2;

    /**
     * Course section 3.
     * @var \stdClass
     */
    private $sec3;

    /**
     * Course module 1.
     * @var \stdClass
     */
    private $cm1;

    /**
     * Course module 2.
     * @var \stdClass
     */
    private $cm2;

    /**
     * Course module 3.
     * @var \stdClass
     */
    private $cm3;

    /**
     * Course module 4.
     * @var \stdClass
     */
    private $cm4;

    /**
     * Course module 5.
     * @var \stdClass
     */
    private $cm5;

    /**
     * Data Generator.
     * @var \testing_data_generator
     */
    private $gen;

    /**
     * Plugin.
     * @var enrol_wallet_plugin
     */
    private $wallet;

    /**
     * Test if a certain type of coupons are enabled.
     * @covers ::is_enabled_type
     */
    public function test_is_enabled_type(): void {
        $this->setUser($this->u2);
        $coupons = new coupons('fixed1');
        $this->assertFalse($coupons->is_enabled_type());

        $this->set_config(coupons::DISCOUNT);
        $coupons = new coupons('fixed1');
        $this->assertFalse($coupons->is_enabled_type());

        $this->set_config(coupons::NOCOUPONS);
        $coupons = new coupons('fixed1');
        $this->assertFalse($coupons->is_enabled_type());

        $this->set_config([coupons::DISCOUNT, coupons::ENROL, coupons::CATEGORY]);
        $coupons = new coupons('fixed1');
        $this->assertFalse($coupons->is_enabled_type());

        $this->set_config([coupons::DISCOUNT, coupons::ALL]);
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->is_enabled_type());

        $this->set_config([coupons::FIXED, coupons::NOCOUPONS]);
        $coupons = new coupons('fixed1');
        $this->assertFalse($coupons->is_enabled_type());

        $this->set_config(coupons::FIXED);
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->is_enabled_type());

        $this->set_config(coupons::ALL);
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->is_enabled_type());

        $this->set_config([coupons::FIXED, coupons::CATEGORY]);
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->is_enabled_type());
    }

    /**
     * Test if coupons are enabled in this site.
     * @covers ::is_enabled()
     */
    public function test_is_enabled(): void {
        $this->assertFalse(coupons::is_enabled());
        $this->set_config(coupons::DISCOUNT);
        $this->assertTrue(coupons::is_enabled());
        $this->set_config(coupons::NOCOUPONS);
        $this->assertFalse(coupons::is_enabled());
        $this->set_config(coupons::ALL);
        $this->assertTrue(coupons::is_enabled());
        $this->set_config([coupons::FIXED, coupons::CATEGORY]);
        $this->assertTrue(coupons::is_enabled());
        $this->set_config([coupons::FIXED, coupons::NOCOUPONS]);
        $this->assertFalse(coupons::is_enabled());
        $this->set_config('');
        $this->assertFalse(coupons::is_enabled());
    }

    /**
     * Test validation for fixed coupons.
     * @covers ::validate_coupon()
     */
    public function test_validate_fixed_coupon(): void {
        $this->set_config(coupons::ALL);
        // Not logged in.
        $coupons = new coupons('fixed1');
        $this->assertNotTrue($coupons->validate_coupon());

        $this->setUser($this->u1);
        // Logged in valid coupon.
        // Valid in any area.
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_TOPUP));
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_CM, $this->cm1->id));
        $coupons = new coupons('fixed1');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_SECTION, $this->sec1->id));

        // Test exceeding max usage.
        $coupons = new coupons('fixed2');
        $this->assertTrue($coupons->validate_coupon());
        $coupons->mark_coupon_used();
        $this->setUser($this->u2);
        $coupons = new coupons('fixed2');
        $this->assertTrue($coupons->validate_coupon());
        $coupons->mark_coupon_used();
        $coupons = new coupons('fixed2');
        $this->assertNotTrue($coupons->validate_coupon());
        $this->setUser($this->u3);
        $coupons = new coupons('fixed2');
        $this->assertNotTrue($coupons->validate_coupon());
        $this->setUser($this->u1);
        $coupons = new coupons('fixed2');
        $this->assertNotTrue($coupons->validate_coupon());

        // Exceeding max usage per user.
        $coupons = new coupons('fixed3');
        $this->assertTrue($coupons->validate_coupon());
        $coupons->mark_coupon_used();
        $coupons = new coupons('fixed3');
        $this->assertTrue($coupons->validate_coupon());
        $coupons->mark_coupon_used();
        $coupons = new coupons('fixed3');
        $this->assertNotTrue($coupons->validate_coupon());

        $this->setUser($this->u2);
        $coupons = new coupons('fixed3');
        $this->assertTrue($coupons->validate_coupon());
        $coupons->mark_coupon_used();
        $coupons = new coupons('fixed3');
        $this->assertTrue($coupons->validate_coupon());
        $coupons->mark_coupon_used();
        $coupons = new coupons('fixed3');
        $this->assertNotTrue($coupons->validate_coupon());

        $this->setUser($this->u3);
        $coupons = new coupons('fixed3');
        $this->assertTrue($coupons->validate_coupon());
        $coupons->mark_coupon_used();
        $coupons = new coupons('fixed3');
        $this->assertNotTrue($coupons->validate_coupon());

        // Not available yet coupon.
        $coupons = new coupons('fixed4');
        $this->assertNotTrue($coupons->validate_coupon());
        // Expired coupon.
        $coupons = new coupons('fixed5');
        $this->assertNotTrue($coupons->validate_coupon());
    }

    /**
     * Test validation for enrol coupons.
     * @covers ::validate_coupon
     */
    public function test_validate_enrol_coupon(): void {
        $this->setUser($this->u1);
        $this->set_config([coupons::ENROL]);
        $coupons    = new coupons('enrol1');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id);
        $this->assertTrue($validation);

        $coupons    = new coupons('enrol1');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst5->id);
        $this->assertNotTrue($validation, $validation);

        $coupons    = new coupons('enrol3');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst5->id);
        $this->assertTrue($validation);

        $coupons    = new coupons('enrol1');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst7->id);
        $this->assertTrue($validation);

        // Invalid areas.
        $coupons    = new coupons('enrol1');
        $validation = $coupons->validate_coupon(coupons::AREA_CM, $this->cm1->id);
        $this->assertNotTrue($validation, $validation);

        $coupons    = new coupons('enrol1');
        $validation = $coupons->validate_coupon(coupons::AREA_SECTION, $this->sec1->id);
        $this->assertNotTrue($validation, $validation);

        $coupons    = new coupons('enrol1');
        $validation = $coupons->validate_coupon(coupons::AREA_TOPUP);
        $this->assertNotTrue($validation, $validation);

        // Invalid record.
        $coupons = new coupons('enrol4');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));

        // Exeeds max usage.
        $coupons = new coupons('enrol5');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $this->assertEquals(0, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());
        $coupons->mark_coupon_used();
        $this->assertEquals(1, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $coupons = new coupons('enrol5');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $coupons->mark_coupon_used();
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(2, $coupons->get_user_use());
        $coupons = new coupons('enrol5');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        // Exceed max usage per user.
        $coupons = new coupons('enrol6');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $this->assertEquals(0, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());
        $coupons->mark_coupon_used();
        $this->assertEquals(1, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $coupons = new coupons('enrol6');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst3->id));
        $coupons->mark_coupon_used();
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(2, $coupons->get_user_use());
        $coupons    = new coupons('enrol6');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst4->id);
        $this->assertNotTrue($validation);
        $coupons = new coupons('enrol6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        $this->setUser($this->u2);

        $coupons = new coupons('enrol6');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());
        $coupons->mark_coupon_used();
        $coupons = new coupons('enrol6');
        $this->assertEquals(3, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst3->id));
        $coupons->mark_coupon_used();
        $coupons = new coupons('enrol6');
        $this->assertEquals(4, $coupons->get_total_use());
        $this->assertEquals(2, $coupons->get_user_use());
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst4->id));
        $coupons = new coupons('enrol6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        $this->setUser($this->u3);

        $coupons = new coupons('enrol6');
        $this->assertEquals(4, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $coupons->mark_coupon_used();
        $coupons = new coupons('enrol6');
        $this->assertEquals(5, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst3->id));
        $coupons = new coupons('enrol6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst4->id));
        $coupons = new coupons('enrol6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        // Not available yet.
        $coupons = new coupons('enrol7');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        // Expired.
        $coupons = new coupons('enrol8');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
    }

    /**
     * Validation for discount coupons.
     * @covers ::validate_coupon()
     */
    public function test_validate_discount_coupon(): void {
        $this->set_config(coupons::DISCOUNT);
        $coupons = new coupons('percent1');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));
        $this->setUser($this->u1);
        $coupons = new coupons('percent1');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));
        $coupons = new coupons('percent1');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_CM, $this->cm1->id));
        $coupons = new coupons('percent1');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_SECTION, $this->sec1->id));
        $coupons = new coupons('percent1');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_TOPUP));

        $coupons = new coupons('percent3');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));
        $coupons = new coupons('percent4');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));

        // Expired.
        $coupons = new coupons('percent5');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $this->assertEquals(0, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());
        $coupons->mark_coupon_used();
        $this->assertEquals(1, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $coupons = new coupons('percent5');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $coupons->mark_coupon_used();
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(2, $coupons->get_user_use());
        $coupons = new coupons('percent5');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        // Exceed max usage per user.
        $coupons = new coupons('percent6');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $this->assertEquals(0, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());
        $coupons->mark_coupon_used();
        $this->assertEquals(1, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $coupons = new coupons('percent6');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst3->id));
        $coupons->mark_coupon_used();
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(2, $coupons->get_user_use());
        $coupons = new coupons('percent6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst4->id));
        $coupons = new coupons('percent6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        $this->setUser($this->u2);

        $balanceop = new balance_op($this->u2->id);
        $balanceop->credit(100);

        $coupons = new coupons('percent6'); // 50% .
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());

        // Check if discount coupons marked as used when the user get enrolled.
        coupons::set_session_coupon('percent6');
        $wallet   = new enrol_wallet_plugin();
        $instance = new instance($this->inst2);
        $this->assertEquals(10, $instance->get_cost_after_discount());

        $wallet->enrol_self($this->inst2, $this->u2);
        $balance = new balance();
        $this->assertEquals(90, $balance->get_total_balance());

        $coupons = new coupons('percent6');
        $this->assertEquals(3, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst3->id));
        $this->assertTrue(is_enrolled(context_course::instance($this->inst2->courseid)));

        $coupons->mark_coupon_used();
        $coupons = new coupons('percent6');
        $this->assertEquals(4, $coupons->get_total_use());
        $this->assertEquals(2, $coupons->get_user_use());
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst4->id));
        $coupons = new coupons('percent6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        $this->setUser($this->u3);

        $coupons = new coupons('percent6');
        $this->assertEquals(4, $coupons->get_total_use());
        $this->assertEquals(0, $coupons->get_user_use());
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));
        $coupons->mark_coupon_used();
        $coupons = new coupons('percent6');
        $this->assertEquals(5, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst3->id));
        $coupons = new coupons('percent6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst4->id));
        $coupons = new coupons('percent6');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst2->id));

        // No available yet coupon.
        $coupons = new coupons('percent7');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));

        // Expired coupon.
        $coupons = new coupons('percent8');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));

        // Restricted by a category.
        $coupons = new coupons('percent9');
        $this->assertTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id));

        $coupons = new coupons('percent9');
        $this->assertNotTrue($coupons->validate_coupon(coupons::AREA_ENROL, $this->inst4->id));
    }

    /**
     * Validate category coupons.
     * @covers ::validate_coupons()
     */
    public function test_validate_category_coupon(): void {
        $this->set_config(coupons::CATEGORY);
        // Not logged in.
        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id);
        $this->assertNotTrue($validation);

        // Logged in used in the same category (valid).
        $this->setUser($this->u1->id);
        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id);
        $this->assertTrue($validation);

        // Used in a child category (valid).
        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst7->id);
        $this->assertTrue($validation);

        // Used in parent category (not valid).
        $coupons    = new coupons('category2');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst1->id);
        $this->assertNotTrue($validation);

        // Used in the same category (valid).
        $coupons    = new coupons('category2');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst7->id);
        $this->assertTrue($validation);

        // Valid for other areas.
        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_TOPUP);
        $this->assertTrue($validation);

        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_SECTION, $this->sec1->id);
        $this->assertTrue($validation);

        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_CM, $this->cm1->id);
        $this->assertTrue($validation);

        // Different category sections and cms.
        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_SECTION, $this->sec3->id);
        $this->assertNotTrue($validation);

        $coupons    = new coupons('category1');
        $validation = $coupons->validate_coupon(coupons::AREA_CM, $this->cm3->id);
        $this->assertNotTrue($validation);

        // Invalid record.
        $coupons    = new coupons('category3');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst7->id);
        $this->assertNotTrue($validation);

        config::make()->catbalance = 0;
        $coupons                   = new coupons('category1');
        $validation                = $coupons->validate_coupon(coupons::AREA_TOPUP);
        $this->assertNotTrue($validation);

        $coupons    = new coupons('category2');
        $validation = $coupons->validate_coupon(coupons::AREA_ENROL, $this->inst7->id);
        $this->assertTrue($validation);
    }

    /**
     * Test applying fixed coupon.
     * @covers ::apply_coupon()
     */
    public function test_apply_fixed_coupon(): void {
        $this->set_config(coupons::FIXED);
        $coupons = new coupons('fixed1', $this->u1->id);
        $coupons->apply_coupon();
        $coupons = new coupons('fixed1', $this->u1->id);
        $this->assertEquals(1, $coupons->get_total_use());
        $balance = new balance($this->u1->id);
        $this->assertEquals(50, $balance->get_main_balance());
        $this->assertEquals(50, $balance->get_main_nonrefundable());

        $this->setUser($this->u2);
        $coupons = new coupons('fixed1');
        $coupons->apply_coupon();
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());
        $coupons = new coupons('fixed1', $this->u1->id);
        $this->assertEquals(2, $coupons->get_total_use());
        $this->assertEquals(1, $coupons->get_user_use());

        $balance = new balance();
        $this->assertEquals(50, $balance->get_main_balance());
        $this->assertEquals(50, $balance->get_main_nonrefundable());
        $balance = new balance($this->u2->id);
        $this->assertEquals(50, $balance->get_main_balance());
        $this->assertEquals(50, $balance->get_main_nonrefundable());

        // Lets try to apply a non valid coupon.
        $this->setUser($this->u3);
        $coupons = new coupons('fixed4');
        $coupons->apply_coupon();
        $balance = new balance();
        $this->assertEquals(0, $balance->get_total_balance());

        // Enrol area, sufficient value, credit then enrol.
        $this->setUser($this->u4);
        $coupons = new coupons('fixed1');
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst1->id);
        $balance = balance::create_from_instance($this->inst1);
        $this->assertEquals(40, $balance->get_valid_balance());
        $this->assertEquals(40, $balance->get_valid_nonrefundable());
        $this->assertEquals(40, $balance->get_main_balance());
        $this->assertTrue(is_enrolled(\context_course::instance($this->c1->id), $this->u4));

        // Enrol area, Insufficient value, credit only.
        $this->setUser($this->u5);
        $coupons = new coupons('fixed1');
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst7->id);
        $balance = balance::create_from_instance($this->inst7->id);
        $this->assertEquals(50, $balance->get_valid_balance());
        $this->assertEquals(50, $balance->get_valid_nonrefundable());
        $this->assertEquals(50, $balance->get_main_balance());
        $this->assertFalse(is_enrolled(\context_course::instance($this->c7->id), $this->u5));

        // User with partial credit.
        $this->setUser($this->u6);
        $coupons = new coupons('fixed1');
        $op      = new balance_op($this->u6->id);
        $op->credit(40);
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst6->id);
        $balance = balance::create_from_instance($this->inst6->id);
        $this->assertEquals(30, $balance->get_valid_balance());
        $this->assertEquals(30, $balance->get_valid_nonrefundable());
        $this->assertEquals(30, $balance->get_main_balance());
        $this->assertTrue(is_enrolled(\context_course::instance($this->c6->id), $this->u6));
    }

    /**
     * Test applying category coupon.
     * @covers ::apply_coupon()
     */
    public function test_apply_category_coupon(): void {
        $this->set_config(coupons::CATEGORY);

        $this->setUser($this->u1);
        $coupons = new coupons('category1');
        $coupons->apply_coupon(coupons::AREA_TOPUP);

        $balance = new balance($this->u1->id, $this->cat1->id);
        $this->assertEquals(0, $balance->get_main_balance());
        $this->assertEquals(100, $balance->get_valid_balance());
        $this->assertEquals(100, $balance->get_valid_nonrefundable());
        $balance = new balance($this->u1->id, $this->cat2->id);
        $this->assertEquals(100, $balance->get_total_balance());
        $this->assertEquals(0, $balance->get_main_balance());
        $this->assertEquals(0, $balance->get_valid_balance());

        $this->setUser($this->u2);
        $coupons = new coupons('category1');
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst1->id);
        $balance = new balance($this->u2->id, $this->cat1->id);
        $this->assertTrue(is_enrolled(\context_course::instance($this->c1->id), $this->u2));
        $this->assertEquals(0, $balance->get_main_balance());
        $this->assertEquals(90, $balance->get_total_balance());
        $this->assertEquals(90, $balance->get_valid_balance());
        $this->assertEquals(90, $balance->get_valid_nonrefundable());

        $this->setUser($this->u3);
        $coupons = new coupons('category1');
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst4->id);
        $this->assertFalse(is_enrolled(\context_course::instance($this->c4->id), $this->u3));
        $balance = new balance($this->u3->id, $this->cat1->id);
        $this->assertEquals(0, $balance->get_total_balance());
        $this->assertEquals(0, $balance->get_main_balance());
        $this->assertEquals(0, $balance->get_valid_balance());
    }

    /**
     * Test applying enrol coupons.
     * @covers ::apply_coupon
     */
    public function test_apply_enrol_coupon(): void {
        $this->set_config(coupons::ENROL);
        $this->setUser($this->u1);
        $coupons = new coupons('enrol1');
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst1->id);
        $this->assertTrue(is_enrolled(\context_course::instance($this->c1->id), $this->u1));
        $this->assertFalse(is_enrolled(\context_course::instance($this->c7->id), $this->u1));
        $balance = new balance($this->u1->id);
        $this->assertEquals(0, $balance->get_total_balance());

        $this->setUser($this->u2);
        $coupons = new coupons('enrol1');
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst7->id);
        $this->assertTrue(is_enrolled(\context_course::instance($this->c7->id), $this->u2));
        $this->assertFalse(is_enrolled(\context_course::instance($this->c1->id), $this->u2));
        $balance = new balance($this->u1->id);
        $this->assertEquals(0, $balance->get_total_balance());

        $this->setUser($this->u3);
        $coupons = new coupons('enrol1');
        $coupons->apply_coupon(coupons::AREA_ENROL, $this->inst3->id);
        $this->assertFalse(is_enrolled(\context_course::instance($this->c3->id), $this->u3));
        $this->assertFalse(is_enrolled(\context_course::instance($this->c1->id), $this->u3));
        $this->assertFalse(is_enrolled(\context_course::instance($this->c7->id), $this->u3));
        $balance = new balance($this->u1->id);
        $this->assertEquals(0, $balance->get_total_balance());
    }

    /**
     * Generate a number of coupons of different data, types and restrictions
     * to be tested.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        $this->resetAfterTest();
        $this->gen    = $this->getDataGenerator();
        $this->wallet = new enrol_wallet_plugin();

        // Create users.
        for ($i = 1; $i <= 6; $i++) {
            $var        = 'u' . $i;
            $this->$var = $this->gen->create_user();
        }

        // Create Categoies.
        for ($i = 1; $i <= 4; $i++) {
            $var    = 'cat' . $i;
            $record = [];

            if ($i == 4) {
                $record['parent'] = $this->cat1->id;
            }
            $this->$var = $this->gen->create_category($record);
        }

        // Create courses.
        for ($i = 1; $i <= 7; $i++) {
            $record = new \stdClass();
            $var    = 'c' . $i;

            switch ($i) {
                case 1:
                case 2:
                    $record->category = $this->cat1->id;
                    break;

                case 3:
                case 4:
                    $record->category = $this->cat2->id;
                    break;

                case 5:
                case 6:
                    $record->category = $this->cat3->id;
                    break;

                case 7:
                    $record->category = $this->cat4->id;
                    break;

                default:
            }
            $this->$var = $this->gen->create_course($record);

            // Update the enrolment instances for each course.
            $instance = $DB->get_record('enrol', ['courseid' => $this->$var->id, 'enrol' => 'wallet'], '*', MUST_EXIST);

            $instance->status      = ENROL_INSTANCE_ENABLED;
            $instance->customint6  = 1;
            $instance->cost        = 10 * $i;
            $instance->enrolperiod = DAYSECS;
            $DB->update_record('enrol', $instance);
            $this->wallet->update_status($instance, ENROL_INSTANCE_ENABLED);
            $inst        = 'inst' . $i;
            $this->$inst = $instance;

            if ($i <= 3) {
                $record          = new \stdClass();
                $record->section = 1;
                $record->course  = $this->$var->id;
                $sec             = 'sec' . $i;
                $this->$sec      = $this->gen->create_course_section($record);
            }

            if ($i <= 5) {
                $record          = new \stdClass();
                $record->section = $this->$sec->id ?? null;
                $record->course  = $this->$var->id;
                $cm              = 'cm' . $i;

                $page = $this->gen->create_module('page', $record);

                $this->$cm = $DB->get_record('course_modules', ['id' => $page->cmid], '*', MUST_EXIST);
            }
        }

        // Created coupons in the database.
        $records = [];
        $now     = timedate::time();
        $expired = $now - 2 * DAYSECS;
        $notav   = $now + 2 * DAYSECS;
        // Fixed coupons.
        $records[] = [
            'code'        => 'fixed1',
            'type'        => 'fixed',
            'value'       => 50,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'fixed2',
            'type'        => 'fixed',
            'value'       => 100,
            'maxusage'    => 2, // Limited by max usage.
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'fixed3',
            'type'        => 'fixed',
            'value'       => 70,
            'maxusage'    => 5,
            'maxperuser'  => 2, // Limited by max use per user.
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'fixed4',
            'type'        => 'fixed',
            'value'       => 70,
            'validfrom'   => $notav, // Not available yet.
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'fixed5',
            'type'        => 'fixed',
            'value'       => 50,
            'validto'     => $expired, // Expired.
            'timecreated' => $now,
        ];
        // Discount coupons.
        $records[] = [
            'code'        => 'percent1',
            'type'        => 'percent',
            'value'       => 20,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent2',
            'type'        => 'percent',
            'value'       => 50,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent3',
            'type'        => 'percent',
            'value'       => 100,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent4',
            'type'        => 'percent',
            'value'       => 150, // Invalid value.
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent5',
            'type'        => 'percent',
            'value'       => 50,
            'maxusage'    => 2,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent6',
            'type'        => 'percent',
            'value'       => 50,
            'maxusage'    => 5,
            'maxperuser'  => 2,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent7',
            'type'        => 'percent',
            'value'       => 50,
            'validfrom'   => $notav,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent8',
            'type'        => 'percent',
            'value'       => 50,
            'validto'     => $expired,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'percent9',
            'type'        => 'percent',
            'value'       => 50,
            'category'    => $this->cat1->id,
            'timecreated' => $now,
        ];
        // Enrol coupons.
        $records[] = [
            'code'        => 'enrol1',
            'type'        => 'enrol',
            'value'       => 100,
            'courses'     => $this->c1->id . ',' . $this->c7->id,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'enrol2',
            'type'        => 'enrol',
            'courses'     => implode(',', [$this->c2->id, $this->c3->id, $this->c4->id]),
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'enrol3',
            'type'        => 'enrol',
            'courses'     => $this->c5->id,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'enrol4',
            'type'        => 'enrol',
            'courses'     => null,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'enrol5',
            'type'        => 'enrol',
            'courses'     => implode(',', [$this->c2->id, $this->c3->id, $this->c4->id]),
            'maxusage'    => 2,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'enrol6',
            'type'        => 'enrol',
            'courses'     => implode(',', [$this->c2->id, $this->c3->id, $this->c4->id]),
            'maxusage'    => 5,
            'maxperuser'  => 2,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'enrol7',
            'type'        => 'enrol',
            'value'       => 50,
            'category'    => null,
            'courses'     => implode(',', [$this->c2->id, $this->c3->id, $this->c4->id]),
            'validfrom'   => $notav,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'enrol8',
            'type'        => 'enrol',
            'value'       => 50,
            'courses'     => implode(',', [$this->c2->id, $this->c3->id, $this->c4->id]),
            'validto'     => $expired,
            'timecreated' => $now,
        ];

        // Category coupons.
        $records[] = [
            'code'        => 'category1',
            'type'        => 'category',
            'value'       => 100,
            'category'    => $this->cat1->id, // Should be valid in its child.
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'category2',
            'type'        => 'category',
            'value'       => 70,
            'category'    => $this->cat4->id, // Shouldn't be valid in its parent.
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'category3',
            'type'        => 'category',
            'value'       => 50,
            'category'    => null, // Invalid record.
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'category4',
            'type'        => 'category',
            'value'       => 50,
            'category'    => $this->cat3->id,
            'maxusage'    => 2,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'category5',
            'type'        => 'category',
            'value'       => 100,
            'category'    => $this->cat3->id,
            'courses'     => null,
            'maxusage'    => 5,
            'maxperuser'  => 2,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'category6',
            'type'        => 'category',
            'value'       => 70,
            'category'    => $this->cat3->id,
            'validfrom'   => $notav,
            'timecreated' => $now,
        ];
        $records[] = [
            'code'        => 'category7',
            'type'        => 'category',
            'value'       => 50,
            'category'    => $this->cat3->id,
            'validto'     => $expired,
            'timecreated' => $now,
        ];

        foreach ($records as $record) {
            $DB->insert_record('enrol_wallet_coupons', $record, false);
        }
    }

    /**
     * We will use this frequently, so we can shorten the arguments.
     * @param int|array $value
     */
    private function set_config($value): void {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        config::make()->coupons = $value;
    }
}
