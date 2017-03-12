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
 * @package    mod_ratingallocate
 * @copyright  2016 Janek Lasocki-Biczysko <j.lasocki-biczysko@intrallect.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/tablelib.php');

class ratings_and_allocations_table extends \flexible_table {
    
    const CHOICE_COL = 'choice_';
    
    private $choicenames = array();
    private $choicemax = array();
    private $choicesum = array();
    
    private $ratings;
    private $titles;
    
    private $shownames;

    /**
     * @var bool if true the cells are rendered as radio buttons
     */
    private $writeable;

    /**
     * @var \ratingallocate
     */
    private $ratingallocate;

    /**
     * @var \mod_ratingallocate_renderer
     */
    private $renderer;
    
    public function __construct(\mod_ratingallocate_renderer $renderer, $titles, $ratingallocate,
                                $action = 'show_alloc_table') {
        parent::__construct('mod_ratingallocate_table');
        global $PAGE;
        $PAGE->set_url(new \moodle_url($PAGE->url->get_path(),
            array_merge($PAGE->url->params(),array("action" => $action))));
        $this->renderer = $renderer;
        $this->titles   = $titles;
        $this->ratingallocate = $ratingallocate;

        // MAXDO maybe a setting in the future?
        // $this->shownames = get_config('mod_ratingallocate', 'show_names');
        $this->shownames = true;
    }
    
    /**
     * Setup this table with choices
     * 
     * @param array $choices an array of choices 
     */
    public function setup_choices($choices) {
        
        if (empty($this->baseurl)) {
            global $PAGE;
            $this->baseurl = $PAGE->url;
        }

        $allocationcounts = $this->ratingallocate->get_choices_with_allocationcount();

        // Store choice data, and sort by choice id.
        foreach ($choices as $choice) {
            $this->choicenames[$choice->id] = $choice->title;
            $this->choicemax[$choice->id] = $choice->maxsize;
            if ($allocationcounts[$choice->id]->usercount) {
                $this->choicesum[$choice->id] = $allocationcounts[$choice->id]->usercount;
            } else {
                $this->choicesum[$choice->id] = 0;
            }

        }
        
        ksort($this->choicenames);
        ksort($this->choicesum);
        
        // Prepare the table structure.
        $columns = [];
        $headers = [];
        
        if ($this->shownames) {
            $columns[] = 'fullname';
            $headers[] = get_string('ratings_table_user', ratingallocate_MOD_NAME);
        }
        
        foreach ($this->choicenames as $choiceid => $choicetitle) {
            $columns[] = self::CHOICE_COL . $choiceid;
            $headers[] = $choicetitle;
        }
        
        $this->define_columns($columns);
        $this->define_headers($headers);

        // Set additional table settings.
        $this->sortable(true);
        $this->set_attribute('class', 'ratingallocate_ratings_table');
        
        // Perform the rest of the flextable setup.
        parent::setup();
    }
    
    /**
     * Should be called after setup_choices
     * 
     * @param array $ratings     an array of ratings -- the data for this table
     * @param array $allocations an array of allocations
     * @param bool $writeable if true the cells are rendered as radio buttons
     */
    public function build_table($ratings, $allocations, $writeable = false) {

        $this->writeable = $writeable;
        $this->initialbars(true);
        $this->pagesize(10,$this->get_count_filtered_users());

        $users = $this->get_query_sorted_users();

        // Group all ratings per user to match table structure.
        $ratingsbyuser = array();
        foreach ($ratings as $rating) {
            if (empty($ratingsbyuser[$rating->userid])) {
                $ratingsbyuser[$rating->userid] = array();
            }
            $ratingsbyuser[$rating->userid][$rating->choiceid] = $rating->rating;
        }
        
        // Group all memberships per user per choice.
        $allocationsbyuser = array();
        foreach ($allocations as $allocation) {
            if (empty($allocationsbyuser[$allocation->userid])) {
                $allocationsbyuser[$allocation->userid] = array();
            }
            $allocationsbyuser[$allocation->userid][$allocation->choiceid] = true;
        }
        
        // Add rating rows for each user.
        foreach ($users as $user) {
            $userratings        = isset($ratingsbyuser[$user->id]) ? $ratingsbyuser[$user->id] : array();
            $userallocations    = isset($allocationsbyuser[$user->id]) ? $allocationsbyuser[$user->id] : array();
            $this->add_user_ratings_row($user, $userratings, $userallocations);
        }
        
        $this->add_summary_row();

        $this->finish_output();
    }
    
    private function add_user_ratings_row($user, $userratings, $userallocations) {
        
        $row = convert_to_array($user);
        
        if ($this->shownames) {
            $row['fullname'] = $user;
        }

        foreach ($userratings as $choiceid => $userrating) {
            $row[self::CHOICE_COL . $choiceid] = array(
                'rating' => $userrating,
                'hasallocation' => false // May be overridden later.
            );
        }

        // Process allocations separately, since assignment can exist for choices that have not been rated.
        // $userallocations *currently* has 0..1 elements, so this loop is rather fast.
        foreach ($userallocations as $choiceid => $userallocation) {
            if (!$userallocation) {
                // Presumably, $userallocation is always true. But maybe that assumption is wrong someday?
                continue;
            }

            $rowkey = self::CHOICE_COL . $choiceid;
            if (!isset($row[$rowkey])) {
                // User has not rated this choice, but it was assigned to him/her.
                $row[$rowkey] = array(
                    'rating' => null,
                    'hasallocation' => true
                );
            } else {
                // User has rated this choice
                $row[$rowkey]['hasallocation'] = true;
            }
        }
        
        $this->add_data_keyed($this->format_row($row));
    }
    
    private function add_summary_row() {
        
        $row = array();
        
        if ($this->shownames) {
            $row[] = get_string('ratings_table_sum_allocations', ratingallocate_MOD_NAME);
        }
        
        foreach ($this->choicesum as $choiceid => $sum) {
            $row[] = get_string(
                'ratings_table_sum_allocations_value',
                ratingallocate_MOD_NAME,
                array('sum' => $sum, 'max' => $this->choicemax[$choiceid])
            );
        }
        
        $this->add_data($row, 'ratingallocate_summary');
    }

    /*
     * Will be called by $this->format_row when processing the 'choice' columns
     * 
     * @param string $column
     * @param unknown $row
     */
    public function other_cols($column, $row) {

        // Only supporting 'choice' columns here.
        if (strpos($column, self::CHOICE_COL) !== 0) {
            return null;
        }

        if (isset($row->$column)) {
            $celldata       = $row->$column;
            if ($celldata['rating'] != null) {
                $ratingtext = $this->titles[$celldata['rating']];
            } else {
                $ratingtext = get_string('no_rating_given', ratingallocate_MOD_NAME);
            }
            $hasallocation    = $celldata['hasallocation'] ? 'checked' : '';
            $ratingclass    = $celldata['hasallocation'] ? 'ratingallocate_member' : '';

            return $this->render_cell($row->id, substr($column,7),
                $ratingtext, $hasallocation, $ratingclass);
        } else {
            return $this->render_cell($row->id, substr($column,7),
                get_string('no_rating_given', ratingallocate_MOD_NAME), '');
        }
    }

    private function render_cell($userid, $choiceid, $text, $checked, $class = ''){
        if ($this->writeable) {
            return \html_writer::span(
                '<input type="radio" name="data[' . $userid . '][assign]"'
                . 'id="user_' . $userid . '_alloc_' . $choiceid .
                '" value="' . $choiceid . '" ' . $checked . '/>' .
                '<label for="user_' . $userid . '_alloc_' . $choiceid.'"">'.$text.'</label>');
        } else {
            return \html_writer::span($text, $class);
        }
    }

    private $shownorating = true;
    private $showallocnecessary = false;

    public function setup_filter($shownorating, $showallocnecessary){
        $this->shownorating = $shownorating;
        $this->showallocnecessary = $showallocnecessary;
    }

    private function filter_userids($userids){
        global $DB;
        if ($this->shownorating && !$this->showallocnecessary){
            return $userids;
        }
        $sql = "SELECT distinct u.id FROM {user} as u ";
        if (!$this->shownorating) {
            $sql .= "JOIN {ratingallocate_ratings} as r ON u.id=r.userid " .
                "JOIN {ratingallocate_choices} as c ON r.choiceid = c.id ".
                "AND c.ratingallocateid = :ratingallocateid ".
                "AND c.active=1";
        }
        if ($this->showallocnecessary) {
            $sql .= "LEFT JOIN ({ratingallocate_allocations} as a " .
                "JOIN {ratingallocate_choices} as c2 ON c2.id = a.choiceid AND c2.active=1 ".
                "AND a.ratingallocateid = :ratingallocateid2 )" .
                "ON u.id=a.userid ".
                "WHERE a.id is null AND u.id in (".implode(",",$userids).") ";
        } else {
            $sql .= "WHERE u.id in (".implode(",",$userids).") ";
        }
        return array_map(function($u){return $u->id;},
            $DB->get_records_sql($sql, array('ratingallocateid' => $this->ratingallocate->ratingallocate->id,
                'ratingallocateid2' => $this->ratingallocate->ratingallocate->id)));
    }

    public function get_query_sorted_users(){
        global $DB;
        $userids = array_map(function($c){return $c->id;},
            $this->ratingallocate->get_raters_in_course());
        $userids = $this->filter_userids($userids);

        // The following db-query is not necessary if no users are there in the first place.
        if (count($userids) == 0){
            return array();
        }

        $sortfields = $this->get_sort_columns();
        $sql = "SELECT u.*
                FROM {user} u ";
        $orderby = [];
        for ($i=0; $i<count($sortfields); $i++){
            $key = array_keys($sortfields)[$i];
            if (substr($key, 0, 6) == "choice"){
                $id = substr($key, 7);
                $sql .= "LEFT JOIN {ratingallocate_ratings} as r$i ON u.id=r$i.userid AND r$i.choiceid=$id ";
                $orderkey = "r$i.rating";
                if ($sortfields[$key] == SORT_DESC) {
                    $orderkey .= " DESC";
                }
                $orderby []= $orderkey;
            } else {
                $orderkey = "u.$key";
                if ($sortfields[$key] == SORT_DESC) {
                    $orderkey .= " DESC";
                }
                $orderby []= $orderkey;
            }
        }
        $sql .= "WHERE u.id in (".implode(",",$userids).")";
        if ($this->get_initial_first()){
            $sql .= " AND u.firstname like '".$this->get_initial_first()."%'";
        }
        if ($this->get_initial_last()){
            $sql .= " AND u.lastname like '".$this->get_initial_last()."%'";
        }
        if (count($orderby)>0){
            $sql .= " ORDER BY ".implode(",",$orderby);
        }
        return $DB->get_records_sql($sql,null,$this->get_page_start(),$this->get_page_size());
    }

    public function get_count_filtered_users(){
        global $DB;
        $userids = array_map(function($c){return $c->id;},
            $this->ratingallocate->get_raters_in_course());
        $userids = $this->filter_userids($userids);

        $sql = "SELECT count(*)
                FROM {user} u ";

        $sql .= "WHERE u.id in (".implode(",",$userids).")";
        if ($this->get_initial_first()){
            $sql .= " AND u.firstname like '".$this->get_initial_first()."%'";
        }
        if ($this->get_initial_last()){
            $sql .= " AND u.lastname like '".$this->get_initial_last()."%'";
        }
        return $DB->get_record_sql($sql)->count;
    }
}