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

class webservice extends \ratingallocate\lp\executor {

    private $uri = '';
    
    public function __construct($uri) {
        $this->uri = $uri;
    }

    public function get_uri() {
        return $this->uri;
    }

    public function main($engine, $lp_file) {
        $data = http_build_query(['lp' => $lp_file]);
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Context-type: application/x-www-form-urlencoded', 'content' => $data]]);
        
        $solution = file_get_contents($this->get_uri(), false, $context);

        exit;
    }
    
}
