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
 * A scheduled task for ratingallocate cron.
 *
 * @package    mod_ratingallocate
 * @copyright  2015 Tobias Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate\task;
use ratingallocate\db as this_db;

require_once(__DIR__.'/../../locallib.php');

class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_ratingallocate');
    }

    /**
     * Run forum cron.
     */
    public function execute() {
        global $DB, $CFG;

        $currenttime = time();
        $statement = 'SELECT R.* FROM {ratingallocate} AS R
        LEFT JOIN {ratingallocate_allocations} AS A
        ON R.'.this_db\ratingallocate::ID.'=A.'.this_db\ratingallocate_allocations::RATINGALLOCATEID.'
        WHERE A.'.this_db\ratingallocate_allocations::ID.' IS NULL AND R.'.this_db\ratingallocate::ACCESSTIMESTOP.'<'.$currenttime;
        $records = $DB->get_records_sql($statement);
        $course = null;
        foreach ($records as $record) {
            $cm = get_coursemodule_from_instance(this_db\ratingallocate::TABLE, $record->{this_db\ratingallocate::ID});
            // Fetch the data for the course, if is has changed
            if (!$course || $course->id != $record->{this_db\ratingallocate::COURSE}) {
                $course = $DB->get_record('course', array('id' => $record->{this_db\ratingallocate::COURSE}), '*', MUST_EXIST);
            }
            // Create ratingallocate instance from record
            $ratingallocate = new \ratingallocate($record, $course, $cm, \context_module::instance($cm->id));
            $currenttime = time();
            $timetoterminate = $CFG->ratingallocate_algorithm_timeout + $ratingallocate->ratingallocate->algorithmstarttime;

            // If last execution exeeds timeout limit assume failure of algorithm run.
            if ($ratingallocate->ratingallocate->algorithmstarttime &&
                $currenttime >= $timetoterminate &&
                $ratingallocate->get_algorithm_status() === \mod_ratingallocate\algorithm_status::running) {
                $ratingallocate->set_algorithm_failed();
                return true;
            }

            // Only start the algorithm, if it should be run by the cron and hasn't been started somehow, yet.
            if ($ratingallocate->ratingallocate->runalgorithmbycron === "1" &&
                $ratingallocate->get_algorithm_status() === \mod_ratingallocate\algorithm_status::notstarted) {
                // Run allocation.
                $ratingallocate->distrubute_choices();
            }
        }
        return true;
    }

}
