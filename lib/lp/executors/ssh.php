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

class ssh extends \ratingallocate\lp\executor {

    private $connection = null;    
    private $local_file = null;
    private $remote_path = '';
    
    public function __construct($connection, $remote_path) {
        $this->connection = $connection;
        $this->local_file = tmpfile();
        $this->remote_path = $remote_path;
    }

    public function get_connection() {
        return $this->connection;
    }

    public function get_local_file() {
        return $this->local_file;
    }
    
    public function get_local_path() {
        return stream_get_meta_data($this->local_file)['uri'];
    }
    
    public function get_remote_path() {
        return $this->remote_path;
    }
    
    public function main($engine, $lp_file) {
        fwrite($this->get_local_file(), $lp_file);
        fseek($this->get_local_file(), 0);

        $this->get_connection()->send_file($this->get_local_path(), $this->get_remote_path());
        return $this->get_connection()->execute($engine->get_command($this->get_remote_path()));
    }
    
}
