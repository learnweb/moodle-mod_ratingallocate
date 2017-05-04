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

class ratings_and_allocations_table extends \table_sql {

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

    public function __construct(\mod_ratingallocate_renderer $renderer, $titles, $ratingallocate,
                                $action = 'show_alloc_table', $uniqueid = 'mod_ratingallocate_table', $downloadable = true) {
        parent::__construct($uniqueid);
        global $PAGE;
        $url = $PAGE->url;
        $url->params(array("action" => $action));
        $PAGE->set_url($url);
        $this->renderer = $renderer;
        $this->titles   = $titles;
        $this->ratingallocate = $ratingallocate;
        if ($downloadable && has_capability('mod/ratingallocate:export_ratings', $ratingallocate->get_context())) {
            $download = optional_param('download', '', PARAM_ALPHA);
            $this->is_downloading($download, 'Test', 'Testsheet');
        }

        $this->shownames = true;
    }

    /**
     * Setup this table with choices and filter options
     *
     * @param array $choices an array of choices
     * @param $hidenorating
     * @param $showallocnecessary
     */
    public function setup_table($choices, $hidenorating = null, $showallocnecessary = null) {

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
            if ($this->is_downloading()) {
                $columns[] = 'id';
                $headers[] = 'ID';
                $columns[] = 'username';
                $headers[] = get_string('username');
                $columns[] = 'firstname';
                $headers[] = get_string('firstname');
                $columns[] = 'lastname';
                $headers[] = get_string('lastname');
                global $COURSE;
                if (has_capability('moodle/course:useremail', $this->ratingallocate->get_context())) {
                    $columns[] = 'email';
                    $headers[] = get_string('email');
                }
            } else {
                $columns[] = 'fullname';
                $headers[] = get_string('ratings_table_user', ratingallocate_MOD_NAME);
            }
        }

        foreach ($this->choicenames as $choiceid => $choicetitle) {
            $columns[] = self::CHOICE_COL . $choiceid;
            $headers[] = $choicetitle;
            if ($this->is_downloading()) {
                $columns[] = self::CHOICE_COL . $choiceid . self::EXPORT_CHOICE_TEXT_SUFFIX;
                $headers[] = $choicetitle . get_string('export_choice_text_suffix', ratingallocate_MOD_NAME);
                $columns[] = self::CHOICE_COL . $choiceid . self::EXPORT_CHOICE_ALLOC_SUFFIX;
                $headers[] = $choicetitle . get_string('export_choice_alloc_suffix', ratingallocate_MOD_NAME);
            }
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Set additional table settings.
        $this->sortable(true);
        $this->set_attribute('class', 'ratingallocate_ratings_table');

        $this->initialbars(true);

        // Setup filter.
        $this->setup_filter($hidenorating, $showallocnecessary);

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

        if (!$this->is_downloading()) {
            $this->add_summary_row();
            $this->print_hidden_user_fields($users);
        }

        $this->finish_output();
    }

    /**
     * Adds one row for each user
     *
     * @param $user object of the user for who a row should be added.
     * @param $userratings array consisting of pairs of choiceid to rating for the user.
     * @param $userallocations array constisting of paris of choiceid and allocation of the user.
     */
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
                // User has rated this choice.
                $row[$rowkey]['hasallocation'] = true;
            }
        }

        $this->add_data_keyed($this->format_row($row));
    }

    /**
     * Will be called by build_table when processing the summary row
     */
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

    /**
     * Will be called by $this->format_row when processing the 'choice' columns
     *
     * @param string $column
     * @param object $row
     *
     * @return string rendered choice cell
     */
    public function other_cols($column, $row) {

        // Only supporting 'choice' columns here.
        if (strpos($column, self::CHOICE_COL) !== 0) {
            return null;
        }
        $suffix = '';
        // Suffixes for additional columns have to be removed.
        if ($this->is_downloading()) {
            foreach (array('text', 'alloc') as $key) {
                if (strpos($column, $key)) {
                    $suffix = $key;
                    $column = str_replace($key, '', $column);
                    break;
                }
            }
        }

        if (isset($row->$column)) {
            $celldata = $row->$column;
            if ($celldata['rating'] != null) {
                $ratingtext = $this->titles[$celldata['rating']];
            } else {
                $ratingtext = get_string('no_rating_given', ratingallocate_MOD_NAME);
            }
            $hasallocation = $celldata['hasallocation'] ? 'checked' : '';
            $ratingclass = $celldata['hasallocation'] ? 'ratingallocate_member' : '';

            if ($this->is_downloading()) {
                if ($suffix === self::EXPORT_CHOICE_TEXT_SUFFIX) {
                    return $ratingtext;
                }
                if ($suffix === self::EXPORT_CHOICE_ALLOC_SUFFIX) {
                    return $celldata['hasallocation'] ? get_string('yes') : get_string('no');
                }
                if ($celldata['rating'] == null) {
                    return "";
                }
                return $celldata['rating'];

            }

            return $this->render_cell($row->id, substr($column, 7),
                $ratingtext, $hasallocation, $ratingclass);
        } else {

            $ratingtext = get_string('no_rating_given', ratingallocate_MOD_NAME);

            if ($this->is_downloading()) {
                if ($suffix === self::EXPORT_CHOICE_TEXT_SUFFIX) {
                    return $ratingtext;
                }
                if ($suffix === self::EXPORT_CHOICE_ALLOC_SUFFIX) {
                    return get_string('no');
                }
                return "";
            }

            return $this->render_cell($row->id, substr($column, 7), $ratingtext, '');
        }
    }

    /**
     * Renders a single table cell.
     * The result is either a checkbox, if the table is writeable, or a text otherwise.
     *
     * @param integer $userid
     * @param integer $choiceid
     * @param string $text of the cell
     * @param string $checked string, which represents if the checkbox is checked
     * @param string $class class string, which is added to the input element
     *
     * @return string html of the rendered cell
     */
    private function render_cell($userid, $choiceid, $text, $checked, $class = '') {
        if ($this->writeable) {
            $result = \html_writer::start_span();
            $result .= \html_writer::tag('input', '',
                array('class' => 'ratingallocate_checkbox_label',
                    'type' => 'radio',
                    'name' => 'allocdata[' . $userid . ']',
                    'id' => 'user_' . $userid . '_alloc_' . $choiceid,
                    'value' => $choiceid,
                     $checked => ''));
            $result .= \html_writer::label(
                \html_writer::span('', 'ratingallocate_checkbox') . $text,
                'user_' . $userid . '_alloc_' . $choiceid
            );
            return $result;
        } else {
            return \html_writer::span($text, $class);
        }
    }

    /**
     * Prints one hidden field for every user currently displayed in the table.
     * Is used for checking, which allocation have to be deleted.
     * @param $users array of users displayed for the current filter settings.
     */
    private function print_hidden_user_fields($users) {
        if ($this->writeable) {
            echo \html_writer::start_span();
            foreach ($users as $user) {
                echo \html_writer::tag('input', '',
                    array(
                        'name' => 'userdata[' . $user->id . ']',
                        'value' => $user->id,
                        'type' => 'hidden',
                    ));
            }
            echo \html_writer::end_span();
        }
    }

    /** @var bool Defines if users with no rating at all should be displayed. */
    private $hidenorating = true;
    /** @var bool Defines if only users with no allocation should be displayed. */
    private $showallocnecessary = false;

    /**
     * Setup for filtering the table.
     * Loads the filter settings from the user preferences and overrides them if wanted, with the two parameters.
     * @param $hidenorating bool if true it shows also users with no rating.
     * @param $showallocnecessary bool if true it shows only users without allocations.
     */
    private function setup_filter($hidenorating = null, $showallocnecessary = null) {
        // Get the filter settings.
        $filter = json_decode(get_user_preferences('flextable_'.$this->uniqueid.'_filter'), true);

        if (!$filter) {
            $filter = array(
                'hidenorating' => $this->hidenorating,
                'showallocnecessary' => $this->showallocnecessary,
            );
        }
        if (!is_null($hidenorating)) {
            $filter['hidenorating'] = $hidenorating;
        }
        if (!is_null($showallocnecessary)) {
            $filter['showallocnecessary'] = $showallocnecessary;
        }
        set_user_preference('flextable_'.$this->uniqueid.'_filter', json_encode($filter));
        $this->hidenorating = $filter['hidenorating'];
        $this->showallocnecessary = $filter['showallocnecessary'];
    }

    /**
     * Gets the filter array used for filtering the table.
     * @return array with keys hidenorating and showallocnecessary
     */
    public function get_filter() {
        $filter = array(
            'hidenorating' => $this->hidenorating,
            'showallocnecessary' => $this->showallocnecessary,
        );
        return $filter;
    }

    /**
     * Filters a set of given userids in accordance of the two filter variables $hidenorating and $showallocnecessary
     * @param $userids array ids, which should be filtered.
     * @return array of filtered user ids.
     */
    private function filter_userids($userids) {
        global $DB;
        if (!$this->hidenorating && !$this->showallocnecessary) {
            return $userids;
        }
        $sql = "SELECT distinct u.id FROM {user} u ";
        if ($this->hidenorating) {
            $sql .= "JOIN {ratingallocate_ratings} r ON u.id=r.userid " .
                "JOIN {ratingallocate_choices} c ON r.choiceid = c.id ".
                "AND c.ratingallocateid = :ratingallocateid ".
                "AND c.active=1 ";
        }
        if ($this->showallocnecessary) {
            $sql .= "LEFT JOIN ({ratingallocate_allocations} a " .
                "JOIN {ratingallocate_choices} c2 ON c2.id = a.choiceid AND c2.active=1 ".
                "AND a.ratingallocateid = :ratingallocateid2 )" .
                "ON u.id=a.userid ".
                "WHERE a.id is null AND u.id in (".implode(",", $userids).") ";
        } else {
            $sql .= "WHERE u.id in (".implode(",", $userids).") ";
        }
        return array_map(
            function($u) {
                return $u->id;
            },
            $DB->get_records_sql($sql,
                array(
                    'ratingallocateid' => $this->ratingallocate->ratingallocate->id,
                    'ratingallocateid2' => $this->ratingallocate->ratingallocate->id
                )
            )
        );
    }

    /**
     * Sets up the sql statement for querying the table data.
     */
    public function init_sql() {
        $userids = array_map(function($c){return $c->id;
        },
            $this->ratingallocate->get_raters_in_course());
        $userids = $this->filter_userids($userids);

        $sortfields = $this->get_sort_columns();
        $fields = "u.*, u.firstname as firstname, u.lastname as lastname";
        if ($userids) {
            $where = "u.id in (".implode(",", $userids).")";
        } else {
            $where = "u.id is null";
        }

        $from = "{user} u";

        $params = array();
        for ($i = 0; $i < count($sortfields); $i++) {
            $key = array_keys($sortfields)[$i];
            if (substr($key, 0, 6) == "choice") {
                $id = substr($key, 7);
                $from .= " LEFT JOIN {ratingallocate_ratings} r$i ON u.id = r$i.userid AND r$i.choiceid = :choiceid$i ";
                $fields .= ", r$i.rating as $key";
                $params["choiceid$i"] = $id;
            }
        }

        $this->set_sql($fields, $from, $where, $params);

        $this->query_db(20);
    }

}