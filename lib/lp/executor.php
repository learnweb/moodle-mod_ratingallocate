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

abstract class executor {

    private $engine = null;
    
    /**
     * Creates a new executor instance
     *
     * @param $engine Engine instance
     */
    public function __construct($engine) {
        $this->engine = $engine;
    }
    
    /**
     * Returns the engine the executor
     *
     * @return Engine of the executor
     */
    public function get_engine() {
        return $this->engine;
    }

    /**
     * Returns the configuration of the engine
     *
     * @return Engine configuration
     */
    public function get_configuration() {
        return $this->get_engine()->get_configuration();
    }
    
    abstract public function main($linear_program);
}