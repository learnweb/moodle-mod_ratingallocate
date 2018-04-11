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

namespace mod_ratingallocate\local\lp\executors\webservice;

class backend
{
    private $engine = null;
    private $local_file = null;
    private $secret = null;

    /**
     * Creates a webservice backend
     *
     * @param $engine Engine which is used by the backend
     * @param $secret Secret used for backend protection
     * @param $local_path Local path where a temporary lp file is stored
     *
     * @return Webservice backend instance
     */
    public function __construct($engine = null, $secret = null) {
        $this->local_file = tmpfile();

        $this->set_engine($engine);
        $this->set_secret($secret);
    }

    /**
     * Sets the engine used by the backend
     *
     * @param $engine Engine
     */
    public function set_engine($engine) {
        $this->engine = $engine;
    }

    /**
     * Returns the engine used by the backend
     *
     * @return Engine
     */
    public function get_engine() {
        return $this->engine;
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
     * Returns the webservice secret
     *
     * @return Webservice secret
     */
    public function get_secret() {
        return $this->secret;
    }

    /**
     * Returns the handle of the local file
     *
     * @return Handle of the local file
     */
    public function get_local_file() {
        return $this->local_file;
    }

    /**
     * Returns the path of the local file
     *
     * @return Local path
     */
    public function get_local_path() {
        return stream_get_meta_data($this->get_local_file())['uri'];
    }

    /**
     * Handles an incomming request
     */
    public function main() {
        if(!$this->verify_secret()) {
            http_response_code(401);
            echo 'Unauthorized';

            return;
        }

        if(isset($_POST['lp_file'])) {
            $executor = new \mod_ratingallocate\local\lp\executors\local($this->get_engine(), $this->get_local_path());

            fpassthru($executor->solve($_POST['lp_file']));
        }
    }

    /**
     * Verifys the secret
     *
     * @return true if secret was verified successfully
     */
    private function verify_secret() {
        if($this->get_secret() === null)
            return true;

        return $this->get_secret() === $_POST['secret'];
    }

}