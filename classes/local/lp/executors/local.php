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

namespace mod_ratingallocate\local\lp\executors;

class local extends \mod_ratingallocate\local\lp\executor {

    private $local_file = null;

    /**
     * Creates a local executor
     *
     * @param $engine Engine that is used
     * @param $local_path Path of the local file used for solver execution
     *
     * @return Local executor instance
     */
    public function __construct($engine = null) {
        parent::__construct($engine);

        $this->local_file = tmpfile();
    }

    public function __destruct() {
        unlink($this->get_local_path().'.lp');
        unlink($this->get_local_path());
    }

    /**
     * Returns the path of the local file
     *
     * @return Local path
     */
    public function get_local_path() {
        return stream_get_meta_data($this->local_file)['uri'];
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
        $path = $this->get_local_path().'.lp';
        $local_file = fopen($path, 'w+');

        fwrite($local_file, $lp_file);
        fseek($local_file, 0);

        return popen($this->get_engine()->get_command($path), 'r');
    }

}
