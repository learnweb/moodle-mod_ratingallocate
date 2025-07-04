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
 * Ratings and allocations table.
 *
 * @package    mod_ratingallocate
 * @copyright  2016 Janek Lasocki-Biczysko <j.lasocki-biczysko@intrallect.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ratingallocate;

use mod_ratingallocate_renderer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Ratings and allocations table
 *
 * @package mod_ratingallocate
 */
class ratings_and_allocations_table extends \table_sql {

    /**
     * Choice column.
     */
    const CHOICE_COL = 'choice_';
    /**
     * Alloc suffix.
     */
    const EXPORT_CHOICE_ALLOC_SUFFIX = 'alloc';
    /**
     * Text suffix.
     */
    const EXPORT_CHOICE_TEXT_SUFFIX = 'text';

    /** @var array $choicenames */
    private $choicenames = [];
    /** @var array $choicemax */
    private $choicemax = [];
    /** @var array $choicesum */
    private $choicesum = [];
    /** @var $titles */
    private $titles;
    /** @var true $shownames */
    private $shownames;

    /**
     * @var array Array of all groups being used in the restriction settings of the choices of this ratingallocate instance.
     */
    private $groupsofallchoices;

    /**
     * @var array Array of all group names assigned to the choices, with choice id as key.
     */
    private $groupnamesofchoices;

    /**
     * @var bool if true the table should show a column with the groups in this ratingallocate instance which the user belongs to.
     */
    private $showgroups;

    /**
     * @var bool if true the cells are rendered as radio buttons
     */
    private $writeable;

    /**
     * @var ratingallocate
     */
    private $ratingallocate;

    /**
     * @var mod_ratingallocate_renderer
     */
    private $renderer;

    /**
     * Construct.
     *
     * @param mod_ratingallocate_renderer $renderer
     * @param array $titles
     * @param ratingallocate $ratingallocate
     * @param int $action
     * @param string|null $uniqueid
     * @param bool $downloadable
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(mod_ratingallocate_renderer $renderer, $titles, $ratingallocate,
                                                            $action = ACTION_SHOW_RATINGS_AND_ALLOCATION_TABLE,
                                $uniqueid = 'mod_ratingallocate_table', $downloadable = true) {
        parent::__construct($uniqueid);
        global $PAGE;
        $url = $PAGE->url;
        $url->params(["action" => $action]);
        $PAGE->set_url($url);
        $this->renderer = $renderer;
        $this->titles = $titles;
        $this->ratingallocate = $ratingallocate;
        $allgroupsofchoices = $this->ratingallocate->get_all_groups_of_choices();
        $this->groupsofallchoices = array_map(function($groupid) {
            return groups_get_group($groupid);
        }, $allgroupsofchoices);
        if ($downloadable && has_capability('mod/ratingallocate:export_ratings', $ratingallocate->get_context())) {
            $download = optional_param('download', '', PARAM_ALPHA);
            $this->is_downloading($download,
                $ratingallocate->ratingallocate->name . '-ratings_and_allocations',
                'ratings_and_allocations');
        }

        $this->shownames = true;
        // We only show the group column if at least one group is being used in at least one active restriction setting of a choice.
        $this->showgroups = !empty($allgroupsofchoices);
    }

    /**
     * Setup this table with choices and filter options
     *
     * @param array $choices an array of choices
     * @param bool|null $hidenorating
     * @param bool|null $showallocnecessary
     * @param int|null $groupselect
     */
    public function setup_table($choices, $hidenorating = null, $showallocnecessary = null, $groupselect = 0) {

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
                global $CFG;
                $additionalfields = explode(',', $CFG->ratingallocate_download_userfields);
                if (in_array('id', $additionalfields)) {
                    $columns[] = 'id';
                    $headers[] = 'ID';
                }
                if (in_array('username', $additionalfields)) {
                    $columns[] = 'username';
                    $headers[] = get_string('username');
                }
                if (in_array('idnumber', $additionalfields)) {
                    $columns[] = 'idnumber';
                    $headers[] = get_string('idnumber');
                }
                if (in_array('department', $additionalfields)) {
                    $columns[] = 'department';
                    $headers[] = get_string('department');
                }
                if (in_array('institution', $additionalfields)) {
                    $columns[] = 'institution';
                    $headers[] = get_string('institution');
                }
                $columns[] = 'firstname';
                $headers[] = get_string('firstname');
                $columns[] = 'lastname';
                $headers[] = get_string('lastname');
                global $COURSE;
                if (in_array('email', $additionalfields) &&
                        has_capability('moodle/course:useremail', $this->ratingallocate->get_context())) {
                    $columns[] = 'email';
                    $headers[] = get_string('email');
                }
            } else {
                $columns[] = 'fullname';
                $headers[] = get_string('ratings_table_user', RATINGALLOCATE_MOD_NAME);
            }
            // We only want to add a group column, if at least one choice has an active group restriction.
            if ($this->showgroups) {
                $columns[] = 'groups';
                $headers[] = get_string('groups');
                // Prepare group names of choices.
                $this->groupnamesofchoices = [];
                foreach ($choices as $choice) {
                    $this->groupnamesofchoices[$choice->id] = array_map(fn($group) => groups_get_group_name($group->id),
                            $this->ratingallocate->get_choice_groups($choice->id));
                }
            }
        }

        // Setup filter.
        $this->setup_filter($hidenorating, $showallocnecessary, $groupselect);

        $filteredchoices = $this->filter_choiceids(array_keys($this->choicenames));
        foreach ($filteredchoices as $choiceid) {

            $columns[] = self::CHOICE_COL . $choiceid;
            $choice = $this->ratingallocate->get_choices()[$choiceid];
            if ($this->showgroups) {
                $choicegroups = $this->groupnamesofchoices[$choiceid];
                if (!$this->is_downloading() && !empty($choice->usegroups) && !empty($choicegroups)) {
                    $this->choicenames[$choiceid] .= ' <br/>' . \html_writer::span('(' . implode(';', $choicegroups) . ')',
                            'groupsinchoiceheadings');
                }
            }
            $headers[] = $this->choicenames[$choiceid];
            if ($this->is_downloading()) {
                $columns[] = self::CHOICE_COL . $choiceid . self::EXPORT_CHOICE_TEXT_SUFFIX;
                $headers[] = $this->choicenames[$choiceid] . get_string('export_choice_text_suffix', RATINGALLOCATE_MOD_NAME);
                $columns[] = self::CHOICE_COL . $choiceid . self::EXPORT_CHOICE_ALLOC_SUFFIX;
                $headers[] = $this->choicenames[$choiceid] . get_string('export_choice_alloc_suffix', RATINGALLOCATE_MOD_NAME);
            }
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Set additional table settings.
        $this->sortable(true, 'lastname');
        $tableclasses = 'ratingallocate_ratings_table';
        if ($this->showgroups) {
            $tableclasses .= ' includegroups';
            $this->no_sorting('groups');
        }
        $this->set_attribute('class', $tableclasses);

        $this->initialbars(true);

        // Perform the rest of the flextable setup.
        parent::setup();

        $this->init_sql();

        $this->add_group_row();
    }

    /**
     * Should be called after setup_choices
     *
     * @param array $ratings an array of ratings -- the data for this table
     * @param array $allocations an array of allocations
     * @param bool $writeable if true the cells are rendered as radio buttons
     */
    public function build_table_by_sql($ratings, $allocations, $writeable = false) {

        $this->writeable = $writeable;

        $users = $this->rawdata;

        // Group all ratings per user to match table structure.
        $ratingsbyuser = [];
        foreach ($ratings as $rating) {
            if (empty($ratingsbyuser[$rating->userid])) {
                $ratingsbyuser[$rating->userid] = [];
            }
            $ratingsbyuser[$rating->userid][$rating->choiceid] = $rating->rating;
        }

        // Group all memberships per user per choice.
        $allocationsbyuser = [];
        foreach ($allocations as $allocation) {
            if (empty($allocationsbyuser[$allocation->userid])) {
                $allocationsbyuser[$allocation->userid] = [];
            }
            $allocationsbyuser[$allocation->userid][$allocation->choiceid] = true;
        }

        // Add rating rows for each user.
        foreach ($users as $user) {
            $userratings = isset($ratingsbyuser[$user->id]) ? $ratingsbyuser[$user->id] : [];
            $userallocations = isset($allocationsbyuser[$user->id]) ? $allocationsbyuser[$user->id] : [];
            $this->add_user_ratings_row($user, $userratings, $userallocations);
        }

        if (!$this->is_downloading()) {
            $this->add_summary_row();
            $this->print_hidden_user_fields($users);
        }

        $this->finish_output();
    }

    /**
     * Add a row containing the group names of the groups assigned to the choices to the export table.
     *
     * @return void
     */
    private function add_group_row(): void {
        if ($this->is_downloading()) {
            $choiceids = array_map(
                function ($c) {
                    return $c->id;
                },
                $this->ratingallocate->get_choices()
            );
            $choices = $this->ratingallocate->get_choices_by_id($this->filter_choiceids($choiceids));
            $row = [];
            foreach ($choices as $choice) {
                $choicegroups = $this->groupnamesofchoices[$choice->id];
                if (empty($choice->usegroups) || empty($choicegroups)) {
                    continue;
                }
                $groupnames = implode(';', $this->groupnamesofchoices[$choice->id]);
                $row[self::CHOICE_COL . $choice->id] = $groupnames;
                $row[self::CHOICE_COL . $choice->id . self::EXPORT_CHOICE_TEXT_SUFFIX] = $groupnames;
                $row[self::CHOICE_COL . $choice->id . self::EXPORT_CHOICE_ALLOC_SUFFIX] = $groupnames;
            }
            $this->add_data_keyed($row);
        }
    }

    /**
     * Adds one row for each user
     *
     * @param object $user of the user for who a row should be added.
     * @param array $userratings consisting of pairs of choiceid to rating for the user.
     * @param array $userallocations constisting of pairs of choiceid and allocation of the user.
     */
    private function add_user_ratings_row($user, $userratings, $userallocations) {

        $row = convert_to_array($user);

        if ($this->shownames) {
            $row['fullname'] = $user;
            // We only can add groups if at least one choice has an active group restriction.
            if ($this->showgroups) {
                $groupsofuser = array_filter($this->groupsofallchoices, function($group) use ($user) {
                    return groups_is_member($group->id, $user->id);
                });
                $groupnames = array_map(function($group) {
                    return $group->name;
                }, $groupsofuser);
                $row['groups'] = implode(';', $groupnames);
            }
        }

        foreach ($userratings as $choiceid => $userrating) {
            $row[self::CHOICE_COL . $choiceid] = [
                    'rating' => $userrating,
                    'hasallocation' => false, // May be overridden later.
            ];
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
                $row[$rowkey] = [
                        'rating' => null,
                        'hasallocation' => true,
                ];
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

        $row = [];

        if ($this->shownames) {
            $row[] = get_string('ratings_table_sum_allocations', RATINGALLOCATE_MOD_NAME);
            if ($this->showgroups) {
                // In case we are showing groups, the second column is the group column and needs to be skipped in summary row.
                $row[] = '';
            }
        }

        foreach ($this->choicesum as $choiceid => $sum) {
            if (in_array($choiceid, $this->filter_choiceids(array_keys($this->choicenames)))) {
                $row[] = get_string(
                    'ratings_table_sum_allocations_value',
                    RATINGALLOCATE_MOD_NAME,
                    ['sum' => $sum, 'max' => $this->choicemax[$choiceid]]
                );
            }
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
            foreach (['text', 'alloc'] as $key) {
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
                $ratingtext = get_string('no_rating_given', RATINGALLOCATE_MOD_NAME);
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

            $ratingtext = get_string('no_rating_given', RATINGALLOCATE_MOD_NAME);

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
                    ['class' => 'ratingallocate_checkbox_label',
                            'type' => 'radio',
                            'name' => 'allocdata[' . $userid . ']',
                            'id' => 'user_' . $userid . '_alloc_' . $choiceid,
                            'value' => $choiceid,
                            $checked => '']);
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
     * @param array $users of users displayed for the current filter settings.
     */
    private function print_hidden_user_fields($users) {
        if ($this->writeable) {
            echo \html_writer::start_span();
            foreach ($users as $user) {
                echo \html_writer::tag('input', '',
                        [
                                'name' => 'userdata[' . $user->id . ']',
                                'value' => $user->id,
                                'type' => 'hidden',
                        ]);
            }
            echo \html_writer::end_span();
        }
    }

    /** @var bool Defines if users with no rating at all should be displayed. */
    private $hidenorating = true;
    /** @var bool Defines if only users with no allocation should be displayed. */
    private $showallocnecessary = false;
    /** @var int Defines the group the displayed users are in */
    private $groupselect = 0;

    /**
     * Setup for filtering the table.
     * Loads the filter settings from the user preferences and overrides them if wanted, with the two parameters.
     * @param bool $hidenorating if true it shows also users with no rating.
     * @param bool $showallocnecessary if true it shows only users without allocations.
     * @param int $groupselect int shows 0 = all users, -1 = all users not in a group, otherwise = only users in the selected group.
     */
    private function setup_filter($hidenorating = null, $showallocnecessary = null, $groupselect = null) {
        // Get the filter settings.
        $settings = get_user_preferences('flextable_' . $this->uniqueid . '_filter');
        $filter = $settings ? json_decode($settings, true) : null;

        if (!$filter) {
            $filter = [
                    'hidenorating' => $this->hidenorating,
                    'showallocnecessary' => $this->showallocnecessary,
                    'groupselect' => $this->groupselect,
            ];
        }
        if (!is_null($hidenorating)) {
            $filter['hidenorating'] = $hidenorating;
        }
        if (!is_null($showallocnecessary)) {
            $filter['showallocnecessary'] = $showallocnecessary;
        }
        if (!is_null($groupselect)) {
            $filter['groupselect'] = $groupselect;
        }
        set_user_preference('flextable_' . $this->uniqueid . '_filter', json_encode($filter));
        $this->hidenorating = $filter['hidenorating'];
        $this->showallocnecessary = $filter['showallocnecessary'];
        $this->groupselect = $filter['groupselect'];
    }

    /**
     * Gets the filter array used for filtering the table.
     * @return array with keys hidenorating and showallocnecessary
     */
    public function get_filter() {
        $filter = [
                'hidenorating' => $this->hidenorating,
                'showallocnecessary' => $this->showallocnecessary,
                'groupselect' => $this->groupselect,
        ];
        return $filter;
    }

    /**
     * Filters a set of given userids in accordance of the two filter variables $hidenorating and $showallocnecessary
     * and the selected group
     *
     * @param array $userids ids, which should be filtered.
     * @return array of filtered user ids.
     */
    private function filter_userids($userids) {
        global $DB;
        if (!$userids) {
            return $userids;
        }
        if (!$this->hidenorating && !$this->showallocnecessary && $this->groupselect == 0) {
            return $userids;
        }
        $sql = "SELECT distinct u.id FROM {user} u ";

        if ($this->hidenorating) {
            $sql .= "JOIN {ratingallocate_ratings} r ON u.id=r.userid " .
                "JOIN {ratingallocate_choices} c ON r.choiceid = c.id " .
                "AND c.ratingallocateid = :ratingallocateid " .
                "AND c.active=1 ";
        }
        if ($this->showallocnecessary) {
            $sql .= "LEFT JOIN ({ratingallocate_allocations} a " .
                "JOIN {ratingallocate_choices} c2 ON c2.id = a.choiceid AND c2.active=1 " .
                "AND a.ratingallocateid = :ratingallocateid2 )" .
                "ON u.id=a.userid " .
                "WHERE a.id is null AND u.id in (" . implode(",", $userids) . ") ";
        } else {
            $sql .= "WHERE u.id in (" . implode(",", $userids) . ") ";
        }
        if ($this->groupselect == -1) {
            $sql .= "AND u.id not in ( SELECT distinct gm.userid FROM {groups_members} gm WHERE gm.groupid in (null";
            if (!empty($gmgroupid = implode(",",
                array_map(
                    function($o) {
                        return $o->id;
                    },
                    $this->groupsofallchoices)))) {
                $sql .= "," . $gmgroupid . ") ) ";
            } else {
                $sql .= "))";
            }
        } else if ($this->groupselect != 0) {
            $sql .= "AND u.id in ( SELECT gm.userid FROM {groups_members} gm WHERE gm.groupid= :groupselect ) ";
        }
        return array_map(
                function($u) {
                    return $u->id;
                },
                $DB->get_records_sql($sql,
                        [
                                'ratingallocateid' => $this->ratingallocate->ratingallocate->id,
                                'ratingallocateid2' => $this->ratingallocate->ratingallocate->id,
                                'groupselect' => $this->groupselect,
                        ]
                )
        );
    }

    /**
     * Filter choiceids.
     *
     * @param array $choiceids
     * @return array
     * @throws \dml_exception
     */
    private function filter_choiceids($choiceids) {
        global $DB;
        if (!$choiceids) {
            return $choiceids;
        }

        if ($this->groupselect == 0) {
            return $choiceids;
        }

        $sql = "SELECT distinct c.id FROM {ratingallocate_choices} c ";

        if ($this->groupselect == -1) {
            $sql .= "WHERE c.usegroups=0 " .
                "AND c.ratingallocateid= :ratingallocateid " .
                "AND c.active=1 " .
                "AND c.id IN (" . implode(",", $choiceids) . ") ";
        } else {
            $sql .= "LEFT JOIN {ratingallocate_group_choices} gc ON c.id=gc.choiceid " .
                "AND c.ratingallocateid= :ratingallocateid " .
                "AND c.active=1 " .
                "WHERE c.id IN (" . implode(",", $choiceids) . ") " .
                "AND ( gc.groupid= :groupselect OR c.usegroups=0) ";
        }

        return array_map(
            function($c) {
                return $c->id;
            },
            $DB->get_records_sql($sql,
                [
                    'ratingallocateid' => $this->ratingallocate->ratingallocate->id,
                    'groupselect' => $this->groupselect,
                ]
            )
        );

    }

    /**
     * Sets up the sql statement for querying the table data.
     */
    public function init_sql() {
        $userids = array_map(function($c) {
            return $c->id;
        },
                $this->ratingallocate->get_raters_in_course());
        $userids = $this->filter_userids($userids);

        $sortfields = $this->get_sort_columns();
        $fields = "u.*";
        if ($userids) {
            $where = "u.id in (" . implode(",", $userids) . ")";
        } else {
            $where = "u.id is null";
        }

        $from = "{user} u";

        $params = [];
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
