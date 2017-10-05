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
    
    public function main() {
        $authentication = new \ratingallocate\ssh\password_authentication($this->get_configuration()['SSH']['USERNAME'], $this->get_configuration()['SSH']['PASSWORD']);
        $connection = new \ratingallocate\ssh\connection($this->get_configuration()['SSH']['HOSTNAME'], $this->get_configuration()['SSH']['FINGERPRINT'], $authentication);
            
        $connection->send_file(stream_get_meta_data($temp_file)['uri'], $this->get_configuration()['SSH']['REMOTE_FILE']);
        return $connection->execute($this->get_command($this->get_configuration()['SSH']['REMOTE_FILE']));
    }
    
}
