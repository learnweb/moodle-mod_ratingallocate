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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/generator/lib.php');
require_once(__DIR__ . '/../locallib.php');

/**
 * Tests restriction of choice availability by group membership.
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group      mod_ratingallocate
 * @copyright  2021 Catalyst IT
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(ratingallocate::class)]
#[CoversFunction('get_group_selections')]
#[CoversFunction('filter_choices_by_groups')]
#[CoversFunction('update_choice_groups')]
final class mod_ratingallocate_choice_groups_test extends \advanced_testcase {

    /**
     * @var stdClass The environment that will be used for testing
     * This Class contains:
     * - A Course
     * - Users (teacher, 4 students)
     * - Choicedata
     * - A ratingallocate instance
     */
    private stdClass $env;

    /**
     * Helper function - Create a range of choices.
     * A through D use groups, E does not.
     */
    private function get_choice_data(): array {
        $choices = [];

        $letters = range('A', 'E');
        foreach ($letters as $key => $letter) {
            $choices[] = [
                'title' => "Choice $letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 10,
                'active' => true,
                'usegroups' => $letter !== 'E',
            ];
        }

        return $choices;
    }

    /**
     * Helper function - Map choice titles to IDs
     *
     * @param array|null $choices
     *
     * @return array
     */
    private function get_choice_map(array|null $choices = null): array {
        if (!$choices) {
            $choices = $this->env->ratingallocate->get_rateable_choices();
        }
        return array_flip(array_map(
            function($a) {
                return $a->title;
            },
            $choices));
    }

    /**
     * Helper function - Map group selection names to IDs
     *
     * @param array|null $groupselections
     *
     * @return array
     */
    private function get_group_map(array|null $groupselections = null): array {
        if (!$groupselections) {
            $groupselections = $this->env->ratingallocate->get_group_selections();
        }
        return array_flip($groupselections);
    }

    protected function setUp(): void {
        parent::setUp();

        $this->env = new stdClass();

        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $this->env->course = $course;
        $this->env->teacher = \mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $this->setUser($this->env->teacher);

        // Make test groups and enrol students.
        $green = $generator->create_group(['name' => 'Green Group', 'courseid' => $course->id]);
        $blue = $generator->create_group(['name' => 'Blue Group', 'courseid' => $course->id]);
        $red = $generator->create_group(['name' => 'Red Group', 'courseid' => $course->id]);

        $this->env->student1 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        groups_add_member($green, $this->env->student1);
        $this->env->student2 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        groups_add_member($blue, $this->env->student2);
        $this->env->student3 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        groups_add_member($red, $this->env->student3);
        $this->env->student4 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        // No groups for student 4.

        $this->env->choicedata = $this->get_choice_data();
        $mod = \mod_ratingallocate_generator::create_instance_with_choices($this, ['course' => $course], $this->env->choicedata);
        $this->env->ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->env->teacher);
    }

    protected function tearDown(): void {
        $this->env->choicedata = null;
        parent::tearDown();
    }

    /**
     * Test the setup.
     *
     * @return void
     * @covers ::get_group_selections
     */
    public function test_setup(): void {
        $this->resetAfterTest();

        $this->assertEquals(5, count($this->env->choicedata));
        $choices = $this->env->ratingallocate->get_rateable_choices();
        $this->assertEquals(5, count($choices));

        // Candidates for groupselector match test fixture.
        $groupselections = $this->env->ratingallocate->get_group_selections();
        $this->assertEquals(3, count($groupselections));
        $this->assertContains('Green Group', $groupselections);
        $this->assertContains('Blue Group', $groupselections);
        $this->assertContains('Red Group', $groupselections);
    }

    /**
     * Test choice groups.
     *
     * @return void
     * @covers ::filter_choices_by_groups
     */
    public function test_choice_groups(): void {
        $this->resetAfterTest();

        // Map choice titles to choice IDs, group names to group IDs.
        $choices = $this->env->ratingallocate->get_rateable_choices();
        $choiceidmap = $this->get_choice_map($choices);
        $groupselections = $this->env->ratingallocate->get_group_selections();
        $groupidmap = $this->get_group_map($groupselections);

        /* Populate choice-group mappings for visibility tests.
         *
         * Choice A: Green only
         * Choice B: Green and Blue
         * Choice C: Red only
         * Choice D: 'usegroups' is selected, but no groups; never available to students.
         * Choice E: 'usegroups' is not selected; always available.
         */
        $this->env->ratingallocate->update_choice_groups($choiceidmap['Choice A'], [
            $groupidmap['Green Group'],
        ]);
        $this->env->ratingallocate->update_choice_groups($choiceidmap['Choice B'], [
            $groupidmap['Green Group'], $groupidmap['Blue Group'],
        ]);
        $this->env->ratingallocate->update_choice_groups($choiceidmap['Choice C'], [
            $groupidmap['Red Group'],
        ]);

        // Teacher context: all choices shown in teacher view.
        $basechoices = array_keys($this->env->ratingallocate->filter_choices_by_groups($choices, $this->env->teacher->id));
        $this->assertContains($choiceidmap['Choice A'], $basechoices);
        $this->assertContains($choiceidmap['Choice B'], $basechoices);
        $this->assertContains($choiceidmap['Choice C'], $basechoices);
        $this->assertContains($choiceidmap['Choice D'], $basechoices);
        $this->assertContains($choiceidmap['Choice E'], $basechoices);

        // Student 1, Green group: A, B, E but not C, D.
        $this->setUser($this->env->student1);
        $s1choices = array_keys($this->env->ratingallocate->filter_choices_by_groups($choices, $this->env->student1->id));
        $this->assertContains($choiceidmap['Choice A'], $s1choices);
        $this->assertContains($choiceidmap['Choice B'], $s1choices);
        $this->assertNotContains($choiceidmap['Choice C'], $s1choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s1choices);
        $this->assertContains($choiceidmap['Choice E'], $s1choices);

        // Student 2, Blue group: B, E but not A, C, D.
        $this->setUser($this->env->student2);
        $s2choices = array_keys($this->env->ratingallocate->filter_choices_by_groups($choices, $this->env->student2->id));
        $this->assertNotContains($choiceidmap['Choice A'], $s2choices);
        $this->assertContains($choiceidmap['Choice B'], $s2choices);
        $this->assertNotContains($choiceidmap['Choice C'], $s2choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s2choices);
        $this->assertContains($choiceidmap['Choice E'], $s2choices);

        // Student 3, Red group: C, E but not A, B, D.
        $this->setUser($this->env->student3);
        $s3choices = array_keys($this->env->ratingallocate->filter_choices_by_groups($choices, $this->env->student3->id));
        $this->assertNotContains($choiceidmap['Choice A'], $s3choices);
        $this->assertNotContains($choiceidmap['Choice B'], $s3choices);
        $this->assertContains($choiceidmap['Choice C'], $s3choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s3choices);
        $this->assertContains($choiceidmap['Choice E'], $s3choices);

        // Student 4, no group: just E.
        $this->setUser($this->env->student4);
        $s4choices = array_keys($this->env->ratingallocate->filter_choices_by_groups($choices, $this->env->student4->id));
        $this->assertNotContains($choiceidmap['Choice A'], $s4choices);
        $this->assertNotContains($choiceidmap['Choice B'], $s4choices);
        $this->assertNotContains($choiceidmap['Choice C'], $s4choices);
        $this->assertNotContains($choiceidmap['Choice D'], $s4choices);
        $this->assertContains($choiceidmap['Choice E'], $s4choices);
    }

    /**
     * Test update choice groups.
     *
     * @return void
     * @covers ::update_choice_groups
     */
    public function test_update_choice_groups(): void {
        $this->resetAfterTest();

        $choiceidmap = $this->get_choice_map();
        $groupidmap = $this->get_group_map();

        // Start empty.
        $groups = $this->env->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertTrue(empty($groups));

        // Add one.
        $this->env->ratingallocate->update_choice_groups($choiceidmap['Choice A'], [
            $groupidmap['Green Group'],
        ]);
        $groups = $this->env->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertContains($groupidmap['Green Group'], array_keys($groups));

        // Update to two.
        $this->env->ratingallocate->update_choice_groups($choiceidmap['Choice A'], [
            $groupidmap['Green Group'], $groupidmap['Blue Group'],
        ]);
        $groups = $this->env->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertContains($groupidmap['Green Group'], array_keys($groups));
        $this->assertContains($groupidmap['Blue Group'], array_keys($groups));

        // Remove one.
        $this->env->ratingallocate->update_choice_groups($choiceidmap['Choice A'], [
            $groupidmap['Blue Group'],
        ]);
        $groups = $this->env->ratingallocate->get_choice_groups($choiceidmap['Choice A']);
        $this->assertNotContains($groupidmap['Green Group'], array_keys($groups));
        $this->assertContains($groupidmap['Blue Group'], array_keys($groups));
    }

}
