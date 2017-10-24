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

namespace ratingallocate\lp\executors\webservice;

class connector extends \ratingallocate\lp\executor {

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
        $handle =  fopen($this->get_uri(), 'rb', false, $this->build_request($lp_file));

        echo stream_get_contents($handle);
        
        exit;
    }

    /**
     * Builds the request for fopen
     *
     * @param $lp_file Content of the lp file
     *
     * @returns Stream context
     */
    public function build_request($lp_file) {
        return stream_context_create(['http' => ['method' => 'POST',
                                                 'header' => 'Content-type: application/x-www-form-urlencoded',
                                                 'content' => http_build_query(['lp' => $lp_file])]]);
    }
    
}

class backend
{

    private $local_path = '';
    
    public function __construct($local_path) {
        $this->local_path = $local_path;
    }

    public function get_local_path() {
        return $this->local_path;
    }
    
    public function get_lp_file() {
        return $_POST['lp'];
    }
    
    public function main() {

        if(isset($_POST['lp'])) {
            $engine = new \ratingallocate\lp\engines\cplex();
            $executor = new \ratingallocate\lp\executors\local($engine, $this->local_path);
            
            fpassthru($executor->solve($this->get_lp_file()));
        }
        
    }
    
}