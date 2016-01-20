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
 * Internal library of functions for module ratingallocate, subpart csv_export.
 *
 *
 * @package    mod_ratingallocate
 * @copyright  2014 Max Schulze, C Usener
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php'); // to include $CFG, for example
require_once($CFG->libdir . '/csvlib.class.php');
require_once('./locallib.php');

$id = required_param('id', PARAM_INT); // course_module ID, or


$cm = get_coursemodule_from_id('ratingallocate', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$ratingallocate = $DB->get_record('ratingallocate', array(
    'id' => $cm->instance
        ), '*', MUST_EXIST);


require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/ratingallocate:export_ratings', $context);

$ratingallocateobj = new ratingallocate($ratingallocate, $course, $cm, $context);

global $DB;
// print all the exported data here


$downloadfilename = clean_filename('export_ratings_' . $ratingallocateobj->ratingallocate->name);
$csvexport = new csv_export_writer('semicolon');
$csvexport->set_filename($downloadfilename);

$renderer = $PAGE->get_renderer('mod_ratingallocate');
$renderer->ratings_csv_for_ratingallocate($ratingallocateobj, $csvexport);

$csvexport->download_file();

