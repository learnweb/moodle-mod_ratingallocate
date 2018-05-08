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
require_once('../../config.php');

require_once('./locallib.php');
//get instance of ratingallocate
$ratingallocateid = 1;
$ratingallocate = $DB->get_records('ratingallocate')[1];
var_dump($ratingallocate);
$courseid = $ratingallocate->course;
$course = get_course($courseid);
$cm = get_coursemodule_from_instance('ratingallocate', $ratingallocate->id, $courseid);
$context = \context_module::instance($cm->id);

$ratingallocate = new ratingallocate($ratingallocate, $course, $cm, $context);

var_dump($ratingallocate->get_allocations());