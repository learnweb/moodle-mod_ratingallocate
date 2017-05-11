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
 * @copyright  2014 M Schulze, T Reischmann, C Usener
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/locallib.php');

class mod_ratingallocate_renderer extends plugin_renderer_base {

    /**
     * @var array rendered notifications to output for handle_view()
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

        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $heading = format_string($header->ratingallocate->name, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);

        if ($header->showintro) {
            $introtext = format_module_intro('ratingallocate', $header->ratingallocate,
                    $header->coursemoduleid);
            if ($introtext) {
                $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
                $o .= $introtext;
                $o .= $this->output->box_end();
            }
        }
        if (!empty($this->notifications)) {
            $o .= $this->output->box_start('box generalbox boxaligncenter');
            foreach ($this->notifications as $elem) {
                $o .= html_writer::div(format_text($elem));
            }
            $o .= $this->output->box_end();
        }
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

        if ($status->show_distribution_info && $status->accesstimestop < $time) {
            // Print algorithm status and last run time
            if ($status->algorithmstarttime) {
                $this->add_table_row_tuple($t, get_string('last_algorithm_run_date', ratingallocate_MOD_NAME), userdate($status->algorithmstarttime));
            } else {
                $this->add_table_row_tuple($t, get_string('last_algorithm_run_date', ratingallocate_MOD_NAME), "-");
            }
            $this->add_table_row_tuple($t, get_string('last_algorithm_run_status', ratingallocate_MOD_NAME),
                get_string('last_algorithm_run_status_' . $status->algorithmstatus, ratingallocate_MOD_NAME));
        }

        //print available choices if no choice form is displayed
        if(!empty($status->available_choices)) {
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

        // Notifications if no choices exist or too few in comparison to strategy settings.
        if (empty($status->available_choices)) {
            $this->add_notification(get_string('no_choice_to_rate', ratingallocate_MOD_NAME));
        } else if ($status->necessary_choices > count($status->available_choices)) {
            if ($status->show_distribution_info) {
                $this->add_notification(get_string('too_few_choices_to_rate', ratingallocate_MOD_NAME, $status->necessary_choices));
            }
        }

        // To early to rate
        if ($status->accesstimestart > $time) {
             $this->add_notification(get_string('too_early_to_rate', ratingallocate_MOD_NAME), 'notifymessage');
        }
        // to late to rate
        else if ($status->accesstimestop < $time) {
            // if results already published
            if ($status->is_published == true) {
                $this->add_notification(get_string('rating_is_over', ratingallocate_MOD_NAME), 'notifymessage');
            } else {
                $this->add_notification(get_string('results_not_yet_published', ratingallocate_MOD_NAME), 'notifymessage');
            }
        }

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
     * @param $note string to be viewed in the notification
     * @param $classes string class for the formatting of the notification
     */
    public function add_notification($note, $classes = 'notifyproblem') {
        array_push($this->notifications, $this->notification($note, $classes));
    }

    /**
     * Output the ratingallocate modfify choices links
     */
    public function modify_choices_group($ratingallocateid, $coursemoduleid, $status) {
        global $PAGE;
        $output = '';
        $output .= $this->heading(get_string('modify_choices_group', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();

        $starturl = new moodle_url($PAGE->url->out(), array('action' => ACTION_SHOW_CHOICES));

        // Get description dependent on status
        $descriptionbaseid = 'modify_choices_group_desc_';
        $description = get_string($descriptionbaseid.$status, ratingallocate_MOD_NAME);

        $output .= $this->format_text($description);

        $output .= html_writer::empty_tag('br', array());

        $button = new single_button($starturl, get_string('modify_choices', ratingallocate_MOD_NAME), 'get');
        $button->tooltip = get_string('modify_choices_explanation', ratingallocate_MOD_NAME);

        $output .= $this->render($button);

        $output .= $this->box_end();
        return $output;
    }

    /**
     * Output the ratingallocate modfify allocation
     */
    public function modify_allocation_group($ratingallocateid, $coursemoduleid, $status, $algorithmstatus, $runalgorithmbycron) {
        global $PAGE;
        $output = '';
        $output .= $this->heading(get_string('modify_allocation_group', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();
        // The instance is called ready if it is in one of the two following status.
        $ratingover = $status !== ratingallocate::DISTRIBUTION_STATUS_TOO_EARLY &&
            $status !== ratingallocate::DISTRIBUTION_STATUS_RATING_IN_PROGRESS;

        $starturl = new moodle_url($PAGE->url, array('action' => ACTION_START_DISTRIBUTION));

        // Get description dependent on status
        $descriptionbaseid = 'modify_allocation_group_desc_';
        $description = get_string($descriptionbaseid.$status, ratingallocate_MOD_NAME);

        $output .= $this->format_text($description);

        $output .= html_writer::empty_tag('br', array());

        $button = new single_button($starturl, get_string('start_distribution', ratingallocate_MOD_NAME), 'get');
        // Enable only if the instance is ready and the algorithm may run manually
        $button->disabled = !($ratingover);
        $button->tooltip = get_string('start_distribution_explanation', ratingallocate_MOD_NAME);
        $button->add_action(new confirm_action(get_string('confirm_start_distribution', ratingallocate_MOD_NAME)));

        $output .= $this->render($button);

        $output .= $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
            'ratingallocateid' => $ratingallocateid,
            'action' => ACTION_MANUAL_ALLOCATION)), get_string('manual_allocation_form', ratingallocate_MOD_NAME), 'get',
            array('disabled' => !$ratingover));

        $output .= $this->box_end();
        return $output;
    }

    /**
     * Output the ratingallocate modfify allocation
     */
    public function publish_allocation_group($ratingallocateid, $coursemoduleid, $status) {
        $output = '';
        $output .= $this->heading(get_string('publish_allocation_group', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();
        $isready = $status === ratingallocate::DISTRIBUTION_STATUS_READY_ALLOC_STARTED;

        // Get description dependent on status
        $descriptionbaseid = 'publish_allocation_group_desc_';
        $description = get_string($descriptionbaseid.$status, ratingallocate_MOD_NAME);

        $output .= $this->format_text($description);

        $output .= html_writer::empty_tag('br', array());

        $output .= $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
            'ratingallocateid' => $ratingallocateid,
            'action' => ACTION_PUBLISH_ALLOCATIONS)), get_string('publish_allocation', ratingallocate_MOD_NAME), 'get',
            array('disabled' => !$isready));

        $output .= $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
            'ratingallocateid' => $ratingallocateid,
            'action' => ACTION_ALLOCATION_TO_GROUPING)), get_string('create_moodle_groups', ratingallocate_MOD_NAME), 'get');

        $output .= $this->box_end();
        return $output;
    }

    /**
     * Output the ratingallocate modfify allocation
     */
    public function reports_group($ratingallocateid, $coursemoduleid, $status, $context) {
        global $PAGE;
        $output = '';
        $output .= $this->heading(get_string('reports_group', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();

        $tableurl = new moodle_url($PAGE->url, array('action' => ACTION_SHOW_ALLOC_TABLE));

        // Button with link to display information about the allocations and ratings
        $output .= $this->single_button($tableurl, get_string('show_table', ratingallocate_MOD_NAME));

        $tableurl = new moodle_url($PAGE->url, array('action' => ACTION_SHOW_STATISTICS));

        // Buttton with link to display statistical information about the allocations
        $output .= $this->single_button($tableurl, get_string('show_allocation_statistics', ratingallocate_MOD_NAME));

        /* TODO: File not readable
        $output .= html_writer::empty_tag('br', array());

        if (has_capability('mod/ratingallocate:export_ratings', $context)) {
            $output .= $this->action_link(new moodle_url('/mod/ratingallocate/solver/export_lp_solve.php', array('id' => $coursemoduleid,
                'ratingallocateid' => $ratingallocateid)), get_string('download_problem_mps_format', ratingallocate_MOD_NAME));
        }*/

        $output .= $this->box_end();
        return $output;
    }

    /**
     * Shows table containing information about the result of the distribution algorithm.
     *
     * @return HTML code
     */
    public function ratingallocate_show_choices_table(ratingallocate $ratingallocate, $choicesmodifiably) {
        global $OUTPUT, $CFG, $PAGE;
        require_once($CFG->libdir . '/tablelib.php');

        $starturl = new moodle_url($PAGE->url->out(), array('action' => ACTION_EDIT_CHOICE));
        // Set up the table.
        echo $OUTPUT->single_button($starturl->out(), get_string('newchoice', 'mod_ratingallocate'), 'get');
        $table = new \flexible_table('show_ratingallocate_options');
        $table->define_baseurl($PAGE->url);
        if ($choicesmodifiably) {
            $table->define_columns(array('title', 'explanation', 'maxsize', 'active', 'tools'));
            $table->define_headers(array(get_string('choice_table_title', 'mod_ratingallocate'),
                get_string('choice_table_explanation', 'mod_ratingallocate'),
                get_string('choice_table_maxsize', 'mod_ratingallocate'),
                get_string('choice_table_active', 'mod_ratingallocate'),
                get_string('choice_table_tools', 'mod_ratingallocate')));
        } else {
            $table->define_columns(array('title', 'explanation', 'maxsize', 'active'));
            $table->define_headers(array(get_string('choice_table_title', 'mod_ratingallocate'),
                get_string('choice_table_explanation', 'mod_ratingallocate'),
                get_string('choice_table_maxsize', 'mod_ratingallocate'),
                get_string('choice_table_tools', 'mod_ratingallocate')));
        }
        $table->set_attribute('id', 'mod_ratingallocateshowoptions');
        $table->set_attribute('class', 'admintable generaltable');
        $table->setup();

        $choices = $ratingallocate->get_choices();

        // When there are no choices, don't print the table.
        if (count($choices) === 0) {
            return;
        }
        foreach ($choices as $idx => $choice) {
            $row = array();
            $class = '';
            $row[] = $choice->{this_db\ratingallocate_choices::TITLE};
            $row[] = $choice->{this_db\ratingallocate_choices::EXPLANATION};
            $row[] = $choice->{this_db\ratingallocate_choices::MAXSIZE};
            if ($choice->{this_db\ratingallocate_choices::ACTIVE}) {
                $row[] = get_string('yes');
            } else {
                $row[] = get_string('no');
            }
            if ($choicesmodifiably) {
                $row[] = $this->render_tools($idx, $choice->{this_db\ratingallocate_choices::ACTIVE},
                    $choice->{this_db\ratingallocate_choices::TITLE});
            }
            if (!$choice->{this_db\ratingallocate_choices::ACTIVE}) {
                $class = 'dimmed_text';
            }

            $table->add_data($row, $class);
        }

        $table->finish_output();
    }

    /**
     * Renders tools for a certain choice entry
     * @param integer $id id of the choice
     * @param boolean $active states if the choice is active
     * @param string $title title of the choice
     * @return string html of the tools for a specific choice
     */
    private function render_tools($id, $active, $title) {
        $tools = $this->format_icon_link(ACTION_EDIT_CHOICE, $id, 't/edit', get_string('edit_choice', ratingallocate_MOD_NAME));
        if ($active) {
            $tools .= $this->format_icon_link(ACTION_DISABLE_CHOICE, $id, 't/hide', get_string('disable'));
        } else {
            $tools .= $this->format_icon_link(ACTION_ENABLE_CHOICE, $id, 't/show', get_string('enable'));
        }
        $tools .= $this->format_icon_link(ACTION_DELETE_CHOICE, $id, 't/delete', get_string('delete_choice', ratingallocate_MOD_NAME),
            new \confirm_action(get_string('deleteconfirm', ratingallocate_MOD_NAME, $title)));

        return $tools;
    }
    /**
     * Util function for writing an action icon link
     *
     * @param string $action URL parameter to include in the link
     * @param string $choice URL parameter to include in the link
     * @param string $icon The key to the icon to use (e.g. 't/up')
     * @param string $alt The string description of the link used as the title and alt text
     * @return string The icon/link
     */
    private function format_icon_link($action, $choice, $icon, $alt, $confirm = null) {
        global $OUTPUT, $PAGE;

        $url = $PAGE->url;

        return $OUTPUT->action_icon(new \moodle_url($url,
            array('action' => $action, 'choiceid' => $choice, 'sesskey' => sesskey())),
            new \pix_icon($icon, $alt, 'moodle', array('title' => $alt)),
            $confirm , array('title' => $alt)) . ' ';
    }

    /**
     * Finish the page (Since the header renders the notifications, it needs to be rendered after the actions)
     *
     * @return string
     */
    public function render_header($ratingallocate, $context, $coursemodid) {
        $headerinfo = new ratingallocate_header($ratingallocate, $context, true,
            $coursemodid);
        return $this->render($headerinfo);
    }

    /**
     * Write the page footer
     *
     * @return string
     */
    public function render_footer() {
        global $OUTPUT;
        return $OUTPUT->footer();
    }

    /**
     * Shows table containing information about the result of the distribution algorithm.
     *
     * @return string html code representing the distribution table
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

        $output = $this->heading(get_string('allocation_statistics', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();
        if (count($distributiondata) == 0) {
            $output .= $this->format_text(get_string('allocation_statistics_description_no_alloc',
                ratingallocate_MOD_NAME,
                array('unassigned' => count($usersinchoice) - count($memberships))));
        } else {
            $output .= $this->format_text(get_string('allocation_statistics_description', ratingallocate_MOD_NAME,
            array('users' => $distributiondata[max(array_keys($distributiondata))], 'total' => count($memberships),
                'rating' => $titles[max(array_keys($distributiondata))],
                'unassigned' => count($usersinchoice) - count($memberships))));
            $output .= html_writer::table($allocationtable);
        }
        $output .= $this->box_end();

        return $output;
    }

    /**
     * Shows table containing information about the users' ratings
     * and their distribution over the choices (allocations).
     *
     * @return string html code representing ratings table
     */
    public function ratings_table_for_ratingallocate($choices, $ratings, $users, $memberships, $ratingallocate) {

        // get rating titles
        $titles = $this->get_options_titles(array_map(function($rating) {return $rating->rating;},$ratings), $ratingallocate);

        // Create and set up the flextable for ratings and allocations.
        $table = new mod_ratingallocate\ratings_and_allocations_table($this, $titles, $ratingallocate);
        $table->setup_table($choices, false, false);

        // The rest must be done through output buffering due to the way flextable works.
        ob_start();
        $table->build_table_by_sql($ratings, $memberships);
        $tableoutput = ob_get_contents();
        ob_end_clean();

        $output = $this->heading(get_string('ratings_table', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();
        $output .= $this->box($tableoutput);
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
