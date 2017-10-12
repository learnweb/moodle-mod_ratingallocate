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

class local extends \ratingallocate\lp\executor {

    public function get_local_configuration($name) {
        return $this->get_configuration()["local_$name"] ?: [];
    }

    public function get_local_file() {
        return $this->get_local_configuration('file') ?: 'problem.lp';
    }
    
    public function main($lp_file) {
        $handle = popen($this->get_engine()->get_command($this->get_local_file()), 'r');
    }
    
}
