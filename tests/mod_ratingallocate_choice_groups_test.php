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

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/generator/lib.php');
require_once(__DIR__ . '/../locallib.php');

/**
 * Tests restriction of choice availability by group membership.
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group      mod_ratingallocate
 * @copyright  2021 David Thompson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_choice_group_testcase extends advanced_testcase {

    /** Helper function - Create a range of choices.
     *
     * A thru D use groups, E does not.
     */
    private function get_choice_data() {
        $choices = array();

        $letters = range('A', 'E');
        foreach ($letters as $key => $letter) {
            $choice = array(
                'title' => "Choice $letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 10,
                'active' => true,
            );
            if ($letter === 'E') {
                $choice['usegroups'] = false;
            } else {
                $choice['usegroups'] = true;
            }
            $choices[] = $choice;
        }

        return $choices;
    }

    /**
     * Helper function - Map choice titles to IDs
     *
     * @param array $choices
     *
     * @return array
     */
    private function get_choice_map($choices = null) {
        if (!$choices) {
            $choices = $this->ratingallocate->get_rateable_choices();
        }
        $choiceidmap = array_flip(array_map(
            function($a) {
                return $a->title;
            },
            $choices));
        return $choiceidmap;
    }

    /**
     * Helper function - Map group selection names to IDs
     *
     * @param array $groups
     *
     * @return array
     */
    private function get_group_map($groupselections = null) {
        if (!$groupselections) {
            $groupselections = $this->ratingallocate->get_group_selections();
        }
        $groupidmap = array_flip($groupselections);
        return $groupidmap;
    }


    protected function setUp(): void {
        parent::setUp();

        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $this->course = $course;
        $this->teacher = mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $this->setUser($this->teacher);

        // Make test groups and enrol students.
        $green = $generator->create_group(array('name' => 'Green Group', 'courseid' => $course->id));
        $blue = $generator->create_group(array('name' => 'Blue Group', 'courseid' => $course->id));
        $red = $generator->create_group(array('name' => 'Red Group', 'courseid' => $course->id));

        $this->student1 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        groups_add_member($green, $this->student1);
        $this->student2 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        groups_add_member($blue, $this->student2);
        $this->student3 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        groups_add_member($red, $this->student3);
        $this->student4 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        // No groups for student 4.

        $this->choicedata = $this->get_choice_data();
        $mod = mod_ratingallocate_generator::create_instance_with_choices($this, array('course' => $course), $this->choicedata);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);
    }


    protected function tearDown(): void {
        $this->choicedata = null;

        parent::tearDown();
    }

    public function test_setup() {
        $this->resetAfterTest();

        $this->assertEquals(5, count($this->choicedata));
        $choices = $this->ratingallocate->get_rateable_choices();
        $this->assertEquals(5, count($choices));

        // Candidates for groupselector match test fixture.
        $groupselections = $this->ratingallocate->get_group_selections();
        $this->assertEquals(3, count($groupselections));
        $this->assertContains('Green Group', $groupselections);
        $this->assertContains('Blue Group', $groupselections);
        $this->assertContains('Red Group', $groupselections);
    }

    public function test_choice_groups() {
        $this->resetAfterTest();

        // Map choice titles to choice IDs, group names to group IDs.
        $choices = $this->ratingallocate->get_rateable_choices();
        $choiceidmap = $this->get_choice_map($choices);
        $groupselections = $this->ratingallocate->get_group_selections();
        $groupidmap = $this->get_group_map($groupselections);

        /* Populate choice-group mappings for visibility tests.
         *
         * Choice A: Green only
         * Choice B: Green and Blue
         * Choice C: Red only
         * Choice D: 'usegroups' is selected, but no groups; never available to students.
         * Choice E: 'usegroups' is not selected; always available.
         */
        $this->ratingallocate->update_choice_groups($choiceidmap['Choice A'], array(
            $groupidmap['Green Group']
        ));
        $this->ratingallocate->update_choice_groups($choiceidmap['Choice B'], array(
            $groupidmap['Green Group'], $groupidmap['Blue Group']
        ));
        $this->ratingallocate->update_choice_groups($choiceidmap['Choice C'], array(
            $groupidmap['Red Group']
        ));

        // Teacher context: all choices shown in teacher view.
        $basechoices = array_keys($this->ratingallocate->filter_choices_by_groups($choices, $this->teacher->id));
        $this->assertContains($choiceidmap['Choice A'], $basechoices);
        $this->assertContains($choiceidmap['Choice B'], $basechoices);
        $this->assertContains($choiceidmap['Choice C'], $basechoices);
        $this->assertContains($choiceidmap['Choice D'], $basechoices);
        $this->assertContains($choiceidmap['Choice E'], $basechoices);

        // Student 1, Green group: A, B, E but not C, D.
        $this->setUser($this->student1);
        $s1choices = array_keys($this->ratingallocate->filter_choices_by_groups($choices, $this->student1->id));
        $this->assertContains($choiceidmap['Choice A'], $s1choices);
        $this->assertContains($choiceidmap['Choice B'], $s1choices);
        $this->assertNotContains($choiceidmap['Choice C'], $s1choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s1choices);
        $this->assertContains($choiceidmap['Choice E'], $s1choices);

        // Student 2, Blue group: B, E but not A, C, D.
        $this->setUser($this->student2);
        $s2choices = array_keys($this->ratingallocate->filter_choices_by_groups($choices, $this->student2->id));
        $this->assertNotContains($choiceidmap['Choice A'], $s2choices);
        $this->assertContains($choiceidmap['Choice B'], $s2choices);
        $this->assertNotContains($choiceidmap['Choice C'], $s2choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s2choices);
        $this->assertContains($choiceidmap['Choice E'], $s2choices);

        // Student 3, Red group: C, E but not A, B, D.
        $this->setUser($this->student3);
        $s3choices = array_keys($this->ratingallocate->filter_choices_by_groups($choices, $this->student3->id));
        $this->assertNotContains($choiceidmap['Choice A'], $s3choices);
        $this->assertNotContains($choiceidmap['Choice B'], $s3choices);
        $this->assertContains($choiceidmap['Choice C'], $s3choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s3choices);
        $this->assertContains($choiceidmap['Choice E'], $s3choices);

        // Student 4, no group: just E.
        $this->setUser($this->student4);
        $s4choices = array_keys($this->ratingallocate->filter_choices_by_groups($choices, $this->student4->id));
        $this->assertNotContains($choiceidmap['Choice A'], $s4choices);
        $this->assertNotContains($choiceidmap['Choice B'], $s4choices);
        $this->assertNotContains($choiceidmap['Choice C'], $s4choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s4choices);
        $this->assertContains($choiceidmap['Choice E'], $s4choices);
    }

    public function test_update_choice_groups() {
        $this->resetAfterTest();

        $choiceidmap = $this->get_choice_map();
        $groupidmap = $this->get_group_map();

        // Start empty.
        $groups = $this->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertTrue(empty($groups));

        // Add one.
        $this->ratingallocate->update_choice_groups($choiceidmap['Choice A'], array(
            $groupidmap['Green Group']
        ));
        $groups = $this->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertContains($groupidmap['Green Group'], array_keys($groups));

        // Update to two.
        $this->ratingallocate->update_choice_groups($choiceidmap['Choice A'], array(
            $groupidmap['Green Group'], $groupidmap['Blue Group']
        ));
        $groups = $this->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertContains($groupidmap['Green Group'], array_keys($groups));
        $this->assertContains($groupidmap['Blue Group'], array_keys($groups));

        // Remove one.
        $this->ratingallocate->update_choice_groups($choiceidmap['Choice A'], array(
            $groupidmap['Blue Group']
        ));
        $groups = $this->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertNotContains($groupidmap['Green Group'], array_keys($groups));
        $this->assertContains($groupidmap['Blue Group'], array_keys($groups));
    }

}