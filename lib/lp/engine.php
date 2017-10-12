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
    
    private $configuration = [];

    /**
     * Creates a new engine instance
     *
     * @param $configuration Array of configuration directives     
     */
    public function __construct($configuration = []) {
        $this->configuration = $configuration;
    }

    /**
     * Returns the configuration directives of the engine
     *
     * @return Configuration directives
     */
    public function get_configuration() {
        return $this->configuration;
    }

    /**
     *
     */
    public function get_executor() {
        return $this->get_configuration()['executor'] ?: 'local';
    }
    
    /**
     * Runs the distribution with user and group objects
     *
     * @param $users Array of users
     * @param $groups Array of groups
     * @param $weighter Weighter instance
     */
    public function solve_objects(&$users, &$groups, $weighter) {
        $this->solve_linear_program(utility::create_linear_program($users, $groups, $weighter));
    }
    
    /**
     * Runs the distribution with a linear program
     */
    public function solve_linear_program($linear_program) {
        $this->solve_lp_file($linear_program->write());
    }

    /**
     * Runs the distribution with a lp file
     */
    public function solve_lp_file($lp_file) {
        $executor_path = '\\ratingallocate\\lp\\executors\\'.$this->get_executor();
        $executor = new $executor_path($this);

        echo get_class($executor);
        
        //$stream = $executor->main($lp_file);
        
        //utility::assign_groups($this->read($this->execute($lp_file), $users, $groups), $users, $groups);
    }

    /**
     * Reads the content of the stream and returns the variables and their optimized values
     *
     * @param $stream Output stream of the program that was executed
     *
     * @return Array of variables and their values
     */
    abstract protected function read($stream);

    /**
     * Returns the command that gets executed
     *
     * @param $input_file Name of the input file
     *
     * @returns Command as a string 
     */
    abstract protected function get_command($input_file);
};