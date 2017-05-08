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

global $CFG;

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir  . '/eventslib.php');
require_once(dirname(__FILE__) . '/form_manual_allocation.php');
require_once(dirname(__FILE__) . '/form_modify_choice.php');
require_once(dirname(__FILE__) . '/renderable.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once(__DIR__.'/classes/algorithm_status.php');

// Takes care of loading all the solvers.
require_once(dirname(__FILE__) . '/solver/ford-fulkerson-koegel.php');
require_once(dirname(__FILE__) . '/solver/edmonds-karp.php');

// Now come all the strategies.
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
define('ACTION_DELETE_RATING', 'delete_rating');
define('ACTION_SHOW_CHOICES', 'show_choices');
define('ACTION_EDIT_CHOICE', 'edit_choice');
define('ACTION_ENABLE_CHOICE', 'enable_choice');
define('ACTION_DISABLE_CHOICE', 'disable_choice');
define('ACTION_DELETE_CHOICE', 'delete_choice');
define('ACTION_START_DISTRIBUTION', 'start_distribution');
define('ACTION_MANUAL_ALLOCATION', 'manual_allocation');
define('ACTION_PUBLISH_ALLOCATIONS', 'publish_allocations'); // Make them displayable for the users.
define('ACTION_SOLVE_LP_SOLVE', 'solve_lp_solve'); // Instead of only generating the mps-file, let it solve.
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
 * @property int $notificationsend
 * @property int $runalgorithmbycron
 * @property int $algorithmstarttime
 * @property int $algorithmstatus
 * -1 failure while running algorithm;
 * 0 algorithm has not been running;
 * 1 algorithm running;
 * 2 algorithm finished;
 * @property string $setting
 */
class ratingallocate_db_wrapper {

    /** @var stdClass */
    public $dbrecord;

    /** Emulates the functionality as if there were explicit records by passing them to the original db record
     *
     * @param string $name
     * @return mixed
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

    /** @var stdClass original db_record of this instance */
    private $origdbrecord;

    /** @var stdClass */
    private $course;

    /** @var stdClass */
    private $coursemodule;

    /** @var context_module */
    private $context;

    /** @var $db moodle_database */
    public $db; // Public because solvers need it, too.

    /**
     * @var mod_ratingallocate_renderer the custom renderer for this module
     */
    protected $renderer;

    const NOTIFY_SUCCESS = 'notifysuccess';
    const NOTIFY_MESSAGE = 'notifymessage';

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
     * @return string
     * @throws coding_exception
     */
    private function process_action_start_distribution() {
        global $DB;
        // Process form: Start distribution and call default page after finishing.
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            /* @var mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();
            if ($this->get_algorithm_status() === \mod_ratingallocate\algorithm_status::running) {
                // Don't run, if an instance is already running.
                $renderer->add_notification(get_string('algorithm_already_running', ratingallocate_MOD_NAME));
            } else if ($this->ratingallocate->runalgorithmbycron === "1" &&
                $this->get_algorithm_status() === \mod_ratingallocate\algorithm_status::notstarted
            ) {
                // Don't run, if the cron has not started yet, but is set as priority.
                $renderer->add_notification(get_string('algorithm_scheduled_for_cron', ratingallocate_MOD_NAME));
            } else {
                $this->origdbrecord->{this_db\ratingallocate::ALGORITHMSTATUS} = \mod_ratingallocate\algorithm_status::running;
                $DB->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);
                // Try to get some more memory, 500 users in 10 groups take about 15mb.
                raise_memory_limit(MEMORY_EXTRA);
                core_php_time_limit::raise();
                // Distribute choices.
                $timeneeded = $this->distrubute_choices();

                // Logging.
                $event = \mod_ratingallocate\event\distribution_triggered::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid, $timeneeded);
                $event->trigger();

                $renderer->add_notification(get_string('distribution_saved', ratingallocate_MOD_NAME, $timeneeded),
                    self::NOTIFY_SUCCESS);
            }
        }
        return $this->process_default();
    }

    private function process_action_give_rating() {
        global $CFG;

        $output = '';
        /* @var mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();
        // Print data and controls for students, but not for admins.
        if (has_capability('mod/ratingallocate:give_rating', $this->context, null, false)) {
            global $DB, $PAGE, $USER;

            $status = $this->get_status();
            // If no choice option exists WARN!
            if (!$DB->record_exists('ratingallocate_choices', array('ratingallocateid' => $this->ratingallocateid))) {
                $renderer->add_notification(get_string('no_choice_to_rate', ratingallocate_MOD_NAME));
            } else if ($status === self::DISTRIBUTION_STATUS_RATING_IN_PROGRESS) {
                // Rating is possible...

                // Suche das richtige Formular nach Strategie.
                $strategyform = 'ratingallocate\\' . $this->ratingallocate->strategy . '\\mod_ratingallocate_view_form';

                /* @var $mform moodleform */
                $mform = new $strategyform($PAGE->url->out(), $this);
                $mform->add_action_buttons();

                if ( $mform->is_cancelled() ) {
                    // Return to view.
                    redirect("$CFG->wwwroot/mod/ratingallocate/view.php?id=".$this->coursemodule->id);
                    return "";
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

    /**
     * Processes the action of a user deleting his rating.
     * @return string rendered html
     */
    private function process_action_delete_rating() {
        /* @var mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();
        // Print data and controls for students, but not for admins.
        if (has_capability('mod/ratingallocate:give_rating', $this->context, null, false)) {
            global $USER;

            $status = $this->get_status();
            if ($status === self::DISTRIBUTION_STATUS_RATING_IN_PROGRESS) {
                // Rating is possible...

                $this->delete_ratings_of_user($USER->id);
                $renderer->add_notification(get_string('ratings_deleted', ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);

                return $this->process_default();
            }
        }
        return '';
    }

    private function process_action_show_choices() {

        if (has_capability('mod/ratingallocate:modify_choices', $this->context)) {
            global $OUTPUT;
            /* @var mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();

            // Notifications if no choices exist or too few in comparison to strategy settings.
            $availablechoices = $this->get_rateable_choices();
            $strategysettings = $this->get_strategy_class()->get_static_settingfields();
            if (array_key_exists(ratingallocate\strategy_order\strategy::COUNTOPTIONS, $strategysettings)) {
                $necessarychoices =
                    $strategysettings[ratingallocate\strategy_order\strategy::COUNTOPTIONS][2];
            } else {
                $necessarychoices = 0;
            }
            if (count($availablechoices) < $necessarychoices) {
                $renderer->add_notification(get_string('too_few_choices_to_rate', ratingallocate_MOD_NAME, $necessarychoices));
            }

            echo $renderer->render_header($this->ratingallocate, $this->context, $this->coursemodule->id);
            echo $OUTPUT->heading(get_string('show_choices_header', ratingallocate_MOD_NAME));

            $renderer->ratingallocate_show_choices_table($this, true);
            echo $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $this->coursemodule->id,
                'ratingallocateid' => $this->ratingallocateid,
                'action' => '')), get_string('back'));
            echo $renderer->render_footer();
        }

    }

    private function process_action_edit_choice() {
        global $DB;

        $output = '';
        if (has_capability('mod/ratingallocate:modify_choices', $this->context)) {
            global $OUTPUT;
            $choiceid = optional_param('choiceid', 0, PARAM_INT);

            if ($choiceid) {
                $record = $DB->get_record(this_db\ratingallocate_choices::TABLE, array('id' => $choiceid));
                $choice = new ratingallocate_choice($record);
            } else {
                $choice = null;
            }
            $mform = new modify_choice_form(new moodle_url('/mod/ratingallocate/view.php',
            array('id' => $this->coursemodule->id,
                'ratingallocateid' => $this->ratingallocateid,
                'action' => ACTION_EDIT_CHOICE)),
            $this, $choice);

            /* @var mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();

            if ($mform->is_submitted() && $data = $mform->get_submitted_data()) {
                if (!$mform->is_cancelled()) {
                    if ($mform->is_validated()) {
                        $this->save_modify_choice_form($data);
                        $renderer->add_notification(get_string("choice_added_notification", ratingallocate_MOD_NAME),
                            self::NOTIFY_SUCCESS);
                    } else {
                        $output .= $OUTPUT->heading(get_string('edit_choice', ratingallocate_MOD_NAME), 2);
                        $output .= $mform->to_html();
                        return $output;
                    }
                }
                if (object_property_exists($data, 'submitbutton2')) {
                    // If form was submitted using submit2, redirect to the empty edit choice form.
                    redirect(new moodle_url('/mod/ratingallocate/view.php',
                        array('id' => $this->coursemodule->id,
                            'ratingallocateid' => $this->ratingallocateid,
                            'action' => ACTION_EDIT_CHOICE, 'next' => true)));
                } else {
                    // If form was submitted using save or cancel, show the choices table.
                    $this->process_action_show_choices();
                    return false;
                }
            } else {
                $isnext = optional_param('next', false, PARAM_BOOL);
                if ($isnext) {
                    $renderer->add_notification(get_string("choice_added_notification", ratingallocate_MOD_NAME),
                        self::NOTIFY_SUCCESS);
                }
                $output .= $OUTPUT->heading(get_string('edit_choice', ratingallocate_MOD_NAME), 2);
                $output .= $mform->to_html();
            }
        }
        return $output;
    }

    /**
     * Enables or disables a choice and displays the choices list.
     * @param bool $active states if the choice should be set active or inavtive
     */
    private function process_action_enable_choice($active) {
        if (has_capability('mod/ratingallocate:modify_choices', $this->context)) {
            global $DB;
            $choiceid = optional_param('choiceid', 0, PARAM_INT);

            if ($choiceid) {
                $DB->set_field(this_db\ratingallocate_choices::TABLE,
                    this_db\ratingallocate_choices::ACTIVE,
                    $active,
                    array('id' => $choiceid));
            }
            $this->process_action_show_choices();
        }
    }

    /**
     * Deletes a choice and displays the choices list.
     */
    private function process_action_delete_choice() {
        if (has_capability('mod/ratingallocate:modify_choices', $this->context)) {
            global $DB;
            $choiceid = optional_param('choiceid', 0, PARAM_INT);

            /* @var mod_ratingallocate_renderer*/
            $renderer = $this->get_renderer();

            if ($choiceid) {
                $choice = $DB->get_record(this_db\ratingallocate_choices::TABLE, array('id' => $choiceid));
                if ($choice) {
                    $DB->delete_records(this_db\ratingallocate_choices::TABLE, array('id' => $choiceid));
                    $renderer->add_notification(get_string('choice_deleted_notification', ratingallocate_MOD_NAME,
                    $choice->{this_db\ratingallocate_choices::TITLE}),
                        self::NOTIFY_SUCCESS);
                } else {
                    $renderer->add_notification(
                        get_string('choice_deleted_notification_error', ratingallocate_MOD_NAME)
                    );

                }

            }
            $this->process_action_show_choices();
        }
    }

    private function process_action_manual_allocation() {
        // Manual allocation.
        $output = '';
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            global $OUTPUT, $PAGE;

            $mform = new manual_alloc_form($PAGE->url, $this);

            if (!$mform->no_submit_button_pressed() && $data = $mform->get_submitted_data()) {
                if (!$mform->is_cancelled() ) {
                    /* @var mod_ratingallocate_renderer */
                    $renderer = $this->get_renderer();
                    $status = $this->get_status();
                    if ($status === self::DISTRIBUTION_STATUS_TOO_EARLY ||
                        $status === self::DISTRIBUTION_STATUS_RATING_IN_PROGRESS) {
                        $renderer->add_notification(
                            get_string('modify_allocation_group_desc_'.$status, ratingallocate_MOD_NAME));
                    } else {
                        $allocationdata = optional_param_array('allocdata', array(), PARAM_INT);
                        if ($userdata = optional_param_array('userdata', null, PARAM_INT)) {
                            $this->save_manual_allocation_form($allocationdata, $userdata);
                            $renderer->add_notification(get_string('manual_allocation_saved',
                                ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);
                        } else {
                            $renderer->add_notification(get_string('manual_allocation_nothing_to_be_saved',
                                ratingallocate_MOD_NAME), self::NOTIFY_MESSAGE);
                        }
                    }
                } else {
                    return $this->process_default();
                }
                // If form was submitted using save or cancel, show the default page.
                if (array_key_exists("submitbutton",$data)){
                    return $this->process_default();
                // If the save and continue button was pressed,
                // reinitialize the form to refresh the checked radiobuttons.
                } else if (array_key_exists("submitbutton2",$data)){
                    $mform = new manual_alloc_form($PAGE->url, $this);
                }
            }
            $output .= $OUTPUT->heading(get_string('manual_allocation', ratingallocate_MOD_NAME), 2);

            $output .= $mform->to_html();
            $this->showinfo = false;
        }
        return $output;
    }

    private function process_action_show_alloc_table() {
        $output = '';
        // Print ratings table.
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            global $OUTPUT;
            /* @var mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();
            $output .= $renderer->ratings_table_for_ratingallocate($this->get_rateable_choices(),
                    $this->get_ratings_for_rateable_choices(), $this->get_raters_in_course(),
                    $this->get_allocations(), $this);

            $output .= html_writer::empty_tag('br', array());
            $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php', array(
                'id' => $this->coursemodule->id,
                'ratingallocateid' => $this->ratingallocateid,
                'action' => '')), get_string('back'));

            // Logging.
            $event = \mod_ratingallocate\event\allocation_table_viewed::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid);
            $event->trigger();
        }
        return $output;
    }

    private function process_action_show_statistics() {
        $output = '';
        // Print ratings table.
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            global $OUTPUT;
            /* @var mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();

            $output .= $renderer->distribution_table_for_ratingallocate($this);

            $output .= html_writer::empty_tag('br', array());
            $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $this->coursemodule->id,
                'ratingallocateid' => $this->ratingallocateid,
                'action' => '')), get_string('back'));
            // Logging.
            $event = \mod_ratingallocate\event\allocation_statistics_viewed::create_simple(
                context_course::instance($this->course->id), $this->ratingallocateid);
            $event->trigger();
        }
        return $output;
    }

    private function process_publish_allocations() {
        $status = $this->get_status();
        if ($status === self::DISTRIBUTION_STATUS_READY_ALLOC_STARTED) {
            global $USER;

            $this->origdbrecord->{this_db\ratingallocate::PUBLISHED}   = true;
            $this->origdbrecord->{this_db\ratingallocate::PUBLISHDATE} = time();
            $this->origdbrecord->{this_db\ratingallocate::NOTIFICATIONSEND} = -1;
            $this->ratingallocate = new ratingallocate_db_wrapper($this->origdbrecord);
            $this->db->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);

            // Create the instance.
            $domination = new mod_ratingallocate\task\send_distribution_notification();

            // Add custom data.
            $domination->set_component('mod_ratingallocate');
            $domination->set_custom_data(array(
                            'userid' => $USER->id, // Will be the sending user.
                            'ratingallocateid' => $this->ratingallocateid
            ));

            // Queue it.
            \core\task\manager::queue_adhoc_task($domination);

            // Logging.
            $event = \mod_ratingallocate\event\allocation_published::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid);
            $event->trigger();

            /* @var mod_ratingallocate_renderer */
            $renderer = $this->get_renderer();
            $renderer->add_notification( get_string('distribution_published', ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);
        }
        return $this->process_default();
    }

    private function process_action_allocation_to_grouping() {
        $groupingidname = ratingallocate_MOD_NAME . '_instid_' . $this->ratingallocateid;
        // Search if there is already a grouping from us.
        $grouping = groups_get_grouping_by_idnumber($this->course->id, $groupingidname);
        $groupingid = null;
        if (!$grouping) {
            // Create grouping.
            $data = new stdClass();
            $data->name = get_string('groupingname', ratingallocate_MOD_NAME, $this->ratingallocate->name);
            $data->idnumber = $groupingidname;
            $data->courseid = $this->course->id;
            $groupingid = groups_create_grouping($data);
        } else {
            $groupingid = $grouping->id;
        }

        $groupidentifierfromchoiceid = function ($choiceid) {
            return ratingallocate_MOD_NAME . '_c_' . $choiceid;
        };

        $choices = $this->get_choices_with_allocationcount();

        // Make a new array containing only the identifiers of the choices.
        $choiceids = array();
        foreach ($choices as $id => $choice) {
            $choiceids[$groupidentifierfromchoiceid($choice->id)] = array('key' => $id            );
        }

        // Dind all associated groups in this grouping.
        $groups = groups_get_all_groups($this->course->id, 0, $groupingid);

        // Loop through the groups in the grouping: if the choice does not exist anymore -> delete.
        // Otherwise mark it.
        foreach ($groups as $group) {
            if (array_key_exists($group->idnumber, $choiceids)) {
                // Group exists, mark.
                $choiceids[$group->idnumber]['exists'] = true;
                $choiceids[$group->idnumber]['groupid'] = $group->id;
            } else {
                // Delete group $group->id.
                groups_delete_group($group->id);
            }
        }

        // Create groups groups for new identifiers or empty group if it exists.
        foreach ($choiceids as $groupid => $choice) {
            if (key_exists('exists', $choice)) {
                // Remove all members.
                groups_delete_group_members_by_group($choice['groupid']);
            } else {
                $data = new stdClass();
                $data->courseid = $this->course->id;
                $data->name = $choices[$choice['key']]->title;
                $data->idnumber = $groupid;
                $createdid = groups_create_group($data);
                groups_assign_grouping($groupingid, $createdid);
                $choiceids[$groupid]['groupid'] = $createdid;
            }
        }

        // Add all participants in the correct group.
        $allocations = $this->get_allocations();
        foreach ($allocations as $id => $allocation) {
            $choiceid = $allocation->choiceid;
            $userid = $allocation->userid;
            $choiceidentifier = $groupidentifierfromchoiceid($choiceid);
            groups_add_member($choiceids[$choiceidentifier]['groupid'], $userid);
        }
        // Invalidate the grouping cache for the course.
        cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($this->course->id));
        $renderer = $this->get_renderer();
        $renderer->add_notification( get_string('moodlegroups_created', ratingallocate_MOD_NAME), self::NOTIFY_SUCCESS);

        return $this->process_default();
    }

    private function process_default() {
        global $OUTPUT;
        $output = '';
        /* @var mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();
        $status = $this->get_status();
        if (has_capability('mod/ratingallocate:give_rating', $this->context, null, false)) {
            if ($status === self::DISTRIBUTION_STATUS_RATING_IN_PROGRESS) {
                if ($this->is_setup_ok()) {
                    $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php',
                    array('id' => $this->coursemodule->id,
                        'ratingallocateid' => $this->ratingallocateid,
                        'action' => ACTION_GIVE_RATING)),
                    get_string('edit_rating', ratingallocate_MOD_NAME));

                $output .= $OUTPUT->single_button(new moodle_url('/mod/ratingallocate/view.php',
                    array('id' => $this->coursemodule->id,
                        'ratingallocateid' => $this->ratingallocateid,
                        'action' => ACTION_DELETE_RATING)),
                    get_string('delete_rating', ratingallocate_MOD_NAME));
                } else {
                    $renderer->add_notification(get_string('no_rating_possible', ratingallocate_MOD_NAME));
                }
            }
        }
        // Print data and controls to edit the choices.
        if (has_capability('mod/ratingallocate:modify_choices', $this->context)) {
            $output .= $renderer->modify_choices_group($this->ratingallocateid, $this->coursemodule->id, $status);
        }

        // Print data and controls for teachers.
        if (has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            $output .= $renderer->modify_allocation_group($this->ratingallocateid, $this->coursemodule->id, $status,
                (int) $this->ratingallocate->algorithmstatus, (boolean) $this->ratingallocate->runalgorithmbycron);
            $output .= $renderer->publish_allocation_group($this->ratingallocateid, $this->coursemodule->id, $status);
            $output .= $renderer->reports_group($this->ratingallocateid, $this->coursemodule->id, $status, $this->context);
        }

        // Logging.
        $event = \mod_ratingallocate\event\ratingallocate_viewed::create_simple(
                context_course::instance($this->course->id), $this->ratingallocateid);
        $event->trigger();

        return $output;
    }

    // States if the ratingallocate info schould be displayed.
    private $showinfo = true;

    /**
     * This is what the view.php calls to make the output
     */
    public function handle_view() {
        global $PAGE, $USER;
        $action = optional_param('action', '', PARAM_TEXT);

        $PAGE->set_cacheable(false); // TODO necessary.

        // Output starts here.
        $output = '';

        /* @var mod_ratingallocate_renderer */
        $renderer = $this->get_renderer();

        switch ($action) {
            case ACTION_START_DISTRIBUTION:
                $output .= $this->process_action_start_distribution();
                break;

            case ACTION_GIVE_RATING:
                $output .= $this->process_action_give_rating();
                break;

            case ACTION_DELETE_RATING:
                $output .= $this->process_action_delete_rating();
                break;

            case ACTION_SHOW_CHOICES:
                $this->process_action_show_choices();
                return "";

            case ACTION_EDIT_CHOICE:
                $result = $this->process_action_edit_choice();
                if (!$result) {
                    return "";
                }
                $output .= $result;
                $this->showinfo = false;
                break;

            case ACTION_ENABLE_CHOICE:
                $this->process_action_enable_choice(true);
                return "";

            case ACTION_DISABLE_CHOICE:
                $this->process_action_enable_choice(false);
                return "";

            case ACTION_DELETE_CHOICE:
                $this->process_action_delete_choice();
                return "";

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

        if ($this->showinfo) {
            $choicestatus = new ratingallocate_choice_status();
            $choicestatus->accesstimestart = $this->ratingallocate->accesstimestart;
            $choicestatus->accesstimestop = $this->ratingallocate->accesstimestop;
            $choicestatus->publishdate = $this->ratingallocate->publishdate;
            $choicestatus->is_published = $this->ratingallocate->published;
            $choicestatus->available_choices = $this->get_rateable_choices();
            $strategysettings = $this->get_strategy_class()->get_static_settingfields();
            if (array_key_exists(ratingallocate\strategy_order\strategy::COUNTOPTIONS, $strategysettings)) {
                $choicestatus->necessary_choices =
                    $strategysettings[ratingallocate\strategy_order\strategy::COUNTOPTIONS][2];
            } else {
                $choicestatus->necessary_choices = 0;
            }
            $choicestatus->own_choices = $this->get_rating_data_for_user($USER->id);
            $choicestatus->allocations = $this->get_allocations_for_user($USER->id);
            $choicestatus->strategy = $this->get_strategy_class();
            $choicestatus->show_distribution_info = has_capability('mod/ratingallocate:start_distribution', $this->context);
            $choicestatus->show_user_info = has_capability('mod/ratingallocate:give_rating', $this->context, null, false);
            $choicestatus->algorithmstarttime = $this->ratingallocate->algorithmstarttime;
            $choicestatus->algorithmstatus = $this->get_algorithm_status();
            $choicestatusoutput = $renderer->render($choicestatus);
        } else {
            $choicestatusoutput = "";
        }

        $header = $renderer->render_header($this->ratingallocate, $this->context, $this->coursemodule->id);
        $footer = $renderer->render_footer();
        return $header . $choicestatusoutput . $output . $footer;
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

        // Filter out everyone who can't give ratings.
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

        // Set algorithm status to running.
        $this->origdbrecord->algorithmstatus = \mod_ratingallocate\algorithm_status::running;
        $this->origdbrecord->algorithmstarttime = time();
        $this->db->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);

        $distributor = new solver_edmonds_karp();
        // $distributor = new solver_ford_fulkerson();
        $timestart = microtime(true);
        $distributor->distribute_users($this);
        $timeneeded = (microtime(true) - $timestart);
        // echo memory_get_peak_usage();

        // Set algorithm status to finished.
        $this->origdbrecord->algorithmstatus = \mod_ratingallocate\algorithm_status::finished;
        $this->db->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);

        return $timeneeded;
    }

    /**
     * Call this function when the algorithm failed and the algorithm status has to be set to failed.
     */
    public function set_algorithm_failed() {
        $this->origdbrecord->algorithmstatus = \mod_ratingallocate\algorithm_status::failure;
        $this->db->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);
    }

    /**
     * Returns all users, that have not been allocated but have given ratings
     *
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
        // Macht daraus ein Array mit userid => quatsch.
        $allocated = array_flip(array_map(function ($entry) {
                    return $entry->userid;
        }, $allocated));

        // Filter out everyone who already has an allocation.
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
     * @return array all allocation objects that belong this ratingallocate
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
     * Removes all allocations for choices in $ratingallocateid
     */
    public function clear_all_allocations() {
        $this->db->delete_records('ratingallocate_allocations', array('ratingallocateid' => intval($this->ratingallocateid)));
    }

    /**
     * Set the published to yes and allow users to see their allocation
     * @deprecated
     */
    public function publish_allocation() {
        $this->process_publish_allocations();
    }

    /**
     * Gets called by the adhoc_taskmanager and its task in send_distribution_notification
     *
     * @param stdClass $userfrom
     */
    public function notify_users_distribution($userfrom) {
        $userfrom = get_complete_user_data('id', $userfrom);

        // Make sure we have not sent them yet.
        if ($this->origdbrecord->{this_db\ratingallocate::NOTIFICATIONSEND} != -1) {
            mtrace('seems we have sent them already');
            return;
        }

        $choices = $this->get_choices_with_allocationcount();
        $allocations = $this->get_allocations();
        foreach ($allocations as $userid => $allocobj) {
            // Get the assigned choice_id.
            $allocchoiceid = $allocobj->choiceid;

            // Prepare the email to be sent to the user.
            $userto = get_complete_user_data('id', $allocobj->userid);
            cron_setup_user($userto);

            // Prepare text.
            $notiftext = $this->make_mail_text($choices[$allocchoiceid]);
            $notifhtml = $this->make_mail_html($choices[$allocchoiceid]);

            $notifsubject = format_string($this->course->shortname, true) . ': ' .
                     get_string('allocation_notification_message_subject', 'ratingallocate',
                     $this->ratingallocate->name);
            // Send the post now!
            if (empty($userto->mailformat) || $userto->mailformat != 1) {
                // This user DOESN'T want to receive HTML.
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

        // Update the 'notified' flag.
        $this->origdbrecord->{this_db\ratingallocate::NOTIFICATIONSEND} = 1;
        $this->ratingallocate = new ratingallocate_db_wrapper($this->origdbrecord);

        $this->db->update_record(this_db\ratingallocate::TABLE, $this->origdbrecord);
    }

    /**
     * Builds and returns the body of the email notification in plain text.
     *
     * @param ratingallocate_choice $choice
     * @return string The email body in plain text format.
     */
    private function make_mail_text($choice) {
        global $CFG;
        $notiftext = '';

        $notiftext .= "\n";
        $notiftext .= $CFG->wwwroot.'/mod/ratingallocate/view.php?id='.$this->coursemodule->id;
        $notiftext .= "\n---------------------------------------------------------------------\n";
        $notiftext .= format_string($this->ratingallocate->name, true);

        $notiftext .= "\n---------------------------------------------------------------------\n";
        $notiftext .= get_string('allocation_notification_message', 'ratingallocate', array(
            'ratingallocate' => $this->ratingallocate->name,
            'choice' => $choice->title,
            'explanation' => $choice->explanation));

        $notiftext .= "\n\n";

        return $notiftext;
    }

    /**
     * Builds and returns the body of the email notification in html
     *
     * @return string The email body in html format.
     */
    private function make_mail_html($choice) {
        global $CFG;

        $shortname = format_string($this->course->shortname, true,
            array('context' => context_course::instance($this->course->id)));

        $notifhtml = '<head>';
        $notifhtml .= '</head>';
        $notifhtml .= "\n<body id=\"email\">\n\n";

        $notifhtml .= '<div class="navbar">'.
            '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.'">'.
            $shortname.'</a> &raquo; '.
            '<a target="_blank" href="'.$CFG->wwwroot.'/mod/ratingallocate/view.php?id='.$this->coursemodule->id.'">'.
            format_string($this->ratingallocate->name, true).'</a>';
        $notifhtml .= '</div><hr />';
        // Format the post body.
        $options = new stdClass();
        $options->para = true;
        $notifhtml .= format_text(get_string('allocation_notification_message', 'ratingallocate',
            array('ratingallocate' => $this->ratingallocate->name,
                'choice' => $choice->title,
                'explanation' => $choice->explanation)
            ),
            FORMAT_HTML, $options, $this->course->id);

        $notifhtml .= '<hr />';
        $notifhtml .= '</body>';

        return $notifhtml;
    }

    /**
     * Create a moodle grouping with the name of the ratingallocate instance
     * and create groups according to the distribution. Groups are identified
     * by their idnumber. If a group exists, all users are removed.
     * @deprecated
     */
    public function create_moodle_groups() {
           $this->process_action_allocation_to_grouping();
    }

    /**
     * Returns all ratings from the user with id $userid.
     * @param int $userid
     * @return array
     */
    public function get_rating_data_for_user($userid) {
        $sql = "SELECT c.id as choiceid, c.title, c.explanation, c.ratingallocateid, c.maxsize, r.rating, r.id AS ratingid, r.userid
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
     * Delete all ratings of a users
     * @param int $userid
     */
    public function delete_ratings_of_user($userid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {

            $choices = $this->get_choices();

            foreach ($choices as $id => $choice) {
                $data = array(
                    'userid' => $userid,
                    'choiceid' => $id
                );

                // Actually delete the rating.
                $DB->delete_records('ratingallocate_ratings', $data);
            }

            $transaction->allow_commit();

            // Logging.
            $event = \mod_ratingallocate\event\rating_deleted::create_simple(
                context_course::instance($this->course->id), $this->ratingallocateid);
            $event->trigger();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Save all the users rating to db
     * @param int $userid
     * @param array $data
     */
    public function save_ratings_to_db($userid, array $data) {
        /* @var moodle_database */
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
                    // We get the id from the database.

                    $oldrating = $DB->get_record('ratingallocate_ratings', $ratingexists);
                    if ($oldrating->{this_db\ratingallocate_ratings::RATING} != $rating->rating) {
                        $rating->id = $oldrating->id;
                        $DB->update_record('ratingallocate_ratings', $rating);

                        // Logging.
                        array_push($loggingdata,
                            array('choiceid' => $oldrating->choiceid, 'rating' => $rating->rating));
                    }
                } else {
                    // Create a new rating in the table.

                    $rating->userid = $userid;
                    $rating->choiceid = $rdata ['choiceid'];
                    $rating->ratingallocateid = $this->ratingallocateid;
                    $DB->insert_record('ratingallocate_ratings', $rating);

                    // Logging.
                    array_push($loggingdata,
                        array('choiceid' => $rating->choiceid, 'rating' => $rating->rating));
                }
                $completion = new completion_info($this->course);
                $completion->set_module_viewed($this->coursemodule);
            }
            $transaction->allow_commit();
            // Logging.
            $event = \mod_ratingallocate\event\rating_saved::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid, $loggingdata);
            $event->trigger();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Returns all active choices in the instance with $ratingallocateid
     */
    public function get_rateable_choices() {
        global $DB;
        return $DB->get_records(this_db\ratingallocate_choices::TABLE,
            array(this_db\ratingallocate_choices::RATINGALLOCATEID => $this->ratingallocateid,
                this_db\ratingallocate_choices::ACTIVE => true,
            ), this_db\ratingallocate_choices::TITLE);
    }

    /**
     * Returns all choices in the instance with $ratingallocateid
     */
    public function get_choices() {
        global $DB;
        return $DB->get_records(this_db\ratingallocate_choices::TABLE,
            array(this_db\ratingallocate_choices::RATINGALLOCATEID => $this->ratingallocateid,
            ), this_db\ratingallocate_choices::TITLE);
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
     * @global mixed $DB
     * @param $allocdata array of users to the choice ids they should be allocated to.
     */
    public function save_manual_allocation_form($allocdata, $userdata) {
        try {
            $transaction = $this->db->start_delegated_transaction();

            $allusers = $this->get_raters_in_course();
            $allchoices = $this->get_rateable_choices();

            foreach ($userdata as $id => $user) {
                $this->remove_allocations($id);
            }

            foreach ($allocdata as $id => $choiceallocationid) {
                // Is this user in this course?
                if (key_exists($id, $allusers) && key_exists($id, $userdata) && key_exists($choiceallocationid, $allchoices)) {
                    // Create new allocation.
                    $this->add_allocation($choiceallocationid, $id);
                }
            }
            // Logging.
            $event = \mod_ratingallocate\event\manual_allocation_saved::create_simple(
                    context_course::instance($this->course->id), $this->ratingallocateid);
            $event->trigger();

            $transaction->allow_commit();
        } catch (Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
        }
    }

    public function save_modify_choice_form($data) {
        global $DB;
        try {
            $transaction = $this->db->start_delegated_transaction();
            $loggingdata = array();

            $allusers = $this->get_raters_in_course();
            $allchoices = $this->get_rateable_choices();

            $choice = new ratingallocate_choice($data);
            $choice->{this_db\ratingallocate_choices::RATINGALLOCATEID} = $this->ratingallocateid;

            if (!empty($data->choiceid)) {
                $choice->id = $data->choiceid;
                $DB->update_record(this_db\ratingallocate_choices::TABLE, $choice->dbrecord);
            } else {
                $DB->insert_record(this_db\ratingallocate_choices::TABLE, $choice->dbrecord);
            }

            // Logging.
//            $event = \mod_ratingallocate\event\choice_saved::create_simple(
//                context_course::instance($this->course->id), $this->ratingallocateid, );
//            $event->trigger();

            $transaction->allow_commit();
        } catch (Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
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
     * Remove all allocations of a user.
     * @param int $userid id of the user.
     */
    public function remove_allocations($userid) {
        $this->db->delete_records('ratingallocate_allocations', array(
            'userid' => $userid,
            'ratingallocateid' => $this->ratingallocateid
        ));
    }

    /**
     * add an allocation between choiceid and userid
     * @param int $choiceid
     * @param int $userid
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
     * @param int $oldchoiceid
     * @param int $newchoiceid
     * @param int $userid
     * @return boolean
     */
    public function alter_allocation($oldchoiceid, $newchoiceid, $userid) {
        $this->db->set_field(this_db\ratingallocate_allocations::TABLE, this_db\ratingallocate_allocations::CHOICEID,
            $newchoiceid, array(
                'choiceid' => $oldchoiceid,
                'userid' => $userid
                )
            );
        return true;
    }

    /**
     * internal helper to populate the real db with random data, currently disabled
     */
    public function addtestdata() {
        return; // Delete this if you really want to call this function!
        $transaction = $this->db->start_delegated_transaction();
        for ($i = 2; $i < 502; $i++) { // Set right user id's!
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
     * @return mod_ratingallocate_renderer
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
     * @param array $radioarray
     * @param moodleform $mform
     * @return array radioarray
     */
    public function prepare_horizontal_radio_choice($radioarray, $mform) {
        $result = array();
        // Add static elements to provide a list with choices annotated with css classes.
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
    public function get_options_titles(array $ratings) {
        return $this->get_strategy_class()->translate_ratings_to_titles($ratings);
    }

    /**
     * Returns the strategy class for the ratingallocate
     */
    private function get_strategy_class() {
        $strategyclassp = 'ratingallocate\\' . $this->ratingallocate->strategy . '\\strategy';
        $allsettings = json_decode($this->ratingallocate->setting, true);
        if (array_key_exists($this->ratingallocate->strategy, $allsettings)) {
            return new $strategyclassp($allsettings[$this->ratingallocate->strategy]);
        } else {
            return new $strategyclassp();
        }
    }

    /** Rating phase has not started yet. */
    const DISTRIBUTION_STATUS_TOO_EARLY = 'too_early';
    /** Rating phase in progress. */
    const DISTRIBUTION_STATUS_RATING_IN_PROGRESS = 'rating_in_progress';
    /** Rating phase ended, but no allocations exist. */
    const DISTRIBUTION_STATUS_READY = 'ready';
    /** Rating phase ended and there are already some allocations. */
    const DISTRIBUTION_STATUS_READY_ALLOC_STARTED = 'ready_alloc_started';
    /** Rating phase ended and allocations have been published. */
    const DISTRIBUTION_STATUS_PUBLISHED = 'published';

    /**
     * Returns the status of the ratingallocate, which is used for altering the help texts
     * as well as for enabling and disabling functionalities.
     * @return string the current status of the ratingallocte
     */
    public function get_status() {
        $now = time();
        if ($this->ratingallocate->accesstimestart > $now) {
            return self::DISTRIBUTION_STATUS_TOO_EARLY;
        }
        if ($this->ratingallocate->accesstimestop > $now) {
            return self::DISTRIBUTION_STATUS_RATING_IN_PROGRESS;
        }
        if ($this->ratingallocate->published == true) {
            return self::DISTRIBUTION_STATUS_PUBLISHED;
        }
        if (count($this->get_allocations()) == 0) {
            return self::DISTRIBUTION_STATUS_READY;
        } else {
            return self::DISTRIBUTION_STATUS_READY_ALLOC_STARTED;
        }
    }

    /**
     * Returns the current algorithm status.
     * The different values can be found in the class ratingallocate_status.
     * @return int the current algorithm status
     */
    public function get_algorithm_status() {
        return (int) $this->ratingallocate->algorithmstatus;
    }

    /** Returns the context of the ratingallocate instance
     * @return context_module
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * @return bool true, if all strategy settings are ok.
     */
    public function is_setup_ok() {
        if ($this->ratingallocate->strategy === 'strategy_order') {
            $choicecount = count($this->get_rateable_choices());
            $strategyclass = $this->get_strategy_class();
            $strategysettings = $strategyclass->get_static_settingfields();
            $necessary_choices = $strategysettings[ratingallocate\strategy_order\strategy::COUNTOPTIONS][2];
            if ($choicecount < $necessary_choices) {
                return false;
            }
        }
        return true;
    }

}

/**
 * Kapselt eine Instanz von ratingallocate_choice
 *
 * @property int $id
 * @property int $ratingallocateid
 * @property string $title
 * @property string explanation
 * @property int $maxsize
 * @property bool $active
 */
class ratingallocate_choice {
    /** @var stdClass original db record */
    public $dbrecord;

    /** Emulates the functionality as if there were explicit records by passing them to the original db record
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->dbrecord->{$name};
    }

    /** Emulates the functionality as if there were explicit records by passing them to the original db record
     *
     * @param string $name
     */
    public function __set($name, $value) {
        $this->dbrecord->{$name} = $value;
    }

    public function __construct($record) {
        $this->dbrecord = $record;
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
    global $DB;

    if (is_bool($groupid)) {
        debugging('Incorrect groupid function parameter');
        return false;
    }

    // Select * so that the function groups_remove_member() gets the whole record.
    $groups = $DB->get_recordset('groups', array('id' => $groupid));

    foreach ($groups as $group) {
        $userids = $DB->get_fieldset_select('groups_members', 'userid', 'groupid = :groupid',
            array('groupid' => $group->id));

        // Very ugly hack because some group-management functions are not provided in lib/grouplib.php
        // but does not add too much overhead since it does not include more files...
        require_once (dirname(dirname(dirname(__FILE__))) . '/group/lib.php');
        foreach ($userids as $id) {
            groups_remove_member($group, $id);
        }
    }
    return true;
}
