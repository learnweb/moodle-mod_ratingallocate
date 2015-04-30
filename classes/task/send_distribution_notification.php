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
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate\task;

require_once(dirname(__FILE__).'/../../db/db_structure.php');
use ratingallocate\db as this_db;

class send_distribution_notification extends \core\task\adhoc_task {      
    // gets executed by the task runner. Will lookup the ratingallocation object and
    // command it to notify users                                                                     
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/ratingallocate/locallib.php');
        
        $site = get_site();
        // parse customdata passed
        $customdata = $this->get_custom_data();
        $userid = $customdata->userid;
        $ratingallocateid = $customdata->ratingallocateid;

        //get instance of ratingallocate
        $ratingallocate = $DB->get_record(this_db\ratingallocate::TABLE,array(this_db\ratingallocate::ID=>$ratingallocateid),'*', MUST_EXIST);
        
        $courseid = $ratingallocate->course;
        $course = get_course($courseid);
        $cm = get_coursemodule_from_instance('ratingallocate', $ratingallocate->id, $courseid);
        $context = \context_module::instance($cm->id);

        $ratingallocateobj = new \ratingallocate($ratingallocate, $course, $cm, $context);
        
        $ratingallocateobj->notify_users_distribution($userid);
        
    }                                                                                                                               
} 
       