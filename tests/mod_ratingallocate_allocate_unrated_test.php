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
 * Tests distribution of users who did not rate.
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group      mod_ratingallocate
 * @copyright  2022 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_allocate_unrated_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $this->course = $course;
        $this->teacher = mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $this->setUser($this->teacher);

        // Make test groups and enrol students.
        $this->green = $generator->create_group(['name' => 'Green Group', 'courseid' => $course->id]);
        $this->blue = $generator->create_group(['name' => 'Blue Group', 'courseid' => $course->id]);
        $this->red = $generator->create_group(['name' => 'Red Group', 'courseid' => $course->id]);

        // We need a few more students to see if distribution is ok.
        for ($i = 0; $i < 10; $i++) {
            $this->studentsgreen[] = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
            groups_add_member($this->green, $this->studentsgreen[$i]);
            $this->studentsblue[] = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
            groups_add_member($this->blue, $this->studentsblue[$i]);
            $this->studentsred[] = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
            groups_add_member($this->red, $this->studentsred[$i]);
            $this->studentsnogroup[] = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        }
    }

    /**
     * Asserts that there is no allocation violating the group restrictions. This should be called after the algorithms have been
     * run to assert that the algorithm did respect the group restrictions when allocating.
     *
     * @return void
     */
    private function test_group_memberships(): void {
        foreach ([$this->studentsred, $this->studentsblue, $this->studentsgreen] as $students) {
            foreach ($students as $student) {
                $allocations = $this->ratingallocate->get_allocations_for_user($student->id);
                foreach ($allocations as $allocation) {
                    if (empty(array_filter($this->ratingallocate->get_rateable_choices(),
                        fn($choice) => $choice->id === $allocation->choiceid)[0]->usegroups)) {
                        // If the choice has no group restrictions active we do not have to assert anything.
                        continue;
                    }
                    $choicegroups =
                        array_map(fn($group) => $group->id, $this->ratingallocate->get_choice_groups($allocation->choiceid));
                    $usergroups = groups_get_user_groups($this->course->id, $student->id)[0];
                    $this->assertFalse(empty(array_intersect($choicegroups, $usergroups)));
                }
            }
        }
    }

    /**
     * Tests the helper function to retrieve all used groups by the choices.
     *
     * @covers ratingallocate::get_all_groups_of_choices
     * @return void
     */
    public function test_get_all_groups_of_choices(): void {
        $choices = [];

        $letters = range('A', 'E');
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 8,
                'active' => true,
            ];

            if ($letter === 'C' || $letter === 'D' || $letter === 'E') {
                $choice['usegroups'] = true;
            } else {
                $choice['usegroups'] = false;
            }
            $choices[] = $choice;
        }

        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);

        // Assign blue group to choice D, green group to choice E.
        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('D'), [$this->blue->id]);
        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('E'), [$this->green->id]);
        // Choice C has group restrictions enabled, but no groups defined, so should be ignored.
        // Choice B has no group restrictions enabled, so its group 'red' should be ignored.
        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('B'), [$this->red->id]);

        $this->assertCount(2, $this->ratingallocate->get_all_groups_of_choices());
        $this->assertTrue(in_array($this->blue->id, $this->ratingallocate->get_all_groups_of_choices()));
        $this->assertTrue(in_array($this->green->id, $this->ratingallocate->get_all_groups_of_choices()));
        $this->assertFalse(in_array($this->red->id, $this->ratingallocate->get_all_groups_of_choices()));
    }

    public function test_get_user_groupids(): void {

        $choices = [];

        $letters = range('A', 'E');
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 8,
                'active' => true,
            ];

            if ($letter === 'D' || $letter === 'E') {
                $choice['usegroups'] = true;
            } else {
                $choice['usegroups'] = false;
            }
            $choices[] = $choice;
        }

        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);

        // Pick random red group user, also assign to group blue and green.
        $studentredbluegreen = $this->studentsred[5];
        groups_add_member($this->green, $studentredbluegreen);
        groups_add_member($this->blue, $studentredbluegreen);

        // Pick another different random red group user, also to group blue.
        $studentredblue = $this->studentsred[7];
        groups_add_member($this->blue, $studentredblue);

        $this->assertCount(0, $this->ratingallocate->get_user_groupids($studentredblue->id));
        $this->assertCount(0, $this->ratingallocate->get_user_groupids($studentredbluegreen->id));

        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('E'), [$this->red->id, $this->blue->id]);
        $this->assertCount(2, $this->ratingallocate->get_user_groupids($studentredblue->id));
        $this->assertCount(2, $this->ratingallocate->get_user_groupids($studentredbluegreen->id));

        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('D'), [$this->green->id]);
        $this->assertCount(2, $this->ratingallocate->get_user_groupids($studentredblue->id));
        $this->assertCount(3, $this->ratingallocate->get_user_groupids($studentredbluegreen->id));
    }

    /**
     * Tests the method retrieving all currently undistributed users.
     *
     * The method will sort the users depending on the amount of groups used in the choices' group restrictions.
     *
     * @covers ratingallocate::get_undistributed_users_with_groupscount
     * @covers ratingallocate::get_undistributed_users
     * @return void
     * @throws coding_exception
     */
    public function test_get_undistributed_users(): void {
        $choices = [];

        $letters = range('A', 'E');
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 8,
                'active' => true,
            ];

            if ($letter === 'D' || $letter === 'E') {
                $choice['usegroups'] = true;
            } else {
                $choice['usegroups'] = false;
            }
            $choices[] = $choice;
        }

        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);

        // Assign blue and green group to choice D, red group to choice E.
        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('D'),
            [$this->blue->id, $this->green->id, $this->red->id]);
        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('E'), [$this->red->id]);

        $studentonetwogroups = $this->studentsred[7];
        groups_add_member($this->blue->id, $studentonetwogroups->id);
        $studenttwotwogroups = $this->studentsred[1];
        groups_add_member($this->blue->id, $studenttwotwogroups->id);
        $studentthreetwogroups = $this->studentsred[4];
        groups_add_member($this->blue->id, $studentthreetwogroups->id);

        $studentonethreegroups = $this->studentsred[2];
        groups_add_member($this->blue->id, $studentonethreegroups->id);
        groups_add_member($this->green->id, $studentonethreegroups->id);
        $studenttwothreegroups = $this->studentsred[3];
        groups_add_member($this->blue->id, $studenttwothreegroups->id);
        groups_add_member($this->green->id, $studenttwothreegroups->id);

        // We now have:
        // 10 students without a group
        // 10 students with only group green, 10 students with only group blue
        // 5 students with only group red
        // 3 students with two groups (red, blue)
        // 2 students with three groups (red, blue, green)
        // Expected order should be: 25 students with one group, 3 students with two groups,
        //   2 students with three groups, 10 students without group.

        $users = $this->ratingallocate->get_undistributed_users();
        $i = 0;
        foreach ($users as $user) {
            $groupscount = count($this->ratingallocate->get_user_groupids($user));
            if ($i < 25) {
                $this->assertEquals(1, $groupscount);
            } else if ($i >= 25 && $i < 28) {
                $this->assertEquals(2, $groupscount);
            } else if ($i >= 28 && $i < 30) {
                $this->assertEquals(3, $groupscount);
            } else {
                $this->assertEquals(0, $groupscount);
            }
            $i++;
        }

        $raters = array_values($this->ratingallocate->get_raters_in_course());
        // Additionally check that the original order of the users has been preserved if group count is equal.
        // For groupcount 2:
        $this->assertEquals(array_search($studentonetwogroups, $raters) > array_search($studenttwotwogroups, $raters),
            array_search($studentonetwogroups->id, $users) > array_search($studenttwotwogroups->id, $users));
        $this->assertEquals(array_search($studenttwotwogroups, $raters) > array_search($studentthreetwogroups, $raters),
            array_search($studenttwotwogroups->id, $users) > array_search($studentthreetwogroups->id, $users));
        // For groupcount 3:
        $this->assertEquals(array_search($studentonethreegroups, $raters) > array_search($studenttwothreegroups, $raters),
            array_search($studentonethreegroups->id, $users) > array_search($studenttwothreegroups->id, $users));
        // For groupcount 1:
        for ($i =0; $i<25; $i++) {
            $this->assertEquals(array_values(array_filter($raters,
                    fn($rater) => count($this->ratingallocate->get_user_groupids($rater->id)) == 1))[$i]->id, $users[$i]);
        }
        // For groupcount 0:
        for ($i = 0; $i<10; $i++) {
            $this->assertEquals(array_values(array_filter($raters,
                fn($rater) => count($this->ratingallocate->get_user_groupids($rater->id)) == 0))[$i]->id, $users[$i + 30]);
        }
    }

    private function get_choice_id_by_title(string $title): int {
        return $this->get_choice_by_title($title)->id;
    }

    private function get_choice_by_title(string $title): stdClass {
        return array_values(array_filter($this->ratingallocate->get_rateable_choices(),
            fn($choice) => $choice->title === $title))[0];
    }

    private function get_allocation_count_for_choice(string $title): int {
        $choiceswithallocationcount = $this->ratingallocate->get_choices_with_allocationcount();
        $choiceswithallocationcount = array_filter($choiceswithallocationcount, fn($choice) => $choice->title === $title);
        return (int) array_values($choiceswithallocationcount)[0]->usercount;
    }

    private function get_allocations_for_choice(string $title): array {
        $allocationsofchoice = array_filter($this->ratingallocate->get_allocations(), fn($allocation) => $allocation->choiceid == $this->get_choice_id_by_title($title));
        return array_map(fn($allocation) => $allocation->userid, $allocationsofchoice);
    }

    private function allocate_random_users(): void {
        $this->ratingallocate->add_allocation($this->get_choice_id_by_title('B'), $this->studentsgreen[3]->id);
        $this->ratingallocate->add_allocation($this->get_choice_id_by_title('B'), $this->studentsred[7]->id);
        $this->ratingallocate->add_allocation($this->get_choice_id_by_title('C'), $this->studentsblue[9]->id);
        $this->ratingallocate->add_allocation($this->get_choice_id_by_title('D'), $this->studentsnogroup[2]->id);
    }

    private function assert_allocation_of_random_users(): void {
        $this->assertEquals($this->get_choice_id_by_title('B'),
            array_values($this->ratingallocate->get_allocations_for_user($this->studentsgreen[3]->id))[0]->choiceid);
        $this->assertEquals($this->get_choice_id_by_title('B'),
            array_values($this->ratingallocate->get_allocations_for_user($this->studentsred[7]->id))[0]->choiceid);
        $this->assertEquals($this->get_choice_id_by_title('C'),
            array_values($this->ratingallocate->get_allocations_for_user($this->studentsblue[9]->id))[0]->choiceid);
        $this->assertEquals($this->get_choice_id_by_title('D'),
            array_values($this->ratingallocate->get_allocations_for_user($this->studentsnogroup[2]->id))[0]->choiceid);
    }

    /**
     * Test distribution without groups.
     *
     * @return void
     * @throws coding_exception
     */
    public function test_distribution_without_groups(): void {
        $choices = [];

        $letters = range('A', 'E');
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 8,
                'active' => true,
                'usegroups' => false
            ];
            $choices[] = $choice;
        }
        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);

        // Tests
        $this->ratingallocate->add_allocation($this->get_choice_id_by_title('A'), $this->studentsnogroup[0]->id);
        $this->ratingallocate->add_allocation($this->get_choice_id_by_title('A'), $this->studentsnogroup[1]->id);
        $this->assertEquals(2, $this->get_allocation_count_for_choice('A'));
        $this->ratingallocate->distribute_users_without_choice(ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY);
        foreach (range('A', 'E') as $groupname) {
            $this->assertEquals(8, $this->get_allocation_count_for_choice($groupname));
        }

        // Reset allocations.
        $this->ratingallocate->clear_all_allocations();

        // We now test what happens with more users than places in the choices.
        for ($i = 0; $i < 10; $i++) {
            mod_ratingallocate_generator::create_user_and_enrol($this, $this->course);
        }
        $this->assertEquals(51, count(enrol_get_course_users($this->course->id)));
        $this->ratingallocate->distribute_users_without_choice(ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY);
        foreach (range('A', 'E') as $choicetitle) {
            // We still should have the maximum amount of students assigned to the choices.
            $this->assertEquals(8, $this->get_allocation_count_for_choice($choicetitle));
        }
    }

    /**
     * Test the distribution of users to choices with group restrictions, using both algorithms.
     *
     * @return void
     */
    public function test_allocation_with_groups_common_features(): void {
        $this->test_allocation_with_groups_with_algorithm(ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY);
        $this->test_allocation_with_groups_with_algorithm(ACTION_DISTRIBUTE_UNALLOCATED_FILL);
    }

    /**
     * This is a proper test function. It's private, because it's called with both types of algorithms by
     *  test_allocation_with_groups function.
     *
     * @param string $algorithm the algorithm to use for running this test function
     * @return void
     */
    private function test_allocation_with_groups_with_algorithm(string $algorithm): void {
        $choices = [];

        $letters = range('A', 'E');
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 8,
                'active' => true,
            ];

            if ($letter === 'D' || $letter === 'E') {
                $choice['usegroups'] = true;
            } else {
                $choice['usegroups'] = false;
            }
            $choices[] = $choice;
        }

        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);
        // Assign blue and green group to choice D. So D is only available to green and blue students.
        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('D'), [$this->blue->id, $this->green->id]);
        $this->ratingallocate->distribute_users_without_choice($algorithm);
        // We could not distribute all users, but choices 'A' to 'D' should be properly filled.
        foreach (range('A', 'D') as $choicetitle) {
            $this->assertEquals(8, $this->get_allocation_count_for_choice($choicetitle));
        }
        /*print_r(array_map(fn($student) => $student->id, $this->studentsblue));
        print_r(array_map(fn($student) => $student->id, $this->studentsgreen));
        print_r($this->get_allocations_for_choice('B'));*/
        //die();

        // We don't assign a group to E, so E should not be available to any student.
        $this->assertEquals(0, $this->get_allocation_count_for_choice('E'));
        // Verify, that choice 'D' only has users from groups blue or green.
        $this->test_group_memberships();

        // We now assign a group to E, so all users should be distributed, because we got 40 places in total for 40 students.
        $this->ratingallocate->clear_all_allocations();
        $this->ratingallocate->update_choice_groups($this->get_choice_id_by_title('E'), [$this->red->id]);
        $this->ratingallocate->distribute_users_without_choice($algorithm);
        foreach (range('A', 'E') as $groupname) {
            $this->assertEquals(8, $this->get_allocation_count_for_choice($groupname));
        }
        $this->test_group_memberships();
    }

    /**
     * Test the distribution of users to choices with group restrictions, using both algorithms.
     *
     * @return void
     */
    public function test_allocation_without_groups_common_features(): void {
        $this->test_allocation_without_groups_with_algorithm(ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY);
        $this->test_allocation_without_groups_with_algorithm(ACTION_DISTRIBUTE_UNALLOCATED_FILL);
    }

    private function test_allocation_without_groups_with_algorithm(string $algorithm): void {
        $choices = [];

        $letters = range('A', 'E');
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'maxsize' => 8,
                'active' => true,
            ];
            $choices[] = $choice;
        }

        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);

        // We now test what happens with more users than places in the choices.
        $newusers = [];
        for ($i = 0; $i < 10; $i++) {
            $newusers[] = mod_ratingallocate_generator::create_user_and_enrol($this, $this->course);
        }
        $this->assertEquals(51, count(enrol_get_course_users($this->course->id)));
        $this->ratingallocate->distribute_users_without_choice($algorithm);
        foreach (range('A', 'E') as $choicetitle) {
            // We still should have the maximum amount of students assigned to the choices.
            $this->assertEquals(8, $this->get_allocation_count_for_choice($choicetitle));
        }
        $this->ratingallocate->clear_all_allocations();
        foreach ($newusers as $user) {
            delete_user($user);
        }
    }

    /**
     * Test the EQUALLY algorithm without groups. The algorithm tries to distribute the users so that each choice has equal places
     * left or at most there is a difference of one user for the left places per choice.
     *
     * @return void
     */
    public function test_distribute_equally_without_groups(): void {
        $choices = [];

        $letters = range('A', 'E');
        $i = 14;
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'active' => true,
                'usegroups' => false,
                // We choose 14, 12, 10, 8 and 6 maxsize values for the groups A, B, C, D, E.
                // This means 50 places for 40 users in the course.
                'maxsize' => $i
            ];

            $choices[] = $choice;
            $i -= 2;
        }
        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);

        // Randomly manually allocate some students to some choices to see if the algorithm can deal with that.
        $this->allocate_random_users();

        $this->ratingallocate->distribute_users_without_choice(ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY);
        $i = 14;
        foreach ($letters as $groupname) {
            // All choices should be equally filled up. We have 50 places and 40 users, so every choice should have 2 places left
            // after distribution.
            $this->assertEquals($i - 2, $this->get_allocation_count_for_choice($groupname));
            $i -= 2;
        }

        // Assert the allocations already existing before have not changed.
        $this->assert_allocation_of_random_users();
    }

    /**
     * Test the FILL algorithm without groups. This algorithm just fills up every choice. Choices with least places left are
     * being filled up first.
     *
     * @return void
     */
    public function test_distribute_fill_without_groups(): void {
        $choices = [];

        $letters = range('A', 'E');
        $i = 14;
        foreach ($letters as $letter) {
            $choice = [
                'title' => "$letter",
                'explanation' => "Explain Choice $letter",
                'active' => true,
                'usegroups' => false,
                // We choose 14, 12, 10, 8 and 6 maxsize values for the groups A, B, C, D, E.
                // This means 50 places for 40 users in the course.
                'maxsize' => $i
            ];

            $choices[] = $choice;
            $i -= 2;
        }
        $mod = mod_ratingallocate_generator::create_instance_with_choices($this,
            ['course' => $this->course,
                'strategyopt' => ['countoptions' => 3],
                'strategy' => 'strategy_order'],
            $choices);
        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);

        // Randomly manually allocate some students to some choices to see if the algorithm can deal with that.
        $this->allocate_random_users();

        $this->ratingallocate->distribute_users_without_choice(ACTION_DISTRIBUTE_UNALLOCATED_FILL);

        $i = 14;
        foreach ($letters as $groupname) {
            // A choice should be filled up completely before going for the next one by this algorithm. Choices with least places
            // left should be first filled up. This would mean we fill 'E', then 'D', then 'C', then 'B' and 4 users should be left
            // for 'A'.
            if ($groupname == 'A') {
                $this->assertEquals(4, $this->get_allocation_count_for_choice($groupname));
            } else {
                $this->assertEquals($i, $this->get_allocation_count_for_choice($groupname));
            }
            $i -= 2;
        }

        // Assert the allocations already existing before have not changed.
        $this->assert_allocation_of_random_users();
    }
}
