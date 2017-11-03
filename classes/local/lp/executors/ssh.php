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

class ssh extends \mod_ratingallocate\local\lp\executor {

    private $connection = null;
    private $local_file = null;
    private $remote_path = '';

    /**
     * Creates a ssh executor
     *
     * @param $engine Engine that is used
     * @param $connection SSH connection
     * @param $remote_path Remote path where the lp file will be stored temporarily
     *
     * @return ssh executor instance
     */
    public function __construct($engine = null, $connection = null, $remote_path = '') {
        parent::__construct($engine);

        $this->local_file = tmpfile();

        $this->set_connection($connection);
        $this->set_remote_path($remote_path);
    }

    /**
     * Sets the SSH connection
     *
     * @return SSH connection
     */
    public function set_connection($connection) {
        $this->connection = $connection;
    }

    /**
     * Returns the SSH connection
     *
     * @return SSH connection
     */
    public function get_connection() {
        return $this->connection;
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
        return stream_get_meta_data($this->local_file)['uri'];
    }

    /**
     * Sets the remote path
     *
     * @param $remote_path Remote path
     */
    public function set_remote_path($remote_path) {
        $this->remote_path = $remote_path;
    }

    /**
     * Returns the remote path
     *
     * @return Remote path
     */
    public function get_remote_path() {
        return $this->remote_path;
    }

    /**
     * Executes engine command on a remote machine using SSH
     *
     * @param $engine Engine that is used
     * @param $lp_file Content of the LP file
     *
     * @return Stream of stdout
     */
    public function solve($lp_file) {
        fwrite($this->get_local_file(), $lp_file);
        fseek($this->get_local_file(), 0);

        $this->get_connection()->send_file($this->get_local_path(), $this->get_remote_path());
        return $this->get_connection()->execute($this->get_engine()->get_command($this->get_remote_path()));
    }

}
