<?php
use ratingallocate\db as db;
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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/form_manual_allocation.php');
require_once(dirname(__FILE__) . '/renderable.php');
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

define('RATING_ALLOC_ACTION_RATE', 'rate');
define('RATING_ALLOC_ACTION_START', 'start_distribution');
define('ACTION_ALLOCATE_SHOW_MANUALFORM', 'ACTION_ALLOCATE_SHOW_MANUALFORM');
define('ACTION_ALLOCATE_MANUAL_SAVE', 'allocate_manual_save');
define('ACTION_PUBLISH_ALLOCATIONS', 'publish_allocations'); // make them displayable for the users
define('ACTION_SOLVE_LP_SOLVE', 'solve_lp_solve'); // instead of only generating the mps-file, let it solve
define('RATING_ALLOC_SHOW_TABLE', 'show_table');

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

    /**
     * This is what the view.php calls to make the output
     */
    public function handle_view() {
        global $PAGE, $OUTPUT, $USER;
        $action = optional_param('action', '', PARAM_TEXT);
        /* if ($action=='populate_test') {
          $this->addtestdata();
          } */

        // add_to_log($this->course->id, 'ratingallocate', 'view', "view.php?id={$this->coursemodule->id}", $this->ratingallocate->name, $this->coursemodule->id);
        // Print the page header

        $PAGE->set_cacheable(false); //TODO necessary

        // Process form: Start distribution and redirect after finishing
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            // Start the distribution algorithm
            if ($action == RATING_ALLOC_ACTION_START) {
                require_capability('mod/ratingallocate:start_distribution', $this->context);

                $distributor = new solver_edmonds_karp();
                // $distributor = new solver_ford_fulkerson();
                $timestart = microtime(true);
                $distributor->distribute_users($this);
                // echo memory_get_peak_usage();
                redirect($PAGE->url->out(), get_string('distribution_saved', ratingallocate_MOD_NAME, (microtime(true) - $timestart)));
            }
        }

        // Output starts here
        $output = '';

        /* @var $renderer mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();

        $choice_status = new ratingallocate_choice_status();
        $choice_status->view_type = ratingallocate_choice_status::STUDENT_VIEW;
        $choice_status->accesstimestart = $this->ratingallocate->accesstimestart;
        $choice_status->accesstimestop = $this->ratingallocate->accesstimestop;
        $choice_status->publishdate = $this->ratingallocate->publishdate;
        $choice_status->is_published = $this->ratingallocate->published;
        $choice_status->available_choices = $this->get_rateable_choices();
        $choice_status->own_choices = array_filter($this->get_rating_data_for_user($USER->id),function($elem) {return !empty($elem->rating);});
        $output .= $renderer->render($choice_status);

        // Get current time
        $now = time();

        // Print data and controls for students, but not for admins
        if (has_capability('mod/ratingallocate:give_rating', $this->context, null, false)) {
            global $DB;
            // if no choice option exists WARN!
            if (!$DB->record_exists('ratingallocate_choices', array('ratingallocateid' => $this->ratingallocateid))) {
                $output .= $renderer->notification(get_string('no_choice_to_rate', ratingallocate_MOD_NAME));
            }
            // rating possible
            else if ($this->ratingallocate->accesstimestart < $now && $this->ratingallocate->accesstimestop > $now) {
                // suche das richtige Formular nach Strategie
                /* @var $strategyform ratingallocate_viewform */
                $strategyform = 'ratingallocate\\' . $this->ratingallocate->strategy . '\\mod_ratingallocate_view_form';
                /* @var $mform ratingallocate_strategyform */
                $mform = new $strategyform($PAGE->url->out(), $this);

                // save submitted data and redirect
                if ($mform->is_validated() && !$mform->is_cancelled() && $data = $mform->get_data()) {
                    if ($action === RATING_ALLOC_ACTION_RATE) {
                        $this->save_ratings_to_db($USER->id, $data->data);
                        redirect($PAGE->url->out(), get_string('ratings_saved', ratingallocate_MOD_NAME));
                    }
                }

                $output .= $renderer->render_ratingallocate_strategyform($mform);
            }
        }

        if (has_capability('mod/ratingallocate:start_distribution', $this->context) && ($action == ACTION_ALLOCATE_SHOW_MANUALFORM || $action == ACTION_ALLOCATE_MANUAL_SAVE)) {
            $mform = new manual_alloc_form($PAGE->url->out(), $this);

            if (!$mform->is_cancelled() && $data = $mform->get_data()) {
                if ($action == ACTION_ALLOCATE_MANUAL_SAVE) {
                    $this->save_manual_allocation_form($data);
                    $output .= $OUTPUT->box(get_string('manual_allocation_saved', ratingallocate_MOD_NAME));
                }
            } else {
                $output .= $OUTPUT->heading(get_string('manual_allocation', ratingallocate_MOD_NAME), 2);
                $output .= $OUTPUT->box('<p>' . get_string('allocation_manual_explain', ratingallocate_MOD_NAME) . '</p>');

                $output .= $mform->to_html();
            }
        }

        // Print data and controls for teachers
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            // Notify if there aren't at least two rateable groups
            if (count($this->get_rateable_choices()) < 1) {
                $output .= $renderer->notification(get_string('at_least_one_rateable_choices_needed', ratingallocate_MOD_NAME));
            }

            // Print group distribution algorithm control
            if ($this->ratingallocate->accesstimestop < $now) {
                $output .= $renderer->algorithm_control_ready();
            } else {
                $output .= $renderer->algorithm_control_tooearly();
            }

            // Print distribution table
            if ($this->ratingallocate->accesstimestop < $now) {
                $output .= $renderer->distribution_table_for_ratingallocate($this);

                $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $this->coursemodule->id,
                    'ratingallocateid' => $this->ratingallocateid,
                    'action' => ACTION_ALLOCATE_SHOW_MANUALFORM)), get_string('manual_allocation_form', ratingallocate_MOD_NAME));

                // if results not published yet, then do now
                if ($this->ratingallocate->published == false) {
                    $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $this->coursemodule->id,
                        'ratingallocateid' => $this->ratingallocateid,
                        'action' => ACTION_PUBLISH_ALLOCATIONS)), get_string('publish_allocation', ratingallocate_MOD_NAME));
                }
                if ($action == ACTION_PUBLISH_ALLOCATIONS) {
                    $this->publish_allocation();
                    $output .= $OUTPUT->notification( get_string('distribution_published', ratingallocate_MOD_NAME), 'notifysuccess');
                }
            }

            // Print ratings table
            if ($action == RATING_ALLOC_SHOW_TABLE) {
                $output .= $renderer->ratings_table_for_ratingallocate($this->get_rateable_choices(),
                        $this->get_ratings_for_rateable_choices(), $this->get_raters_in_course(), $this->get_all_allocations());
            } else {
                $output .= $renderer->show_ratings_table_button();
            }

            $output .= $OUTPUT->heading(get_string('export_options', ratingallocate_MOD_NAME), 2);
            $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/export_ratings_csv.php', array('id' => $this->coursemodule->id,
                'ratingallocateid' => $this->ratingallocate->id)), get_string('download_votetest_allocation', ratingallocate_MOD_NAME));
            $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/solver/export_lp_solve.php', array('id' => $this->coursemodule->id,
                'ratingallocateid' => $this->ratingallocate->id)), get_string('download_problem_mps_format', ratingallocate_MOD_NAME));
        }

        // Finish the page
        $header_info = new ratingallocate_header($this->ratingallocate, $this->context, true,
                $this->coursemodule->id);
        $header = $this->get_renderer()->render($header_info);
        $footer = $this->get_renderer()->render_footer();
        return $header . $output . $footer;
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
        $sql = 'SELECT *
			FROM mdl_ratingallocate_choices AS c
			LEFT JOIN (
				SELECT choiceid, count( userid ) usercount
				FROM {ratingallocate_allocations}
				WHERE ratingallocateid =:ratingallocateid1
				GROUP BY choiceid
			) AS al ON c.id = al.choiceid
			WHERE c.ratingallocateid =:ratingallocateid and c.active = 1';

        $choices = $this->db->get_records_sql($sql, array(
            'ratingallocateid' => $this->ratingallocateid,
            'ratingallocateid1' => $this->ratingallocateid
        ));
        return $choices;
    }

    /**
     * Returns all group memberships from users who can give ratings,
     * for rateable groups in the course with id $courseid.
     * Also contains the rating the user gave for that group or null if he gave none.
     * *Known Limitation* Does only return 1 Allocation only
     *
     * @return array of the form array($userid => array($groupid => $rating, ...), ...)
     *         i.e. for every user who is a member of at least one rateable group,
     *         the array contains a set of ids representing the groups the user is a member of
     *         and possibly the respective rating.
     */
    public function get_all_allocations() {
        $query = 'SELECT al.id, al.userid, al.choiceid, r.rating
                FROM {ratingallocate_allocations} al
           LEFT JOIN {ratingallocate_choices} c
                  ON al.choiceid = c.id
           LEFT JOIN {ratingallocate_ratings} r
                  ON al.choiceid = r.choiceid AND al.userid = r.userid
               WHERE al.ratingallocateid = :ratingallocateid AND c.active = 1';
        $records = $this->db->get_records_sql($query, array(
            'ratingallocateid' => $this->ratingallocateid
        ));
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
     */
    public function publish_allocation() {
        $this->origdbrecord->published = true;
        $this->ratingallocate = new ratingallocate_db_wrapper($this->origdbrecord);
        $this->db->update_record('ratingallocate', $this->origdbrecord);
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
                    $rating->id = $oldrating->id;
                    $DB->update_record('ratingallocate_ratings', $rating);
                } else {
                    // Create a new rating in the table

                    $rating->userid = $userid;
                    $rating->choiceid = $rdata ['choiceid'];
                    $rating->ratingallocateid = $this->ratingallocateid;
                    $DB->insert_record('ratingallocate_ratings', $rating);
                }
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Returns all choices in the instance with $ratingallocateid
     */
    public function get_rateable_choices() {
        $sql = 'SELECT *
            FROM {ratingallocate_choices} c
            WHERE c.ratingallocateid = :ratingallocateid AND c.active = 1
            ORDER by c.title';
        return $this->db->get_records_sql($sql, array(
                    'ratingallocateid' => $this->ratingallocateid
        ));
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

            $allusers = $this->get_raters_in_course();
            $allchoices = $this->get_rateable_choices();

            $allocdata = $data->data;
            foreach ($allocdata as $id => $choiceallocationid) {
                // Is this user in this course?
                if (key_exists($id, $allusers) && key_exists($choiceallocationid['assign'], $allchoices)) {
                    // Create new allocation
                    $this->add_allocation($choiceallocationid['assign'], $id, $this->ratingallocateid);
                }
            }
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
}
