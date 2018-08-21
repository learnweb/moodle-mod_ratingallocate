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
require_once($CFG->dirroot.'/user/lib.php');

class allocations_table extends \table_sql {

    /**
     * @var \ratingallocate
     */
    private $ratingallocate;

    /**
     * allocations_table constructor.
     * @param \ratingallocate $ratingallocate
     */
    public function __construct($ratingallocate) {
        parent::__construct('mod_ratingallocate_allocation_table');
        global $PAGE;
        $url = $PAGE->url;
        $url->params(array("action" => ACTION_SHOW_ALLOCATION_TABLE));
        $PAGE->set_url($url);
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

        $columns[] = 'choicetitle';
        $headers[] = get_string('allocations_table_choice', ratingallocate_MOD_NAME);

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
     * Builds the data for the table. For the online version the users are aggregated for the choices to
     * which they are allocated. For the download version no changes are necessary.
     */
    public function build_table_by_sql() {
        $data = $this->rawdata;

        // Retrieve all users, who rated within the course.
        $userwithratingids = array_map(function($x) {return $x->userid;},
            $this->ratingallocate->get_users_with_ratings());
        $userwithrating = \user_get_users_by_id($userwithratingids);

        if ($this->is_downloading()){
            // Search for all users, who rated but were not allocated and add them to the data set.
            foreach ($userwithrating as $userid => $user) {
                if (!array_key_exists($userid, $data)) {
                    $data[$userid] = $user;
                    $data[$userid]->choicetitle = '';
                }
            }
        } else {
            // Aggregate all users allocated to a specific choice to the users column.
            $allocations = $this->ratingallocate->get_allocations();

            $users = $this->ratingallocate->get_raters_in_course();

            foreach ($allocations as $allocation) {
                $userid = $allocation->userid;
                if (array_key_exists($userid, $users)) {
                    if (array_key_exists($allocation->choiceid, $data)) {
                        if (object_property_exists($data[$allocation->choiceid], 'users')) {
                            $data[$allocation->choiceid]->users .= ', ';
                        } else {
                            $data[$allocation->choiceid]->users = '';
                        }

                        $data[$allocation->choiceid]->users .= $this->get_user_link($users[$userid]);
                    }
                    unset($userwithrating[$userid]);
                }
            }

            // Enrich data with empty string for choices with no allocation.
            foreach ($data as $row) {
                if (!property_exists($row, 'users')) {
                    $row->users = '';
                }
            }

            // If there are users, which rated but were not allocated, add them to a special row.
            if (count($userwithrating) > 0 AND ($this->currpage + 1) * $this->pagesize >= $this->totalrows) {
                $noallocation = new \stdClass();
                $noallocation->choicetitle = get_string(
                    'allocations_table_noallocation',
                    ratingallocate_MOD_NAME);

                foreach ($userwithrating as $userid => $user) {
                    if (object_property_exists($noallocation, 'users')) {
                        $noallocation->users .= ', ';
                    } else {
                        $noallocation->users = '';
                    }
                    $noallocation->users .= $this->get_user_link($user);
                }
                $data []= $noallocation;
            }
        }

        // Finally, add all data to the table.
        foreach ($data as $row) {
            $this->add_data_keyed($this->format_row($row));
        }

        $this->finish_output();
    }

    /**
     * Returns a link to a user profile labeled with the full name of the user.
     * @param $user \stdClass user object.
     * @return string HTML code representing the link to the users profile.
     */
    private function get_user_link($user) {
        global $COURSE;
        $name = fullname($user);

        if ($COURSE->id == SITEID) {
            $profileurl = new \moodle_url('/user/profile.php', array('id' => $user->id));
        } else {
            $profileurl = new \moodle_url('/user/view.php',
                array('id' => $user->id, 'course' => $COURSE->id));
        }
        return \html_writer::link($profileurl, $name);
    }

    /**
     * Sets up the sql statement for querying the table data.
     */
    public function init_sql() {
        if ($this->is_downloading()) {
            $fields = "u.*, c.title as choicetitle";

            $from = "{ratingallocate_allocations} a JOIN {ratingallocate_choices} c ON a.choiceid = c.id JOIN {user} u ON a.userid = u.id";
        } else {
            $fields = "c.id, c.title as choicetitle";

            $from = "{ratingallocate_choices} c";
        }

        $where = "c.ratingallocateid = :ratingallocateid";

        $params = array();
        $params['ratingallocateid'] = $this->ratingallocate->ratingallocate->id;

        $this->set_sql($fields, $from, $where, $params);

        $this->query_db(20);
    }

}