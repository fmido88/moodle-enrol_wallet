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

/**
 * Extensive integration test for offers class covering all offer types, combinations, sets, and edge cases.
 *
 * @package    enrol_wallet
 * @category   test
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\discounts;

use context_course;
use enrol_wallet\local\config;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\testing;
use enrol_wallet\local\utils\timedate;
use enrol_wallet_plugin;
use phpunit_util;
use stdClass;

/**
 * Testing offers and offer items.
 * @coversDefaultClass \enrol_wallet\local\discounts\offers
 */
class offers_extensive_test extends \advanced_testcase {
    /**
     * The user object.
     * @var stdClass
     */
    protected $user;

    /**
     * @var stdClass
     */
    protected $cattarget;

    /**
     * @var stdClass
     */
    protected $catother;

    /**
     * @var stdClass
     */
    protected $catmixed;

    /**
     * @var stdClass
     */
    protected $targetcourse;

    /**
     * @var stdClass[]
     */
    protected $samecatcourses = [];

    /**
     * @var stdClass[]
     */
    protected $othercatcourses = [];

    /**
     * @var stdClass[]
     */
    protected $mixedcourses = [];

    /**
     * @var instance
     */
    protected $targetinstance;

    /**
     * Build courses, categories and anything needed for testing.
     * @return void
     */
    protected function build() {
        $generator = phpunit_util::get_data_generator();
        $walletgenerator = testing::get_generator();

        // Check if any static courses still exist in the database.
        if (!empty($this->samecatcourses) && isset($this->samecatcourses[1])) {
            global $DB;
            $courseexists = $DB->record_exists('course', ['id' => $this->samecatcourses[1]->id]);

            if ($courseexists) {
                return; // Data is still valid, don't rebuild.
            }
        }

        // Custom profile fields for profile offers.
        $generator->create_custom_profile_field([
            'datatype'  => 'text',
            'shortname' => 'testfield1',
            'name'      => 'Test Field 1',
        ]);
        $generator->create_custom_profile_field([
            'datatype'  => 'text',
            'shortname' => 'testfield2',
            'name'      => 'Test Field 2',
        ]);
        $generator->create_custom_profile_field([
            'datatype'  => 'text',
            'shortname' => 'testfield3',
            'name'      => 'Test Field 3 (empty)',
        ]);

        // Main test user with profile data for matching.
        $this->user = $generator->create_user([
            'username'                 => 'testusercool',
            'firstname'                => 'TestFirst',
            'lastname'                 => 'TestLast',
            'email'                    => 'test@example.com',
            'institution'              => 'TestUni',
            'profile_field_testfield1' => 'MatchValue1',
            'profile_field_testfield2' => 'MatchValue2',
            'profile_field_testfield3' => '', // Empty for IS_EMPTY test.
        ]);

        // Categories.
        $this->cattarget = $generator->create_category();
        $this->catother = $generator->create_category();
        $this->catmixed = $generator->create_category();

        // Target course & instance (high cost for discount verification).
        $this->targetcourse = $generator->create_course(['category' => $this->cattarget->id]);
        $this->targetinstance = testing::get_generator()->create_instance($this->targetcourse->id, false, 1000.00);
        $this->targetinstance->set_user($this->user);

        // Same category courses for COURSES_ENROL_SAME_CAT (4 courses).
        for ($i = 1; $i <= 6; $i++) {
            $course = $generator->create_course(['category' => $this->cattarget->id]);
            $this->samecatcourses[$i] = $course;
        }

        // Other category courses for OTHER_CATEGORY_COURSES (4 courses).
        for ($i = 1; $i <= 4; $i++) {
            $course = $generator->create_course(['category' => $this->catother->id]);
            $this->othercatcourses[$i] = $course;
        }

        // Mixed category (2 courses).
        for ($i = 1; $i <= 2; $i++) {
            $course = $generator->create_course(['category' => $this->catmixed->id]);
            $this->mixedcourses[$i] = $course;
        }
    }

    /**
     * Created enrolment and enrol the user in some courses for test validation of the
     * offers.
     */
    protected function create_enrollments() {
        \availability_profile\condition::wipe_static_cache();

        // Mix enrolments between manual and wallet to test wallet only validation.
        $wallet = enrol_wallet_plugin::get_plugin();
        $manual = enrol_get_plugin('manual');

        // Store and retrieve manual enrol instances.
        $manualinstance = function (int $courseid) use ($manual) {
            global $DB;
            static $instances = [];

            if (isset($instances[$courseid])) {
                return $instances[$courseid];
            }

            $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid]);

            if ($instance->status != ENROL_INSTANCE_ENABLED) {
                $instance->status = ENROL_INSTANCE_ENABLED;
                $DB->update_record('enrol', $instance);
            }

            $instances[$courseid] = $instance;

            return $instances[$courseid];
        };

        // Prepare course enrollment patterns for different categories.
        foreach ([$this->samecatcourses, $this->othercatcourses] as $categorycourses) {
            foreach ($categorycourses as $i => $course) {
                if ($i > 3) {
                    break;
                }

                $courseid = $course->id;
                $instance = testing::get_generator()->create_instance($courseid);

                if ($i === 1) {
                    // With wallet and active.
                    $wallet->enrol_user($instance, $this->user->id);
                    $this->assertTrue(is_enrolled(context_course::instance($courseid), $this->user));
                    $this->assertTrue(is_enrolled(context_course::instance($courseid), $this->user, onlyactive: true));
                } else if ($i === 2) {
                    // With manual and active.
                    $manualinstanceobj = $manualinstance($courseid);
                    $manual->enrol_user($manualinstanceobj, $this->user->id);
                    $this->assertTrue(is_enrolled(context_course::instance($courseid), $this->user));
                    $this->assertTrue(is_enrolled(context_course::instance($courseid), $this->user, onlyactive: true));
                } else if ($i === 3) {
                    // With wallet not active.
                    $wallet->enrol_user($instance, $this->user->id, null, timedate::time() - WEEKSECS, timedate::time() - DAYSECS);
                    $this->assertTrue(is_enrolled(context_course::instance($courseid), $this->user));
                    $this->assertFalse(is_enrolled(context_course::instance($courseid), $this->user, onlyactive: true));
                }
            }
        }

        // Mixed category uses two courses for different edge states.
        foreach ($this->mixedcourses as $i => $course) {
            $courseid = $course->id;
            $instance = testing::get_generator()->create_instance($courseid);

            if ($i === 1) {
                // With wallet and active.
                $wallet->enrol_user($instance, $this->user->id);
                $this->assertTrue(is_enrolled(context_course::instance($courseid), $this->user));
            } else if ($i === 2) {
                // With wallet inactive.
                $wallet->enrol_user($instance, $this->user->id, null, timedate::time() - WEEKSECS, timedate::time() - DAYSECS);
                $this->assertTrue(is_enrolled(context_course::instance($courseid), $this->user));
                $this->assertFalse(is_enrolled(context_course::instance($courseid), $this->user, onlyactive: true));
            }
        }
    }

    /**
     * Data provider for atomic offer combinations (type => [type, valid offers, invalid offers]).
     * @return array
     */
    protected function atomic_offer_provider(): array {
        $data = [];

        // TIME offers - simple valid/invalid pairs.
        $data[time_offer::key()] = [
            time_offer::key(),
            [
                time_offer::mock_offer(null, 10),
                time_offer::mock_offer(null, 20),
            ],
            [
                time_offer::mock_offer(null, 30, timedate::time() - DAYSECS, timedate::time() - WEEKSECS), // Expired.
                time_offer::mock_offer(null, 40, timedate::time() + WEEKSECS, timedate::time() + DAYSECS), // Not start.
            ],
        ];

        // COURSE_ENROL_COUNT - match actual enrollments (2 same-category courses exist).
        $data[course_enrol_count_offer::key()] = [
            course_enrol_count_offer::key(),
            [
                course_enrol_count_offer::mock_offer(null, 5, 1, true),
                course_enrol_count_offer::mock_offer(null, 5, 1, false),
                course_enrol_count_offer::mock_offer(null, 10, 2, true),
                course_enrol_count_offer::mock_offer(null, 10, 2, false),
                course_enrol_count_offer::mock_offer(null, 20, 3, false),
            ],
            [
                course_enrol_count_offer::mock_offer(null, 20, 3, true), // Not active in one.
                course_enrol_count_offer::mock_offer(null, 30, 4, false), // Exceed.
                course_enrol_count_offer::mock_offer(null, 30, 4, true), // Exceed.
            ],
        ];

        // PROFILE_FIELD offers.
        $data[profile_field_offer::key()] = [
            profile_field_offer::key(),
            [
                profile_field_offer::mock_offer(null, 15, null, 'firstname', profile_field_offer::PFOP_STARTS_WITH, 'Test'),
                profile_field_offer::mock_offer(null, 10, null, 'institution', profile_field_offer::PFOP_IS_EQUAL_TO, 'TestUni'),
                profile_field_offer::mock_offer(null, 10, null, 'username', profile_field_offer::PFOP_ENDS_WITH, 'cool'),
                profile_field_offer::mock_offer(null, 10, null, 'address', profile_field_offer::PFOP_IS_EMPTY),
                profile_field_offer::mock_offer(null, 10, null, 'email', profile_field_offer::PFOP_IS_NOT_EMPTY),
                profile_field_offer::mock_offer(null, 10, 'testfield1', null, profile_field_offer::PFOP_IS_NOT_EMPTY),
                profile_field_offer::mock_offer(null, 10, 'testfield2', null, profile_field_offer::PFOP_CONTAINS, '2'),
                profile_field_offer::mock_offer(null, 10, 'testfield1', null, profile_field_offer::PFOP_DOES_NOT_CONTAIN, 'not'),
                profile_field_offer::mock_offer(null, 10, 'testfield3', null, profile_field_offer::PFOP_IS_EMPTY),
            ],
            [
                profile_field_offer::mock_offer(null, 20, null, 'firstname', profile_field_offer::PFOP_IS_EQUAL_TO, 'NoMatch'),
                profile_field_offer::mock_offer(null, 10, null, 'username', profile_field_offer::PFOP_STARTS_WITH, 'cool'),
                profile_field_offer::mock_offer(null, 10, null, 'address', profile_field_offer::PFOP_IS_NOT_EMPTY),
                profile_field_offer::mock_offer(null, 10, null, 'email', profile_field_offer::PFOP_ENDS_WITH, 'mydomain.com'),
                profile_field_offer::mock_offer(null, 10, 'testfield1', null, profile_field_offer::PFOP_IS_EMPTY),
                profile_field_offer::mock_offer(null, 10, 'testfield2', null, profile_field_offer::PFOP_DOES_NOT_CONTAIN, '2'),
                profile_field_offer::mock_offer(null, 10, 'testfield1', null, profile_field_offer::PFOP_IS_EQUAL_TO, 'not'),
                profile_field_offer::mock_offer(null, 10, 'testfield3', null, profile_field_offer::PFOP_IS_NOT_EMPTY),
            ],
        ];

        // OTHER_CATEGORY_COURSES offers.
        $data[other_category_courses_offer::key()] = [
            other_category_courses_offer::key(),
            [
                other_category_courses_offer::mock_offer(null, 12, $this->catother->id, 1, false),
                other_category_courses_offer::mock_offer(null, 12, $this->catother->id, 1, true),
                other_category_courses_offer::mock_offer(null, 12, $this->catother->id, 2, false),
                other_category_courses_offer::mock_offer(null, 12, $this->catother->id, 2, true),
                other_category_courses_offer::mock_offer(null, 12, $this->catother->id, 3, false),
                other_category_courses_offer::mock_offer(null, 12, $this->catmixed->id, 2, false),
                other_category_courses_offer::mock_offer(null, 12, $this->catmixed->id, 1, true),
            ],
            [
                other_category_courses_offer::mock_offer(null, 12, $this->catother->id, 3, true),
                other_category_courses_offer::mock_offer(null, 12, $this->catother->id, 4, false),
            ],
        ];

        $extractids = fn ($courses) => array_map(fn (stdClass $course) => $course->id, $courses);

        // COURSES_ENROL_SAME_CAT offers - use valid course IDs from setup.
        $data[courses_enrol_same_cat_offer::key()] = [
            courses_enrol_same_cat_offer::key(),
            [
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    18,
                    [$this->samecatcourses[1]->id],
                    courses_enrol_same_cat_offer::COND_ALL,
                    true,
                    true
                ),
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    18,
                    \array_slice($extractids($this->samecatcourses), 0, 3),
                    courses_enrol_same_cat_offer::COND_ANY,
                    true,
                    true
                ),
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    18,
                    \array_slice($extractids($this->samecatcourses), 0, 5),
                    courses_enrol_same_cat_offer::COND_ANY,
                    true,
                    true
                ),
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    18,
                    \array_slice($extractids($this->samecatcourses), 0, 3),
                    courses_enrol_same_cat_offer::COND_ALL,
                    false,
                    false
                ),
            ],
            [
                // By wallet but not active.
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    40,
                    \array_slice($extractids($this->samecatcourses), 2, 1),
                    courses_enrol_same_cat_offer::COND_ANY,
                    true,
                    false
                ),
                // By Manual and active.
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    40,
                    \array_slice($extractids($this->samecatcourses), 1, 1),
                    courses_enrol_same_cat_offer::COND_ANY,
                    false,
                    true
                ),
                // One not enrolled.
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    40,
                    \array_slice($extractids($this->samecatcourses), 0, 4),
                    courses_enrol_same_cat_offer::COND_ALL,
                    false,
                    false
                ),
                // One not wallet.
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    40,
                    \array_slice($extractids($this->samecatcourses), 0, 2),
                    courses_enrol_same_cat_offer::COND_ALL,
                    false,
                    true
                ),
                // One not active.
                courses_enrol_same_cat_offer::mock_offer(
                    null,
                    40,
                    \array_slice($extractids($this->samecatcourses), 0, 3),
                    courses_enrol_same_cat_offer::COND_ALL,
                    true,
                    false
                ),
            ],
        ];

        // OFFERS_SET offers.
        $data['set'] = [
            offers_set::key(),
            [
                // All valid.
                offers_set::mock_offer(null, 30, [
                    time_offer::mock_offer(null, 5),
                    profile_field_offer::mock_offer(null, 10, null, 'firstname', profile_field_offer::PFOP_STARTS_WITH, 'Test'),
                    course_enrol_count_offer::mock_offer(null, 7, 1, true),
                ], offers_set::OP_AND),
                // Only one valid.
                offers_set::mock_offer(null, 30, [
                    time_offer::mock_offer(null, 5),
                    profile_field_offer::mock_offer(null, 10, null, 'firstname', profile_field_offer::PFOP_STARTS_WITH, 'First'),
                    course_enrol_count_offer::mock_offer(null, 7, 3, true),
                ], offers_set::OP_OR),
            ],
            [
                // All Invalid OR.
                offers_set::mock_offer(null, 30, [
                    time_offer::mock_offer(null, 10, timedate::time() - DAYSECS, timedate::time() - WEEKSECS),
                    profile_field_offer::mock_offer(
                        null,
                        10,
                        null,
                        'lastname',
                        profile_field_offer::PFOP_CONTAINS,
                        'First'
                    ),
                    course_enrol_count_offer::mock_offer(null, 7, 5, false),
                ], offers_set::OP_OR),
                // Only one invalid AND.
                offers_set::mock_offer(null, 50, [
                    time_offer::mock_offer(null, 10),
                    profile_field_offer::mock_offer(
                        null,
                        3,
                        null,
                        'firstname',
                        profile_field_offer::PFOP_IS_NOT_EMPTY,
                        'NoMatch'
                    ),
                    courses_enrol_same_cat_offer::mock_offer(
                        null,
                        40,
                        \array_slice($extractids($this->samecatcourses), 0, 3),
                        courses_enrol_same_cat_offer::COND_ALL,
                        true,
                        false
                    ),
                ], offers_set::OP_AND),
            ],
        ];

        $getrandomoffer = function (bool $valid = true) use ($data) {
            $offers = $data[array_rand($data)][$valid ? 1 : 2];

            return $offers[array_rand($offers)];
        };

        // Depth 1 offers_set (set containing another set).
        $data['set_depth1'] = [
            offers_set::key() . '_depth1',
            [
                offers_set::mock_offer(null, 55, [
                    offers_set::mock_offer(null, 25, [
                        time_offer::mock_offer(null, 10),
                        profile_field_offer::mock_offer(
                            null,
                            15,
                            null,
                            'institution',
                            profile_field_offer::PFOP_IS_EQUAL_TO,
                            'TestUni'
                        ),
                    ], offers_set::OP_AND),
                    time_offer::mock_offer(null, 5),
                ], offers_set::OP_OR),
                offers_set::mock_offer(null, 35, [
                    $getrandomoffer(false),
                    $getrandomoffer(false),
                    $getrandomoffer(false),
                    offers_set::mock_offer(null, null, [
                        $getrandomoffer(),
                        $getrandomoffer(),
                        $getrandomoffer(),
                    ], offers_set::OP_AND),
                ], offers_set::OP_OR),
                offers_set::mock_offer(null, 35, [
                    $getrandomoffer(),
                    $getrandomoffer(),
                    $getrandomoffer(),
                    offers_set::mock_offer(null, null, [
                        $getrandomoffer(false),
                        $getrandomoffer(),
                        $getrandomoffer(false),
                    ], offers_set::OP_OR),
                ], offers_set::OP_AND),
            ],
            [
                offers_set::mock_offer(null, 70, [
                    offers_set::mock_offer(null, 25, [
                        time_offer::mock_offer(
                            null,
                            10,
                            timedate::time() - DAYSECS,
                            timedate::time() - WEEKSECS
                        ), // Expired.
                        profile_field_offer::mock_offer(
                            null,
                            5,
                            null,
                            'firstname',
                            profile_field_offer::PFOP_IS_EQUAL_TO,
                            'NoMatch'
                        ),
                    ], offers_set::OP_AND),
                ], offers_set::OP_AND), // All invalid.
                offers_set::mock_offer(null, 35, [
                    $getrandomoffer(false),
                    $getrandomoffer(false),
                    offers_set::mock_offer(null, null, [
                        $getrandomoffer(),
                        $getrandomoffer(),
                        $getrandomoffer(false),
                    ], offers_set::OP_AND),
                ], offers_set::OP_OR),
                offers_set::mock_offer(null, 35, [
                    $getrandomoffer(false),
                    $getrandomoffer(false),
                    offers_set::mock_offer(null, null, [
                        $getrandomoffer(),
                        $getrandomoffer(),
                        $getrandomoffer(false),
                    ], offers_set::OP_AND),
                ], offers_set::OP_OR),
            ],
        ];

        // Depth 2 offers_set (set containing set containing set).
        $data['set_depth2'] = [
            offers_set::key() . '_depth2',
            [
                offers_set::mock_offer(null, 88, [
                    offers_set::mock_offer(null, 30, [
                        offers_set::mock_offer(null, 15, [
                            time_offer::mock_offer(null, 8),
                            profile_field_offer::mock_offer(
                                null,
                                5,
                                null,
                                'institution',
                                profile_field_offer::PFOP_IS_EQUAL_TO,
                                'TestUni'
                            ),
                        ], offers_set::OP_OR),
                        profile_field_offer::mock_offer(
                            null,
                            14,
                            null,
                            'firstname',
                            profile_field_offer::PFOP_STARTS_WITH,
                            'Test'
                        ),
                    ], offers_set::OP_AND),
                ], offers_set::OP_OR),
            ],
            [
                offers_set::mock_offer(null, 90, [
                    offers_set::mock_offer(null, 35, [
                        offers_set::mock_offer(null, 20, [
                            time_offer::mock_offer(
                                null,
                                17,
                                timedate::time() - DAYSECS,
                                timedate::time() - WEEKSECS
                            ), // Expired.
                            profile_field_offer::mock_offer(
                                null,
                                2,
                                null,
                                'firstname',
                                profile_field_offer::PFOP_IS_EQUAL_TO,
                                'NoMatch'
                            ),
                        ], offers_set::OP_AND),
                    ], offers_set::OP_AND),
                ], offers_set::OP_AND),
            ],
        ];

        return $data;
    }

    /**
     * Combination of offers.
     * @return array<array>
     */
    protected function combo_provider(): array {
        $now = timedate::time();
        $combos = [];

        // Empty offers.
        $combos['empty'] = [
            [],
            [
                'availablecount'         => 0,
                'availablevalues'        => [],
                'maxvalid'               => 0,
                'sum'                    => 0,
                'seq'                    => 0,
                'detailedavailablecount' => 0,
            ],
        ];

        // Single valid time offer.
        $combos['singlevalid'] = [
            [time_offer::mock_offer(null, 25)],
            [
                'availablecount'         => 1,
                'availablevalues'        => [25],
                'maxvalid'               => 25,
                'sum'                    => 25,
                'seq'                    => 0,
                'detailedavailablecount' => 1,
            ],
        ];

        // Multiple valid.
        $validtime1 = time_offer::mock_offer(null, 10);
        $validtime2 = time_offer::mock_offer(null, 20);
        $combos['multiplevalid'] = [
            [$validtime1, $validtime2],
            [
                'availablecount'         => 2,
                'availablevalues'        => [10, 20],
                'maxvalid'               => 20,
                'sum'                    => 30,
                'seq'                    => 0,
                'detailedavailablecount' => 2,
            ],
        ];

        // Mixed valid/invalid.
        $expiredtime = time_offer::mock_offer(null, 50, $now + WEEKSECS, $now + DAYSECS);
        $combos['mixed'] = [
            [$validtime1, $expiredtime],
            [
                'availablecount'         => 1,
                'availablevalues'        => [10],
                'maxvalid'               => 10,
                'sum'                    => 10,
                'seq'                    => 0,
                'detailedavailablecount' => 1,
            ],
        ];

        // High sum capped.
        $combos['sumcap'] = [
            [time_offer::mock_offer(null, 60), time_offer::mock_offer(null, 60)],
            [
                'availablecount'         => 2,
                'availablevalues'        => [60, 60],
                'maxvalid'               => 60,
                'sum'                    => 100,
                'seq'                    => 0,
                'detailedavailablecount' => 2,
            ], // Capped.
        ];

        // Sequential calculation.
        $combos['seq'] = [
            [time_offer::mock_offer(null, 20), time_offer::mock_offer(null, 30)],
            [
                'availablecount'         => 2,
                'availablevalues'        => [20, 30],
                'maxvalid'               => 30,
                'sum'                    => 50,
                'seq'                    => 44,
                'detailedavailablecount' => 2,
            ], // 20 + 30% of 80 = 44
        ];

        // Profile field matching user data.
        $profilevalid = profile_field_offer::mock_offer(
            null,
            15,
            sf: 'username',
            op: profile_field_offer::PFOP_STARTS_WITH,
            value: 'test'
        );
        $profileinvalid = profile_field_offer::mock_offer(
            null,
            25,
            sf: 'firstname',
            op: profile_field_offer::PFOP_IS_EQUAL_TO,
            value: 'NoMatch'
        );
        $combos['profile'] = [
            [$profilevalid, $profileinvalid],
            [
                'availablecount'         => 1,
                'availablevalues'        => [15],
                'maxvalid'               => 15,
                'sum'                    => 15,
                'seq'                    => 0,
                'detailedavailablecount' => 2,
            ],
        ];

        // Course enrollment offers (pre-enroll user).
        $countoffervalid = course_enrol_count_offer::mock_offer(null, 12, 1, true);
        $countofferinvalid = course_enrol_count_offer::mock_offer(null, 18, 3, true);
        $combos['enrolcount'] = [
            [$countoffervalid, $countofferinvalid],
            [
                'availablecount'         => 1,
                'availablevalues'        => [12],
                'maxvalid'               => 12,
                'sum'                    => 12,
                'seq'                    => 0,
                'detailedavailablecount' => 2,
            ],
        ];

        // Offer set - all valid AND.
        $setallvalid = offers_set::mock_offer(null, 45, [
            $validtime1,
            $profilevalid,
            course_enrol_count_offer::mock_offer(null, 7, 1, true),
        ], offers_set::OP_AND);
        $combos['setallvalid'] = [
            [$setallvalid],
            [
                'availablecount'         => 1,
                'availablevalues'        => [45],
                'maxvalid'               => 45,
                'sum'                    => 45,
                'seq'                    => 0,
                'detailedavailablecount' => 1,
            ],
        ];

        // Offer set - mixed AND (fails). All suboffers are invalid but not hidden.
        $setmixed = offers_set::mock_offer(null, 35, [
            $profileinvalid,
            course_enrol_count_offer::mock_offer(null, 3, 4, true),
            other_category_courses_offer::mock_offer(null, 5, $this->cattarget->id, 4, false),
        ], offers_set::OP_AND);
        $combos['setmixedfail'] = [
            [$setmixed],
            [
                'availablecount'         => 0,
                'availablevalues'        => [],
                'maxvalid'               => 0,
                'sum'                    => 0,
                'seq'                    => 0,
                'detailedavailablecount' => 1,
            ],
        ];

        // Nested sets.
        $nestedvalid = offers_set::mock_offer(null, 55, [
            $setallvalid,
            offers_set::mock_offer(null, 22, [$validtime2, $profilevalid, $expiredtime], offers_set::OP_OR),
            time_offer::mock_offer(null, 3, timedate::time() + DAYSECS, timedate::time() - DAYSECS),
        ], offers_set::OP_AND);
        $combos['nestedvalid'] = [
            [$nestedvalid],
            [
                'availablecount'         => 1,
                'availablevalues'        => [55],
                'maxvalid'               => 55,
                'sum'                    => 55,
                'seq'                    => 0,
                'detailedavailablecount' => 1,
            ],
        ];

        // Nested sets (depth 2): inner set fails but OR makes the outer valid.
        $nesteddepth2 = offers_set::mock_offer(null, 65, [
            $setmixed,
            $validtime2,
            offers_set::mock_offer(null, 10, [$expiredtime, $setallvalid, $profilevalid], offers_set::OP_OR),
        ], offers_set::OP_OR);
        $combos['nesteddepth2'] = [
            [$nesteddepth2],
            [
                'availablecount'            => 1,
                'availablevalues'           => [65],
                'maxvalid'                  => 65,
                'sum'                       => 65,
                'seq'                       => 0,
                'detailedavailablecount'    => 1,
            ],
        ];

        // Fully invalid nested set (depth 2 AND inner set invalid, non-hidden sub offers).
        $nesteddepth2invalid = offers_set::mock_offer(null, 75, [
            $setmixed,
            profile_field_offer::mock_offer(null, 2, null, 'firstname', profile_field_offer::PFOP_IS_EQUAL_TO, 'NoMatch'),
            offers_set::mock_offer(null, 5, [
                course_enrol_count_offer::mock_offer(null, 3, 4, true),
                other_category_courses_offer::mock_offer(null, 3, $this->cattarget->id, 4, false),
                profile_field_offer::mock_offer(null, 2, null, 'email', profile_field_offer::PFOP_IS_EQUAL_TO, 'nope@example.com'),
            ], offers_set::OP_AND),
        ], offers_set::OP_AND);
        $combos['nesteddepth2invalid'] = [
            [$nesteddepth2invalid],
            [
                'availablecount'         => 0,
                'availablevalues'        => [],
                'maxvalid'               => 0,
                'sum'                    => 0,
                'seq'                    => 0,
                'detailedavailablecount' => 1,
            ],
        ];

        return $combos;
    }

    /**
     * Testing with atomic offer provider.
     * @param  string $type
     * @param  array  $validoffers
     * @param  array  $invalidoffers
     * @return void
     */
    protected function testing_with_atomic_offer_provider(string $type, array $validoffers, array $invalidoffers): void {
        $instance = clone $this->targetinstance;

        // Test with valid offers only.
        $instance->customtext3 = json_encode($validoffers);

        $offersobj = new offers($instance, $this->user->id);
        $available = $offersobj->get_available_discounts();

        $this->assertCount(
            \count($validoffers),
            $available,
            'Available discounts count mismatch for valid offers of type ' . $type
        );

        if (!empty($validoffers)) {
            foreach ($validoffers as $offer) {
                $this->assertContainsEquals(
                    $offer->discount,
                    $available,
                    'Valid offer discount should be available for type ' . $type
                );
            }
            $this->assertGreaterThan(0.01, $offersobj->get_max_valid_discount());
            $this->assertGreaterThan(0.01, $offersobj->get_sum_discounts());
            $this->assertGreaterThan(0.01, $offersobj->get_seq_discounts());
        }

        // Test that each invalid offer fails validation/is hidden.
        foreach ($invalidoffers as $offer) {
            $instance->mark_as_dirty();
            $instance->customtext3 = json_encode([$offer]);

            $offersobj = new offers($instance, $this->user->id);
            $available = $offersobj->get_available_discounts();
            // If we get here without exception, the offer should either be absent or not available.
            $this->assertNotContains(
                $offer->discount,
                $available,
                'Invalid offer discount should not be available for type ' . $type
            );
        }
    }

    /**
     * Testing with combo provider.
     * @param  array $offers
     * @param  array $expected
     * @return void
     */
    protected function testing_methods_with_combos(array $offers, array $expected): void {
        $instance = clone $this->targetinstance;
        $instance->customtext3 = json_encode($offers);

        // Test constructor & raw offers.
        $offersobj = new offers($instance, $this->user->id);
        $this->assertEquals(\count($offers), \count($offersobj->get_raw_offers()));

        // Test available discounts.
        $available = $offersobj->get_available_discounts();
        $this->assertEquals($expected['availablecount'], \count($available));

        if (!empty($expected['availablevalues'])) {
            $this->assertEqualsCanonicalizing($expected['availablevalues'], $available);
        }

        // Test max valid.
        $this->assertEqualsWithDelta($expected['maxvalid'], $offersobj->get_max_valid_discount(), 0.01);

        // Test sum (capped 100).
        $this->assertEqualsWithDelta($expected['sum'], $offersobj->get_sum_discounts(), 0.01);

        // Test detailed.
        $detailed = $offersobj->get_detailed_offers(true);
        $this->assertEquals($expected['detailedavailablecount'], \count($detailed));
        $detailedall = $offersobj->get_detailed_offers(false);
        $this->assertGreaterThanOrEqual(count($detailed), \count($detailedall));

        // Test format (HTML).
        $html = $offersobj->format_offers_descriptions(true);

        if ($expected['detailedavailablecount'] > 1) {
            $this->assertStringContainsString('Offers', $html); // Heading present.
            $this->assertStringContainsString('li', $html); // List present if offers.
        } else if ($expected['detailedavailablecount'] == 0) {
            $this->assertEmpty($html);
        } else {
            $this->assertStringContainsString('Offers', $html); // Heading present.
            $this->assertStringContainsString('% DISCOUNT', $html); // Heading present.
        }

        // Test raw max discount (ignores validation).
        $rawmax = $offersobj->get_max_discount(); // Todo: Fix this test because it not ignore hidden.
        $expectedmax = $offers ? max(array_column($offers, 'discount')) : 0;
        $this->assertGreaterThanOrEqual(round($expectedmax, 2), round($rawmax, 2));

        // Cost after discount integration.
        $instance->cost = 1000.00;
        $instance->update();

        config::make()->discount_behavior = instance::B_SUM;
        $discountedcost = $instance->get_cost_after_discount();
        $expectedcost = 1000 * (100 - $offersobj->get_sum_discounts()) / 100;
        $this->assertEqualsWithDelta($expectedcost, $discountedcost, 0.01);

        $instance->mark_as_dirty();
        config::make()->discount_behavior = instance::B_MAX;
        $discountedcost = $instance->get_cost_after_discount();
        $expectedcost = 1000 * (100 - $offersobj->get_max_valid_discount()) / 100;
        $this->assertEqualsWithDelta($expectedcost, $discountedcost, 0.01);
    }

    /**
     * Run all tests.
     * @return void
     */
    public function test_all(): void {
        $this->resetAfterTest();
        $this->build();
        $this->create_enrollments();

        $this->setUser($this->user);
        $data = $this->atomic_offer_provider();

        foreach ($data as $args) {
            $this->testing_with_atomic_offer_provider(...$args);
        }

        $data = $this->combo_provider();

        foreach ($data as $args) {
            $this->testing_methods_with_combos(...$args);
        }
    }
}
