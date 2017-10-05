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
    private $name = '';
    
    /**
     * Creates a new engine instance
     *
     * @param $name Name of the engine
     * @param $configuration Array of configuration directives     
     */
    public function __construct($engine, $name = '') {
        if(empty($name)) {
    		$segments = explode("\\", get_class($this));
    		$name = $segments[count($segments) - 1];
    	}
    		
        $this->engine = $engine;
        $this->name = $name;
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
    
    /**
     * Returns the name of the executor
     *
     * @return Name of the executor
     */
    public function get_name() {
        return $this->name;
    }

    abstract public function main($lp_file);
}