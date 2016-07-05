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

/**
 * Prints a particular instance of ratingallocate
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 T Reischmann, C Usener
 * @copyright  based on code by M Schulze copyright (C) 2014 M Schulze
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once(dirname(__FILE__).'/solver/ford-fulkerson-koegel.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('m', 0, PARAM_INT);  // ratingallocate instance ID - it should be named as the first character of the module


if ($id) {
    $cm         = get_coursemodule_from_id('ratingallocate', $id, 0, false, MUST_EXIST);
    $course     = get_course($cm->course);
    $ratingallocate  = $DB->get_record('ratingallocate', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $ratingallocate  = $DB->get_record('ratingallocate', array('id' => $n), '*', MUST_EXIST);
    $course     = get_course($ratingallocate->course);
    $cm         = get_coursemodule_from_instance('ratingallocate', $ratingallocate->id, $course->id, false, MUST_EXIST);
} else {
    print_error('no_id_or_m_error', ratingallocate_MOD_NAME);
}


require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$PAGE->set_title($cm->name);
$PAGE->set_context($context);
$PAGE->set_url('/mod/ratingallocate/view.php', array('id' => $cm->id));

require_capability('mod/ratingallocate:view', $context);

$ratingallocateobj = new ratingallocate($ratingallocate, $course, $cm, $context);
echo $ratingallocateobj->handle_view();
