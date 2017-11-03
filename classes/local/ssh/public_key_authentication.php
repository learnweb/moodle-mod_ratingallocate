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

class public_key_authentication extends authentication {

    private $public_key = '';
    private $private_key = '';
    private $passphrase = '';

    public function __construct($username, $public_key, $private_key, $passphrase = '') {
        parent::__construct($username);
        
        $this->public_key = $public_key;
        $this->private_key = $private_key;
        $this->passphrase = $passphrase;
    }

    public function set_public_key($public_key) {
        $this->public_key = $public_key;
    }

    public function get_public_key() {
        return $this->public_key;
    }

    public function set_private_key($private_key) {
        $this->private_key = $private_key;
    }

    public function get_private_key() {
        return $this->private_key;
    }

    public function set_passphrase($passphrase) {
        $this->passphrase = $passphrase;
    }

    public function get_passphrase() {
        return $this->passphrase;
    }

    public function authenticate($connection) {
        return ssh2_auth_pubkey_file($connection, $this->get_username(), $this->get_public_key(), $this->get_private_key(), $this->get_passphrase());
    }

}