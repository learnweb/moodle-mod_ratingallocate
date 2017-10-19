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

namespace ratingallocate\lp\executors;

class webservice extends \ratingallocate\lp\executor {

    private $uri = '';
    
    /**
     * Creates a webservice executor
     */
    public function __construct($engine, $uri) {
        parent::__construct($engine);
        $this->uri = $uri;
    }

    /**
     * Returns the uri to the webservice
     */
    public function get_uri() {
        return $this->uri;
    }

    /**
     * Executes engine command on a remote machine using a webservice
     *
     * @param $engine Engine that is used
     * @param $lp_file Content of the LP file
     *
     * @return Stream of stdout
     */
    public function solve($lp_file) {
        $data = http_build_query(['lp' => $lp_file]);
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Context-type: application/x-www-form-urlencoded', 'content' => $data]]);
        
        return fopen($this->get_uri(), 'rb', false, $context);
    }
    
}
