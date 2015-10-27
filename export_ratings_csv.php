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

if ($id) {
    $cm = get_coursemodule_from_id('ratingallocate', $id, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $ratingallocate = $DB->get_record('ratingallocate', array(
        'id' => $cm->instance
            ), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/ratingallocate:export_ratings', $context);

$ratingallocateobj = new ratingallocate($ratingallocate, $course, $cm, $context);

global $DB;
// print all the exported data here

$downloadfilename = clean_filename('export_ratings_' . $ratingallocateobj->ratingallocate->name);
$csvexport = new csv_export_writer('semicolon');
$csvexport->set_filename($downloadfilename);
// id firstname lastname

$exporttitle [0] = 'userid';
$exporttitle [1] = 'username';
$exporttitle [2] = 'firstname';
$exporttitle [3] = 'lastname';

$offsetchoices = count($exporttitle);

$choices = $ratingallocateobj->get_rateable_choices();

foreach ($choices as $choice) {
    $columnid [$choice->id] = ($choice->id + $offsetchoices);
    $exporttitle [($choice->id + $offsetchoices)] = $choice->id . '|' . $choice->title;
}

// Sort headings by (choice-)id to align them with exported data (created below).
ksort($exporttitle);

$exporttitle [] = "allocation";
$columnid ["allocation"] = key(array_slice($exporttitle, - 1, 1, true));

// add the header to the data
$csvexport->add_data($exporttitle);

$userslines = array();

$ratings = $ratingallocateobj->get_ratings_for_rateable_choices();
$ratingscells = array();
foreach ($ratings as $rating) {
    if (!array_key_exists($rating->userid, $ratingscells)) {
        $ratingscells [$rating->userid] = array();
    }
    $ratingscells [$rating->userid] [$columnid [$rating->choiceid]] = $rating->rating;
}

// If there is no rating from a user for a group,
// put a 'no_rating_given' cell into the table.
$usersincourse = $ratingallocateobj->get_raters_in_course();

foreach ($usersincourse as $user) {
    if (!array_key_exists($user->id, $ratingscells)) {
        $ratingscells [$user->id] = array();
    }
    foreach ($columnid as $choice) {
        if (!array_key_exists($choice, $ratingscells [$user->id])) {
            $ratingscells [$user->id] [$choice] = '-';
        }
    }

    $ratingscells [$user->id] [0] = $user->id;
    $ratingscells[$user->id][1] = $user->username;

    $ratingscells[$user->id][2] = fullname($user);
    $ratingscells[$user->id][3] = $user->lastname;
    // Sort ratings by choiceid to align them with the group names in the table
    ksort($ratingscells [$user->id]);
}

$memberships = $ratingallocateobj->get_all_allocations();

foreach ($memberships as $userid => $groups) {
    foreach ($groups as $groupsid => $rating) {
        if (array_key_exists($userid, $ratingscells)) {
            $ratingscells [$userid] [$columnid ["allocation"]] = $groupsid;
        }
    }
}

foreach ($ratingscells as $userline) {
    $csvexport->add_data($userline);
}

$csvexport->download_file();

