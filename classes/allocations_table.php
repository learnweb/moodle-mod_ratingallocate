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
 * Table displaying the allocated users for each choice.
 * @package    mod_ratingallocate
 * @copyright  2018 T Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/tablelib.php');

class allocations_table extends \table_sql {

    const CHOICE_COL = 'choice_';
    const EXPORT_CHOICE_ALLOC_SUFFIX = 'alloc';
    const EXPORT_CHOICE_TEXT_SUFFIX = 'text';

    private $choicenames = array();
    private $choicemax = array();
    private $choicesum = array();

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

    /**
     * allocations_table constructor.
     * @param \mod_ratingallocate_renderer $renderer responsible renderers
     * @param $titles
     * @param \ratingallocate $ratingallocate
     */
    public function __construct(\mod_ratingallocate_renderer $renderer, $titles, $ratingallocate) {
        parent::__construct('mod_ratingallocate_allocation_table');
        global $PAGE;
        $url = $PAGE->url;
        $url->params(array("action" => ACTION_SHOW_ALLOCATION_TABLE));
        $PAGE->set_url($url);
        $this->renderer = $renderer;
        $this->titles   = $titles;
        $this->ratingallocate = $ratingallocate;
        if (has_capability('mod/ratingallocate:export_ratings', $ratingallocate->get_context())) {
            $download = optional_param('download', '', PARAM_ALPHA);
            $this->is_downloading($download, $ratingallocate->ratingallocate->name, 'Testsheet');
        }
    }

    /**
     * Setup the table headers and columns
     */
    public function setup_table() {

        if (empty($this->baseurl)) {
            global $PAGE;
            $this->baseurl = $PAGE->url;
        }

        if ($this->is_downloading()) {
            $columns[] = 'id';
            $headers[] = 'ID';
            $columns[] = 'username';
            $headers[] = get_string('username');
            $columns[] = 'firstname';
            $headers[] = get_string('firstname');
            $columns[] = 'lastname';
            $headers[] = get_string('lastname');
            if (has_capability('moodle/course:useremail', $this->ratingallocate->get_context())) {
                $columns[] = 'email';
                $headers[] = get_string('email');
            }
        }
        $columns[] = 'choice';
        $headers[] = get_string('choice', ratingallocate_MOD_NAME);

        if (!$this->is_downloading()) {
            $columns[] = 'users';
            $headers[] = get_string('allocations_table_users', ratingallocate_MOD_NAME);
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Perform the rest of the flextable setup.
        parent::setup();

        $this->init_sql();
    }

    /**
     * Should be called after setup_choices
     *
     * @param array $ratings     an array of ratings -- the data for this table
     * @param array $allocations an array of allocations
     * @param bool $writeable if true the cells are rendered as radio buttons
     */
    public function build_table_by_sql($ratings, $allocations, $writeable = false) {

        $this->writeable = $writeable;

        $users = $this->rawdata;

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

        $this->finish_output();
    }


    /**
     * Will be called by $this->format_row when processing the 'choice' columns
     *
     * @param string $column
     * @param object $row
     *
     * @return string rendered choice cell
     */
    public function other_cols($column, $row) {

    }

    /**
     * Sets up the sql statement for querying the table data.
     */
    public function init_sql() {
        $fields = "c.*";

        $from = "{ratingallocate_choices} c";

        $where = "ratingallocateid = :ratingallocateid";

        $params = array();
        $params['ratingallocateid'] = $this->ratingallocate->ratingallocate->id;

        $this->set_sql($fields, $from, $where, $params);

        $this->query_db(20);
    }

}