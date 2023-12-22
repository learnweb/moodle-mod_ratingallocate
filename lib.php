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
 * Library of interface functions and constants for module ratingallocate
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the ratingallocate specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package mod_ratingallocate
 * @abstract sollte nur minimalstes, was von außen aufgerufen wird.
 * @copyright 2014 M Schulze, T Reischmann, C Usener
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * example constant
 */
define('RATINGALLOCATE_MOD_NAME', 'ratingallocate');
define('RATINGALLOCATE_EVENT_TYPE_START', 'start');
define('RATINGALLOCATE_EVENT_TYPE_STOP', 'stop');
// define('NEWMODULE_ULTIMATE_ANSWER', 42);

require_once(dirname(__FILE__) . '/db/db_structure.php');

use ratingallocate\db as this_db;

// //////////////////////////////////////////////////////////////////////////////
// Moodle core API //
// //////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature
 *            FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 * @see plugin_supports() in lib/moodlelib.php
 */
function ratingallocate_supports($feature) {
    if (defined('FEATURE_MOD_PURPOSE')) {
        if ($feature == FEATURE_MOD_PURPOSE) {
            return MOD_PURPOSE_ADMINISTRATION;
        }
    }

    switch ($feature) {
        case FEATURE_MOD_INTRO :
            return true;
        case FEATURE_SHOW_DESCRIPTION :
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        default :
            return null;
    }
}

/**
 * Saves a new instance of the ratingallocate into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $ratingallocate
 *            An object from the form in mod_form.php
 * @param mod_ratingallocate_mod_form $mform
 * @return int The id of the newly inserted ratingallocate record
 */
function ratingallocate_add_instance(stdClass $ratingallocate, mod_ratingallocate_mod_form $mform = null) {
    global $DB, $COURSE;

    $ratingallocate->timecreated = time();

    $transaction = $DB->start_delegated_transaction();
    try {
        $ratingallocate->{this_db\ratingallocate::SETTING} = json_encode($ratingallocate->strategyopt);
        // Insert instance to get ID for children.
        $id = $DB->insert_record(this_db\ratingallocate::TABLE, $ratingallocate);
        $ratingallocate->id = $id;

        ratingallocate_set_events($ratingallocate);
        $transaction->allow_commit();
        return $id;
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
}

/**
 * Updates an instance of the ratingallocate in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $ratingallocate
 *            An object from the form in mod_form.php
 * @param mod_ratingallocate_mod_form $mform
 * @return boolean Success/Fail
 */
function ratingallocate_update_instance(stdClass $ratingallocate, mod_ratingallocate_mod_form $mform = null) {
    global $DB;

    $ratingallocate->timemodified = time();
    $ratingallocate->id = $ratingallocate->instance;

    try {
        $transaction = $DB->start_delegated_transaction();

        // Serialize strategy settings.
        $ratingallocate->setting = json_encode($ratingallocate->strategyopt);

        $bool = $DB->update_record('ratingallocate', $ratingallocate);

        // Create or update the new events.
        ratingallocate_set_events($ratingallocate);

        $transaction->allow_commit();
        return $bool;
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
}

/**
 * Removes an instance of the ratingallocate from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 *            Id of the module instance
 * @return boolean Success/Failure
 */
function ratingallocate_delete_instance($id) {
    global $DB;

    if (!$ratingallocate = $DB->get_record('ratingallocate', array(
            'id' => $id
    ))) {
        return false;
    }

    // Delete any dependent records here # .
    $DB->delete_records('ratingallocate_allocations', array(
            'ratingallocateid' => $ratingallocate->id
    ));

    $deleteids = array_keys($DB->get_records('ratingallocate_choices', array(
        'ratingallocateid' => $ratingallocate->id
            ), '', 'id'));

    if (!empty($deleteids)) {
        list ($insql, $params) = $DB->get_in_or_equal($deleteids);
        $DB->delete_records_select('ratingallocate_group_choices',
            'choiceid ' . $insql, $params);
        $DB->delete_records_select('ratingallocate_ch_gengroups',
            'choiceid ' . $insql, $params);
    }

    $DB->delete_records('ratingallocate_groupings', array(
        'ratingallocateid' => $ratingallocate->id
    ));

    $DB->delete_records_list('ratingallocate_ratings', 'choiceid', $deleteids);

    $DB->delete_records('ratingallocate_choices', array(
            'ratingallocateid' => $ratingallocate->id
    ));

    // Delete associated events.
    $DB->delete_records('event', array('modulename' => 'ratingallocate', 'instance' => $ratingallocate->id));

    $DB->delete_records('ratingallocate', array(
            'id' => $ratingallocate->id
    ));

    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in ratingallocate activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function ratingallocate_print_recent_activity($course, $viewfullnames, $timestart) {
    return false; // True if anything was printed, otherwise false.
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link ratingallocate_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function ratingallocate_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid,
        $userid = 0, $groupid = 0) {
}

/**
 * Prints single activity item prepared by {@see ratingallocate_get_recent_mod_activity()}
 *
 * @return void
 */
function ratingallocate_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {

}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function ratingallocate_get_extra_capabilities() {
    return array();
}

// //////////////////////////////////////////////////////////////////////////////
// File API //
// //////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function ratingallocate_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for ratingallocate file areas
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 * @package mod_ratingallocate
 * @category files
 *
 */
function ratingallocate_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the ratingallocate file areas
 *
 * @param stdClass $course
 *            the course object
 * @param stdClass $cm
 *            the course module object
 * @param stdClass $context
 *            the ratingallocate's context
 * @param string $filearea
 *            the name of the file area
 * @param array $args
 *            extra arguments (itemid, path)
 * @param bool $forcedownload
 *            whether or not force download
 * @param array $options
 *            additional options affecting the file serving
 * @category files
 *
 * @package mod_ratingallocate
 */
function ratingallocate_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea !== 'choice_attachment') {
        return false;
    }

    require_login($course, true, $cm);

    if (!has_capability('mod/ratingallocate:view', $context)) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    if (!$args) {
        // Empty path, use root.
        $filepath = '/';
    } else {
        // Assemble filepath.
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_ratingallocate', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // Send the file to the browser. Cache lifetime of 1 day, no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

// //////////////////////////////////////////////////////////////////////////////
// Navigation API //
// //////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding ratingallocate nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref
 *            An object representing the navigation tree node of the ratingallocate module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function ratingallocate_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {

}

/**
 * Extends the settings navigation with the ratingallocate settings
 *
 * This function is called when the context for the page is a ratingallocate module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav
 *            {@link settings_navigation}
 * @param navigation_node $ratingallocatenode
 *            {@link navigation_node}
 */
function ratingallocate_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $ratingallocatenode = null) {

}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every ratingallocate event in the site is checked, else
 * only ratingallocate events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance Ratingallocate module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function ratingallocate_refresh_events($courseid = 0, $instance = null, $cm = null): bool {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('ratingallocate', array('id' => $instance), '*', MUST_EXIST);
        }
        ratingallocate_set_events($instance);
        return true;
    }

    if ($courseid) {
        if (! $ratingallocates = $DB->get_records('ratingallocate', array('course' => $courseid))) {
            return true;
        }
    } else {
        if (! $ratingallocates = $DB->get_records('ratingallocate')) {
            return true;
        }
    }

    foreach ($ratingallocates as $ratingallocate) {
        ratingallocate_set_events($ratingallocate);
    }
    return true;
}

/**
 * Creates events for accesstimestart and accestimestop of a ratingallocate instance
 *
 * @param $ratingallocate
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function ratingallocate_set_events($ratingallocate) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');

    // Get CMID if not sent as part of $ratingallocate.
    if (!isset($ratingallocate->coursemodule)) {

        $cm = get_fast_modinfo($ratingallocate->course)->instances['ratingallocate'][$ratingallocate->id];

        $ratingallocate->coursemodule = $cm->id;
    }

    // Ratingallocate-accessstart calendar events.
    $eventid = $DB->get_field('event', 'id',
        array('modulename' => 'ratingallocate', 'instance' => $ratingallocate->id, 'eventtype' => RATINGALLOCATE_EVENT_TYPE_START));

    $timestart = $DB->get_field('ratingallocate', 'accesstimestart', array('id' => $ratingallocate->id));

    if (isset($timestart) && $timestart > 0) {
        $event = new stdClass();
        $event->eventtype = RATINGALLOCATE_EVENT_TYPE_START;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->name = get_string('calendarstart', RATINGALLOCATE_MOD_NAME, $ratingallocate->name);
        $event->description = format_module_intro('ratingallocate', $ratingallocate, $ratingallocate->coursemodule, false);
        $event->format = FORMAT_HTML;
        $event->instance = $ratingallocate->id;
        $event->timestart = $timestart;
        $event->timesort = $timestart;
        // Visibility should depend on the user.
        if (isset($ratingallocate->visible)) {
            $event->visible = $ratingallocate->visible;
        } else {
            $event->visible = instance_is_visible('ratingallocate', $ratingallocate);
        }
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Event doesn't exist so create one.
            $event->courseid = $ratingallocate->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'ratingallocate';
            $event->instance = $ratingallocate->id;
            $event->eventtype = RATINGALLOCATE_EVENT_TYPE_START;
            calendar_event::create($event, false);
        }
    } else if ($eventid) {
        // Delete calendarevent as it is no longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }

    // Ratingallocate-accessstop calendar events.
    $eventid = $DB->get_field('event', 'id',
        array('modulename' => 'ratingallocate', 'instance' => $ratingallocate->id, 'eventtype' => RATINGALLOCATE_EVENT_TYPE_STOP));

    $timestop = $DB->get_field('ratingallocate', 'accesstimestop', array('id' => $ratingallocate->id));

    if (isset($timestop) && $timestop > 0) {
        $event = new stdClass();
        $event->eventtype = RATINGALLOCATE_EVENT_TYPE_STOP;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->name = get_string('calendarstop', RATINGALLOCATE_MOD_NAME, $ratingallocate->name);
        $event->description = format_module_intro('ratingallocate', $ratingallocate, $ratingallocate->coursemodule, false);
        $event->format = FORMAT_HTML;
        $event->instance = $ratingallocate->id;
        $event->timestart = $timestop;
        $event->timesort = $timestop;
        // Visibility should depend on the user.
        if (isset($ratingallocate->visible)) {
            $event->visible = $ratingallocate->visible;
        } else {
            $event->visible = instance_is_visible('ratingallocate', $ratingallocate);
        }
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Event doesn't exist so create one.
            $event->courseid = $ratingallocate->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'ratingallocate';
            $event->instance = $ratingallocate->id;
            calendar_event::create($event, false);
        }
    } else if ($eventid) {
        // Delete calendarevent as it is no longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }
}

/**
 * Is the event visible?
 *
 * @param calendar_event
 * @return bool
 * @throws moodle_exception
 * @throws dml_exception
 */
function mod_ratingallocate_core_is_event_visible(calendar_event $event): bool {

    global $DB, $USER;

    $instance = $event->instance;
    if (!$instance) {
        return false;
    }

    $ratingallocaterecord = $DB->get_record('ratingallocate', ['id' => $instance]);
    $modinfo = get_fast_modinfo($event->courseid)->instances['ratingallocate'][$instance];
    $context = context_module::instance($modinfo->id);
    $course = get_course($event->courseid);

    $ratingallocate = new ratingallocate($ratingallocaterecord, $course, $modinfo, $context);
    $raters = $ratingallocate->get_raters_in_course();

    return in_array($USER, $raters);

}

/**
 * This function will update the ratingallocate module according to the event that has been modified.
 *
 * @params calendar_event, stdClass
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function mod_ratingallocate_core_calendar_event_timestart_updated (\calendar_event $event, \stdClass $ratingallocate) {

    global $CFG, $DB;

    if (empty($event->eventtype) || $event->modulename != 'ratingallocate') {
        return;
    }

    if ($event->instance != $ratingallocate->id) {
        return;
    }

    if (!in_array($event->eventtype, [RATINGALLOCATE_EVENT_TYPE_STOP, RATINGALLOCATE_EVENT_TYPE_START])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    $timeopen = $DB->get_field('ratingallocate', 'accesstimestart', ['id' => $ratingallocate->id]);
    $timeclose = $DB->get_field('ratingallocate', 'accesstimestop', ['id' => $ratingallocate->id]);

    // Modify the dates for accesstimestart and accesstimestop if the event was dragged.
    if ($event->eventtype == RATINGALLOCATE_EVENT_TYPE_START) {
        if ($timeopen != $event->timestart) {
            $ratingallocate->accesstimestart = $event->timestart;
            $modified = true;
        }
    } else if ($event->eventtype == RATINGALLOCATE_EVENT_TYPE_STOP) {
        if ($timeclose != $event->timestart) {
            $ratingallocate->accesstimestop = $event->timestart;
            $publishtime = $DB->get_field('ratingallocate', 'publishdate', ['id' => $ratingallocate->id]);
            // Modify the estimated publication date if it is now before the accesstimestop.
            if ($publishtime && $publishtime <= $ratingallocate->accesstimestop) {
                $ratingallocate->publishdate = $ratingallocate->accesstimestop + 2 * 24 * 60 * 60;
            }
            $modified = true;
        }
    }

    if ($modified) {
        $ratingallocate->timemodified = time();
        $DB->update_record('ratingallocate', $ratingallocate);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * Calculates the minimum and maximum range of dates this event can be in
 * according to the settings of the ratingallocate instance.
 *
 * @param calendar_event $event
 * @param stdClass $instance
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function mod_ratingallocate_core_calendar_get_valid_event_timestart_range (\calendar_event $event, \stdClass $instance): array {

    global $DB;

    $mindate = null;
    $maxdate = null;

    $timeopen = $DB->get_field('ratingallocate', 'accesstimestart', ['id' => $instance->id]);
    $timeclose = $DB->get_field('ratingallocate', 'accesstimestop', ['id' => $instance->id]);

    if ($event->eventtype == RATINGALLOCATE_EVENT_TYPE_START) {
        if (!empty($timeclose)) {
            $maxdate = [$timeclose, get_string('openafterclose', RATINGALLOCATE_MOD_NAME)];
        }
    } else if ($event->eventtype == RATINGALLOCATE_EVENT_TYPE_STOP) {
        if (!empty($timeopen)) {
            $mindate = [$timeopen, get_string('closebeforeopen', RATINGALLOCATE_MOD_NAME)];
        }
    }
    return [$mindate, $maxdate];
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all ratings and allocations
 * and clean up any related data.
 *
 * @global object
 * @global object
 * @param $data stdClass the data submitted from the reset course.
 * @return array status array
 */
function ratingallocate_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', RATINGALLOCATE_MOD_NAME);
    $status = [];

    $params = array('courseid' => $data->courseid);

    if (!empty($data->reset_ratings_and_allocations)) {

        // Delete all ratings.
        $ratingidssql = "SELECT r.id FROM {ratingallocate_ratings} r
                      INNER JOIN {ratingallocate_choices} c ON r.choiceid=c.id
                      INNER JOIN {ratingallocate} ra ON c.ratingallocateid=ra.id
                      WHERE ra.course= :courseid";
        $DB->delete_records_select('ratingallocate_ratings', "id IN ($ratingidssql)", $params);

        // Delete all allocations.
        $allocationidssql = "SELECT a.id FROM {ratingallocate_allocations} a
                            INNER JOIN {ratingallocate} r ON a.ratingallocateid=r.id
                            WHERE r.course= :courseid";
        $DB->delete_records_select('ratingallocate_allocations', "id IN ($allocationidssql)", $params);

        $status[] = [
            'component' => $componentstr,
            'item' => get_string('ratings_and_allocations_deleted', RATINGALLOCATE_MOD_NAME),
            'error' => false];
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        shift_course_mod_dates(RATINGALLOCATE_MOD_NAME, array('accesstimestart', 'accesstimestop'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Called by course/reset.php.
 *
 * @param MoodleQuickForm $mform form passed by reference
 */
function ratingallocate_reset_course_form_definition($mform) {

    $mform->addElement('header', 'ratingallocateheader', get_string('modulenameplural', RATINGALLOCATE_MOD_NAME));
    $mform->addElement('advcheckbox', 'reset_ratings_and_allocations',
        get_string('remove_ratings_and_allocations', RATINGALLOCATE_MOD_NAME));

}

/**
 * Course reset form defaults.
 * @return array the defaults.
 */
function ratingallocate_reset_course_form_defaults($course) {
    return ['reset_ratings_and_allocations' => 1];
}

/**
 * Add a get_coursemodule_info function in case any ratingallocate type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function ratingallocate_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = array('id'=>$coursemodule->instance);
    if (! $ratingallocate = $DB->get_record('ratingallocate', $dbparams)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $ratingallocate->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('ratingallocate', $ratingallocate, $coursemodule->id, false);
    }

    // Populate some other values that can be used in calendar or on dashboard.
    if ($ratingallocate->accesstimestart) {
        $result->customdata['accesstimestart'] = $ratingallocate->accesstimestart;
    }
    if ($ratingallocate->accesstimestop) {
        $result->customdata['accesstimestop'] = $ratingallocate->accesstimestop;
    }

    return $result;
}
