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

/**
 * Abstract class which defines abstract methods for weighter 
 */
abstract class weighter {
    
    /**
     * Returns an array of available weighters
     * 
     * @return Array with names of available weighters
     */
    public static function get_weighters() {
    	$weighters = array_diff(scandir(dirname(__FILE__).'/weighters'), array('.', '..'));
    	
    	foreach($weighters as &$weighter)
			$weighter = str_ireplace('.php', '', $weighter);
    	
    	return array_values($weighters); 
    }

    /**
     * Applys a concrete value for x
     *
     * @param $x Value for x
     *
     * @return Function value for x
     */
    abstract public function apply($x);
    
    /**
     * Returns the functional representation as a string
     *
     * @param $variable_name The name of the variable
     *
     * @return Functional representation as a string
     */
    abstract public function to_string($variable_name = 'x');

};