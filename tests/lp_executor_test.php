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

class lp_executor_test extends basic_testcase {

    private $local = null;
    private $ssh = null;
    private $webservice = null;

    public function setUp() {
        $this->local = new \mod_ratingallocate\local\lp\executors\local();
        $this->ssh= new \mod_ratingallocate\local\lp\executors\ssh();
        $this->webservice = new \mod_ratingallocate\local\lp\executors\webservice\connector();
    }

    /**
     * @covers \mod_ratingallocate\local\lp\executor::set_engine
     * @covers \mod_ratingallocate\local\lp\executor::get_engine
     */
    public function test_engine() {
        $this->local->set_engine(new stdClass());
        $this->assertEquals($this->local->get_engine(), new stdClass());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\executors\local::get_local_path
     */
    public function test_local_file_path() {
        $this->assertNotEmpty($this->local->get_local_path());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\executors\ssh::get_local_path
     */
    public function test_ssh_file_path() {
        $this->assertNotEmpty($this->ssh->get_local_path());
    }

    /**
     * @covers \mod_ratingallocate\local\lp\executors\ssh::get_local_path
     */
    public function test_ssh_file() {
        $this->assertNotNull($this->ssh->get_local_file());
    }

}