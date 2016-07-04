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
 * Tests the internal processor, which controls the different steps of the workflow.
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate2
 * @copyright  reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_processor_testcase extends advanced_testcase {

    public function setUp() {
            global $PAGE;
            $PAGE->set_url('/');
    }

    /**
     * Tests if process_publish_allocations is not working before time runs out
     * Assert, that the ratingallocate can not be published
     */
    public function test_publishing_before_accesstimestop() {
        $ratingallocate = mod_ratingallocate_generator::get_open_ratingallocate_for_teacher($this);
        $this->assertEquals(0, $ratingallocate->ratingallocate->published);
        $this->call_private_ratingallocate_method($ratingallocate, 'process_publish_allocations');
        $this->assertEquals(0, $ratingallocate->ratingallocate->published);
    }
    /**
     * Tests if process_publish_allocations is working after time runs out
     * Assert, that the ratingallocate can be published
     */
    public function test_publishing_after_accesstimestop() {
        $ratingallocate = mod_ratingallocate_generator::get_closed_ratingallocate_for_teacher($this);
        $this->assertEquals(0, $ratingallocate->ratingallocate->published);
        $this->call_private_ratingallocate_method($ratingallocate, 'process_publish_allocations');
        $this->assertEquals(1, $ratingallocate->ratingallocate->published);
    }
    /**
     * Tests if process_action_allocation_to_grouping is working before time runs out
     * Assert, that the number of groupings does not change
     */
    public function test_grouping_before_accesstimestop() {
        global $DB;
        $ratingallocate = mod_ratingallocate_generator::get_open_ratingallocate_for_teacher($this);
        $this->assertEquals(0, $DB->count_records('groupings'));
        $this->call_private_ratingallocate_method($ratingallocate, 'process_action_allocation_to_grouping');
        $this->assertEquals(1, $DB->count_records('groupings'));
    }
    /**
     * Tests if process_action_allocation_to_grouping is working after time runs out
     * Assert, that the number of groupings changes as expected (1 Grouping should be created)
     */
    public function test_grouping_after_accesstimestop() {
        global $DB;
        $ratingallocate = mod_ratingallocate_generator::get_closed_ratingallocate_for_teacher($this);
        $this->assertEquals(0, $DB->count_records('groupings'));
        $this->call_private_ratingallocate_method($ratingallocate, 'process_action_allocation_to_grouping');
        $this->assertEquals(1, $DB->count_records('groupings'));
    }

    /**
     * Before the rating phase is over a modification of the allocations should not be possible.
     * After the rating phase has ended the allocations may be modified.
     * @return array datasets for the test_modify_allocation method.
     */
    public function modify_allocation_provider() {
        return [
            'Rating phase is over.' => [
                'get_closed_ratingallocate_for_teacher',
                20],
            'Rating phase is not over, yet.' => [
                'get_open_ratingallocate_for_teacher',
                10]
        ];
    }

    /**
     * Tests if process_action_modify_allocation is working after and before time runs out
     * Assert, that the number of allocations changes as expected
     * @dataProvider modify_allocation_provider
     */
    public function test_modify_allocation($ratingallocatecreationmethod, $expected) {
        global $DB;
        $ratingallocate = forward_static_call(array('mod_ratingallocate_generator', $ratingallocatecreationmethod),
            $this, $this->get_choice_data());
        $choices = $DB->get_records('ratingallocate_choices');
        $choiceid = array_keys($choices)[0];
        $this->assertEquals(10, $DB->count_records('ratingallocate_allocations',
            array('choiceid' => $choiceid)));
        $this->prepare_manual_alloc_form($ratingallocate, $choiceid);
        $this->call_private_ratingallocate_method($ratingallocate, 'process_action_manual_allocation');
        $this->assertEquals($expected, $DB->count_records('ratingallocate_allocations',
            array('choiceid' => $choiceid)));
    }

    /**
     * Returns an array with 2 choices with size of 10.
     */
    private function get_choice_data() {
        return array(
            array('title' => 'Choice 1',
                'explanation' => 'Some explanatory text for choice 1',
                'maxsize' => '10',
                'active' => true),
            array('title' => 'Choice 2',
                'explanation' => 'Some explanatory text for choice 2',
                'maxsize' => '10',
                'active' => true
            )
        );
    }

    /**
     * Mocks the manual_alloc_form to return submitted data.
     */
    private function prepare_manual_alloc_form(ratingallocate $ratingallocate, $choiceid) {
        global $DB;
        require_once(__DIR__ .'/../form_manual_allocation.php');
        $allocations = $DB->get_records('ratingallocate_allocations');
        $allocdata = array();
        foreach ($allocations as $id => $allocation) {
            $allocdata[$allocation->userid]['assign'] = "$choiceid";
        }

        $data = array();
        $data['action'] = 'manual_allocation';
        $data['data'] = $allocdata;
        $data['submitbutton'] = 'Save changes';
        $data['filter_state'] = manual_alloc_form::FILTER_ALL;
        manual_alloc_form::mock_submit($data);
    }

    /**
     * Enables for calling the private processing functions of the ratingallocate
     * @param ratingallocate $ratingallocate
     * @param string $methodname name of private or protected method
     * @param unknown $args arguments the method should be called with
     */
    private function call_private_ratingallocate_method(ratingallocate $ratingallocate, $methodname, $args=[]) {
        $class = new ReflectionClass('ratingallocate');
        $method = $class->getMethod($methodname);
        $method->setAccessible(true);
        $method->invokeArgs($ratingallocate, $args);
    }

}