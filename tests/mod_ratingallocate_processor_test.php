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
 * mod_ratingallocate processor tests
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  usener
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_processor_testcase extends advanced_testcase {
    
        function setUp() {
            global $PAGE;
            $PAGE->set_url('/');
        }
    
        /**
         * Tests if process_publish_allocations is not working before time runs out
         */
        public function test_publishing_before_accesstimestop(){
            $record = mod_ratingallocate_generator::get_default_values();
            $record['accesstimestart'] = time() + (0 * 24 * 60 * 60);
            $record['accesstimestop'] = time() + (6 * 24 * 60 * 60);
            $record['publishdate'] = time() + (7 * 24 * 60 * 60);
            $test_module = new mod_ratingallocate_generated_module($this,$record);
            $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->mod_db, $test_module->teacher);
            $this->assertEquals(0,$ratingallocate->ratingallocate->published);
            $this->call_private_ratingallocate_method($ratingallocate,'process_publish_allocations');
            $this->assertEquals(0,$ratingallocate->ratingallocate->published);
        }
        /**
         * Tests if process_publish_allocations is working after time runs out
         */
        public function test_publishing_after_accesstimestop(){
            $record = mod_ratingallocate_generator::get_default_values();
            $record['accesstimestart'] = time() - (7 * 24 * 60 * 60);
            $record['accesstimestop'] = time() - (1 * 24 * 60 * 60);
            $record['publishdate'] = time() + (0 * 24 * 60 * 60);
            $test_module = new mod_ratingallocate_generated_module($this,$record);
            $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $test_module->mod_db, $test_module->teacher);
            $this->assertEquals(0,$ratingallocate->ratingallocate->published);
            $this->call_private_ratingallocate_method($ratingallocate,'process_publish_allocations');
            $this->assertEquals(1,$ratingallocate->ratingallocate->published);
        }
        
        /**
         * Enables for calling the private processing functions of the ratingallocate
         * @param ratingallocate $ratingallocate
         * @param unknown $method_name name of private or protected method
         * @param unknown $args arguments the method should be called with
         */
        private function call_private_ratingallocate_method(ratingallocate $ratingallocate, $method_name, $args=[]){
            $class = new ReflectionClass('ratingallocate');
            $method = $class->getMethod($method_name);
            $method->setAccessible(true);
            $method->invokeArgs($ratingallocate, $args);
        }
    
    
    
    
    
    
    
    
}