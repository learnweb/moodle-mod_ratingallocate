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

namespace mod_ratingallocate\local\ssh;

class authentication {

    private $username = '';

    public function __construct($username) {
        $this->username = $username;
    }

    public function set_username($username) {
        $this->username = $username;
    }

    public function get_username() {
        return $this->username;
    }

    public function authenticate($connection) {
        return ssh2_auth_none($connection, $this->username);
    }

}