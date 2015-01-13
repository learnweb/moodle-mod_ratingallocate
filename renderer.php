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

use ratingallocate\db as this_db;

/**
 * @package    mod
 * @subpackage mod_ratingallocate
 * @copyright  2013, Stefan Koegel, original version
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/locallib.php');

class mod_ratingallocate_renderer extends plugin_renderer_base {
    
    /**
     * @var string rendered notifications to output for handle_view()
     */
    private $notifications = array();

    /**
     * Render the header.
     *
     * @param ratingallocate_header $header
     * @return string
     */
    public function render_ratingallocate_header(ratingallocate_header $header) {
        $o = '';

        $this->page->set_title(get_string('pluginname', ratingallocate_MOD_NAME));
        $this->page->set_heading($this->page->course->fullname);
        $this->page->requires->css('/mod/ratingallocate/styles.css');

        $o .= $this->output->header();
        $heading = format_string($header->ratingallocate->name, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);

        if ($header->showintro) {
            $intro_text = format_module_intro('ratingallocate', $header->ratingallocate, 
                    $header->coursemoduleid);
            if ($intro_text) {
                $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
                $o .= $intro_text;
                $o .= $this->output->box_end();
            }
        }
        //$o .= $this->notifications;
        if(!empty($this->notifications)) {
            $o .= $this->output->box_start('box generalbox boxaligncenter');
            foreach ($this->notifications as $elem) {
                $o .= html_writer::div(format_text($elem));
            }
            $o .= $this->output->box_end();
        }
        return $o;
    }

    /**
     * Page is done - render the footer.
     *
     * @return void
     */
    public function render_footer() {
        $o = '';
        $o .= $this->output->footer();
        return $o;
    }

    public function render_ratingallocate_strategyform($mform) {
        /* @var $mform ratingallocate_strategyform */
        $o = '';
        $o .= $this->heading(get_string('your_rating', ratingallocate_MOD_NAME), 2);
        $o .= $this->format_text($mform->get_strategy_description_header() . '<br/>' . $mform->describe_strategy());
        $o .= $mform->to_html();

        return $o;
    }

    /**
     * render current choice status
     * @param ratingallocate_choice_status $status
     * @return string
     */
    public function render_ratingallocate_choice_status(ratingallocate_choice_status $status) {
        $o = '';
        $o .= $this->output->container_start('choicestatustable');
        $o .= $this->output->heading(get_string('choicestatusheading', ratingallocate_MOD_NAME), 3);
        $time = time();

        $o .= $this->output->box_start('boxaligncenter choicesummarytable');
        $t = new html_table();

        $accesstimestart = $status->accesstimestart;
        if ($accesstimestart > $time) {
            // Access not yet available
            $this->add_table_row_tuple($t, get_string('rating_begintime', ratingallocate_MOD_NAME), userdate($accesstimestart));
        }

        $duedate = $status->accesstimestop;
        if ($duedate > 0) {
            // Due date.
            $this->add_table_row_tuple($t, get_string('rating_endtime', ratingallocate_MOD_NAME), userdate($duedate));

                if($accesstimestart > 0 && $accesstimestart < $time) {
                // Time remaining.
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('timeremaining', ratingallocate_MOD_NAME));
                if ($duedate - $time <= 0) {
                    $cell2 = new html_table_cell(get_string('rating_is_over', ratingallocate_MOD_NAME));
                } else {
                    $cell2 = new html_table_cell(format_time($duedate - $time));
                }
                $row->cells = array(
                                $cell1,
                                $cell2
                );
                $t->data[] = $row;
            }
        }
        if ($status->is_published && $status->publishdate) {
            $this->add_table_row_tuple($t, get_string('publishdate', ratingallocate_MOD_NAME), userdate($status->publishdate));
        } else if ($status->publishdate) {
            $this->add_table_row_tuple($t, get_string('publishdate_estimated', ratingallocate_MOD_NAME), userdate($status->publishdate));
        }

        //print available choices if no choice form is displayed
        if(!empty($status->available_choices) && ($time < $status->accesstimestart || $status->accesstimestop < $time) && $status->show_distribution_info) {
        $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('rateable_choices', ratingallocate_MOD_NAME));

            $choices_html = '';
            foreach ($status->available_choices as $choice) {
                $choices_html .= '<li>';
                $choices_html .= format_string($choice->title);
                $choices_html .= '</li>';
            }

            $cell2 = new html_table_cell('<ul>' . $choices_html . '</ul>');
//             $cell2->attributes = array('class' => 'submissionnotgraded');
            $row->cells = array(
                            $cell1,
                            $cell2
            );
            $t->data[] = $row;
        }

        //print own choices if no choice form is displayed
        if(!empty($status->own_choices) && $status->show_user_info) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('your_rating', ratingallocate_MOD_NAME));

            $choices_html = '';
            foreach ($status->own_choices as $choice) {
                $choices_html .= '<li>';
                $choices_html .= format_string($choice->title) . ' (' . s($this->get_option_title($choice->rating, $status->strategy)) . ')';
                $choices_html .= '</li>';
            }

            $cell2 = new html_table_cell('<ul>' . $choices_html . '</ul>');
//             $cell2->attributes = array('class' => 'submissionnotgraded');
            $row->cells = array(
                            $cell1,
                            $cell2
            );
            $t->data[] = $row;
        }

        if (!empty($status->allocations) && $status->is_published) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(
                    get_string('your_allocated_choice', ratingallocate_MOD_NAME));
            $allocation_html = '';
            foreach ($status->allocations as $allocation) {
                $allocation_html .= '<li>';
                $allocation_html .= format_string($allocation->{this_db\ratingallocate_choices::TITLE});
                $allocation_html .= '</li>';
            }
            $allocation_html = '<ul>' . $allocation_html . '</ul>';
            $cell2 = new html_table_cell($allocation_html);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        $status_summary = array();

        $error = function($text) { return $this->notification($text); };
        $warning = function($text) { return $this->notification($text); };
        $info = function($text) { return html_writer::span($text,'info'); };

        if (empty($status->available_choices))
             $this->add_notification(get_string('no_choice_to_rate', ratingallocate_MOD_NAME));
        // To early to rate
        if ($status->accesstimestart > $time) {
             $this->add_notification(get_string('too_early_to_rate', ratingallocate_MOD_NAME));
        }
        // to late to rate
        else if ($status->accesstimestop < $time) {
            // if results already published
            if ($status->is_published == true) {
                $this->add_notification(get_string('rating_is_over', ratingallocate_MOD_NAME));
            } else {
                $this->add_notification(get_string('results_not_yet_published', ratingallocate_MOD_NAME),'info');
            }
        }

     /*   // Links.
        if ($status->view_type == ratingallocate_choice_status::STUDENT_VIEW) {
            if ($status->canedit) {

                if (!$status->submission) {
                    $button_text = get_string('addsubmission', ratingallocate_MOD_NAME);
                } else {
                    $button_text = get_string('editsubmission', ratingallocate_MOD_NAME);
                }
                $o .= $this->output->box_start('generalbox submissionaction');
                $urlparams = array(
                                'id' => $status->coursemoduleid,
                                'action' => DSBUILDER_ACTION_EDIT_SUBMISSION
                );
                $o .= $this->output->single_button(new moodle_url('/mod/dsbuilder/view.php', $urlparams), $button_text, 'get');
                $o .= $this->output->box_start('boxaligncenter submithelp');
                $o .= get_string('editsubmission_help', ratingallocate_MOD_NAME);
                $o .= $this->output->box_end();
                $o .= $this->output->box_end();
            }
        }
        */

        $o .= $this->output->container_end();
        return $o;
    }

    public function format_text($text) {
        $output = '';

        $output .= $this->box_start();
        $output .= format_text($text);
        $output .= $this->box_end();

        return $output;
    }
    
    /**
     * Add a notification with the given $note to the renderer. 
     * This notification will be rendered in the header of the site.
     * @param $note Text to be viewed in the notification
     */
    public function add_notification($note, $classes = 'notifyproblem'){
        array_push($this->notifications, $this->notification($note, $classes));
    }


    /**
     * Output the ratingallocate algorithm control section (as long as the rating period is not over)
     */
    public function algorithm_control_tooearly() {
        $output = $this->heading(get_string('distribution_algorithm', ratingallocate_MOD_NAME), 2);

        // Rating period is not over, tell the user
        $note = get_string('too_early_to_distribute', ratingallocate_MOD_NAME);
        $output .= $this->notification($note);

        return $output;
    }

    /**
     * Output the ratingallocate algorithm control section (as soon as the rating period is over)
     */
    public function algorithm_control_ready() {
        global $PAGE;

        $starturl = new moodle_url($PAGE->url, array('action' => ACTION_START_DISTRIBUTION));

        // print button
        $output = $this->single_button($starturl->out(), get_string('start_distribution', ratingallocate_MOD_NAME), 'get', array('tooltip'=>get_string('start_distribution_explanation', ratingallocate_MOD_NAME)));

        return $output;
    }

    /**
     * Shows table containing information about the result of the distribution algorithm.
     *
     * @return HTML code
     */
    public function distribution_table_for_ratingallocate(ratingallocate $ratingallocate) {
        // Count the number of allocations with a specific rating
        $distributiondata = array();

        $memberships = $ratingallocate->get_allocations();

        foreach ($memberships as $id => $membership) {
            $rating = $membership->rating;
            if (key_exists($rating, $distributiondata)) {
                $distributiondata[$rating] ++;
            } else {
                $distributiondata[$rating] = 1;
            }
        }
        
        // get rating titles
        $titles = $this->get_options_titles(array_keys($distributiondata),$ratingallocate);

        // Although al indizes should be numeric or null, 
        // SORT_STRING cares for the correct comparison of null and 0
        krsort($distributiondata, SORT_STRING);
        $allocationrow = array();
        $allocationhead = array();
        foreach ($distributiondata as $rating => $count) {
            $cell = new html_table_cell();
            $cell->text = $count;
            $cell->attributes['class'] = 'ratingallocate_rating_' . $rating;
            $allocationrow[$rating] = $cell;

            $cell = new html_table_cell();
            $cell->text = $titles[$rating];
            $allocationhead[$rating] = $cell;
        }

        $cell = new html_table_cell();
        $usersinchoice = $ratingallocate->get_raters_in_course();
        $cell->text = count($usersinchoice) - count($memberships);
        $allocationrow[] = $cell;

        $cell = new html_table_cell();
        $cell->text = get_string('unassigned_users', ratingallocate_MOD_NAME);
        $allocationhead[] = $cell;

        $allocationtable = new html_table();
        $allocationtable->data = array($allocationrow);
        $allocationtable->head = $allocationhead;

        $output = $this->heading(get_string('distribution_table', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();
        $output .= html_writer::table($allocationtable);
        $output .= $this->box_end();

        return $output;
    }

    /**
     * Shows table containing information about the users' ratings
     * and their distribution over the choices (allocations).
     *
     * @return HTML code
     */
    public function ratings_table_for_ratingallocate($choices, $ratings, $users, $memberships, $ratingallocate) {

        // MAXDO maybe a setting in the future?
        // $config_show_names = get_config('mod_ratingallocate', 'show_names');
        $configshownames = true;

        // $choices = get_rateable_choices_for_ratingallocate($ratingallocateid);
        $choicenames = array();
        foreach ($choices as $choice) {
            $choicenames[$choice->id] = $choice->title;
        }

        // get rating titles
        $titles = $this->get_options_titles(array_map(function($rating) {return $rating->rating;},$ratings), $ratingallocate);
        
        // $ratings = all_ratings_for_rateable_choices_from_raters($ratingallocateid);
        $ratingscells = array();
        foreach ($ratings as $rating) {

            // Create a cell in the table for each rating
            if (!array_key_exists($rating->userid, $ratingscells)) {
                $ratingscells[$rating->userid] = array();
            }
            $cell = new html_table_cell();
            $cell->text = $titles[$rating->rating];
            $ratingscells[$rating->userid][$rating->choiceid] = $cell;
        }

        // If there is no rating from a user for a group,
        // put a 'no_rating_given' cell into the table.
        // $usersincourse = every_rater_in_course_by_ratingallocate($ratingallocateid);
        $usersincourse = $users;
        foreach ($usersincourse as $user) {
            if (!array_key_exists($user->id, $ratingscells)) {
                $ratingscells[$user->id] = array();
            }
            foreach ($choicenames as $ratingallocateid2 => $name) {
                if (!array_key_exists($ratingallocateid2, $ratingscells[$user->id])) {
                    $cell = new html_table_cell();
                    $cell->text = get_string('no_rating_given', ratingallocate_MOD_NAME);
                    $ratingscells[$user->id][$ratingallocateid2] = $cell;
                }
            }
            if ($configshownames) {
                // -1 is smaller than any id
                $ratingscells[$user->id][-1] = self::format_user_data($user);
            }
            // Sort ratings by choiceid to align them with the group names in the table
            ksort($ratingscells[$user->id]);
        }

        if ($configshownames) {
            // -1 is smaller than any id
            $choicenames[-1] = 'User';
        }
        // Sort group names by groupid
        ksort($choicenames);

        // Highlight ratings according to which users have been distributed
        // and count the number of such distributions
        // $memberships = memberships_per_ratingallocate($ratingallocateid);
        foreach ($memberships as $userid => $choices) {
            foreach ($choices as $choiceid => $rating) {
                if (array_key_exists($userid, $ratingscells)
                        && array_key_exists($choiceid, $ratingscells[$userid])) {

                    // Highlight the cell
                    $ratingscells[$userid][$choiceid]->attributes['class'] .= ' ratingallocate_member';
                }
            }
        }

        // The ratings table shows the users' ratings for the choices
        $ratingstable = new html_table();
        $ratingstable->data = $ratingscells;
        $ratingstable->head = $choicenames;
        $ratingstable->attributes['class'] = 'ratingallocate_ratings_table';

        $output = $this->heading(get_string('ratings_table', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();
        // $output .= '<p>' . get_string('view_ratings_table_explanation', ratingallocate_MOD_NAME) . '</p>';
        $output .= $this->box(html_writer::table($ratingstable), 'ratingallocate_ratings_box');
        $output .= $this->box_end();
        
        return $output;
    }
    
    /**
     * Formats the ratings
     * @param unknown $ratings
     * @return multitype:Ambigous <string, lang_string>
     */
    private function get_options_titles($ratings, ratingallocate $ratingallocate){
        $titles = array();
        $unique_ratings = array_unique($ratings);
        $options = $ratingallocate->get_options_titles($unique_ratings);
        foreach ($options as $id => $option){
            $titles[$id] = empty($option) ? get_string('no_rating_given', ratingallocate_MOD_NAME): get_string('rating_raw', ratingallocate_MOD_NAME, $option);
        }
        return $titles;
    }
    
    /**
     * Formats the rating
     * @param unknown $rating
     * @return multitype:Ambigous <string, lang_string>
     */
    private function get_option_title($rating, strategytemplate $strategy){
        $option = $strategy->translate_rating_to_titles($rating);
        return empty($option) ? get_string('no_rating_given', ratingallocate_MOD_NAME): get_string('rating_raw', ratingallocate_MOD_NAME, $option);
    }

    /**
     * Renders the button to show the ratings table
     */
    public function show_ratings_table_button() {
        global $PAGE;

        $tableurl = new moodle_url($PAGE->url, array('action' => ACTION_SHOW_ALLOC_TABLE));

        // Button to display information about the distribution and ratings
        $output = $this->single_button($tableurl->out(), get_string('show_table', ratingallocate_MOD_NAME), 'get');

        return $output;
    }

    /**
     * Format the users in the rating table
     */
    public function format_user_data($data) {
        global $CFG, $OUTPUT, $USER, $COURSE, $PAGE;

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'ratingallocate_user'));
        $output .= html_writer::start_tag('div', array('class' => 'name'));
        $output .= fullname($data);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'icons'));
        if (has_capability('moodle/user:viewdetails', $PAGE->context)) {
            $a = array();
            $a['href'] = new moodle_url('/user/view.php', array('id' => $data->id, 'course' => $COURSE->id));
            $a['title'] = get_string('viewprofile', 'core');
            $output .= html_writer::start_tag('a', $a);

            $src = array('src' => $OUTPUT->pix_url('i/user'), 'class' => 'icon', 'alt' => get_string('viewprofile', 'core'));
            $output .= html_writer::empty_tag('img', $src);

            $output .= html_writer::end_tag('a');
        }

        if ($CFG->messaging && has_capability('moodle/site:sendmessage', $PAGE->context) && $data->id != $USER->id) {
            $a = array();
            $a['href'] = new moodle_url('/message/index.php', array('id' => $data->id));
            $a['title'] = get_string('sendmessageto', 'core_message', fullname($data));
            $output .= html_writer::start_tag('a', $a);

            $src = array('src' => $OUTPUT->pix_url('t/email'), 'class' => 'icon');
            $src['alt'] = get_string('sendmessageto', 'core_message', fullname($data));
            $output .= html_writer::empty_tag('img', $src);

            $output .= html_writer::end_tag('a');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Utility function to add a row of data to a table with 2 columns. Modified
     * the table param and does not return a value
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }
}
