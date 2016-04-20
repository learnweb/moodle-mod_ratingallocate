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
     * @var \mod_ratingallocate_renderer
     */
    private $renderer;
    
    public function __construct(\mod_ratingallocate_renderer $renderer, $titles) {
        parent::__construct('mod_ratingallocate_table');
        
        $this->renderer = $renderer;
        $this->titles   = $titles;
        
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
            $this->baseurl = $PAGE->url->out(false);
        }
        
        // Store choice data, and sort by choice id.
        foreach ($choices as $choice) {
            $this->choicenames[$choice->id] = $choice->title;
            $this->choicemax[$choice->id] = $choice->maxsize;
            $this->choicesum[$choice->id] = 0;
        }
        
        ksort($this->choicenames);
        ksort($this->choicesum);
        
        // Prepare the table structure.
        $columns = [];
        $headers = [];
        
        if ($this->shownames) {
            $columns[] = 'user';
            $headers[] = get_string('ratings_table_user', ratingallocate_MOD_NAME);
        }
        
        foreach ($this->choicenames as $choiceid => $choicetitle) {
            $columns[] = self::CHOICE_COL . $choiceid;
            $headers[] = $choicetitle;
        }
        
        $this->define_columns($columns);
        $this->define_headers($headers);

        // Set additional table settings.
        $this->sortable(false);
        $this->set_attribute('class', 'ratingallocate_ratings_table');
        
        // Perform the rest of the flextable setup.
        parent::setup();
    }
    
    /**
     * Should be called after setup_choices
     * 
     * @param array $users       an array of all users participating in this module instance
     * @param array $ratings     an array of ratings -- the data for this table
     * @param array $allocations an array of allocations
     */
    public function build_table($users, $ratings, $allocations) {
        
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
        
        $row = array();
        
        if ($this->shownames) {
            $row['user'] = $user;
        }
        
        foreach ($userratings as $choiceid => $userrating) {
            
            $hasallocation  = isset($userallocations[$choiceid]);
            
            $row[self::CHOICE_COL . $choiceid] = array(
                'rating' => $userrating,
                'hasallocation' => $hasallocation
            );
            
            if ($hasallocation) {
                $this->choicesum[$choiceid]++;
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
     * Will be called by $this->format_row when processing the 'user' column.
     * 
     * @param unknown $row
     */
    protected function col_user($row) {
        return $this->renderer->format_user_data($row->user);
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
            $ratingtext     = $this->titles[$celldata['rating']];
            $ratingclass    = $celldata['hasallocation'] ? 'ratingallocate_member' : '';
            return \html_writer::span($ratingtext, $ratingclass);
        } else {
            return get_string('no_rating_given', ratingallocate_MOD_NAME);
        }
    }
}