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

namespace mod_ratingallocate\local\lp;

abstract class executor {

    private $engine = null;

    /**
     * Creates an executor instance
     *
     * @param $engine Engine that is used
     *
     * @return Executor instance
     */
    public function __construct($engine = null) {
        $this->set_engine($engine);
    }

    /**
     * Sets the engine used by the executor
     */
    public function set_engine($engine) {
        $this->engine = $engine;
    }

    /**
     * Returns the engine
     *
     * @return Engine
     */
    public function get_engine() {
        return $this->engine;
    }

    /**
     * Runs the distribution with user and group objects and assigns users to their selected groups
     *
     * @param $users Array of users
     * @param $groups Array of groups
     * @param $weighter Weighter instance
     */
    public function solve_objects(&$users, &$groups, $weighter) {
        $values = $this->solve_linear_program(utility::create_linear_program($users, $groups, $weighter));

        utility::assign_groups($values, $users, $groups);
    }

    /**
     * Runs the distribution with a linear program and returns variables and their values
     *
     * @param $linear_program Linear program that gets solved
     * @param $executor Executor that is used
     *
     * @return Array of variables and their value
     */
    public function solve_linear_program($linear_program) {
        return $this->solve_lp_file($linear_program->write());
    }

    /**
     * Runs the distribution with a lp file and returns variables and their values
     *
     * @param $linear_program Linear program that gets solved
     * @param $executor Executor that is used
     *
     * @return Array of variables and their value
     */
    public function solve_lp_file($lp_file) {
        return $this->get_engine()->read($this->solve($lp_file));
    }

    /**
     * Solves the lp file
     *
     * @param $lp_file Content of the lp file
     *
     * @return Array of variables and their value
     */
    abstract public function solve($lp_file);

}