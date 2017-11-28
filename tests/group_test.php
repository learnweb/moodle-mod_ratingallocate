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

class mod_ratingallocate_group_test extends basic_testcase {

    private $group1 = null;
    private $group2 = null;

    private $user1= null;
    private $user2= null;

    /**
     * @covers \mod_ratingallocate\local\group::__construct
     */
    protected function setUp() {
        $this->group1 = new \mod_ratingallocate\local\group(1);
        $this->group2 = new \mod_ratingallocate\local\group(2);

        $this->user1 = new \mod_ratingallocate\local\user(1, []);
        $this->user2 = new \mod_ratingallocate\local\user(2, []);
    }

    /**
     * @covers \mod_ratingallocate\local\group::has_limit
     */
    public function test_initial_limit() {
        $this->assertFalse($this->group1->has_limit());
    }

    /**
     * @covers \mod_ratingallocate\local\group::get_assigned_users
     */
    public function test_assigned_users_initially_empty() {
        $this->assertEmpty($this->group1->get_assigned_users());
    }

    /**
     * @covers \mod_ratingallocate\local\group::has_limit
     */
    public function test_initial_size() {
        $this->assertTrue($this->group1->is_empty());
    }

    /**
     * @covers \mod_ratingallocate\local\group::set_limit
     * @covers \mod_ratingallocate\local\group::get_limit
     */
    public function test_valid_limit() {
        $this->group1->set_limit(1000);
        $this->assertEquals(1000, $this->group1->get_limit());
    }

    /**
     * @covers \mod_ratingallocate\local\group::set_limit
     * @expectedException exception
     */
    public function test_invalid_limit() {
        $this->group1->set_limit(-1);
    }

    /**
     * @covers \mod_ratingallocate\local\group::add_assigned_user
     * @covers \mod_ratingallocate\local\group::exists_assigned_user
     */
    public function test_assign_one_user() {
        $this->group1->add_assigned_user($this->user1);
        $this->assertTrue($this->group1->exists_assigned_user($this->user1));
    }

    /**
     * @depends test_assign_one_user
     * @covers \mod_ratingallocate\local\group::add_assigned_user
     * @covers \mod_ratingallocate\local\group::get_assigned_users
     */
    public function test_assign_multiple_users() {
        $this->group1->add_assigned_user($this->user1);
        $this->group1->add_assigned_user($this->user2);
        $this->assertContains($this->user1, $this->group1->get_assigned_users());
    }

    /**
     * @depends test_valid_limit
     * @depends test_assign_one_user
     * @covers \mod_ratingallocate\local\group::add_assigned_user
     * @covers \mod_ratingallocate\local\group::is_full
     */
    public function test_full_group() {
        $this->group1->set_limit(1);
        $this->group1->add_assigned_user($this->user1);
        $this->assertTrue($this->group1->is_full());
    }

    /**
     * @depends test_assign_one_user
     * @depends test_valid_limit
     * @covers \mod_ratingallocate\local\group::add_assigned_user
     * @expectedException exception
     */
    public function test_assign_to_full_group() {
        $this->group1->set_limit(1);
        $this->group1->add_assigned_user($this->user1);
        $this->group1->add_assigned_user($this->user2);
    }

    /**
     * @depends test_assign_one_user
     * @covers \mod_ratingallocate\local\group::add_assigned_user
     * @expectedException exception
     */
    public function test_assign_assigned_user() {
        $this->group1->add_assigned_user($this->user1);
        $this->group2->add_assigned_user($this->user1);
    }

    /**
     * @covers \mod_ratingallocate\local\group::add_assigned_user
     * @covers \mod_ratingallocate\local\group::get_assigned_users
     */
    public function test_double_assign() {
        $this->group1->add_assigned_user($this->user1);
        $this->group1->add_assigned_user($this->user1);
        $this->assertContains($this->user1, $this->group1->get_assigned_users());
    }

    /**
     * @covers \mod_ratingallocate\local\group::remove_assigned_user
     * @covers \mod_ratingallocate\local\group::exists_assigned_user
     */
    public function test_remove_valid_user() {
        $this->group1->add_assigned_user($this->user1);
        $this->group1->remove_assigned_user($this->user1);
        $this->assertFalse($this->group1->exists_assigned_user($this->user1));
    }

    /**
     * @depends test_assigned_users_initially_empty
     * @covers \mod_ratingallocate\local\group::remove_assigned_user
     * @expectedException exception
     */
    public function test_remove_invalid_user() {
        $this->group1->remove_assigned_user($this->user1);
    }
}
