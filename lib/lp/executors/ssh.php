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

    public function get_ssh_configuration($name) {
        return $this->get_configuration()["ssh_$name"] ?: [];
    }

    public function get_ssh_hostname() {
        return $this->get_ssh_configuration('hostname') ?: '';
    }
    
    public function get_ssh_username() {
        return $this->get_ssh_configuration('username') ?: '';
    }
    
    public function get_ssh_password() {
        return $this->get_ssh_configuration('password') ?: '';
    }
    
    public function get_ssh_fingerprint() {
        return $this->get_ssh_configuration('fingerprint') ?: '';
    }

    public function get_ssh_authentication() {
        return new \ratingallocate\ssh\password_authentication($this->get_ssh_username(), $this->get_ssh_password());
    }
    
    public function get_ssh_file() {
        return $this->get_ssh_configuration('file') ?: 'problem.lp';
    }
    
    public function main($lp_file) {
        $connection = new \ratingallocate\ssh\connection($this->get_ssh_hostname(),
                                                         $this->get_ssh_fingerprint(),
                                                         $this->get_ssh_authentication());
        
        $connection->send_file(stream_get_meta_data($temp_file)['uri'], $this->get_ssh_file());
        
        return $connection->execute($this->get_engine()->get_command($this->get_ssh_file()));
    }
    
}
