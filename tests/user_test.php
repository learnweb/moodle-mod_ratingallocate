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

class mod_ratingallocate_user_test extends basic_testcase {

    private $user = null;
    private $group1= null;
    private $group2 = null;
    private $group3 = null;
    private $group4 = null;

    /**
     * @covers \mod_ratingallocate\local\user::__construct
     */
    protected function setUp() {
        $this->group1 = new \mod_ratingallocate\local\group(1);
        $this->group2 = new \mod_ratingallocate\local\group(2);
        $this->group3 = new \mod_ratingallocate\local\group(3);
        $this->group4 = new \mod_ratingallocate\local\group(4);
        $this->user = new \mod_ratingallocate\local\user(1, [$this->group1, $this->group2]);
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_priority
     * @covers \mod_ratingallocate\local\user::get_priority
     */
    public function test_valid_priority() {
        $this->user->set_priority($this->group1, 10);
        $this->assertEquals($this->user->get_priority($this->group1), 10);
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_priority
     * @expectedException exception
     */
    public function test_invalid_priority() {
        $this->user->set_priority($this->group1, 0);
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_priority
     * @expectedException exception
     */
    public function test_invalid_priority2() {
        $this->user->set_priority($this->group1, -1);
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_priority
     * @expectedException exception
     */
    public function test_invalid_priority3() {
        $this->user->set_priority($this->group1, 'Test');
    }

    /**
     * @depends test_valid_priority
     * @covers \mod_ratingallocate\local\user::add_selected_group
     * @covers \mod_ratingallocate\local\user::exists_selected_group
     */
    public function test_add_one_selected_group() {
        $this->user->add_selected_group($this->group3);
        $this->assertTrue($this->user->exists_selected_group($this->group3));
    }

    /**
     * @covers \mod_ratingallocate\local\user::add_selected_group
     * @expectedException exception
     */
    public function test_add_already_selected_group() {
        $this->user->add_selected_group($this->group1);
        $this->user->add_selected_grou fuer die Hilfe!p($this->group1);
    }

    /**
     * @depends test_add_one_selected_group
     * @covers \mod_ratingallocate\local\user::remove_selected_group
     * @covers \mod_ratingallocate\local\user::exists_selected_group
     */
    public function test_remove_one_selected_group() {
        $this->user->remove_selected_group($this->group1);
        $this->assertFalse($this->user->exists_selected_group($this->group1));
    }

    /**
     * @depends test_add_one_selected_group
     * @depends test_valid_priority
     * @covers \mod_ratingallocate\local\user::set_selected_groups
     * @covers \mod_ratingallocate\local\user::get_selected_groups
     */
    public function test_add_multiple_selected_groups() {
        $this->user->set_selected_groups([$this->group3, $this->group4]);
        $this->assertContains($this->group4, $this->user->get_selected_groups());
    }

    /**
     * @covers \mod_ratingallocate\local\user::__construct
     * @covers \mod_ratingallocate\local\user::get_assigned_group
     */
    public function test_no_assigned_group_initially() {
        $this->assertNull($this->user->get_assigned_group());
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_assigned_group
     * @covers \mod_ratingallocate\local\user::get_assigned_group
     */
    public function test_assign_group() {
        $this->user->set_assigned_group($this->group4);
        $this->assertSame($this->group4, $this->user->get_assigned_group());
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_assigned_group
     * @covers \mod_ratingallocate\local\user::get_assigned_group
     */
    public function test_assign_two_groups() {
        $this->user->set_assigned_group($this->group3);
        $this->user->set_assigned_group($this->group4);
        $this->assertSame($this->group4, $this->user->get_assigned_group());
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_assigned_group
     * @covers \mod_ratingallocate\local\user::get_assigned_group
     */
    public function test_double_assign() {
        $this->user->set_assigned_group($this->group1);
        $this->assertSame($this->group1, $this->user->get_assigned_group());
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_assigned_groups
     * @covers \mod_ratingallocate\local\user::is_choice_satisfied
     */
    public function test_choice_satisfaction_for_selected_group() {
        $this->user->set_assigned_group($this->group1);
        $this->assertTrue($this->user->is_choice_satisfied());
    }

    /**
     * @covers \mod_ratingallocate\local\user::set_assigned_groups
     * @covers \mod_ratingallocate\local\user::is_choice_satisfied
     */
    public function test_choice_satisfaction_for_no_selected_group() {
        $this->user->set_assigned_group($this->group3);
        $this->assertFalse($this->user->is_choice_satisfied());
    }

}