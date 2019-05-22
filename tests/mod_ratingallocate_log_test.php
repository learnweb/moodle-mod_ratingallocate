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
 * Ratingallocate log tests.
 *
 * @package    mod_ratingallocate
 * @copyright  2019 R. Tschudi, N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\request\deletion_criteria;

defined('MOODLE_INTERNAL') || die();

/**
 * Ratingallocate log tests.
 *
 * @package    mod_ratingallocate
 * @copyright  2019 R. Tschudi, N Herrmann
 * @group      mod_ratingallocate
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_log_test extends advanced_testcase {

    /**
     * Test the creation of logs.
     */
    public function test_algorithm_log_create() {
        global $DB;
        $this->resetAfterTest(true);

        // Create minnimal dummy data.
        $course = $this->getDataGenerator()->create_course();
        $data = mod_ratingallocate_generator::get_default_values();
        $data['course'] = $course;
        $dbrec = mod_ratingallocate_generator::create_instance_with_choices($this, $data);
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate($dbrec);

        $algorithm = new \mod_ratingallocate\algorithm_testable($ratingallocate);
        $logs = array('Test Message 0', 'Test Message 1');
        foreach ($logs as $log) {
            $algorithm->append_to_log($log);
        }
        $entries = $DB->get_records('ratingallocate_execution_log');
        $first = array_shift($entries);
        $second = array_shift($entries);

        $this->assertEquals($first->message, 'Test Message 0');
        $this->assertEquals($second->message, 'Test Message 1');
        $this->assertEquals($first->ratingallocateid, $ratingallocate->get_id());
        $this->assertEquals($second->ratingallocateid, $ratingallocate->get_id());
        $this->assertEquals($first->algorithm, $algorithm->get_subplugin_name());
        $this->assertEquals($second->algorithm, $algorithm->get_subplugin_name());
    }
}