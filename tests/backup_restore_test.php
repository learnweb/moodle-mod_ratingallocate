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
require_once(dirname(__FILE__) . '/generator/lib.php');
require_once(dirname(__FILE__) . '/../locallib.php');

// Get the necessary files to perform backup and restore.
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use ratingallocate\db as this_db;

/**
 * mod_ratingallocate backup restore procedure test
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  usener
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class backup_restore_test extends advanced_testcase {

    public function test_backup_restore() {
        // TODO this test does not check if userids are correctly mapped
        global $CFG, $DB;
        core_php_time_limit::raise();
        // Set to admin user.
        $this->setAdminUser();

        $gen_mod = new mod_ratingallocate_generated_module($this);
        $course1 = $gen_mod->course;
        // Create backup file and save it to the backup location.
        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $gen_mod->mod_db->cmid, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, 2);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        //TODO: Necessary to ensure backward compatibility
        if (tgz_packer::is_tgz_file($file)) {
            $fp = get_file_packer('application/x-gzip');
        } else {
            $fp = get_file_packer();
        }
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();
        unset($bc);

        // Create a course that we are going to restore the other course to.
        $course2 = $this->getDataGenerator()->create_course();
        // Now restore the course.
        $rc = new restore_controller('test-restore-course', $course2->id, backup::INTERACTIVE_NO,
                backup::MODE_GENERAL, 2, backup::TARGET_NEW_COURSE);
        $rc->execute_precheck();
        $rc->execute_plan();

        $unset_values = function($elem1, $elem2, $varname) { $this->assertNotEquals($elem1->{$varname}, $elem2->{$varname}); $result = array($elem1->{$varname},$elem2->{$varname}); unset($elem1->{$varname}); unset($elem2->{$varname}); return $result; };

        $ratingallocate1 = $DB->get_record(this_db\ratingallocate::TABLE, array(this_db\ratingallocate::COURSE => $course1->id));
        $ratingallocate2 = $DB->get_record(this_db\ratingallocate::TABLE, array(this_db\ratingallocate::COURSE => $course2->id));
        list($rating_id1, $rating_id2) = $unset_values($ratingallocate1, $ratingallocate2, this_db\ratingallocate::ID);
        $unset_values($ratingallocate1, $ratingallocate2, this_db\ratingallocate::COURSE);
        $this->assertEquals($ratingallocate1, $ratingallocate2);

        $choices1 = $DB->get_records(this_db\ratingallocate_choices::TABLE,array(this_db\ratingallocate_choices::RATINGALLOCATEID => $rating_id1), this_db\ratingallocate_choices::TITLE);
        $choices2 = $DB->get_records(this_db\ratingallocate_choices::TABLE,array(this_db\ratingallocate_choices::RATINGALLOCATEID => $rating_id2), this_db\ratingallocate_choices::TITLE);
        $this->assertCount(2, $choices1);
        $this->assertCount(2, array_values($choices2));
        $choice2_copy = $choices2;
        foreach ($choices1 as $choice1) {
            //work with copies
            $choice2 = json_decode(json_encode(array_shift($choice2_copy)));
            $choice1 = json_decode(json_encode($choice1));
            list($choiceid1,$choiceid2) = $unset_values($choice1, $choice2, this_db\ratingallocate_choices::ID);
            $unset_values($choice1, $choice2, this_db\ratingallocate_choices::RATINGALLOCATEID);
            $this->assertEquals($choice1, $choice2);
            // compare ratings for this choice
            $ratings1 = array_values($DB->get_records(this_db\ratingallocate_ratings::TABLE, array(this_db\ratingallocate_ratings::CHOICEID => $choiceid1), this_db\ratingallocate_ratings::USERID));
            $ratings2 = array_values($DB->get_records(this_db\ratingallocate_ratings::TABLE, array(this_db\ratingallocate_ratings::CHOICEID => $choiceid2), this_db\ratingallocate_ratings::USERID));
            $this->assertEquals(count($ratings1), count($ratings2));
            $ratings2_copy = $ratings2;
            foreach ($ratings1 as $rating1) {
                $rating2 = json_decode(json_encode(array_shift($ratings2_copy)));
                $rating1 = json_decode(json_encode($rating1));
                $unset_values($rating1, $rating2, this_db\ratingallocate_ratings::CHOICEID);
                $unset_values($rating1, $rating2, this_db\ratingallocate_ratings::ID);
                $this->assertEquals($rating1, $rating2);
            }
        }


        // compare allocations
        $allocations1 = $DB->get_records(this_db\ratingallocate_allocations::TABLE,array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $rating_id1), this_db\ratingallocate_allocations::USERID);
        $allocations2 = $DB->get_records(this_db\ratingallocate_allocations::TABLE,array(this_db\ratingallocate_allocations::RATINGALLOCATEID => $rating_id2), this_db\ratingallocate_allocations::USERID);
        // number of allocations is equal
        //$this->assertCount(count($allocations1), $allocations2);
        $this->assertCount(count($gen_mod->allocations) , $allocations2);
        // create function that can be used to replace  
        $map_allocation_to_choice_title = function(&$alloc, $choices) {
                $alloc->{'choice_title'} = $choices[$alloc->{this_db\ratingallocate_allocations::CHOICEID}]->{this_db\ratingallocate_choices::TITLE};
        };
        // compare allocations in detail!
        $alloc2 = reset($allocations2);
        foreach ($allocations1 as &$alloc1) {
            $map_allocation_to_choice_title($alloc1, $choices1);
            $map_allocation_to_choice_title($alloc2, $choices2);
            $unset_values($alloc1,$alloc2,this_db\ratingallocate_allocations::RATINGALLOCATEID);
            $unset_values($alloc1,$alloc2,this_db\ratingallocate_allocations::CHOICEID);
            $unset_values($alloc1,$alloc2,this_db\ratingallocate_allocations::ID);
            $alloc2 = next($allocations2);
        }
        $this->assertEquals(array_values($allocations1), array_values($allocations2));
    }
}