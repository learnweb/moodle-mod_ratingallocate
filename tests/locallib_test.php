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

namespace mod_ratingallocate;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(dirname(__FILE__) . '/generator/lib.php');
require_once(dirname(__FILE__) . '/../locallib.php');

use mod_ratingallocate\db as this_db;
use mod_ratingallocate_generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * mod_ratingallocate generator tests
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group      mod_ratingallocate
 * @copyright  usener
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_ratingallocate\ratingallocate
 */
#[CoversClass(ratingallocate::class)]
#[CoversFunction('get_choices_with_allocationcount')]
#[CoversFunction('distribute_choices')]
#[CoversFunction('get_options_titles')]
#[CoversFunction('get_allocations')]
#[CoversFunction('get_ratings_for_rateable_choices')]
final class locallib_test extends \advanced_testcase {

    /**
     * Tests a simple allocation scenario.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers \mod_ratingallocate\ratingallocate::distribute_choices
     */
    public function test_simple(): void {
        global $DB;
        \core_php_time_limit::raise();
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $teacher = \mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $this->setUser($teacher);

        // There should not be any module for that course first.
        $this->assertFalse(
                $DB->record_exists(this_db\ratingallocate::TABLE,
                       [this_db\ratingallocate::COURSE => $course->id]
                )
        );

        // Set default data for category.
        $moduledata = \mod_ratingallocate_generator::get_default_values();
        $moduledata['course'] = $course;

        $choicedata = \mod_ratingallocate_generator::get_default_choice_data();
        foreach ($choicedata as $id => $choice) {
            $choice['maxsize'] = 2;
            $choice['active'] = true;
            $choicedata[$id] = $choice;
        }

        // Create activity.
        $mod = \mod_ratingallocate_generator::create_instance_with_choices($this, $moduledata, $choicedata);
        $this->assertEquals(2, $DB->count_records(this_db\ratingallocate_choices::TABLE),
                "Failure, debug info: " . implode(",", [this_db\ratingallocate_choices::ID => $mod->id]));

        $student1 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student2 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student3 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student4 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);

        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $teacher);
        $choices = $ratingallocate->get_rateable_choices();

        $choice1 = reset($choices);
        $choice2 = end($choices);

        // Create preferences.
        $prefersnon = [];
        foreach ($choices as $choice) {
            $prefersnon[$choice->{this_db\ratingallocate_choices::ID}] = [
                    this_db\ratingallocate_ratings::CHOICEID => $choice->{this_db\ratingallocate_choices::ID},
                    this_db\ratingallocate_ratings::RATING => 0];
        }
        $prefersfirst = json_decode(json_encode($prefersnon), true);
        $prefersfirst[$choice1->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;
        $preferssecond = json_decode(json_encode($prefersnon), true);
        $preferssecond[$choice2->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;

        // Assign preferences.
        \mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student1, $prefersfirst);
        \mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student2, $prefersfirst);
        \mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student3, $preferssecond);
        \mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student4, $preferssecond);

        // Allocate choices.
        $timeneeded = $ratingallocate->distribute_choices();
        $this->assertGreaterThan(0, $timeneeded);
        $this->assertLessThan(0.1, $timeneeded, 'Allocation is very slow');

        $allocationcount = $ratingallocate->get_choices_with_allocationcount();
        $this->assertCount(2, $allocationcount);

        // Test allocations.
        $numallocations = $DB->count_records(this_db\ratingallocate_allocations::TABLE);

        $this->assertEquals(4, $numallocations, 'There should be only 4 allocations, since there are only 4 choices.');
        $allocations = $DB->get_records(this_db\ratingallocate_allocations::TABLE,
                [this_db\ratingallocate_allocations::RATINGALLOCATEID => $mod->{this_db\ratingallocate::ID}],
                '');

        $mapuserid = function($elem) {
            return $elem->{this_db\ratingallocate_allocations::USERID};
        };

        $alloc1 = self::filter_allocations_by_choice($allocations, $choice1->{this_db\ratingallocate_choices::ID});
        $alloc2 = self::filter_allocations_by_choice($allocations, $choice2->{this_db\ratingallocate_choices::ID});

        // Assert, that student 1 was allocated to choice 1.
        $this->assertContains($student1->id, array_map($mapuserid, $alloc1));
        // Assert, that student 2 was allocated to choice 1.
        $this->assertContains($student2->id, array_map($mapuserid, $alloc1));
        // Assert, that student 3 was allocated to choice 2.
        $this->assertContains($student3->id, array_map($mapuserid, $alloc2));
        // Assert, that student 4 was allocated to choice 2.
        $this->assertContains($student4->id, array_map($mapuserid, $alloc2));

        // We now unenrol a user and make sure he will not be considered in distribution.
        $manualenrolplugin = enrol_get_plugin('manual');
        $enrolinstance = array_values(
            array_filter(enrol_get_instances($course->id, true), fn($instance) => $instance->enrol == "manual"))[0];

        $manualenrolplugin->unenrol_user($enrolinstance, $student3->id);
        // Re-distributing will first clear all allocations, so afterwards we will see if the unenrolled user has been considered.
        $ratingallocate->distribute_choices();

        $numallocations = $DB->count_records(this_db\ratingallocate_allocations::TABLE);
        $this->assertEquals(3, $numallocations, 'There should be only 3 allocations, because we unenrolled '
            . 'a student, so this one should not have been distributed.');
    }

    /**
     * Filter allocations by choice
     *
     * @param array $allocations
     * @param int $choiceid
     * @return array
     */
    private static function filter_allocations_by_choice($allocations, $choiceid) {
        $filterchoiceid = function($elem) use ($choiceid) {
            return $elem->{this_db\ratingallocate_allocations::CHOICEID} == $choiceid;
        };
        return array_filter($allocations, $filterchoiceid);
    }

    /**
     * Default data has two choices but only one is active.
     * Test if count of rateable choices is 1.
     */
    public function test_get_ratable_choices(): void {
        $record = \mod_ratingallocate_generator::get_default_values();
        $testmodule = new \mod_ratingallocate_generated_module($this, $record);
        $ratingallocate =
                \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $testmodule->moddb, $testmodule->teacher);
        $this->assertCount(1, $ratingallocate->get_rateable_choices());
    }

    /**
     * Test if option titles are returned according to the default values
     */
    public function test_get_option_titles_default(): void {
        $expectedresult = [1 => 'Accept', 0 => 'Deny']; // Depends on language file.
        $ratings = [0, 1, 1, 1, 0];

        $record = \mod_ratingallocate_generator::get_default_values();
        $testmodule = new \mod_ratingallocate_generated_module($this, $record);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user(
                $this, $testmodule->moddb, $testmodule->teacher);

        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expectedresult, $result);
    }

    /**
     * Test if option titles are returned according to defined custom values
     */
    public function test_get_option_titles_custom(): void {
        $expectedresult = [1 => 'Ja1234', 0 => 'Nein1234']; // Test data.
        $ratings = [1, 1, 1, 0, 1, 1];

        $record = \mod_ratingallocate_generator::get_default_values();
        $record['strategyopt']['strategy_yesno'] = $expectedresult;
        $testmodule = new \mod_ratingallocate_generated_module($this, $record);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user(
                $this, $testmodule->moddb, $testmodule->teacher);

        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expectedresult, $result);
    }

    /**
     * Test if option titles are returned according to defined custom values, if ratings consist of just one rating
     */
    public function test_get_option_titles_custom1(): void {
        $expectedresult = [1 => 'Ja1234']; // Test data.
        $ratings = [1, 1, 1, 1, 1];

        $record = \mod_ratingallocate_generator::get_default_values();
        $record['strategyopt']['strategy_yesno'] = $expectedresult;
        $testmodule = new \mod_ratingallocate_generated_module($this, $record);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user(
                $this, $testmodule->moddb, $testmodule->teacher);

        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expectedresult, $result);
    }

    /**
     * Test if option titles are returned according to a mixture of defined and custom values
     *
     * @covers \mod_ratingallocate\ratingallocate::get_options_titles
     */
    public function test_get_option_titles_mixed(): void {
        $settings = [1 => 'Ja1234']; // Test data.
        $ratings = [0, 1, 1, 1, 1];
        $expectedresult = $settings;
        $expectedresult[0] = 'Deny'; // Depends on language file.

        $record = \mod_ratingallocate_generator::get_default_values();
        $record['strategyopt']['strategy_yesno'] = $settings;
        $testmodule = new \mod_ratingallocate_generated_module($this, $record);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user(
                $this, $testmodule->moddb, $testmodule->teacher);

        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expectedresult, $result);
    }

    /**
     * Test if reset_userdata works as expected.
     *
     * @covers \mod_ratingallocate\ratingallocate::distribute_choices
     */
    public function test_reset_userdata(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = \mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $student1 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student2 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);

        $this->setUser($teacher);

        $choices = [
            [
                'title' => 'C1',
                'maxsize' => '1',
                'active' => '1',
            ],
            [
                'title' => 'C2',
                'maxsize' => '1',
                'active' => '1',
            ],
        ];
        $ratings = [
            $student1->id => [
                [
                    'choice' => 'C1',
                    'rating' => 1,
                ],
                [
                    'choice' => 'C2',
                    'rating' => 0,
                ],
            ],
            $student2->id => [
                [
                    'choice' => 'C1',
                    'rating' => 0,
                ],
                [
                    'choice' => 'C2',
                    'rating' => 1,
                ],
            ],
        ];

        // Create ratingallocate instance.
        $ratingallocate = \mod_ratingallocate_generator::get_closed_ratingallocate_for_teacher($this, $choices,
            $course, $ratings);

        // Simulate Allocation.
        $ratingallocate->distribute_choices();

        // There should be two ratings in the course.
        $this->assertEquals(2, count($ratingallocate->get_users_with_ratings()));
        $this->assertEquals(2, count($ratingallocate->get_allocations()));

        // Keep dates for comparison.
        $accesstimestart = $DB->get_record(
            'ratingallocate',
            ['id' => $ratingallocate->get_ratingallocateid()],
            'accesstimestart'
        )->accesstimestart;
        $accesstimestop = $DB->get_record(
            'ratingallocate',
            ['id' => $ratingallocate->get_ratingallocateid()],
            'accesstimestop'
        )->accesstimestop;

        // Now try and reset.
        $data = new \stdClass();
        $data->reset_ratings_and_allocations = 1;
        $data->courseid = $course->id;
        $data->timeshift = (2 * DAYSECS);
        $this->setUser($teacher);
        $status = ratingallocate_reset_userdata($data);

        // Reload the instance data.
        $ra = $DB->get_record('ratingallocate', ['id' => $ratingallocate->get_ratingallocateid()]);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate($ra);

        // There should be no ratings and allocations anymore.
        $this->assertEquals(0, count($ratingallocate->get_allocations()));
        $this->assertEquals(0, count($ratingallocate->get_ratings_for_rateable_choices()));
        if (isset($status['error'])) {
            $this->assertEquals(false, $status['error']);
        }

        // Check if the timeshift happened successfully.
        $this->assertEquals($accesstimestart + (2 * DAYSECS), $ra->accesstimestart);
        $this->assertEquals($accesstimestop + (2 * DAYSECS), $ra->accesstimestop);
    }

}
