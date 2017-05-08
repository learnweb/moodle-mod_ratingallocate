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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 M Schulze, C Usener
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);   // Courseid.

$course = get_course($id);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/ratingallocate/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', ratingallocate_MOD_NAME), 2);

require_capability('mod/ratingallocate:view', $coursecontext);

$event = \mod_ratingallocate\event\index_viewed::create_simple(
    context_course::instance($course->id));
$event->trigger();

if (! $ratingallocates = get_all_instances_in_course('ratingallocate', $course, $USER->id)) {
    notice(get_string('noratingallocates', ratingallocate_MOD_NAME),
        new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$table->head  = array(
                    get_string('name'),
                    get_string('rating_begintime', 'mod_ratingallocate'),
                    get_string('rating_endtime', 'mod_ratingallocate'),
                    get_string('is_published', 'mod_ratingallocate'));
$table->align = array('left', 'left', 'left', 'left');


foreach ($ratingallocates as $ratingallocate) {
    $ratingallocateinstance = $DB->get_record('ratingallocate', array('id' => $ratingallocate->id));
    if (!$ratingallocate->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/ratingallocate/view.php', array('id' => $ratingallocate->coursemodule)),
            format_string($ratingallocate->name, true),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/ratingallocate/view.php', array('id' => $ratingallocate->coursemodule)),
            format_string($ratingallocate->name, true));
    }
    $table->data[] = array($link, userdate($ratingallocateinstance->accesstimestart),
        userdate($ratingallocateinstance->accesstimestop),
        $ratingallocateinstance->published == 0 ? get_string('no') : get_string('yes'));

}

echo html_writer::table($table);
echo $OUTPUT->footer();
