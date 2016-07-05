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
global $CFG;
require_once(__DIR__ . '/generator/lib.php');
require_once(__DIR__ . '/../locallib.php');

use ratingallocate\db as this_db;

/**
 * mod_ratingallocate cron tests
 *
 * Tests the correct behaviour of the cron task according to the rating period,
 * the current time and the current algorithm status.
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  T Reischmann 2015
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_test extends advanced_testcase{

    private $teacher;
    private $mod;

    // <editor-fold defaultstate="collapsed" desc="Algorithm Run Tests">
    /**
     * The cron should run, when the algorithm status is not_started and the rating period ended.
     */
    public function test_successful_run(){
        $this->create_ratingallocate(true);
        $this->run_cron();
        $this->assert_finish();
    }

    /**
     * The cron should not run, when the rating period has not ended.
     */
    public function test_rating_in_progress(){
        $this->create_ratingallocate(false);
        $this->run_cron();
        $this->assert_not_started();
    }

    /**
     * The cron should not run, when the algorithm status is running.
     */
    public function test_running(){
        $this->create_ratingallocate(true, \mod_ratingallocate\algorithm_status::running);
        $this->run_cron();
        $this->assert_running();
    }

    /**
     * The cron should not run, when the algorithm status is failure.
     */
    public function test_failure(){
        $this->create_ratingallocate(true, \mod_ratingallocate\algorithm_status::failure);
        $this->run_cron();
        $this->assert_failure();
    }

    /**
     * The cron should not run, when the algorithm status is finished.
     */
    public function test_finished(){
        $this->create_ratingallocate(true, \mod_ratingallocate\algorithm_status::finished);
        $this->run_cron();
        $this->assert_already_finish();
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Failure Handling Tests">
    /**
     * The cron should not change the status of the algorithm, since it is not timedout.
     */
    public function test_undue_failure_handling(){
        $this->create_ratingallocate(true, \mod_ratingallocate\algorithm_status::running, time());
        $this->run_cron();
        $this->assert_running();
    }

    /**
     * The cron should switch the status of the algorithm to failure, since it has timedout.
     */
    public function test_due_failure_handling(){
        global $CFG;
        $this->create_ratingallocate(true, \mod_ratingallocate\algorithm_status::running, time() - 2);
        $CFG->ratingallocate_algorithm_timeout = 1;
        $this->run_cron();
        $this->assert_failure();
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Helper Methods">
    /**
     * Creates a cron task and executes it.
     */
    private function run_cron(){
        $task = new \mod_ratingallocate\task\cron_task();
        $this->setAdminUser();
        $task->execute();
    }

    /**
     * Assert, that the algorithm status is not_started and the algorithm has created no allocation.
     */
    private function assert_not_started(){
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, array());
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(\mod_ratingallocate\algorithm_status::notstarted, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations ::TABLE,
            array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id)));
    }

    /**
     * Assert, that the algorithm status is running and the algorithm has created no allocation.
     */
    private function assert_running(){
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, array());
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(\mod_ratingallocate\algorithm_status::running, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations ::TABLE,
            array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id)));
    }

    /**
     * Assert, that the algorithm status is failure and the algorithm has created no allocation.
     */
    private function assert_failure(){
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, array());
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(\mod_ratingallocate\algorithm_status::failure, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations ::TABLE,
            array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id)));
    }

    /**
     * Assert, that the algorithm status is finished and the algorithm has created 4 allocations.
     */
    private function assert_finish(){
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, array());
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(\mod_ratingallocate\algorithm_status::finished, $ratingallocate->get_algorithm_status());
        $this->assertEquals(4, $DB->count_records(this_db\ratingallocate_allocations ::TABLE,
            array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id)));
    }

    /**
     * Assert, that the algorithm status is still finished and the algorithm has created no allocation.
     */
    private function assert_already_finish(){
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, array());
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(\mod_ratingallocate\algorithm_status::finished, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations ::TABLE,
            array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id)));
    }

    /**
     * Create an ratingallocate module with 4 enroled students and their ratings.
     * @param $ratingperiodended determines if the rating period should have ended.
     * @param int $algorithmstatus the algorithm status of the modul to be created.
     * @param datetime $algorithmstarttime the start time of the algorithm.
     */
    private function create_ratingallocate($ratingperiodended,
                                           $algorithmstatus = \mod_ratingallocate\algorithm_status::notstarted, $algorithmstarttime = null){
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->teacher = mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $this->setUser($this->teacher);

        // There should not be any module for that course first
        $this->assertFalse(
            $DB->record_exists(this_db\ratingallocate::TABLE, array(this_db\ratingallocate::COURSE => $course->id
            )));
        $data = mod_ratingallocate_generator::get_default_values();
        $data['course'] = $course;
        // Shift the rating period depending on its ending.
        $data['accesstimestart'] = time();
        $data['accesstimestop'] = time();
        if ($ratingperiodended){
            $data['accesstimestart'] -= (6 * 24 * 60 * 60);
            // Necessary to ensure access time stop being in the past
            --$data['accesstimestop'];
        } else {
            $data['accesstimestop'] += (6 * 24 * 60 * 60);
        }
        $data['algorithmstatus'] = $algorithmstatus;
        $data['algorithmstarttime'] = $algorithmstarttime;

        // create activity
        $this->mod = mod_ratingallocate_generator::create_instance_with_choices($this, $data);
        $this->assertEquals(2, $DB->count_records(this_db\ratingallocate_choices::TABLE,
            array(this_db\ratingallocate_choices::RATINGALLOCATEID => $this->mod->id)));

        $student_1 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student_2 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student_3 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student_4 = mod_ratingallocate_generator::create_user_and_enrol($this, $course);

        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $this->mod, $this->teacher);
        $choices = $ratingallocate->get_rateable_choices();

        $choice1 = reset($choices);
        $choice2 = end($choices);

        //Create preferences
        $prefers_non = array();
        foreach ($choices as $choice) {
            $prefers_non[$choice->{this_db\ratingallocate_choices::ID}] = array(
                this_db\ratingallocate_ratings::CHOICEID => $choice->{this_db\ratingallocate_choices::ID},
                this_db\ratingallocate_ratings::RATING => 0);
        }
        $prefers_first = json_decode(json_encode($prefers_non),true);
        $prefers_first[$choice1->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;
        $prefers_second =  json_decode(json_encode($prefers_non),true);
        $prefers_second[$choice2->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;

        //assign preferences
        mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student_1, $prefers_first);
        mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student_2, $prefers_first);
        mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student_3, $prefers_second);
        mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student_4, $prefers_second);

        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations ::TABLE,
            array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id)));
    }
    //  </editor-fold>

}