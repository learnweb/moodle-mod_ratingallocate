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
        set_time_limit(0);
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
        $mod = $this->getDataGenerator()->create_module('ratingallocate',
                array('course' => $course
                ));
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
            'published' => '0'
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
        $mod = $this->getDataGenerator()->create_module('ratingallocate', $params);
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
        $record = mod_ratingallocate_generator::get_default_values();
        foreach ($record['choices'] as $id => &$choice) {
            $choice['maxsize'] = 10;
            $choice['active'] = true;
        }
        $record['num_students'] = 22;
        $test_module = new mod_ratingallocate_generated_module($this,$record);
        $this->assertCount($record['num_students'], $test_module->students);
        $this->assertCount(20, $test_module->allocations);

        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->mod_db, $test_module->teacher);
        foreach ($ratingallocate->get_choices_with_allocationcount() as $choice) {
            $this->assertEquals(10, $choice->{'usercount'});
        }
    }
}