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
require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * mod_ratingallocate generator tests
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  usener
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_generator_testcase extends advanced_testcase {

    public function test_create_instance() {

        global $DB, $USER;
        core_php_time_limit::raise();
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        
        // There should not be any module for that course first
        $this->assertFalse(
                $DB->record_exists('ratingallocate', array('course' => $course->id
                )));
        $records = $DB->get_records('ratingallocate_choices', array(), 'id');
        $this->assertEquals(0, count($records));

        // create activity
        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
                array('course' => $course));
        $records = $DB->get_records('ratingallocate', array('course' => $course->id
        ), 'id');
        $this->assertEquals(1, count($records));
        $this->assertTrue(array_key_exists($mod->id, $records));
        $expected_values_db = array(
            'id' => $mod->id,
            'course' => $course->id,
            'name' => 'Rating Allocation',
            'intro' => 'Test ratingallocate 1',
            'introformat' => '0',
            'timecreated' => reset($records)->{'timecreated'},
            'timemodified' => '0',
            'accesstimestart' => reset($records)->{'accesstimestart'},
            'accesstimestop' => reset($records)->{'accesstimestop'},
            'setting' => '{"strategy_yesno":{"maxcrossout":"1"}}',
            'strategy' => 'strategy_yesno',
            'publishdate' => reset($records)->{'publishdate'},
            'published' => '0',
            'notificationsend' => '0',
            'algorithmstarttime' => null,
            'algorithmstatus' => '0',
            'runalgorithmbycron' => '1'
        );

        $this->assertEquals(json_decode(json_encode($expected_values_db, false)), reset($records));
        // must have two choices
        $records = $DB->get_records('ratingallocate_choices',
                array('ratingallocateid' => $mod->id
                ), 'title');
        $this->assertEquals(2, count($records));
        $choice_ids = array_keys($records);
        $expected_choices = array(
            $choice_ids[0] => (object) array(
                'title' => 'Choice 1',
                'id' => $choice_ids[0],
                'ratingallocateid' => $mod->id,
                'explanation' => 'Some explanatory text for choice 1',
                'maxsize' => '10',
                'active' => '1'
            ),
            $choice_ids[1] => (object) array(
                'title' => 'Choice 2',
                'id' => $choice_ids[1],
                'ratingallocateid' => $mod->id,
                'explanation' => 'Some explanatory text for choice 2',
                'maxsize' => '5',
                'active' => '0'
            )
        );
        $this->assertEquals($expected_choices, $records);

        // Create an other mod_ratingallocate within the course
        $params = array('course' => $course->id, 'name' => 'Another mod_ratingallocate'
        );
        $mod =  mod_ratingallocate_generator::create_instance_with_choices($this, $params);
        $records = $DB->get_records('ratingallocate', array('course' => $course->id
        ), 'id');
        // are there 2 modules within the course
        $this->assertEquals(2, count($records));
        // is the name correct
        $this->assertEquals('Another mod_ratingallocate', $records[$mod->id]->name);

        $records = $DB->get_records('ratingallocate_choices', array(), 'id');
        $this->assertEquals(4, count($records));

        // other tables
        $records = $DB->get_records('ratingallocate_ratings', array(), 'id');
        $this->assertEquals(0, count($records));
        $records = $DB->get_records('ratingallocate_allocations', array(), 'id');
        $this->assertEquals(0, count($records));
    }

    public function test_mod_ratingallocate_generated_module() {
        $choicedata = mod_ratingallocate_generator::get_default_choice_data();
        foreach ($choicedata as $id => $choice) {
            $choice['maxsize'] = 10;
            $choice['active'] = true;
            $choicedata[$id] = $choice;
        }
        $moduledata = mod_ratingallocate_generator::get_default_values();
        $moduledata['num_students'] = 22;
        $testmodule = new mod_ratingallocate_generated_module($this, $moduledata, $choicedata);
        $this->assertCount($moduledata['num_students'], $testmodule->students);
        $this->assertCount(20, $testmodule->allocations);

        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user(
            $this, $testmodule->moddb, $testmodule->teacher);
        foreach ($ratingallocate->get_choices_with_allocationcount() as $choice) {
            $this->assertEquals(10, $choice->{'usercount'});
        }
    }
}