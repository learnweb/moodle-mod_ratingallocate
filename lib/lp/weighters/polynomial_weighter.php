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

namespace ratingallocate\lp\weighters;

/**
 * Class which represents a polynomial weighter
 */
class polynomial_weighter extends \ratingallocate\lp\weighter {
    
    /**
     * Coeefficients, that represent the polynom
     */
    private $coefficients = [];

    /**
     * Creates a polynomial weighter
     *
     * @param $coefficients Coefficients, which reprent the polynom 
     */
    public function __construct($coefficients) {
        $this->coefficients = array_reverse($coefficients);
    }

    /**
     * Returns the coefficients of the polynom
     *
     * @return Array of coefficients
     */
    public function get_coefficients() {
        return $this->coefficients;
    }

    /**
     * Applys a concrete value for x
     *
     * @param $x Value for x
     *
     * @return Function value for x
     */
    public function apply($x) {
        $weight = 0;

        for($i = 0; $i < count($this->coefficients); ++$i)
            $weight += $this->coefficients[$i] * pow($x, $i);
        
        return $weight;
    }
 
    /**
     * Returns the functional representation as a string
     *
     * @param $variable_name The name of the variable
     *
     * @return Functional representation as a string
     */
    public function to_string($variable_name = 'x') {
        $string = '';
        
        $coefficients_size = count($this->coefficients);
        
        for($i = 0; $i < $coefficients_size; ++$i) {
            if($this->coefficients[$i] == 0)
                continue;
            
            if(!empty($string))
                $string .= '+';

            $string .= ($this->coefficients[$i] == 1 ? '' : $this->coefficients[$i].'*') . $variable_name . "^$i";
        }
        
        return $string;
    }

};