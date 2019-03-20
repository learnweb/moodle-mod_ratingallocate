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

class password_authentication extends authentication {

    private $password = '';

    public function __construct($username, $password) {
        parent::__construct($username);
        $this->password = $password;
    }

    public function set_password($password) {
        $this->password = $password;
    }

    public function get_password() {
        return $this->password;
    }

    public function authenticate($connection) {
        return ssh2_auth_password($connection, $this->get_username(), $this->get_password());
    }

}