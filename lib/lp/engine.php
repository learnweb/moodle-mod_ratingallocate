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

namespace ratingallocate\lp;

abstract class engine {
    
    /**
     * Runs the distribution with user and group objects
     *
     * @param $users Array of users
     * @param $groups Array of groups
     * @param $weighter Weighter instance
     * @param $executor Executor that is used
     */
    public function solve(&$users, &$groups, $weighter, $executor) {
        $values = $this->solve_linear_program(utility::create_linear_program($users, $groups, $weighter), $executor);
    }
    
    /**
     * Runs the distribution with a linear program and returns variables and their values
     *
     * @param $linear_program Linear program that gets solved
     * @param $executor Executor that is used
     */
    public function solve_linear_program($linear_program, $executor) {
        return $this->solve_lp_file($linear_program->write(), $executor);
    }

    /**
     * Runs the distribution with a lp file and returns variables and their values
     *
     * @param $linear_program LP file that gets solved
     * @param $executor Executor that is used
     */
    public function solve_lp_file($lp_file, $executor) {
        return $this->read($executor->main($this, $lp_file));
    }
    
    /**
     * Reads the content of the stream and returns the variables and their optimized values
     *
     * @param $stream Output stream of the program that was executed
     *
     * @return Array of variables and their values
     */
    abstract public function read($stream);

    /**
     * Returns the command that gets executed
     *
     * @param $input_file Name of the input file
     *
     * @returns Command as a string 
     */
    abstract public function get_command($input_file);
};