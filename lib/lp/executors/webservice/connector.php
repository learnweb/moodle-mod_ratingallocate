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
    private $secret = null;
    
    /**
     * Creates a webservice connector
     */
    public function __construct($engine = null, $uri = '', $secret = null) {
        parent::__construct($engine);
        $this->set_uri($uri);
        $this->set_secret($secret);
    }

    /**
     * Sets the webservices secret
     *
     * @param $secret Webservice secret
     */
    public function set_secret($secret) {
        $this->secret = $secret;
    }
    
    /**
     * Returns the webservices secret
     *
     * @return Secret of the backend
     */
    public function get_secret() {
        return $this->secret;
    }

    /**
     * Sets the uri to the backend
     *
     * @param $uri URI to the backend
     */
    public function set_uri($uri) {
        $this->uri = $uri;
    }
    
    /**
     * Returns the uri to the backend
     *
     * @return URI of the backend
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
     * @throws exception 
     *
     * @return Stream of stdout
     */
    public function solve($lp_file) {
        $handle = fopen($this->get_uri(), 'rb', false, $this->build_request($lp_file));

        if(!$handle)
            throw new \exception('Could not connect to webservice backend!');

        return $handle;
    }

    /**
     * Builds the request for fopen
     *
     * @param $lp_file Content of the lp file
     *
     * @return Stream context
     */
    public function build_request($lp_file) {
        return stream_context_create(['http' => ['method' => 'POST',
                                                 'header' => 'Content-type: application/x-www-form-urlencoded',
                                                 'content' => $this->build_query($lp_file)]]);
    }

    /**
     * Build the query for the request
     *
     * @param $lp_file Content of the lp file
     *
     * @return Encoded http query
     */
    public function build_query($lp_file) {
        return http_build_query(['lp_file' => $lp_file,
                                 'secret' => $this->get_secret()]);
    }
    
}
