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

class lp_engine_test extends basic_testcase {

    private $cplex = null;
    private $scip = null;

    public function setUp() {
        $this->cplex = new \mod_ratingallocate\local\lp\engines\cplex();
        $this->scip = new \mod_ratingallocate\local\lp\engines\scip();
    }

    /**
     * @covers \mod_ratingallocate\local\lp\engines\scip::read
     */
    public function test_scip_read() {
        $handle = fopen(__DIR__.'/scip.log', 'r');
        $solution = $this->scip->read($handle);

        $this->assertEquals($solution['x1'], 5);
        $this->assertEquals($solution['x2'], 2);

        fclose($handle);
    }

    /**
     * @covers \mod_ratingallocate\local\lp\engines\cplex::read
     */
    public function test_cplex_read() {
        $handle = fopen(__DIR__.'/cplex.log', 'r');
        $solution = $this->cplex->read($handle);

        $this->assertEquals($solution['x1'], 5);
        $this->assertEquals($solution['x2'], 2);

        fclose($handle);
    }

    /**
     * @covers \mod_ratingallocate\local\lp\engines\cplex::get_command
     */
    public function test_cplex_command() {
        $this->assertNotEmpty($this->cplex->get_command('file.lp'));
    }

    /**
     * @covers \mod_ratingallocate\local\lp\engines\scip::get_command
     */
    public function test_scip_command() {
        $this->assertNotEmpty($this->scip->get_command('file.lp'));
    }
}