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
 * Internal library of functions for module ratingallocate
 *
 * All the ratingallocate specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package mod_ratingallocate
 * @copyright 2014 M Schulze
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
use ratingallocate\db as this_db;
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir  . '/eventslib.php');
require_once(dirname(__FILE__) . '/form_manual_allocation.php');
require_once(dirname(__FILE__) . '/renderable.php');
require_once($CFG->dirroot.'/group/lib.php');

// Takes care of loading all the solvers
require_once(dirname(__FILE__) . '/solver/ford-fulkerson-koegel.php');
require_once(dirname(__FILE__) . '/solver/edmonds-karp.php');

// now come all the strategies
require_once(dirname(__FILE__) . '/strategy/strategy01_yes_no.php');
require_once(dirname(__FILE__) . '/strategy/strategy02_yes_maybe_no.php');
require_once(dirname(__FILE__) . '/strategy/strategy03_lickert.php');
require_once(dirname(__FILE__) . '/strategy/strategy04_points.php');
require_once(dirname(__FILE__) . '/strategy/strategy05_order.php');
require_once(dirname(__FILE__) . '/strategy/strategy06_tickyes.php');

/**
 * Simulate a static/singleton class that holds all the strategies that registered with him
 */
class strategymanager {

    /** @var array of string-identifier of all registered strategies  */
    private static $strategies = array();

    /**
     * Add a strategy to the strategymanager
     * @param string $strategyname
     */
    public static function add_strategy($strategyname) {
        self::$strategies[] = $strategyname;
    }

    /**
     * Get the current list of strategies
     * @return array
     */
    public static function get_strategies() {
        return self::$strategies;
    }

}

define('ACTION_GIVE_RATING', 'give_rating');
define('ACTION_START_DISTRIBUTION', 'start_distribution');
define('ACTION_MANUAL_ALLOCATION', 'manual_allocation');
define('ACTION_PUBLISH_ALLOCATIONS', 'publish_allocations'); // make them displayable for the users
define('ACTION_SOLVE_LP_SOLVE', 'solve_lp_solve'); // instead of only generating the mps-file, let it solve
define('ACTION_SHOW_ALLOC_TABLE', 'show_alloc_table');
define('ACTION_SHOW_STATISTICS', 'show_statistics');
define('ACTION_ALLOCATION_TO_GROUPING', 'allocation_to_gropuping');

/**
 * Wrapper for db-record to have IDE autocomplete feature of fields
 * @property int $id
 * @property int $course
 * @property string $name
 * @property string $intro
 * @property string $strategy
 * @property int $accesstimestart
 * @property int $accesstimestop
 * @property int $publishdate
 * @property int $published
 * @property string $setting
 */
class ratingallocate_db_wrapper {
    /** @var dbrecord original db record */
    public $dbrecord;

    /** Emulates the functionality as if there were explicit records by passing them to the original db record
     * 
     * @param string $name
     * @return type
     */
    public function __get($name) {
            return $this->dbrecord->{$name};
    }

    public function __construct($record) {
        $this->dbrecord = $record;
    }

}

/**
 * Kapselt eine Instanz von ratingallocate
 *
 * @author max
 *        
 */
class ratingallocate {

    /** @var int */
    private $ratingallocateid;

    /** @var ratingallocate_db_wrapper */
    public $ratingallocate;

    /** @var db_record original db_record of this instance */
    private $origdbrecord;

    /** @var int  */
    private $course;

    /** @var int */
    private $coursemodule;

    /** @var context_module */
    private $context;

    /** @var $_db moodle_database */
    public $db; // public because solvers need it, too

    /**
     * @var mod_ratingallocate_renderer the custom renderer for this module
     */
    protected $renderer;
    
    const NOTIFY_SUCCESS = 'notifysuccess';

    /**
     * Returns all users enrolled in the course the ratingallocate is in
     */
    public function get_raters_in_course() {
        $raters = get_enrolled_users($this->context, 'mod/ratingallocate:give_rating');
        return $raters;
    }

    public function __construct($ratingallocaterecord, $course, $coursem, context_module $context) {
        global $DB;
        $this->db = & $DB;

        $this->origdbrecord = $ratingallocaterecord;
        $this->ratingallocate = new ratingallocate_db_wrapper($ratingallocaterecord);
        $this->ratingallocateid = $this->ratingallocate->id;
        $this->course = $course;
        $this->coursemodule = $coursem;
        $this->context = $context;
    }

    private function process_action_start_distribution(){
        // Process form: Start distribution and call default page after finishing
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            global $PAGE;
            // try to get some more memory, 500 users in 10 groups take about 15mb
            raise_memory_limit(MEMORY_EXTRA);
            set_time_limit(120);
            //distribute choices
            $time_needed = $this->distrubute_choices();
    
            //Logging
            $event = \mod_ratingallocate\event\distribution_triggered::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid, $this->get_allocations_for_logging(), $time_needed);
            $event->trigger();
            
            /* @var $renderer mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();
            $renderer->add_notification(get_string('distribution_saved', ratingallocate_MOD_NAME, $time_needed), self::NOTIFY_SUCCESS);
            return $this->process_default();
        }
    }
    
    private function process_action_give_rating() {
        global $CFG;

        // Get current time.
        $now = time();
        $output = '';
        /* @var $renderer mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();
        // Print data and controls for students, but not for admins.
        if (has_capability('mod/ratingallocate:give_rating', $this->context, null, false)) {
            global $DB, $PAGE, $USER;
            // If no choice option exists: WARN!
            if (!$DB->record_exists('ratingallocate_choices', array('ratingallocateid' => $this->ratingallocateid))) {
                $renderer->add_notification(get_string('no_choice_to_rate', ratingallocate_MOD_NAME));
            } else if ($this->ratingallocate->accesstimestart < $now && $this->ratingallocate->accesstimestop > $now) {
                // Rating possible.
                // suche das richtige Formular nach Strategie
                /* @var $strategyform ratingallocate_viewform */
                $strategyform = 'ratingallocate\\' . $this->ratingallocate->strategy . '\\mod_ratingallocate_view_form';
                /* @var $mform ratingallocate_strategyform */
                $mform = new $strategyform($PAGE->url->out(), $this);
                $mform->add_action_buttons();

                if ( $mform->is_cancelled() ) {
                    // Return to view.
                    redirect("$CFG->wwwroot/mod/ratingallocate/view.php?id=".$this->coursemodule->id);
                    return;
                } else if ($mform->is_submitted() && $mform->is_validated() && $data = $mform->get_data() ) {
                    // Save submitted data and call default page.
                    $this->save_ratings_to_db($USER->id, $data->data);
                    $renderer->add_notification(get_string('ratings_saved', ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);
                    return $this->process_default();
                }

                $mform->definition_after_data();
                $output .= $renderer->render_ratingallocate_strategyform($mform);
                // Logging.
                $event = \mod_ratingallocate\event\rating_viewed::create_simple(
                        context_course::instance($this->course->id), $this->ratingallocateid);
                $event->trigger();
            }
        }
        return $output;
    }
    
    private function process_action_manual_allocation(){
        // Manual allocation
        $output = '';
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            global $OUTPUT,$PAGE;

            $mform = new manual_alloc_form($PAGE->url->out(), $this);
        
            if (!$mform->no_submit_button_pressed() && $data = $mform->get_submitted_data()) {
                if (!$mform->is_cancelled() ) {
                    $this->save_manual_allocation_form($data);
                    /* @var $renderer mod_ratingallocate_renderer */
                    $renderer = $this->get_renderer();
                    $renderer->add_notification(get_string('manual_allocation_saved', ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);
                }
                // If form was submitted using save or cancel, show the default page.
                return $this->process_default();
            } else {
                $output .= $OUTPUT->heading(get_string('manual_allocation', ratingallocate_MOD_NAME), 2);
        
                $output .= $mform->to_html();
            }
        }
        return $output;
    }

    private function process_action_show_alloc_table() {
        $output = '';
        // Print ratings table.
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            global $OUTPUT;
            $renderer = $this->get_renderer();
            $output .= $renderer->ratings_table_for_ratingallocate($this->get_rateable_choices(),
                    $this->get_ratings_for_rateable_choices(), $this->get_raters_in_course(),
                    $this->get_all_allocations(), $this);

            $output .= html_writer::empty_tag('br', array());
            $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php',
                        array('id' => $this->coursemodule->id,
                            'ratingallocateid' => $this->ratingallocateid,
                            'action' => '')), get_string('back'));
            if (has_capability('mod/ratingallocate:export_ratings', $this->context)) {
                $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/export_ratings_csv.php',
                    array('id' => $this->coursemodule->id,
                        'ratingallocateid' => $this->ratingallocate->id)),
                    get_string('download_votetest_allocation', ratingallocate_MOD_NAME));
            }

            // Logging.
            $event = \mod_ratingallocate\event\allocation_table_viewed::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid);
            $event->trigger();
        }
        return $output;
    }

    private function process_action_show_statistics(){
        $output = '';
        // Print ratings table
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            global $OUTPUT;
            $renderer = $this->get_renderer();

            $output .= $renderer->distribution_table_for_ratingallocate($this);

            $output .= html_writer::empty_tag('br', array());
            $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $this->coursemodule->id,
                'ratingallocateid' => $this->ratingallocateid,
                'action' => '')), get_string('back'));
            //Logging
            $event = \mod_ratingallocate\event\allocation_statistics_viewed::create_simple(
                context_course::instance($this->course->id), $this->ratingallocateid);
            $event->trigger();
        }
        return $output;
    }
    
    private function process_publish_allocations(){
        $now = time();
        if ($this->ratingallocate->accesstimestop < $now){
            global $USER,$OUTPUT;
    
            $this->origdbrecord->{this_db\ratingallocate::PUBLISHED}   = true;
            $this->origdbrecord->{this_db\ratingallocate::PUBLISHDATE} = time();
            $this->origdbrecord->{this_db\ratingallocate::NOTIFICATIONSEND} = -1;
            $this->ratingallocate = new ratingallocate_db_wrapper($this->origdbrecord);
            $this->db->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);
            
            // create the instance
            $domination = new mod_ratingallocate\task\send_distribution_notification();
            // set blocking if required (it probably isn't)
            // $domination->set_blocking(true);
            // add custom data
            $domination->set_component('mod_ratingallocate');
            $domination->set_custom_data(array(
                            'userid' => $USER->id, // will be the sending user
                            'ratingallocateid' => $this->ratingallocateid
            ));
            
            // queue it
            \core\task\manager::queue_adhoc_task($domination);
            
            //Logging
            $event = \mod_ratingallocate\event\allocation_published::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid, $this->get_allocations_for_logging());
            $event->trigger();
            
            /* @var $renderer mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();
            $renderer->add_notification( get_string('distribution_published', ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);
            return $this->process_default();
        }
    }
    
    private function process_action_allocation_to_grouping(){
        $now = time();
        if ($this->ratingallocate->accesstimestop < $now){
            global $OUTPUT;
            $allgroupings = groups_get_all_groupings($this->course->id);
            $groupingidname = ratingallocate_MOD_NAME . '_instid_' . $this->ratingallocateid;
            // search if there is already a grouping from us
            $grouping = groups_get_grouping_by_idnumber($this->course->id, $groupingidname);
            $groupingid = null;
            if (!$grouping) {
                // create grouping
                $data = new stdClass();
                $data->name = 'created from ' . $this->ratingallocate->name;
                $data->idnumber = $groupingidname;
                $data->courseid = $this->course->id;
                $groupingid = groups_create_grouping($data);
            } else {
                $groupingid = $grouping->id;
            }
            
            $group_identifier_from_choice_id = function ($choiceid) {
                return ratingallocate_MOD_NAME . '_c_' . $choiceid;
            };
            
            $choices = $this->get_choices_with_allocationcount();
            
            // make a new array containing only the identifiers of the choices
            $choice_identifiers = array();
            foreach ($choices as $id => $choice) {
                $choice_identifiers[$group_identifier_from_choice_id($choice->id)] = array('key' => $id
                );
            }
            
            // find all associated groups in this grouping
            $groups = groups_get_all_groups($this->course->id, 0, $groupingid);
            
            // loop through the groups in the grouping: if the choice does not exist anymore -> delete
            // otherwise mark it
            foreach ($groups as $group) {
                if (array_key_exists($group->idnumber, $choice_identifiers)) {
                    // group exists, mark
                    $choice_identifiers[$group->idnumber]['exists'] = true;
                    $choice_identifiers[$group->idnumber]['groupid'] = $group->id;
                } else {
                    // delete group $group->id
                    groups_delete_group($group->id);
                }
            }
            
            // create groups groups for new identifiers or empty group if it exists
            foreach ($choice_identifiers as $group_idnumber => $choice) {
                if (key_exists('exists', $choice)) {
                    // remove all members
                    groups_delete_group_members_by_group($choice['groupid']);
                } else {
                    $data = new stdClass();
                    $data->courseid = $this->course->id;
                    $data->name = $choices[$choice['key']]->title;
                    $data->idnumber = $group_idnumber;
                    $createdid = groups_create_group($data);
                    groups_assign_grouping($groupingid, $createdid);
                    $choice_identifiers[$group_idnumber]['groupid'] = $createdid;
                }
            }
            
            // add all participants in the correct group
            $allocations = $this->get_allocations();
            foreach ($allocations as $id => $allocation) {
                $choice_id = $allocation->choiceid;
                $user_id = $allocation->userid;
                $choiceidnumber = $group_identifier_from_choice_id($choice_id);
                groups_add_member($choice_identifiers[$choiceidnumber]['groupid'], $user_id);
            }
            // Invalidate the grouping cache for the course
            cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($this->course->id));
            $renderer = $this->get_renderer();
            $renderer->add_notification( get_string('moodlegroups_created', ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);
        }
        return $this->process_default();
    }

    private function process_default(){
        global $OUTPUT;
        $output = '';
        $now = time();
        /* @var $renderer mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();
        if (has_capability('mod/ratingallocate:give_rating', $this->context, null, false)) {
            if ($this->ratingallocate->accesstimestop > $now) {
            $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $this->coursemodule->id,
                            'ratingallocateid' => $this->ratingallocateid,
                            'action' => ACTION_GIVE_RATING)), get_string('edit_rating', ratingallocate_MOD_NAME)); //TODO: Include in choice_status
            }
        }
        
        // Print data and controls for teachers
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            $status = $this->get_status();
            $output .= $renderer->modify_allocation_group($this->ratingallocateid, $this->coursemodule->id, $status);
            $output .= $renderer->publish_allocation_group($this->ratingallocateid, $this->coursemodule->id, $status);
            $output .= $renderer->reports_group($this->ratingallocateid, $this->coursemodule->id, $status, $this->context);
        }
        
        //Logging
        $event = \mod_ratingallocate\event\ratingallocate_viewed::create_simple(
                context_course::instance($this->course->id), $this->ratingallocateid);
        $event->trigger();
        
        return $output;
    }
    
    /**
     * This is what the view.php calls to make the output
     */
    public function handle_view() {
        global $PAGE, $USER;
        $action = optional_param('action', '', PARAM_TEXT);

        $PAGE->set_cacheable(false); //TODO necessary

        // Output starts here
        $output = '';

        /* @var $renderer mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();
        
        switch ($action) {
            case ACTION_START_DISTRIBUTION:
                $output .= $this->process_action_start_distribution();
                break;
            
            case ACTION_GIVE_RATING:
                $output .= $this->process_action_give_rating();
                break;
            
            case ACTION_PUBLISH_ALLOCATIONS:
                $output .= $this->process_publish_allocations();
                break;
            
            case ACTION_ALLOCATION_TO_GROUPING:
                $output .= $this->process_action_allocation_to_grouping();
                break;
            
            case ACTION_MANUAL_ALLOCATION:
                $output .= $this->process_action_manual_allocation();
                break;

            case ACTION_SHOW_ALLOC_TABLE:
                $output .= $this->process_action_show_alloc_table();
                break;

            case ACTION_SHOW_STATISTICS:
                $output .= $this->process_action_show_statistics();
                break;

            default:
                $output .= $this->process_default();
        }        
        
        $choice_status = new ratingallocate_choice_status();
        $choice_status->accesstimestart = $this->ratingallocate->accesstimestart;
        $choice_status->accesstimestop = $this->ratingallocate->accesstimestop;
        $choice_status->publishdate = $this->ratingallocate->publishdate;
        $choice_status->is_published = $this->ratingallocate->published;
        $choice_status->available_choices = $this->get_rateable_choices();
        $choice_status->own_choices = $this->get_rating_data_for_user($USER->id);
        $choice_status->allocations = $this->get_allocations_for_user($USER->id);
        $choice_status->strategy = $this->get_strategy_class();
        $choice_status->show_distribution_info = has_capability('mod/ratingallocate:start_distribution', $this->context);
        $choice_status->show_user_info = has_capability('mod/ratingallocate:give_rating', $this->context, null, false);
        $choice_status_output = $renderer->render($choice_status);
        
        // Finish the page (Since the header renders the notifications, it needs to be rendered after the actions)
        $header_info = new ratingallocate_header($this->ratingallocate, $this->context, true,
                $this->coursemodule->id);
        $header = $this->get_renderer()->render($header_info);
        $footer = $this->get_renderer()->render_footer();        
        return $header . $choice_status_output . $output . $footer;
    }

    /**
     * Returns all ratings for active choices
     */
    public function get_ratings_for_rateable_choices() {
        $sql = 'SELECT r.*
                FROM {ratingallocate_choices} c
                JOIN {ratingallocate_ratings} r
                  ON c.id = r.choiceid
               WHERE c.ratingallocateid = :ratingallocateid AND c.active = 1';

        $ratings = $this->db->get_records_sql($sql, array(
            'ratingallocateid' => $this->ratingallocateid
        ));
        $raters = $this->get_raters_in_course();

        // Filter out everyone who can't give ratings
        $fromraters = array_filter($ratings, function ($rating) use($raters) {
            return array_key_exists($rating->userid, $raters);
        });

        return $fromraters;
    }

    /**
     * distribution of choices for each user
     * take care about max_execution_time and memory_limit
     */
    public function distrubute_choices() {
        require_capability('mod/ratingallocate:start_distribution', $this->context);

        $distributor = new solver_edmonds_karp();
        // $distributor = new solver_ford_fulkerson();
        $timestart = microtime(true);
        $distributor->distribute_users($this);
        $time_needed = (microtime(true) - $timestart);
        // echo memory_get_peak_usage();
        return $time_needed;
    }
    /**
     * Returns all users, that have not been allocated but have given ratings
     *
     * @param unknown $ratingallocateid
     * @return array;
     */
    public function get_ratings_for_rateable_choices_for_raters_without_alloc() {
        $sql = 'SELECT al.*
                FROM {ratingallocate_allocations} al
               WHERE al.ratingallocateid = :ratingallocateid';

        $allocated = $this->db->get_records_sql($sql, array(
            'ratingallocateid' => $this->ratingallocateid
        ));
        $ratings = $this->get_ratings_for_rateable_choices();
        // macht daraus ein Array mit userid => quatsch
        $allocated = array_flip(array_map(function ($entry) {
                    return $entry->userid;
        }, $allocated));

        // Filter out everyone who already has an allocation
        $unallocraters = array_filter($ratings, function ($ratings) use($allocated) {
            return !array_key_exists($ratings->userid, $allocated);
        });

        return $unallocraters;
    }

    /*
     * Returns all active choices with allocation count
     */
    public function get_choices_with_allocationcount() {
        $sql = 'SELECT c.*, al.usercount
            FROM {ratingallocate_choices} AS c
            LEFT JOIN (
                SELECT choiceid, count( userid ) AS usercount
                FROM {ratingallocate_allocations}
                WHERE ratingallocateid =:ratingallocateid1
                GROUP BY choiceid
            ) AS al ON c.id = al.choiceid
            WHERE c.ratingallocateid =:ratingallocateid and c.active = :active';

        $choices = $this->db->get_records_sql($sql, array(
            'ratingallocateid' => $this->ratingallocateid,
            'ratingallocateid1' => $this->ratingallocateid,
            'active' => true,
        ));
        return $choices;
    }

    /**
     * @return all allocation objects that belong this ratingallocate
     */
    public function get_allocations() {
        $query = 'SELECT al.*, r.rating
                FROM {ratingallocate_allocations} al
           LEFT JOIN {ratingallocate_choices} c ON al.choiceid = c.id
           LEFT JOIN {ratingallocate_ratings} r ON al.choiceid = r.choiceid AND al.userid = r.userid
               WHERE al.ratingallocateid = :ratingallocateid AND c.active = 1';
        $records = $this->db->get_records_sql($query, array(
                        'ratingallocateid' => $this->ratingallocateid
        ));
        return $records;
    }
    
    /**
     * @return attributes needed for logging of all allocation objects that belong this ratingallocate
     */
    private function get_allocations_for_logging() {
        $query = 'SELECT al.userid, al.choiceid
                FROM {ratingallocate_allocations} al
                LEFT JOIN {ratingallocate_choices} c ON al.choiceid = c.id
               WHERE al.ratingallocateid = :ratingallocateid AND c.active = 1';
        $records = $this->db->get_records_sql($query, array(
                        'ratingallocateid' => $this->ratingallocateid
        ));
        return $records;
    }

    /**
     * Returns all group memberships from users who can give ratings,
     * for rateable groups in the course with id $courseid.
     * Also contains the rating the user gave for that group or null if he gave none.
     * *Known Limitation* Does only return 1 Allocation only
     * @deprecated
     * @return array of the form array($userid => array($groupid => $rating, ...), ...)
     *         i.e. for every user who is a member of at least one rateable group,
     *         the array contains a set of ids representing the groups the user is a member of
     *         and possibly the respective rating.
     */
    public function get_all_allocations() {
        debugging('get_all_allocations() has been deprecated, please rewrite your code to use get_allocations', DEBUG_DEVELOPER); //TODO
        $records = $this->get_allocations();
        $memberships = array();

        $raters = $this->get_raters_in_course();
        foreach ($records as $r) {

            // Ignore all members who can't give ratings
            if (!array_key_exists($r->userid, $raters)) {
                continue;
            }
            if (!array_key_exists($r->userid, $memberships)) {
                $memberships [$r->userid] = array();
            }
            $memberships [$r->userid] [$r->choiceid] = $r->rating;
        }

        return $memberships;
    }

    /**
     * Removes all allocations for choices in $ratingallocateid
     */
    public function clear_all_allocations() {
        $this->db->delete_records('ratingallocate_allocations', array('ratingallocateid' => intval($this->ratingallocateid)));
    }

    /**
     * Set the published to yes and allow users to see their allocation
     * @deprecated
     */
    public function publish_allocation()
    {
        $this->process_publish_allocations();
    }

    /**
     * Gets called by the adhoc_taskmanager and its task in send_distribution_notification
     * 
     * @param user $userfrom
     */
    public function notify_users_distribution($userfrom) {
        global $CFG;
        $userfrom = get_complete_user_data('id', $userfrom);

        // make sure we have not sent them yet
        if ($this->origdbrecord->{this_db\ratingallocate::NOTIFICATIONSEND} != -1) {
            mtrace('seems we have sent them already');
            return true;
        }

        $choices = $this->get_choices_with_allocationcount();
        $allocations = $this->get_allocations();
        foreach ($allocations as $userid => $allocobj) {
            // get the assigned choice_id
            $alloc_choic_id = $allocobj->choiceid;

            // Prepare the email to be sent to the user
            $userto = get_complete_user_data('id', $allocobj->userid);
            cron_setup_user($userto);

            // prepare Text
            $notiftext = $this->make_mail_text($choices[$alloc_choic_id]);
            $notifhtml = $this->make_mail_html($choices[$alloc_choic_id]);

            $notifsubject = format_string($this->course->shortname, true) . ': ' .
                     get_string('allocation_notification_message_subject', 'ratingallocate',
                     $this->ratingallocate->name);
            // Send the post now!
            if (empty($userto->mailformat) || $userto->mailformat != 1) {
                // This user DOESN'T want to receive HTML
                $notifhtml = '';
            }

            $attachment = $attachname = '';

            $mailresult = email_to_user($userto, $userfrom, $notifsubject, $notiftext, $notifhtml,
                    $attachment, $attachname);
            if (!$mailresult) {
                mtrace(
                        "ERROR: mod/ratingallocate/locallib.php: Could not send out digest mail to user $userto->id " .
                                 "($userto->email)... not trying again.");
            } else {
                mtrace("success.");
            }
        }

        // update the 'notified' flag
        $this->origdbrecord->{this_db\ratingallocate::NOTIFICATIONSEND} = 1;
        $this->ratingallocate = new ratingallocate_db_wrapper($this->origdbrecord);

        $this->db->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);
    }

    /**
     * Builds and returns the body of the email notification in plain text.
     *
     * @return string The email body in plain text format.
     */
    function make_mail_text($choice) {
        global $CFG;
        $notiftext = '';

        $notiftext .= "\n";
        $notiftext .= $CFG->wwwroot.'/mod/ratingallocate/view.php?id='.$this->coursemodule->id;
        $notiftext .= "\n---------------------------------------------------------------------\n";
        $notiftext .= format_string($this->ratingallocate->name,true);

        $notiftext .= "\n---------------------------------------------------------------------\n";
        $notiftext .= get_string('allocation_notification_message', 'ratingallocate', array('ratingallocate'=>$this->ratingallocate->name, 'choice' => $choice->title));
        $notiftext .= "\n\n";

        return $notiftext;
    }
    /**
     * Builds and returns the body of the email notification in html
     *
     * @return string The email body in html format.
     */
    function make_mail_html($choice) {
        global $CFG;

        $shortname = format_string($this->course->shortname, true, array('context' => context_course::instance($this->course->id)));

        $notifhtml = '<head>';
        $notifhtml .= '</head>';
        $notifhtml .= "\n<body id=\"email\">\n\n";

        $notifhtml .= '<div class="navbar">'.
                '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.'">'.$shortname.'</a> &raquo; '.
                '<a target="_blank" href="'.$CFG->wwwroot.'/mod/ratingallocate/view.php?id='.$this->coursemodule->id.'">'.format_string($this->ratingallocate->name,true).'</a>';
        $notifhtml .= '</div><hr />';
        // format the post body
        $options = new stdClass();
        $options->para = true;
        $notifhtml .= format_text(get_string('allocation_notification_message', 'ratingallocate', array('ratingallocate'=>$this->ratingallocate->name, 'choice' => $choice->title)),FORMAT_HTML,$options,$this->course->id);

        $notifhtml .= '<hr />';
        $notifhtml .= '</body>';

        return $notifhtml;
    }

    /**
     * create a moodle grouping with the name of the ratingallocate instance
     * and create groups according to the distribution. Groups are identified 
     * by their idnumber. If a group exists, all users are removed.
     * @deprecated
     */
    public function create_moodle_groups() {
           $this->process_action_allocation_to_grouping();
    }

    /**
     * Returns all ratings from the user with id $userid 
     * @param int $userid
     * @return multitype:
     */
    public function get_rating_data_for_user($userid) {
        $sql = "SELECT c.id as choiceid, c.title, c.explanation, c.ratingallocateid, r.rating, r.id AS ratingid, r.userid
                FROM {ratingallocate_choices} c
           LEFT JOIN {ratingallocate_ratings} r
                  ON c.id = r.choiceid and r.userid = :userid
               WHERE c.ratingallocateid = :ratingallocateid AND c.active = 1
               ORDER by c.title";
        return $this->db->get_records_sql($sql, array(
                    'ratingallocateid' => $this->ratingallocateid,
                    'userid' => $userid
        ));
    }

    /**
     * Save all the users rating to db
     * @param int $userid
     * @param array $data
     */
    public function save_ratings_to_db($userid, array $data) {
        /* @var $DB moodle_database */
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $loggingdata = array();
        try {
            foreach ($data as $id => $rdata) {
                $rating = new stdClass ();
                $rating->rating = $rdata ['rating'];

                $ratingexists = array(
                    'choiceid' => $rdata ['choiceid'],
                    'userid' => $userid
                );
                if ($DB->record_exists('ratingallocate_ratings', $ratingexists)) {
                    // The rating exists, we need to update its value
                    // We get the id from the database
 
                    $oldrating = $DB->get_record('ratingallocate_ratings', $ratingexists);
                    if ($oldrating->{this_db\ratingallocate_ratings::RATING}!=$rating->rating){
                        $rating->id = $oldrating->id;
                        $DB->update_record('ratingallocate_ratings', $rating);
                        
                        //Logging
                        array_push($loggingdata,array('choiceid' => $oldrating->choiceid, 'rating' => $rating->rating));
                    }
                } else {
                    // Create a new rating in the table

                    $rating->userid = $userid;
                    $rating->choiceid = $rdata ['choiceid'];
                    $rating->ratingallocateid = $this->ratingallocateid;
                    $DB->insert_record('ratingallocate_ratings', $rating);
                    
                    //Logging
                    array_push($loggingdata,array('choiceid' => $rating->choiceid, 'rating' => $rating->rating));
                }
                $completion = new completion_info($this->course);
                $completion->set_module_viewed($this->coursemodule);
            }
            $transaction->allow_commit();
            //Logging
            $event = \mod_ratingallocate\event\rating_saved::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid, $loggingdata);
            $event->trigger();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Returns all choices in the instance with $ratingallocateid
     */
    public function get_rateable_choices() {
        global $DB;
        return $DB->get_records(this_db\ratingallocate_choices::TABLE,
            array(this_db\ratingallocate_choices::RATINGALLOCATEID => $this->ratingallocateid,
                  this_db\ratingallocate_choices::ACTIVE => true,
            ),this_db\ratingallocate_choices::TITLE);
    }

    /**
     * Returns all memberships of a user for rateable choices in this instance of ratingallocate
     */
    public function get_allocations_for_user($userid) {
        $sql = 'SELECT m.id AS ratingallocateid, c.title, c.explanation, al.choiceid
            FROM {ratingallocate} m
            JOIN {ratingallocate_allocations} al
            ON m.id = al.ratingallocateid
            JOIN {ratingallocate_choices} c
            ON al.choiceid = c.id
            WHERE al.ratingallocateid = :ratingallocateid
            AND al.userid = :userid';

        return $this->db->get_records_sql($sql, array(
                    'ratingallocateid' => $this->ratingallocateid,
                    'userid' => $userid
        ));
    }

    /**
     * Adds the manual allocation to db. Does not perform checks if there is already an allocation user-choice
     * @global type $DB
     * @param type $data
     */
    public function save_manual_allocation_form($data) {
        try {
            $transaction = $this->db->start_delegated_transaction();
            $loggingdata = array();

            $allusers = $this->get_raters_in_course();
            $allchoices = $this->get_rateable_choices();

            $allocdata = $data->data;
            foreach ($allocdata as $id => $choiceallocationid) {
                // Is this user in this course?
                if (key_exists($id, $allusers) && key_exists($choiceallocationid[manual_alloc_form::ASSIGN], $allchoices)) {
                    $existing_allocations = $this->get_allocations_for_user($id);
                    $existing_allocation = array_pop($existing_allocations);
                    if (empty($existing_allocation)) {
                        // Create new allocation
                        $this->add_allocation($choiceallocationid[manual_alloc_form::ASSIGN], $id);
                        // Logging
                        array_push($loggingdata, array('userid' => $id,'choiceid' => $choiceallocationid[manual_alloc_form::ASSIGN]));
                    } else {
                        if ($existing_allocation->{this_db\ratingallocate_allocations::CHOICEID}!=$choiceallocationid[manual_alloc_form::ASSIGN]){
                        // Alter existing allocation
                        $this->alter_allocation(
                            $existing_allocation->{this_db\ratingallocate_allocations::CHOICEID}, 
                            $choiceallocationid[manual_alloc_form::ASSIGN], $id);
                        array_push($loggingdata, array('userid' => $id,'choiceid' => $choiceallocationid[manual_alloc_form::ASSIGN]));
                        }
                    }
                }
            }
            //Logging
            $event = \mod_ratingallocate\event\manual_allocation_saved::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid, $loggingdata);
            $event->trigger();
            
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * remove an allocation between choiceid and userid
     * @param int $choiceid
     * @param int $userid
     * @return boolean
     */
    public function remove_allocation($choiceid, $userid) {
        $this->db->delete_records('ratingallocate_allocations', array(
            'choiceid' => $choiceid,
            'userid' => $userid
        ));
        return true;
    }

    /**
     * add an allocation between choiceid and userid
     * @param type $choiceid
     * @param type $userid
     * @param type $ratingallocateid
     * @return boolean
     */
    public function add_allocation($choiceid, $userid) {
        $this->db->insert_record_raw('ratingallocate_allocations', array(
            'choiceid' => $choiceid,
            'userid' => $userid,
            'ratingallocateid' => $this->ratingallocateid
        ));
        return true;
    }
    
    /**
     * alter an allocation between old_choiceid and userid
     * @param int $old_choiceid
     * @param int $new_choiceid
     * @param int $userid
     * @return boolean
     */
    public function alter_allocation($old_choiceid, $new_choiceid, $userid) {
        $this->db->set_field(this_db\ratingallocate_allocations::TABLE, this_db\ratingallocate_allocations::CHOICEID, $new_choiceid, array(
                        'choiceid' => $old_choiceid,
                        'userid' => $userid
        ));
        return true;
    }

    /**
     * internal helper to populate the real db with random data, currently disabled
     */
    public function addtestdata() {
        return true; // delete this if you really want to call this function!
        $transaction = $this->db->start_delegated_transaction();
        for ($i = 2; $i < 502; $i++) { // set right user id's!
            for ($c = 1; $c <= 21; $c++) {
                $ratingi = rand(1, 5);
                if ($ratingi > 0) {
                    $rating = new stdclass();
                    $rating->userid = $i;
                    $rating->choiceid = $c;
                    $rating->rating = $ratingi;
                    $this->db->insert_record('ratingallocate_ratings', $rating);
                }
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Lazy load the page renderer and expose the renderer to plugin.
     *
     * @return mod_rlatingallocate_renderer
     */
    public function get_renderer() {
        global $PAGE;
        if ($this->renderer ) {
            return $this->renderer;
        }
        $this->renderer = $PAGE->get_renderer('mod_ratingallocate');
        return $this->renderer;
    }
    
    /**
     * Adds static elements to the radioarray to make use of css for formatting
     * @param unknown $radioarray
     * @param unknown $mform
     * @return enriched radioarray
     */
    public function prepare_horizontal_radio_choice($radioarray, $mform){
        $result = array();
        // add static elements to provide a list with choices annotated with css classes
        $result [] =& $mform->createElement('static', 'li', null, '<ul class="horizontal choices">');
        foreach ($radioarray as $id => $radio) {
            $result [] =& $mform->createElement('static', 'static' . $id, null, '<li class="option">');
            $result [] = $radio;
            $result [] =& $mform->createElement('static', 'static' . $id, null, '</li>');
        }
        $result [] =& $mform->createElement('static', 'static' , null, '</ul>');
        
        return $result;
    }
    
    /**
     * Return a set of option titles for the given array of rating values
     * @param array $ratings
     */
    public function get_options_titles(array $ratings){
        return $this->get_strategy_class()->translate_ratings_to_titles($ratings);
    }
    
    /**
     * Returns the strategy class for the ratingallocate
     */
    private function get_strategy_class(){
        $strategyclassp = 'ratingallocate\\' . $this->ratingallocate->strategy . '\\strategy';
        $allsettings = json_decode($this->ratingallocate->setting,true);
        if (array_key_exists($this->ratingallocate->strategy, $allsettings)){
            return new $strategyclassp($allsettings[$this->ratingallocate->strategy]);
        } else {
            return new $strategyclassp();
        }
    }

    const DISTRIBUTION_STATUS_TOO_EARLY = 'too_early';
    const DISTRIBUTION_STATUS_READY = 'ready';
    const DISTRIBUTION_STATUS_READY_ALLOC_STARTED = 'ready_alloc_started';
    const DISTRIBUTION_STATUS_PUBLISHED = 'published';

    private function get_status(){
        $now = time();
        if ($this->ratingallocate->accesstimestop < $now) {
            if ($this->ratingallocate->published == false) {
                if (count($this->get_allocations()) > 0) {
                    return self::DISTRIBUTION_STATUS_READY_ALLOC_STARTED;
                }
                return self::DISTRIBUTION_STATUS_READY;
            } else {
                return self::DISTRIBUTION_STATUS_PUBLISHED;
            }
        } else {
            return self::DISTRIBUTION_STATUS_TOO_EARLY;
        }
    }
    
}

/**
 * Remove all users (or one user) from one group, invented by MxS by copying from group/lib.php
 * because it didn't exist there
 *
 * @param int $courseid
 * @return bool success
 */
function groups_delete_group_members_by_group($groupid) {
    global $DB, $OUTPUT;
    
    if (is_bool($groupid)) {
        debugging('Incorrect groupid function parameter');
        return false;
    }
    
    // Select * so that the function groups_remove_member() gets the whole record.
    $groups = $DB->get_recordset('groups', array('id' => $groupid));
    
    foreach ($groups as $group) {
        $userids = $DB->get_fieldset_select('groups_members', 'userid', 'groupid = :groupid', 
            array('groupid' => $group->id));

        // very ugly hack because some group-management functions are not provided in lib/grouplib.php
        // but does not add too much overhead since it does not include more files...
        require_once (dirname(dirname(dirname(__FILE__))) . '/group/lib.php');
        foreach ($userids as $id) {
            groups_remove_member($group, $id);
        }
    }
    return true;
}
