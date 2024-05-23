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
 * Task for distributing unallocated users in the background.
 *
 * @package    mod_ratingallocate
 * @copyright  2023 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ratingallocate\task;

use context_module;
use core\task\adhoc_task;
use moodle_exception;
use ratingallocate;

/**
 * Task for distributing unallocated users in the background.
 *
 * @copyright  2023 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class distribute_unallocated_task extends adhoc_task {

    /**
     * Executes the distribution of unallocated users.
     *
     * @throws moodle_exception
     */
    public function execute(): void {
        global $CFG, $DB;
        // Make sure to include the global definitions of constants defined in locallib.
        require_once($CFG->dirroot . '/mod/ratingallocate/locallib.php');

        $data = $this->get_custom_data();
        if (empty($data->distributionalgorithm) ||
            !in_array($data->distributionalgorithm, [ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY, ACTION_DISTRIBUTE_UNALLOCATED_FILL])) {
            mtrace('No distribution algorithm has been specified, exiting.');
            return;
        }
        if (empty($data->courseid)) {
            mtrace('No course ID has been found, exiting.');
            return;
        }
        if (empty($data->cmid)) {
            mtrace('No course module ID has been found, exiting.');
            return;
        }

        $modinfo = get_fast_modinfo($data->courseid);
        $cm = $modinfo->get_cm($data->cmid);
        $course = $modinfo->get_course();
        $ratingallocatedb = $DB->get_record('ratingallocate', ['id' => $cm->instance]);
        if (empty($ratingallocatedb)) {
            mtrace('Could not find database record of ratingallocate instance for course module id ' . $cm->id
                . '. Nothing to do.');
            return;
        }
        $context = context_module::instance($cm->id);
        $ratingallocate = new ratingallocate($ratingallocatedb, $course, $cm, $context);

        mtrace('Distributing unallocated users for ratingallocate with course module id ' . $cm->id);
        $ratingallocate->distribute_users_without_choice($data->distributionalgorithm);
        mtrace('Distribution of unallocated users for ratingallocate instance with course module id ' . $cm->id . ' done.');
    }
}
