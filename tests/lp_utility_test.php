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

class lp_utility_test extends basic_testcase {

    private $users = null;
    private $groups = null;

    protected function setUp() {
        $this->users = [];
        $this->groups = [];

        for($i = 0; $i < 2; ++$i)
            $this->groups[] = new \mod_ratingallocate\local\group($i);

        for($i = 0; $i < 4; ++$i) {
            $this->users[] = new \mod_ratingallocate\local\user($i, $this->groups);
            $this->users[$i]->set_assigned_group($this->groups[rand() % count($this->groups)]);
        }
    }

    /**
     * @covers \mod_ratingallocate\local\lp\utility::translate_to_name
     */
    public function test_to_translation() {
        $this->assertEquals('x_2_1', \mod_ratingallocate\local\lp\utility::translate_to_name($this->users[2], $this->groups[1]));
    }

    /**
     * @covers \mod_ratingallocate\local\lp\utility::translate_from_name
     */
    public function test_from_translation() {
        $this->assertEquals([$this->users[2], $this->groups[1]], \mod_ratingallocate\local\lp\utility::translate_from_name('x_2_1', $this->users, $this->groups));
    }

    /**
     * @covers \mod_ratingallocate\local\lp\utility::assign_groups
     */
    public function test_assigning_groups() {
        \mod_ratingallocate\local\lp\utility::assign_groups(['x_2_1' => 1], $this->users, $this->groups);
        $this->assertSame($this->users[2]->get_assigned_group(), $this->groups[1]);
    }

    /**
     * @covers \mod_ratingallocate\local\lp\utility::create_linear_program
     */
    public function test_linear_program_creation() {
        $linear_program = \mod_ratingallocate\local\lp\utility::create_linear_program($this->users, $this->groups, new \mod_ratingallocate\local\lp\weighters\identity_weighter());
        $this->assertNotNull($linear_program);
    }

}