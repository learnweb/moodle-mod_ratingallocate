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
     * Render the header.
     *
     * @param ratingallocate_header $header
     * @return string
     */
    public function render_ratingallocate_header(ratingallocate_header $header) {
        $o = '';

        $this->page->set_title(get_string('pluginname', ratingallocate_MOD_NAME));
        $this->page->set_heading($this->page->course->fullname);
        // $this->page->requires->css('/mod/ratingallocate/style/ratingallocate.css');

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
     * nur allgemeine Informationen
     * @param ratingallocate $ratingallocate
     * @return unknown
     */
    public function format_ratingallocate(ratingallocate $ratingallocate) {
        global $COURSE;

        $output = $this->heading(format_string($ratingallocate->name), 2);

        if ($ratingallocate->intro) {
            $cm = get_coursemodule_from_instance('ratingallocate', $ratingallocate->id, $COURSE->id, false, MUST_EXIST);
            $output .= $this->box(format_module_intro('ratingallocate', $ratingallocate, $cm->id), 'generalbox', 'intro');
        }

        $output .= $this->box_start();

        $a = new stdClass();
        $begin = userdate($ratingallocate->accesstimestart);
        $a->begin = '<span class="ratingallocate_highlight">' . $begin . '</span>';
        $end = userdate($ratingallocate->accesstimestop);
        $a->end = '<span class="ratingallocate_highlight">' . $end . '</span>';
        $note = get_string('show_rating_period', ratingallocate_MOD_NAME, $a);
        $output .= '<p>' . $note . '</p>';

        $output .= $this->box_end();

        return $output;
    }

    public function format_text($text) {

        $output = $this->box_start();
        $output .= $text;
        $output .= $this->box_end();

        return $output;
    }

    /**
     * Output the rating form section (as long as the rating period has not yet started)
     */
    public function user_rating_form_tooearly(ratingallocate $ratingallocate) {
        $output = $this->notification(get_string('too_early_to_rate', ratingallocate_MOD_NAME));

        $choices = $ratingallocate->get_rateable_choices();

        if (count($choices) > 0) {
            $output .= $this->heading(get_string('rateable_choices', ratingallocate_MOD_NAME), 2);
            foreach ($choices as $choice) {
                $output .= $this->format_choice($choice, true);
                $output .= '<hr />';
            }
        }

        return $output;
    }

    /**
     * Output the rating form section (as long as the rating period has already finished)
     */
    public function user_rating_form_finished($allocations) {

        $output = $this->notification(get_string('rating_is_over', ratingallocate_MOD_NAME));

        if (count($allocations) > 0) {
            $output .= $this->heading(get_string('your_allocated_choice', ratingallocate_MOD_NAME), 2);
            foreach ($allocations as $alloc) {
                $output .= $this->format_choice($alloc, true);
            }
        }

        return $output;
    }

    public function format_publishdate($publishdate) {

        $output = $this->box_start();
        $output .= '<p>' . get_string('publishdate_explain', ratingallocate_MOD_NAME, userdate($publishdate)) . '</p>';
        $output .= $this->box_end();

        return $output;
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

        $starturl = new moodle_url($PAGE->url, array('action' => RATING_ALLOC_ACTION_START));

        $output = $this->heading(get_string('distribution_algorithm', ratingallocate_MOD_NAME), 2);

        // print button
        $output .= $this->box_start();
        $output .= '<p>' . get_string('start_distribution_explanation', ratingallocate_MOD_NAME) . '</p>';
        $output .= $this->box_end();
        $output .= $this->single_button($starturl->out(), get_string('start_distribution', ratingallocate_MOD_NAME), 'get');

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

        $memberships = $ratingallocate->get_all_allocations();

        foreach ($memberships as $userid => $choice) {
            $rating = array_shift($choice);
            if (key_exists($rating, $distributiondata)) {
                $distributiondata[$rating] ++;
            } else {
                $distributiondata[$rating] = 1;
            }
        }

        krsort($distributiondata);
        $allocationrow = array();
        $allocationhead = array();
        foreach ($distributiondata as $rating => $count) {
            $cell = new html_table_cell();
            $cell->text = $count;
            $cell->attributes['class'] = 'ratingallocate_rating_' . $rating;
            $allocationrow[$rating] = $cell;

            $cell = new html_table_cell();
            $cell->text = get_string('rating_raw', ratingallocate_MOD_NAME, $rating);
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
    public function ratings_table_for_ratingallocate($choices, $ratings, $users, $memberships) {

        // MAXDO maybe a setting in the future?
        // $config_show_names = get_config('mod_ratingallocate', 'show_names');
        $configshownames = true;

        // $choices = get_rateable_choices_for_ratingallocate($ratingallocateid);
        $choicenames = array();
        foreach ($choices as $choice) {
            $choicenames[$choice->id] = $choice->title;
        }

        // $ratings = all_ratings_for_rateable_choices_from_raters($ratingallocateid);
        $ratingscells = array();
        foreach ($ratings as $rating) {

            // Create a cell in the table for each rating
            if (!array_key_exists($rating->userid, $ratingscells)) {
                $ratingscells[$rating->userid] = array();
            }
            $cell = new html_table_cell();
            $cell->text = get_string('rating_raw', ratingallocate_MOD_NAME, $rating->rating);
            $cell->attributes['class'] = 'ratingallocate_rating_' . $rating->rating;

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
                    $cell->attributes['class'] = 'ratingallocate_rating_none';
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
     * Renders the button to show the ratings table
     */
    public function show_ratings_table_button() {
        global $PAGE;

        $tableurl = new moodle_url($PAGE->url, array('action' => RATING_ALLOC_SHOW_TABLE));

        $output = $this->heading(get_string('ratings_table', ratingallocate_MOD_NAME), 2);
        $output .= $this->box_start();
        // $output .= get_string('view_ratings_table', ratingallocate_MOD_NAME);
        // Button to display information about the distribution and ratings
        $output .= $this->single_button($tableurl->out(), get_string('show_table', ratingallocate_MOD_NAME), 'get');
        $output .= $this->box_end();

        return $output;
    }

    /**
     * Formats the $description and return HTML.
     */
    public function format_choice_description($description) {
        $output = $this->box_start('ratingallocate_description clearfix');
        $output .= format_text($description);
        $output .= $this->box_end();

        return $output;
    }

    /**
     * Format a choice for displaying it to students
     * @param stdclass $choice
     * @param boolean $showheading
     * @return string
     */
    public function format_choice($choice, $showheading) {
        $output = $this->box_start('generalbox');

        if ($showheading) {
            $output .= $this->heading($choice->title, 3, 'ratingallocate_heading');
        }

        if ($choice->explanation !== '') {
            $output .= $this->format_choice_description($choice->explanation);
        }

        $output .= $this->box_end();

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
     * Formats the notifications for the recent activity block and the course overview block
     */
    public function format_notifications($ratingallocate, $timestart) {
        $output = '';

        if ($ratingallocate->accesstimestart < time() and time() < $ratingallocate->accesstimestop) {
            // during the rating period.
            $a = new stdclass();
            $a->until = userdate($ratingallocate->enddate);
            $output .= $this->container(get_string('rating_has_begun', ratingallocate_MOD_NAME, $a), 'overview ratingallocate');
        }

        return $output;
    }

}
