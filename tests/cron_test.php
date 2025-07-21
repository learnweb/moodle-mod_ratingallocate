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

namespace mod_ratingallocate;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/generator/lib.php');
require_once(__DIR__ . '/../locallib.php');

use mod_ratingallocate\db as this_db;
use mod_ratingallocate\task\cron_task;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * mod_ratingallocate cron tests
 *
 * Tests the correct behaviour of the cron task according to the rating period,
 * the current time and the current algorithm status.
 *
 * @package     mod_ratingallocate
 * @category    test
 * @group       mod_ratingallocate
 * @copyright   T Reischmann 2015
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_ratingallocate\task\cron_task
 */
#[CoversClass(cron_task::class)]
#[CoversFunction('execute')]
final class cron_test extends \advanced_testcase {

    /** @var $teacher */
    private $teacher;
    /** @var $mod */
    private $mod;

    /**
     * The cron should run, when the algorithm status is not_started and the rating period ended.
     *
     * @return void
     * @covers \mod_ratingallocate\task\cron_task::execute
     */
    public function test_successful_run(): void {
        $this->create_ratingallocate(true);
        $this->run_cron();
        $this->assert_finish();
    }

    /**
     * The cron should not run, when the rating period has not ended.
     * @covers \mod_ratingallocate\task\cron_task::execute
     */
    public function test_rating_in_progress(): void {
        $this->create_ratingallocate(false);
        $this->run_cron();
        $this->assert_not_started();
    }

    /**
     * The cron should not run, when the algorithm status is running.
     * @covers \mod_ratingallocate\task\cron_task::execute
     */
    public function test_running(): void {
        $this->create_ratingallocate(true, algorithm_status::RUNNING);
        $this->run_cron();
        $this->assert_running();
    }

    /**
     * The cron should not run, when the algorithm status is failure.
     * @covers \mod_ratingallocate\task\cron_task::execute
     */
    public function test_failure(): void {
        $this->create_ratingallocate(true, algorithm_status::FAILURE);
        $this->run_cron();
        $this->assert_failure();
    }

    /**
     * The cron should not run, when the algorithm status is finished.
     * @covers \mod_ratingallocate\task\cron_task::execute
     */
    public function test_finished(): void {
        $this->create_ratingallocate(true, algorithm_status::FINISHED);
        $this->run_cron();
        $this->assert_already_finish();
    }

    /**
     * The cron should not change the status of the algorithm, since it is not timedout.
     * @covers \mod_ratingallocate\task\cron_task::execute
     */
    public function test_undue_failure_handling(): void {
        $this->create_ratingallocate(true, algorithm_status::RUNNING, time());
        $this->run_cron();
        $this->assert_running();
    }

    /**
     * The cron should switch the status of the algorithm to failure, since it has timedout.
     * @covers \mod_ratingallocate\task\cron_task::execute
     */
    public function test_due_failure_handling(): void {
        global $CFG;
        $this->create_ratingallocate(true, algorithm_status::RUNNING, time() - 2);
        $CFG->ratingallocate_algorithm_timeout = 1;
        $this->run_cron();
        $this->assert_failure();
    }
    /**
     * Creates a cron task and executes it.
     */
    private function run_cron() {
        $task = new cron_task();
        $this->setAdminUser();
        $task->execute();
    }

    /**
     * Assert, that the algorithm status is not_started and the algorithm has created no allocation.
     */
    private function assert_not_started() {
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, []);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(algorithm_status::NOTSTARTED, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations::TABLE,
                [this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id]));
    }

    /**
     * Assert, that the algorithm status is running and the algorithm has created no allocation.
     */
    private function assert_running() {
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, []);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(algorithm_status::RUNNING, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations::TABLE,
                [this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id]));
    }

    /**
     * Assert, that the algorithm status is failure and the algorithm has created no allocation.
     */
    private function assert_failure() {
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, []);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(algorithm_status::FAILURE, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations::TABLE,
                [this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id]));
    }

    /**
     * Assert, that the algorithm status is finished and the algorithm has created 4 allocations.
     */
    private function assert_finish() {
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, []);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(algorithm_status::FINISHED, $ratingallocate->get_algorithm_status());
        $this->assertEquals(4, $DB->count_records(this_db\ratingallocate_allocations::TABLE,
                [this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id]));
    }

    /**
     * Assert, that the algorithm status is still finished and the algorithm has created no allocation.
     */
    private function assert_already_finish() {
        global $DB;
        $record = $DB->get_record(this_db\ratingallocate::TABLE, []);
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $record, $this->teacher);
        $this->assertEquals(algorithm_status::FINISHED, $ratingallocate->get_algorithm_status());
        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations::TABLE,
                [this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id]));
    }

    /**
     * Create an ratingallocate module with 4 enroled students and their ratings.
     * @param bool $ratingperiodended determines if the rating period should have ended.
     * @param int $algorithmstatus the algorithm status of the modul to be created.
     * @param int $algorithmstarttime the start time of the algorithm.
     */
    private function create_ratingallocate($ratingperiodended,
                                           $algorithmstatus = algorithm_status::NOTSTARTED,
                                           $algorithmstarttime = null): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->teacher = \mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);
        $this->setUser($this->teacher);

        // There should not be any module for that course first.
        $this->assertFalse(
                $DB->record_exists(this_db\ratingallocate::TABLE, [this_db\ratingallocate::COURSE => $course->id,
                ]));
        $data = \mod_ratingallocate_generator::get_default_values();
        $data['course'] = $course;
        // Shift the rating period depending on its ending.
        $data['accesstimestart'] = time();
        $data['accesstimestop'] = time();
        if ($ratingperiodended) {
            $data['accesstimestart'] -= (6 * 24 * 60 * 60);
            // Necessary to ensure access time stop being in the past.
            --$data['accesstimestop'];
        } else {
            $data['accesstimestop'] += (6 * 24 * 60 * 60);
        }
        $data['algorithmstatus'] = $algorithmstatus;
        $data['algorithmstarttime'] = $algorithmstarttime;

        // Create activity.
        $this->mod = \mod_ratingallocate_generator::create_instance_with_choices($this, $data);
        $this->assertEquals(2, $DB->count_records(this_db\ratingallocate_choices::TABLE,
                [this_db\ratingallocate_choices::RATINGALLOCATEID => $this->mod->id]));

        $student1 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student2 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student3 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        $student4 = \mod_ratingallocate_generator::create_user_and_enrol($this, $course);

        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $this->mod, $this->teacher);
        $choices = $ratingallocate->get_rateable_choices();

        $choice1 = reset($choices);
        $choice2 = end($choices);

        // Create preferences.
        $prefersnon = [];
        foreach ($choices as $choice) {
            $prefersnon[$choice->{this_db\ratingallocate_choices::ID}] = [
                    this_db\ratingallocate_ratings::CHOICEID => $choice->{this_db\ratingallocate_choices::ID},
                    this_db\ratingallocate_ratings::RATING => 0];
        }
        $prefersfirst = json_decode(json_encode($prefersnon), true);
        $prefersfirst[$choice1->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;
        $preferssecond = json_decode(json_encode($prefersnon), true);
        $preferssecond[$choice2->{this_db\ratingallocate_choices::ID}][this_db\ratingallocate_ratings::RATING] = true;

        // Assign preferences.
        \mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student1, $prefersfirst);
        \mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student2, $prefersfirst);
        \mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student3, $preferssecond);
        \mod_ratingallocate_generator::save_rating_for_user($this, $this->mod, $student4, $preferssecond);

        $this->assertEquals(0, $DB->count_records(this_db\ratingallocate_allocations::TABLE,
                [this_db\ratingallocate_allocations::RATINGALLOCATEID => $this->mod->id]));
    }
}
