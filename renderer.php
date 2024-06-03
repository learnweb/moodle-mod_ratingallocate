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

use mod_ratingallocate\db as this_db;


defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/locallib.php');

/**
 * @package    mod_ratingallocate
 * @copyright  2014 M Schulze, T Reischmann, C Usener
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

        if (!empty($this->notifications)) {
            $o .= $this->output->box_start('box generalbox boxaligncenter');
            foreach ($this->notifications as $elem) {
                $o .= html_writer::div(format_text($elem));
            }
            $o .= $this->output->box_end();
        }
        return $o;
    }

    /**
     * @param $mform ratingallocate_strategyform
     * @return string
     * @throws coding_exception
     */
    public function render_ratingallocate_strategyform($mform) {
        $o = '';
        $o .= $this->heading(get_string('your_rating', RATINGALLOCATE_MOD_NAME), 2);
        $o .= $this->format_text($mform->get_strategy_description_header() . '<br/>' . $mform->describe_strategy());
        $o .= $mform->to_html();

        return $o;
    }

    /**
     * Displays the status of the allocation with buttons to start the algorithm, delete existing distributions,
     * and distribute unallocated users.
     *
     * @param $coursemoduleid
     * @param $status
     * @param $undistributeduserscount
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function render_ratingallocate_allocation_status($coursemoduleid, $status, $undistributeduserscount) {

        $output = '';
        $output .= $this->output->container_start('allocationstatustable');
        $output .= $this->heading(get_string('modify_allocation_group', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->box_start();

        $isdistributionrunning = $this->is_distribution_of_unallocated_users_running($coursemoduleid);

        // The instance is called ready if it is in one of the two following status.
        $ratingover = $status !== ratingallocate::DISTRIBUTION_STATUS_TOO_EARLY &&
            $status !== ratingallocate::DISTRIBUTION_STATUS_RATING_IN_PROGRESS;

        $starturl = new moodle_url($this->page->url, array('action' => ACTION_START_DISTRIBUTION));
        $deleteurl = new moodle_url($this->page->url, array('id' => $coursemoduleid, 'action' => ACTION_DELETE_ALL_RATINGS));

        // Get description dependent on status.
        $descriptionbaseid = 'modify_allocation_group_desc_';
        $description = get_string($descriptionbaseid . $status, RATINGALLOCATE_MOD_NAME);

        // Create start algorithm button.
        $button = new single_button($starturl, get_string('start_distribution', RATINGALLOCATE_MOD_NAME), 'get');
        // Enable only if the instance is ready and the algorithm may run manually.
        $button->disabled = !($ratingover) || $isdistributionrunning;
        $button->tooltip = get_string('start_distribution_explanation', RATINGALLOCATE_MOD_NAME);
        $button->add_action(new confirm_action(get_string('confirm_start_distribution', RATINGALLOCATE_MOD_NAME)));

        // Create delete all ratings button.
        $deletebutton = new single_button($deleteurl, get_string('delete_all_ratings', RATINGALLOCATE_MOD_NAME, 'get'));
        // Only allow deletion if new submission is possible and distribution currently not running.
        $deletebutton->disabled = $ratingover || $isdistributionrunning;
        $deletebutton->tooltip = get_string('delete_all_ratings_explanation', RATINGALLOCATE_MOD_NAME);
        $deletebutton->add_action(new confirm_action(get_string('confirm_delete_all_ratings', RATINGALLOCATE_MOD_NAME)));

        $table = new html_table();

        // Add status, buttons for manual and algorithmic allocation and delete all ratings button to the table.
        $this->add_table_row_triple($table,
            $description,
            $this->render($button) . '<br/>' . '<br/>' . $this->single_button(
                new moodle_url(
                    '/mod/ratingallocate/view.php',
                    array('id' => $coursemoduleid,
                    'action' => ACTION_MANUAL_ALLOCATION)),
                    get_string('manual_allocation_form',
                        RATINGALLOCATE_MOD_NAME),
                    'get',
                    array('disabled' => !$ratingover || $isdistributionrunning)
                ),
            $this->render($deletebutton)
        );

        if (has_capability('mod/ratingallocate:distribute_unallocated', context_module::instance($coursemoduleid))) {

            if ($ratingover && $undistributeduserscount != 0 && !$isdistributionrunning) {

                // Add empty row.
                $this->add_table_row_triple($table, '', '', '');

                $distributeunallocatedurleq = new moodle_url(
                    $this->page->url,
                    array('action' => ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY)
                );
                $buttondisteq = new single_button($distributeunallocatedurleq,
                    get_string('distributeequally', RATINGALLOCATE_MOD_NAME), 'get');
                $buttondisteq->class = 'ratingallocate_front_page_buttons';

                $buttondisteq->add_action(new confirm_action(
                    get_string('distribute_unallocated_equally_confirm', RATINGALLOCATE_MOD_NAME)));

                $distributeunallocatedurlfill = new moodle_url(
                    $this->page->url,
                    array('action' => ACTION_DISTRIBUTE_UNALLOCATED_FILL)
                );
                $buttondistfill = new single_button($distributeunallocatedurlfill,
                    get_string('distributefill', RATINGALLOCATE_MOD_NAME), 'get');
                $buttondistfill->class = 'ratingallocate_front_page_buttons';
                $buttondistfill->add_action(new confirm_action(
                    get_string('distribute_unallocated_fill_confirm', RATINGALLOCATE_MOD_NAME)));

                // Add Amount of users that are unallocated and buttons to allocate them manually.
                $this->add_table_row_triple($table,
                    get_string('unallocated_user_count',
                        RATINGALLOCATE_MOD_NAME,
                        ['count' => $undistributeduserscount]) . $this->help_icon('distribution_description', RATINGALLOCATE_MOD_NAME),
                    $this->render($buttondisteq),
                    $this->render($buttondistfill)
                );

            } else if ($isdistributionrunning) {

                // Add empty row.
                $this->add_table_row_triple($table, '', '', '');

                $this->add_table_row_triple($table,
                    get_string('unallocated_user_count', RATINGALLOCATE_MOD_NAME, ['count' => $undistributeduserscount]),
                    get_string('distribution_unallocated_already_running', RATINGALLOCATE_MOD_NAME),
                    ''
                );
            }
        }

        $output .= html_writer::table($table);
        $output .= $this->output->box_end();
        $output .= $this->output->container_end();
        return $output;
    }

    /**
     * Displays the status concerning publishing the allocation together with the buttons to publish the allocation
     * and to create groups.
     *
     * @param $ratingallocateid
     * @param $coursemoduleid
     * @param $status
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function render_ratingallocate_publish_allocation($ratingallocateid, $coursemoduleid, $status) {

        $output = '';
        $output .= $this->output->container_start('allocationstatustable');
        $output .= $this->heading(get_string('publish_allocation_group', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->box_start();

        $isready = $status === ratingallocate::DISTRIBUTION_STATUS_READY_ALLOC_STARTED;

        $table = new html_table();

        // Get description dependent on status.
        $descriptionbaseid = 'publish_allocation_group_desc_';
        $description = get_string($descriptionbaseid . $status, RATINGALLOCATE_MOD_NAME);

        if ($isready) {
            $description = $description . $this->help_icon('publish_allocation_group_desc_' . $status, RATINGALLOCATE_MOD_NAME);
        } else {
            $description = $this->format_text($description);
        }

        $this->add_table_row_triple($table,
            $description,
            $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
                'ratingallocateid' => $ratingallocateid,
                'action' => ACTION_PUBLISH_ALLOCATIONS)), get_string('publish_allocation', RATINGALLOCATE_MOD_NAME), 'get',
                array('disabled' => !$isready)),
            $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
                'ratingallocateid' => $ratingallocateid,
                'action' => ACTION_ALLOCATION_TO_GROUPING)), get_string('create_moodle_groups', RATINGALLOCATE_MOD_NAME), 'get')
        );

        $output .= html_writer::table($table);
        $output .= $this->output->box_end();
        $output .= $this->output->container_end();
        return $output;
    }

    /**
     * render current choice status
     * @param ratingallocate_choice_status $status
     * @return string
     */
    public function render_ratingallocate_choice_status(ratingallocate_choice_status $status) {
        $o = '';
        $o .= $this->output->container_start('choicestatustable');
        $o .= $this->output->heading(get_string('choicestatusheading', RATINGALLOCATE_MOD_NAME), 3);
        $time = time();

        $o .= $this->output->box_start('boxaligncenter choicesummarytable');
        $t = new html_table();

        $accesstimestart = $status->accesstimestart;
        if ($accesstimestart > $time) {
            // Access not yet available.
            $this->add_table_row_tuple($t, get_string('rating_begintime', RATINGALLOCATE_MOD_NAME), userdate($accesstimestart));
        }

        $duedate = $status->accesstimestop;
        if ($duedate > 0) {
            // Due date.
            $this->add_table_row_tuple($t, get_string('rating_endtime', RATINGALLOCATE_MOD_NAME), userdate($duedate));

            if ($accesstimestart > 0 && $accesstimestart < $time) {
                // Time remaining.
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('timeremaining', RATINGALLOCATE_MOD_NAME));
                if ($duedate - $time <= 0) {
                    $cell2 = new html_table_cell(get_string('rating_is_over', RATINGALLOCATE_MOD_NAME));
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
        if ($status->ispublished && $status->publishdate) {
            $this->add_table_row_tuple($t, get_string('publishdate', RATINGALLOCATE_MOD_NAME), userdate($status->publishdate));
        } else if ($status->publishdate) {
            $this->add_table_row_tuple($t, get_string('publishdate_estimated', RATINGALLOCATE_MOD_NAME),
                    userdate($status->publishdate));
        }

        if ($status->showdistributioninfo && $status->accesstimestop < $time) {
            // Print algorithm status and last run time.
            if ($status->algorithmstarttime) {
                $this->add_table_row_tuple($t, get_string('last_algorithm_run_date', RATINGALLOCATE_MOD_NAME),
                        userdate($status->algorithmstarttime));
            } else {
                $this->add_table_row_tuple($t, get_string('last_algorithm_run_date', RATINGALLOCATE_MOD_NAME), "-");
            }
            $this->add_table_row_tuple($t, get_string('last_algorithm_run_status', RATINGALLOCATE_MOD_NAME),
                    get_string('last_algorithm_run_status_' . $status->algorithmstatus, RATINGALLOCATE_MOD_NAME));
        }

        // Print own choices or full list of available choices.
        if (!empty($status->ownchoices) && $status->showuserinfo && $accesstimestart < $time) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('your_rating', RATINGALLOCATE_MOD_NAME));

            $choiceshtml = array();
            foreach ($status->ownchoices as $choice) {
                array_push($choiceshtml, format_string($choice->title) .
                        ' (' . s($this->get_option_title($choice->rating, $status->strategy)) . ')');
            }

            $cell2 = new html_table_cell(html_writer::alist($choiceshtml));
            $row->cells = array(
                    $cell1,
                    $cell2
            );
            $t->data[] = $row;
        } else if (!empty($status->availablechoices)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('rateable_choices', RATINGALLOCATE_MOD_NAME));

            $choiceshtml = array();
            foreach ($status->ownchoices as $choice) {
                array_push($choiceshtml, format_string($choice->title));
            }

            $cell2 = new html_table_cell(html_writer::alist($choiceshtml));
            $row->cells = array(
                    $cell1,
                    $cell2
            );
            $t->data[] = $row;
        }

        $hasrating = false;
        // Check if the user has rated at least one choice.
        foreach ($status->ownchoices as $choice) {
            if (object_property_exists($choice, 'ratingid') && $choice->ratingid != null) {
                $hasrating = true;
                break;
            }
        }

        if ($status->ispublished) {
            if (!empty($status->allocations)) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(
                        get_string('your_allocated_choice', RATINGALLOCATE_MOD_NAME));
                $allocationhtml = '';
                foreach ($status->allocations as $allocation) {
                    $allocationhtml .= html_writer::span(
                            format_string($allocation->{this_db\ratingallocate_choices::TITLE}),
                            'allocation tag tag-success');
                    $allocationhtml .= '<br/>' . format_text($allocation->{this_db\ratingallocate_choices::EXPLANATION});
                }
                $cell2 = new html_table_cell($allocationhtml);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            } else if (!empty($status->ownchoices)) {
                // Only print warning that user is not allocated if she has any rating.
                if ($hasrating) {
                    $row = new html_table_row();
                    $cell1 = new html_table_cell(
                            get_string('your_allocated_choice', RATINGALLOCATE_MOD_NAME));
                    $allocationhtml = html_writer::span(
                            get_string('you_are_not_allocated', RATINGALLOCATE_MOD_NAME),
                            'allocation tag tag-danger');
                    $cell2 = new html_table_cell($allocationhtml);
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Notifications if no choices exist or too few in comparison to strategy settings.
        if (empty($status->availablechoices)) {
            $this->add_notification(get_string('no_choice_to_rate', RATINGALLOCATE_MOD_NAME));
        } else if ($status->necessarychoices > count($status->availablechoices)) {
            if ($status->showdistributioninfo) {
                $this->add_notification(get_string('too_few_choices_to_rate', RATINGALLOCATE_MOD_NAME, $status->necessarychoices));
            }
        }

        // To early to rate.
        if ($status->accesstimestart > $time) {
            $this->add_notification(get_string('too_early_to_rate', RATINGALLOCATE_MOD_NAME), 'notifymessage');
        } else if ($status->accesstimestop < $time) { // Too late to rate.
            // If results already published.
            if ($status->ispublished == true) {
                if (count($status->allocations) > 0) {
                    $this->add_notification(get_string('rating_is_over_with_allocation', RATINGALLOCATE_MOD_NAME,
                            array_pop($status->allocations)->title), 'notifysuccess');
                } else if ($hasrating) {
                    $this->add_notification(get_string('rating_is_over_no_allocation', RATINGALLOCATE_MOD_NAME),
                            'notifyproblem');
                } else {
                    $this->add_notification(get_string('rating_is_over', RATINGALLOCATE_MOD_NAME),
                            'notifymessage');
                }
            } else {
                $this->add_notification(get_string('results_not_yet_published', RATINGALLOCATE_MOD_NAME), 'notifymessage');
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
        array_push($this->notifications, $this->notification($note, $classes, false));
    }

    /**
     * Output the ratingallocate modfify choices links
     */
    public function modify_choices_group($ratingallocateid, $coursemoduleid, $status) {
        $output = '';
        $output .= $this->heading(get_string('modify_choices_group', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->box_start();

        $starturl = new moodle_url($this->page->url->out(), array('action' => ACTION_SHOW_CHOICES));

        // Get description dependent on status.
        $descriptionbaseid = 'modify_choices_group_desc_';
        $description = get_string($descriptionbaseid . $status, RATINGALLOCATE_MOD_NAME);

        $output .= $this->format_text($description);

        $output .= html_writer::empty_tag('br', array());

        $button = new single_button($starturl, get_string('modify_choices', RATINGALLOCATE_MOD_NAME), 'get');
        $button->tooltip = get_string('modify_choices_explanation', RATINGALLOCATE_MOD_NAME);

        $output .= $this->render($button);

        $output .= $this->box_end();
        return $output;
    }

    /**
     * Output the ratingallocate modfify allocation
     */
    public function modify_allocation_group($ratingallocateid, $coursemoduleid,
            $status, $undistributeduserscount, $algorithmstatus, $runalgorithmbycron) {
        $isdistributionrunning = $this->is_distribution_of_unallocated_users_running($coursemoduleid);
        $output = '';
        $output .= $this->heading(get_string('modify_allocation_group', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->box_start();
        // The instance is called ready if it is in one of the two following status.
        $ratingover = $status !== ratingallocate::DISTRIBUTION_STATUS_TOO_EARLY &&
                $status !== ratingallocate::DISTRIBUTION_STATUS_RATING_IN_PROGRESS;

        $starturl = new moodle_url($this->page->url, array('action' => ACTION_START_DISTRIBUTION));
        $deleteurl = new moodle_url($this->page->url, array('id' => $coursemoduleid, 'action' => ACTION_DELETE_ALL_RATINGS));

        // Get description dependent on status.
        $descriptionbaseid = 'modify_allocation_group_desc_';
        $description = get_string($descriptionbaseid . $status, RATINGALLOCATE_MOD_NAME);

        $output .= $this->format_text($description);

        $output .= html_writer::empty_tag('br', array());

        $button = new single_button($starturl, get_string('start_distribution', RATINGALLOCATE_MOD_NAME), 'get');
        // Enable only if the instance is ready and the algorithm may run manually.
        $button->disabled = !($ratingover) || $isdistributionrunning;
        $button->tooltip = get_string('start_distribution_explanation', RATINGALLOCATE_MOD_NAME);
        $button->add_action(new confirm_action(get_string('confirm_start_distribution', RATINGALLOCATE_MOD_NAME)));

        $output .= $this->render($button);

        $output .= $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
                'action' => ACTION_MANUAL_ALLOCATION)), get_string('manual_allocation_form', RATINGALLOCATE_MOD_NAME), 'get',
                array('disabled' => !$ratingover || $isdistributionrunning));

        // Add delete all ratings button.
        $deletebutton = new single_button($deleteurl, get_string('delete_all_ratings', RATINGALLOCATE_MOD_NAME, 'get'));
        // Only allow deletion if new submission is possible and distribution currently not running.
        $deletebutton->disabled = $ratingover || $isdistributionrunning;
        $deletebutton->tooltip = get_string('delete_all_ratings_explanation', RATINGALLOCATE_MOD_NAME);
        $deletebutton->add_action(new confirm_action(get_string('confirm_delete_all_ratings', RATINGALLOCATE_MOD_NAME)));

        $output .= $this->render($deletebutton);

        if (has_capability('mod/ratingallocate:distribute_unallocated', context_module::instance($coursemoduleid))) {

            $output .= html_writer::start_div('ratingallocate_distribute_unallocated');

            $distributeunallocatedurl = new moodle_url($this->page->url, array('action' => ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY));

            $button = new single_button($distributeunallocatedurl,
                get_string('distributeequally', RATINGALLOCATE_MOD_NAME), 'get');
            // Enable only if the instance is ready and the algorithm may run manually.
            $button->disabled = !($ratingover) || $undistributeduserscount === 0 || $isdistributionrunning;
            $button->add_action(new confirm_action(
                get_string('distribute_unallocated_equally_confirm', RATINGALLOCATE_MOD_NAME)));

            $output .= $this->render($button);

            $distributeunallocatedurl = new moodle_url($this->page->url, array('action' => ACTION_DISTRIBUTE_UNALLOCATED_FILL));
            $button = new single_button($distributeunallocatedurl,
                get_string('distributefill', RATINGALLOCATE_MOD_NAME), 'get');
            // Enable only if the instance is ready, there are users to distribute and the algorithm may run manually.
            $button->disabled = !($ratingover) || $undistributeduserscount === 0 || $isdistributionrunning;
            $button->add_action(new confirm_action(
                get_string('distribute_unallocated_fill_confirm', RATINGALLOCATE_MOD_NAME)));

            $output .= $this->render($button);
            $output .= $this->help_icon('distribution_description', RATINGALLOCATE_MOD_NAME);
            if ($isdistributionrunning) {
                $output .= html_writer::div(
                        get_string('distribution_unallocated_already_running', RATINGALLOCATE_MOD_NAME),
                        'alert alert-info m-3'
                );
            }
            $output .= html_writer::end_div();
        }

        $output .= $this->box_end();
        return $output;
    }

    /**
     * Output the ratingallocate modfify allocation
     */
    public function publish_allocation_group($ratingallocateid, $coursemoduleid, $status) {
        $output = '';
        $output .= $this->heading(get_string('publish_allocation_group', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->box_start();
        $isready = $status === ratingallocate::DISTRIBUTION_STATUS_READY_ALLOC_STARTED;

        // Get description dependent on status.
        $descriptionbaseid = 'publish_allocation_group_desc_';
        $description = get_string($descriptionbaseid . $status, RATINGALLOCATE_MOD_NAME);

        $output .= $this->format_text($description);

        $output .= html_writer::empty_tag('br', array());

        $output .= $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
                'ratingallocateid' => $ratingallocateid,
                'action' => ACTION_PUBLISH_ALLOCATIONS)), get_string('publish_allocation', RATINGALLOCATE_MOD_NAME), 'get',
                array('disabled' => !$isready));

        $output .= $this->single_button(new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid,
                'ratingallocateid' => $ratingallocateid,
                'action' => ACTION_ALLOCATION_TO_GROUPING)), get_string('create_moodle_groups', RATINGALLOCATE_MOD_NAME), 'get');

        $output .= $this->box_end();
        return $output;
    }

    /**
     * Output the ratingallocate modfify allocation
     */
    public function reports_group($ratingallocateid, $coursemoduleid, $status, $context, $action = '') {
        $output = '';
        $output .= $this->heading(get_string('reports_group', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->box_start();

        $output .= $this->output->single_select(
            new moodle_url('/mod/ratingallocate/view.php', array('id' => $coursemoduleid)),
            'action', array(
                ACTION_SHOW_RATINGS_AND_ALLOCATION_TABLE => get_string('show_table', RATINGALLOCATE_MOD_NAME),
                ACTION_SHOW_ALLOCATION_TABLE => get_string('show_allocation_table', RATINGALLOCATE_MOD_NAME),
                ACTION_SHOW_STATISTICS => get_string('show_allocation_statistics', RATINGALLOCATE_MOD_NAME)
            ),
            $action
        );

        /* TODO: File not readable
        $output .= html_writer::empty_tag('br', array());

        if (has_capability('mod/ratingallocate:export_ratings', $context)) {
            $output .= $this->action_link(new moodle_url(
                '/mod/ratingallocate/solver/export_lp_solve.php', array('id' => $coursemoduleid,
                'ratingallocateid' => $ratingallocateid)), get_string('download_problem_mps_format', RATINGALLOCATE_MOD_NAME));
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
        global $CFG;
        require_once($CFG->libdir . '/tablelib.php');

        $starturl = new moodle_url($this->page->url, array('action' => ACTION_EDIT_CHOICE));
        echo $this->output->single_button($starturl, get_string('newchoice', 'mod_ratingallocate'), 'get');

        $uploadcsvurl = new moodle_url($this->page->url, array('action' => ACTION_UPLOAD_CHOICES));
        echo $this->output->single_button($uploadcsvurl, get_string('csvupload', 'ratingallocate'), 'get', array(
            'tooltip' => get_string('csvupload_explanation', 'ratingallocate')
        ));

        // Set up the table.
        $table = new \flexible_table('show_ratingallocate_options');
        $table->define_baseurl($this->page->url);
        if ($choicesmodifiably) {
            $table->define_columns(array('title', 'explanation', 'maxsize', 'active', 'usegroups', 'tools'));
            $table->define_headers(array(get_string('choice_table_title', 'mod_ratingallocate'),
                    get_string('choice_table_explanation', 'mod_ratingallocate'),
                    get_string('choice_table_maxsize', 'mod_ratingallocate'),
                    get_string('choice_table_active', 'mod_ratingallocate'),
                    get_string('choice_table_usegroups', 'mod_ratingallocate'),
                    get_string('choice_table_tools', 'mod_ratingallocate')));
        } else {
            $table->define_columns(array('title', 'explanation', 'maxsize', 'active', 'usegroups'));
            $table->define_headers(array(get_string('choice_table_title', 'mod_ratingallocate'),
                    get_string('choice_table_explanation', 'mod_ratingallocate'),
                    get_string('choice_table_maxsize', 'mod_ratingallocate'),
                    get_string('choice_table_usegroups', 'mod_ratingallocate'),
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
            $explanation = format_text($choice->{this_db\ratingallocate_choices::EXPLANATION});
            $attachments = $ratingallocate->get_file_attachments_for_choice($choice->id);
            if ($attachments) {
                $explanation .= $this->render_attachments($attachments, true);
            }
            $row[] = $explanation;
            $row[] = $choice->{this_db\ratingallocate_choices::MAXSIZE};
            if ($choice->{this_db\ratingallocate_choices::ACTIVE}) {
                $row[] = get_string('yes');
            } else {
                $row[] = get_string('no');
            }
            if ($choice->{this_db\ratingallocate_choices::USEGROUPS}) {
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
     * Render file attachments for a certain choice entry
     * @param array $files Array of file attachments
     * @param bool $break Insert a line break on the first file attachment
     * @return string HTML for the attachments
     */
    public function render_attachments($files, $break = false) {
        $entries = array();
        foreach ($files as $f) {
            $filename = $f->get_filename();
            $url = moodle_url::make_pluginfile_url(
                    $f->get_contextid(),
                    $f->get_component(),
                    $f->get_filearea(),
                    $f->get_itemid(),
                    $f->get_filepath(),
                    $f->get_filename(),
                    false);
            $a = array(
                    'href' => $url,
                    'title' => $filename,
            );

            $entry = '';
            if (!$break) {
                // Skip first line break; update flag for any subsequent attachments.
                $break = true;
            } else {
                $entry .= html_writer::empty_tag('br');
            }
            $entry .= html_writer::start_tag('a', $a);
            $entry .= $this->output->image_icon('t/right', $filename, 'moodle', array('title' => 'Download file'));
            $entry .= $filename;
            $entry .= html_writer::end_tag('a');
            $entries[] = $entry;
        }
        return implode($entries);
    }

    /**
     * Renders tools for a certain choice entry
     * @param integer $id id of the choice
     * @param boolean $active states if the choice is active
     * @param string $title title of the choice
     * @return string html of the tools for a specific choice
     */
    private function render_tools($id, $active, $title) {
        $tools = $this->format_icon_link(ACTION_EDIT_CHOICE, $id, 't/edit', get_string('edit_choice', RATINGALLOCATE_MOD_NAME));
        if ($active) {
            $tools .= $this->format_icon_link(ACTION_DISABLE_CHOICE, $id, 't/hide', get_string('disable'));
        } else {
            $tools .= $this->format_icon_link(ACTION_ENABLE_CHOICE, $id, 't/show', get_string('enable'));
        }
        $tools .= $this->format_icon_link(ACTION_DELETE_CHOICE, $id, 't/delete',
                get_string('delete_choice', RATINGALLOCATE_MOD_NAME),
                new \confirm_action(get_string('deleteconfirm', RATINGALLOCATE_MOD_NAME, $title)));

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
        $url = $this->page->url;

        return $this->output->action_icon(new \moodle_url($url,
                        array('action' => $action, 'choiceid' => $choice, 'sesskey' => sesskey())),
                        new \pix_icon($icon, $alt, 'moodle', array('title' => $alt)),
                        $confirm, array('title' => $alt)) . ' ';
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
        return $this->output->footer();
    }

    /**
     * Shows table containing information about the result of the distribution algorithm.
     *
     * @return string html code representing the distribution table
     */
    public function statistics_table_for_ratingallocate(ratingallocate $ratingallocate) {
        // Count the number of allocations with a specific rating.
        $distributiondata = array();

        $memberships = $ratingallocate->get_allocations();

        foreach ($memberships as $id => $membership) {
            $rating = $membership->rating;
            if (key_exists($rating, $distributiondata)) {
                $distributiondata[$rating]++;
            } else {
                $distributiondata[$rating] = 1;
            }
        }

        // Get rating titles.
        $titles = $this->get_options_titles(array_keys($distributiondata), $ratingallocate);

        // Although all indices should be numeric or null,
        // SORT_STRING cares for the correct comparison of null and 0.
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
        $cell->text = count($ratingallocate->get_undistributed_users());
        $allocationrow[] = $cell;

        $cell = new html_table_cell();
        $cell->text = get_string('unassigned_users', RATINGALLOCATE_MOD_NAME);
        $allocationhead[] = $cell;

        $allocationtable = new html_table();
        $allocationtable->data = array($allocationrow);
        $allocationtable->head = $allocationhead;

        $output = $this->heading(get_string('allocation_statistics', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->box_start();
        // Get the number of users that have placed a vote.
        $activeraters = $ratingallocate->get_number_of_active_raters();
        $notrated = count($usersinchoice) - $activeraters;
        if (count($distributiondata) == 0) {
            $output .= $this->format_text(get_string('allocation_statistics_description_no_alloc',
                    RATINGALLOCATE_MOD_NAME,
                    array('notrated' => $notrated, 'rated' => $activeraters)));
        } else {
            $output .= $this->format_text(get_string('allocation_statistics_description', RATINGALLOCATE_MOD_NAME,
                    array('users' => $distributiondata[max(array_keys($distributiondata))],
                            'usersinchoice' => count($usersinchoice),
                            'total' => count($memberships),
                            'notrated' => $notrated,
                            'rated' => $activeraters,
                            'rating' => $titles[max(array_keys($distributiondata))],
                            'unassigned' => count($ratingallocate->get_undistributed_users()))));
            $output .= html_writer::table($allocationtable);
        }
        $output .= $this->box_end();

        return $output;
    }

    /**
     * Shows table containing information about the allocation of users.
     *
     * @return string html code representing ratings table
     */
    public function allocation_table_for_ratingallocate($ratingallocate) {

        // Create and set up the flextable for ratings and allocations.
        $table = new mod_ratingallocate\allocations_table($ratingallocate);
        $table->setup_table();

        // The rest must be done through output buffering due to the way flextable works.
        ob_start();
        $table->build_table_by_sql();
        $tableoutput = ob_get_contents();
        ob_end_clean();

        $output = $this->heading(get_string('allocations_table', RATINGALLOCATE_MOD_NAME), 2);
        $output .= $this->format_text(get_string('allocation_table_description',
                RATINGALLOCATE_MOD_NAME));
        $output .= $this->box_start();
        $output .= $this->box($tableoutput);
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

        // Get rating titles.
        $titles = $this->get_options_titles(array_map(function($rating) {
            return $rating->rating;
        }, $ratings), $ratingallocate);

        // Create and set up the flextable for ratings and allocations.
        $table = new mod_ratingallocate\ratings_and_allocations_table($this, $titles, $ratingallocate);
        $table->setup_table($choices, false, false);

        // The rest must be done through output buffering due to the way flextable works.
        ob_start();
        $table->build_table_by_sql($ratings, $memberships);
        $tableoutput = ob_get_contents();
        ob_end_clean();

        $output = $this->heading(get_string('ratings_table', RATINGALLOCATE_MOD_NAME), 2);
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
    private function get_options_titles($ratings, ratingallocate $ratingallocate) {
        $titles = array();
        $uniqueratings = array_unique($ratings);
        $options = $ratingallocate->get_options_titles($uniqueratings);
        foreach ($options as $id => $option) {
            $titles[$id] = empty($option) ? get_string('no_rating_given', RATINGALLOCATE_MOD_NAME) :
                    get_string('rating_raw', RATINGALLOCATE_MOD_NAME, $option);
        }
        return $titles;
    }

    /**
     * Formats the rating
     * @param unknown $rating
     * @return multitype:Ambigous <string, lang_string>
     */
    private function get_option_title($rating, strategytemplate $strategy) {
        $option = $strategy->translate_rating_to_titles($rating);
        return empty($option) ? get_string('no_rating_given', RATINGALLOCATE_MOD_NAME) :
                get_string('rating_raw', RATINGALLOCATE_MOD_NAME, $option);
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

    /**
     * Utility function to add a row of data to a table with 3 columns. Modified
     * the table param and does not return a value
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @param string $third The third column text
     * @return void
     */
    private function add_table_row_triple(html_table $table, $first, $second, $third) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell1->attributes['class'] = 'ratingallocate_front_page_table_1';
        $cell2 = new html_table_cell($second);
        $cell2->attributes['class'] = 'ratingallocate_front_page_table_23';
        $cell3 = new html_table_cell($third);
        $cell3->attributes['class'] = 'ratingallocate_front_page_table_23';
        $row->cells = array($cell1, $cell2, $cell3);
        $table->data[] = $row;
    }

    /**
     * Method to check if an adhoc task for distributing unallocated users has already been queued.
     *
     * @return bool true if an adhoc task for the current course module can be found, false otherwise
     */
    private function is_distribution_of_unallocated_users_running(int $coursemoduleid): bool {
        $queuedtasks = \core\task\manager::get_adhoc_tasks(\mod_ratingallocate\task\distribute_unallocated_task::class);
        $taskofcurrentmodule = array_filter($queuedtasks, fn($task) => intval($task->get_custom_data()->cmid) === $coursemoduleid);
        return !empty($taskofcurrentmodule);
    }
}
