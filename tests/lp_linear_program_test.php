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

class lp_linear_program_test extends basic_testcase {

    private $linear_program = null;

    public function setUp() {
        $this->linear_program = new mod_ratingallocate\local\lp\linear_program();

        $this->linear_program->set_objective_method(\mod_ratingallocate\local\lp\linear_program::MAXIMIZE);
        $this->linear_program->set_objective_function('3x1+4x2');

        $this->linear_program->add_constraint('x1 + x2 < 100');
        $this->linear_program->add_bound('x2 < 4');
        $this->linear_program->add_variable('x1');
        $this->linear_program->add_variable('x2');
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::set_objective_method
     * @covers \mod_ratingallocate\local\lp\linear_program::get_objective_method
     */
    public function test_objective_method() {
        $this->linear_program->set_objective_method(\mod_ratingallocate\local\lp\linear_program::MINIMIZE);
        $this->assertEquals($this->linear_program->get_objective_method(), \mod_ratingallocate\local\lp\linear_program::MINIMIZE);
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::set_objective_function
     * @covers \mod_ratingallocate\local\lp\linear_program::get_objective_function
     */
    public function test_objective_function() {
        $this->linear_program->set_objective_function('2x+3');
        $this->assertEquals($this->linear_program->get_objective_function(), '2x+3');
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::add_constraint
     * @covers \mod_ratingallocate\local\lp\linear_program::get_constraints
     */
    public function test_constraints() {
        $this->linear_program->add_constraint('x1 + x2 < 100');
        $this->assertContains('x1 + x2 < 100', $this->linear_program->get_constraints());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::set_constraints
     * @covers \mod_ratingallocate\local\lp\linear_program::get_constraints
     */
    public function test_constraints2() {
        $this->linear_program->set_constraints(['x1 + x2 < 100']);
        $this->assertContains('x1 + x2 < 100', $this->linear_program->get_constraints());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::add_bound
     * @covers \mod_ratingallocate\local\lp\linear_program::get_bounds
     */
    public function test_bounds() {
        $this->linear_program->add_bound('x2 > 25');
        $this->assertContains('x2 > 25', $this->linear_program->get_bounds());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::set_bounds
     * @covers \mod_ratingallocate\local\lp\linear_program::get_bounds
     */
    public function test_bounds2() {
        $this->linear_program->set_bounds(['x2 > 25']);
        $this->assertContains('x2 > 25', $this->linear_program->get_bounds());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::add_variable
     * @covers \mod_ratingallocate\local\lp\linear_program::get_variable_names
     */
    public function test_variables() {
        $this->linear_program->add_variable('x2');
        $this->assertContains('x2', $this->linear_program->get_variable_names());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_program::add_variable
     * @expectedException exception
     */
    public function test_variable_long_name() {
        $this->linear_program->add_variable('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write_objective_method
     */
    public function test_write_objective_method() {
        $this->assertEquals('maximize', strtolower($this->linear_program->write_objective_method()));
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write_objective_method
     */
    public function test_write_objective_method2() {
        $this->linear_program->set_objective_method(\mod_ratingallocate\local\lp\linear_program::MINIMIZE);
        $this->assertEquals('minimize', strtolower($this->linear_program->write_objective_method()));
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write_objective_method
     * @expectedException exception
     */
    public function test_write_objective_method3() {
        $this->linear_program->set_objective_method(\mod_ratingallocate\local\lp\linear_program::NONE);
        $this->linear_program->write_objective_method();
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write_objective
     * @expectedException exception
     */
    public function test_write_objective_function() {
        $this->linear_program->set_objective_function('');
        $this->linear_program->write_objective();
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write_constraints
     */
    public function test_write_constraints() {
        $this->assertEquals($this->linear_program->write_constraints(), "Subject To\nx1+x2<100\n");
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write_bounds
     */
    public function test_write_bounds() {
        $this->assertEquals($this->linear_program->write_bounds(), "Bounds\nx2<4\n");
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write_variables
     */
    public function test_write_variables() {
        $this->assertEquals($this->linear_program->write_variables(), "General\nx1 x2 \n");
    }

    /**
     * @covers \mod_ratingallocate\local\lp\linear_programm::write
     */
    public function test_write() {
        $this->assertNotEmpty($this->linear_program->write());
    }
}