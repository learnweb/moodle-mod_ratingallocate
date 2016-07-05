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
define('ratingallocate_MOD_NAME', 'ratingallocate');
// define('NEWMODULE_ULTIMATE_ANSWER', 42);

require_once(dirname(__FILE__).'/db/db_structure.php');
use ratingallocate\db as this_db;

// //////////////////////////////////////////////////////////////////////////////
// Moodle core API //
// //////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature
 *        	FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function ratingallocate_supports($feature) {
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
 *        	An object from the form in mod_form.php
 * @param mod_ratingallocate_mod_form $mform
 * @return int The id of the newly inserted ratingallocate record
 */
function ratingallocate_add_instance(stdClass $ratingallocate, mod_ratingallocate_mod_form $mform = null) {
    global $DB, $COURSE;

    $ratingallocate->timecreated = time();

    $transaction = $DB->start_delegated_transaction();
    try {
        $ratingallocate->{this_db\ratingallocate::SETTING} = json_encode($ratingallocate->strategyopt);
        // instanz einfuegen, damit wir die ID fuer die Kinder haben
        $id = $DB->insert_record(this_db\ratingallocate::TABLE, $ratingallocate);

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
 *        	An object from the form in mod_form.php
 * @param mod_ratingallocate_mod_form $mform
 * @return boolean Success/Fail
 */
function ratingallocate_update_instance(stdClass $ratingallocate, mod_ratingallocate_mod_form $mform = null) {
    /* @var $DB moodle_database */
    global $DB;

    $ratingallocate->timemodified = time();
    $ratingallocate->id = $ratingallocate->instance;

    try {
        $transaction = $DB->start_delegated_transaction();

        // serialize strategy settings
        $ratingallocate->setting = json_encode($ratingallocate->strategyopt);

        $bool = $DB->update_record('ratingallocate', $ratingallocate);

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
 *        	Id of the module instance
 * @return boolean Success/Failure
 */
function ratingallocate_delete_instance($id) {
    global $DB;

    if (!$ratingallocate = $DB->get_record('ratingallocate', array(
        'id' => $id
            ))) {
        return false;
    }

    // Delete any dependent records here #
    $DB->delete_records('ratingallocate_allocations', array(
        'ratingallocateid' => $ratingallocate->id
    ));

    $deleteids = $DB->get_records('ratingallocate_choices', array(
        'ratingallocateid' => $ratingallocate->id
            ), '', 'id');

    $DB->delete_records_list('ratingallocate_ratings', 'choiceid', array_keys($deleteids));

    $DB->delete_records('ratingallocate_choices', array(
        'ratingallocateid' => $ratingallocate->id
    ));

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
    return false; // True if anything was printed, otherwise false
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
 * @package mod_ratingallocate
 * @category files
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
 */
function ratingallocate_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the ratingallocate file areas
 *
 * @package mod_ratingallocate
 * @category files
 *
 * @param stdClass $course
 *        	the course object
 * @param stdClass $cm
 *        	the course module object
 * @param stdClass $context
 *        	the ratingallocate's context
 * @param string $filearea
 *        	the name of the file area
 * @param array $args
 *        	extra arguments (itemid, path)
 * @param bool $forcedownload
 *        	whether or not force download
 * @param array $options
 *        	additional options affecting the file serving
 */
function ratingallocate_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
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
 *        	An object representing the navigation tree node of the ratingallocate module instance
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
 *        	{@link settings_navigation}
 * @param navigation_node $ratingallocatenode
 *        	{@link navigation_node}
 */
function ratingallocate_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $ratingallocatenode = null) {

}
