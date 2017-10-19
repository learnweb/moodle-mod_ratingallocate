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

namespace ratingallocate\lp\engines;

class scip extends \ratingallocate\lp\engine {
    
    /**
     * Returns the command that gets executed
     *
     * @param $input_file Name of the input file
     *
     * @returns Command as a string 
     */
    public function get_command($input_file) {
        return "scip -f $input_file";
    }

    /**
     * Reads the content of the stream and returns the variables and their optimized values
     *
     * @param $stream Output stream of the program that was executed
     *
     * @return Array of variables and their values
     */
    public function read($stream) {
        $content = stream_get_contents($stream);
        $solution = [];
        
        foreach(array_slice(explode("\n", substr($content, strpos($content, 'objective value:'))), 1) as $variable) {
            $parts = explode(' ', preg_replace('!\s+!', ' ', $variable));

            if(empty($parts[0]))
                break;
            
            $solution[$parts[0]] = $parts[1];
        }
        
        return $solution;
    }

}