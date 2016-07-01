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
global $CFG;
require_once(dirname(__FILE__) . '/generator/lib.php');
require_once(dirname(__FILE__) . '/../locallib.php');

use ratingallocate\db as this_db;

/**
 * mod_ratingallocate generator tests
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  usener
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locallib_test extends advanced_testcase {

    public function test_simple() {
        global $DB, $USER;
        core_php_time_limit::raise();
        $this->resetAfterTest();
        $this->setAdminUser();
        
        $course = $this->getDataGenerator()->create_course();
        
        $teacher = mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $this->setUser($teacher);

        // There should not be any module for that course first
        $this->assertFalse(
                $DB->record_exists(this_db\ratingallocate::TABLE, array(this_db\ratingallocate::COURSE => $course->id
                )));

        //set default data for category
        $data = mod_ratingallocate_generator::get_default_values();
        $data['course'] = $course;
        foreach ($data as $name => $value) {
            if (subStr($name, strlen($name) - 7, 7) === 'maxsize') {
                $data[$name] = 2;
            }
            if (subStr($name, strlen($name) - 6, 6) === 'active') {
                $data[$name] = true;
            }
        }
        
        // create activity
        $mod = $this->getDataGenerator()->create_module(ratingallocate_MOD_NAME, $data);
        $this->assertEquals(2, $DB->count_records(this_db\ratingallocate_choices::TABLE), array(this_db\ratingallocate_choices::ID => $mod->id));
        
        $student_1 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student_2 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student_3 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student_4 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $teacher);
        $choices = $ratingallocate->get_rateable_choices();

        $choice1 = reset($choices);
        $choice2 = end($choices);

        //Create preferences
        $prefers_non = array();
        foreach ($choices as $choice) {
            $prefers_non[$choice->{this_db\ratingallocate_choices::ID}] = array(
                            this_db\ratingallocate_ratings::CHOICEID => $choice->{this_db\ratingallocate_choices::ID},
                            this_db\ratingallocate_ratings::RATING => 0);
        }
        $prefers_first = json_decode(json_encode($prefers_non),true);
        $prefers_first[$choice1->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;
        $prefers_second =  json_decode(json_encode($prefers_non),true);
        $prefers_second[$choice2->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;

        //assign preferences
        mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student_1, $prefers_first);
        mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student_2, $prefers_first);
        mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student_3, $prefers_second);
        mod_ratingallocate_generator::save_rating_for_user($this, $mod, $student_4, $prefers_second);

        // allocate choices
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $teacher);
        $time_needed = $ratingallocate->distrubute_choices();
        $this->assertGreaterThan(0, $time_needed);
        $this->assertLessThan(0.1, $time_needed, 'Allocation is very slow');

        $allocation_count = $ratingallocate->get_choices_with_allocationcount();
        $this->assertCount(2, $allocation_count);

        //Test allocations
        $num_allocations = $DB->count_records(this_db\ratingallocate_allocations::TABLE);

        $this->assertEquals(4, $num_allocations, 'There should be only 4 allocations, since there are only 4 choices.');
        $allocations = $DB->get_records(this_db\ratingallocate_allocations::TABLE, 
                 array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $mod->{this_db\ratingallocate::ID}), 
                '');// '' /*sort*/, /*fields*/ this_db\ratingallocate_allocations::USERID . ',' . this_db\ratingallocate_allocations::CHOICEID );
        
        $map_user_id = function ($elem) {return $elem->{this_db\ratingallocate_allocations::USERID};};
        
        $alloc1 = self::filter_allocations_by_choice($allocations,$choice1->{this_db\ratingallocate_choices::ID});
        $alloc2 = self::filter_allocations_by_choice($allocations,$choice2->{this_db\ratingallocate_choices::ID});
        
        //Assert, that student 1 was allocated to choice 1
        $this->assertContains($student_1->id, array_map($map_user_id, $alloc1));
        //Assert, that student 2 was allocated to choice 1
        $this->assertContains($student_2->id, array_map($map_user_id, $alloc1));
        //Assert, that student 3 was allocated to choice 2
        $this->assertContains($student_3->id, array_map($map_user_id, $alloc2));
        //Assert, that student 4 was allocated to choice 2
        $this->assertContains($student_4->id, array_map($map_user_id, $alloc2));
    }
    private static function filter_allocations_by_choice($allocations, $choiceid) {
        $filter_choice_id = function($elem) use ($choiceid) { return $elem->{this_db\ratingallocate_allocations::CHOICEID} == $choiceid; };
        return array_filter($allocations, $filter_choice_id);
    }
    /**
     * Default data has two choices but only one is active.
     * Test if count of rateable choices is 1.
     */
    public function test_get_ratable_choices(){
        $record = mod_ratingallocate_generator::get_default_values();
        $test_module = new mod_ratingallocate_generated_module($this,$record);
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->moddb, $test_module->teacher);
        $this->assertCount(1,$ratingallocate->get_rateable_choices());
    } 
    
    /**
     * Test if option titles are returned according to the default values
     */
    public function test_get_option_titles_default(){       
        $expected_result = array(1 => 'Yes',0 => 'No'); //Depends on language file
        $ratings = array(0,1,1,1,0);
        
        $record = mod_ratingallocate_generator::get_default_values();
        $test_module = new mod_ratingallocate_generated_module($this,$record);
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->moddb, $test_module->teacher);

        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expected_result,$result);
    }
    
    /**
     * Test if option titles are returned according to defined custom values
     */
    public function test_get_option_titles_custom(){
        $expected_result = array(1 => 'Ja1234', 0 => 'Nein1234'); //Test data
        $ratings = array(1,1,1,0,1,1);
    
        $record = mod_ratingallocate_generator::get_default_values();
        $record['strategyopt']['strategy_yesno'] = $expected_result;
        $test_module = new mod_ratingallocate_generated_module($this,$record);
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->moddb, $test_module->teacher);
    
        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expected_result,$result);
    }
    
    /**
     * Test if option titles are returned according to defined custom values, if ratings consist of just one rating
     */
    public function test_get_option_titles_custom1(){
        $expected_result = array(1 => 'Ja1234'); //Test data
        $ratings = array(1,1,1,1,1);
        
        $record = mod_ratingallocate_generator::get_default_values();
        $record['strategyopt']['strategy_yesno'] = $expected_result;
        $test_module = new mod_ratingallocate_generated_module($this,$record);
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->moddb, $test_module->teacher);
    
        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expected_result,$result);
    }
    
    /**
     * Test if option titles are returned according to a mixture of defined and custom values,
     */
    public function test_get_option_titles_mixed(){
        $settings = array(1 => 'Ja1234'); //Test data
        $ratings = array(0,1,1,1,1);
        $expected_result = $settings;
        $expected_result [0] = 'No'; //Depends on language file
    
        $record = mod_ratingallocate_generator::get_default_values();
        $record['strategyopt']['strategy_yesno'] = $settings;
        $test_module = new mod_ratingallocate_generated_module($this,$record);
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->moddb, $test_module->teacher);
    
        $result = $ratingallocate->get_options_titles($ratings);
        $this->assertEquals($expected_result,$result);
    }
}