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
 * Privacy provider tests.
 *
 * @package    mod_ratingallocate
 * @copyright  2018 Tamara Gunkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use mod_ratingallocate\privacy\provider;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/generator/lib.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_ratingallocate
 * @copyright  2018 Tamara Gunkel
 * @group      mod_ratingallocate
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {
    protected $testmodule;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
        $this->resetAfterTest();
        $this->testmodule = new mod_ratingallocate_generated_module($this);
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('mod_ratingallocate');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(4, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('ratingallocate_ratings', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('choiceid', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('rating', $privacyfields);

        $this->assertEquals('privacy:metadata:ratingallocate_ratings', $table->get_summary());

        $table = next($itemcollection);
        $this->assertEquals('ratingallocate_allocations', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('ratingallocateid', $privacyfields);
        $this->assertArrayHasKey('choiceid', $privacyfields);

        $this->assertEquals('privacy:metadata:ratingallocate_allocations', $table->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $cm = get_coursemodule_from_instance('ratingallocate', $this->testmodule->moddb->id);

        $contextlist = provider::get_contexts_for_userid($this->testmodule->students[0]->id);
        $this->assertCount(1, $contextlist);
        $contextforuser = $contextlist->current();
        $cmcontext = context_module::instance($cm->id);
        $this->assertEquals($cmcontext->id, $contextforuser->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context() {
        $cm = get_coursemodule_from_instance('ratingallocate',  $this->testmodule->moddb->id);
        $cmcontext = context_module::instance($cm->id);

        // Export all of the data for the context.
        $this->export_context_data_for_user($this->testmodule->students[0]->id, $cmcontext, 'mod_ratingallocate');
        $writer = \core_privacy\local\request\writer::with_context($cmcontext);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $cm = get_coursemodule_from_instance('ratingallocate',  $this->testmodule->moddb->id);

        // Before deletion, we should have 20 responses and 20 allocations.
        $count = $DB->count_records('ratingallocate_ratings');
        $this->assertEquals(20, $count);
        $count = $DB->count_records('ratingallocate_allocations');
        $this->assertEquals(10, $count);

        // Delete data based on context.
        $cmcontext = context_module::instance($cm->id);
        provider::delete_data_for_all_users_in_context($cmcontext);

        // After deletion, the ratings and allocations for the ratingallocate activity should have been deleted.
        $count = $DB->count_records('ratingallocate_ratings');
        $this->assertEquals(0, $count);
        $count = $DB->count_records('ratingallocate_allocations');
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;

        $cm = get_coursemodule_from_instance('ratingallocate',  $this->testmodule->moddb->id);
        $context = context_module::instance($cm->id);
        $student = core_user::get_user(array_pop($this->testmodule->allocations)->userid);

        // Before deletion, we should have 2 responses.
        $count = $DB->count_records('ratingallocate_ratings');
        $this->assertEquals(20, $count);

        $contextlist = new \core_privacy\local\request\approved_contextlist($student, 'ratingallocate',
            [$context->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion, the ratings and allocations for the first student should have been deleted.
        $count = $DB->count_records('ratingallocate_ratings', ['userid' => $student->id]);
        $this->assertEquals(0, $count);
        $count = $DB->count_records('ratingallocate_allocations', ['userid' => $student->id]);
        $this->assertEquals(0, $count);

        // The other ratings and allocations should be still available.
        $count = $DB->count_records('ratingallocate_ratings');
        $this->assertEquals(19, $count);
        $count = $DB->count_records('ratingallocate_allocations');
        $this->assertEquals(9, $count);
    }

    /**
     * Test for provider::get_users_in_context().
     */
    public function test_get_users_in_context() {
        global $DB;
        $cm = get_coursemodule_from_instance('ratingallocate',  $this->testmodule->moddb->id);

        // Before deletion, we should have 20 responses and 20 allocations.
        $count = $DB->count_records('ratingallocate_ratings');
        $this->assertEquals(20, $count);
        $count = $DB->count_records('ratingallocate_allocations');
        $this->assertEquals(10, $count);

        // Get data based on context.
        $cmcontext = context_module::instance($cm->id);

        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'mod_ratingallocate');
        provider::get_users_in_context($userlist);

        // There are 20 students with ratings.
        $this->assertCount(20, $userlist, "There should be 20 students with data in the instance.");

        mod_ratingallocate_generator::create_user_and_enrol($this, $this->testmodule->course);

        $enrolledusers = get_enrolled_users($cmcontext);

        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'mod_ratingallocate');
        provider::get_users_in_context($userlist);

        // Tere are one teacher and 21 students.
        $this->assertCount(22, $enrolledusers);
        $this->assertCount(20, $userlist, "There should still be only 20 students with data in the instance.");
    }

    /**
     * Test for provider::delete_for_users_in_context().
     */
    public function test_delete_for_users_in_context(){
        global $DB;
        $testmodule2 = new mod_ratingallocate_generated_module($this);
        $testmodule2->moddb->id;
        $cm = get_coursemodule_from_instance('ratingallocate',  $this->testmodule->moddb->id);

        $params1 = array(
            'ratingallocateid' => $this->testmodule->moddb->id
        );
        $params2 = array(
            'ratingallocateid' => $testmodule2->moddb->id
        );

        // Before deletion, we should have 20 responses and 10 allocations in instance 1.
        $count = $DB->count_records_select('ratingallocate_ratings',
            "choiceid IN (SELECT id FROM {ratingallocate_choices} ".
            "WHERE ratingallocateid = :ratingallocateid)", $params1);
        $this->assertEquals(20, $count);
        $count = $DB->count_records('ratingallocate_allocations', $params1);
        $this->assertEquals(10, $count);
        // Before deletion, we should have 20 responses and 10 allocations in instance 2.
        $count = $DB->count_records_select('ratingallocate_ratings',
            "choiceid IN (SELECT id FROM {ratingallocate_choices} ".
            "WHERE ratingallocateid = :ratingallocateid)", $params2);
        $this->assertEquals(20, $count);
        $count = $DB->count_records('ratingallocate_allocations', $params2);
        $this->assertEquals(10, $count);

        // Delete data based on context.
        $cmcontext = context_module::instance($cm->id);

        $userlist = array();
        // Select one unassigned student.
        $userlist []= $DB->get_record_sql("SELECT ra.userid 
            FROM {ratingallocate_choices} ch JOIN
            {ratingallocate_ratings} ra ON ra.choiceid = ch.id LEFT JOIN 
            {ratingallocate_allocations} a ON a.choiceid = ch.id AND ra.userid = a.userid 
            WHERE ch.ratingallocateid = :ratingallocateid AND a.id is null limit 1", $params1)->userid;
        // Select one assigned student.
        $userlist []= array_pop($this->testmodule->allocations)->userid;

        $approveduserlist = new \core_privacy\local\request\approved_userlist($cmcontext, 'mod_ratingallocate',
            $userlist);
        provider::delete_data_for_users($approveduserlist);

        // Afterwards 2 ratings and 1 allocation should be missing.
        $count = $DB->count_records_select('ratingallocate_ratings',
            "choiceid IN (SELECT id FROM {ratingallocate_choices} ".
            "WHERE ratingallocateid = :ratingallocateid)", $params1);
        $this->assertEquals(18, $count);
        $count = $DB->count_records('ratingallocate_allocations', $params1);
        $this->assertEquals(9, $count);
        // The second instance should not be touched.
        $count = $DB->count_records_select('ratingallocate_ratings',
            "choiceid IN (SELECT id FROM {ratingallocate_choices} ".
            "WHERE ratingallocateid = :ratingallocateid)", $params2);
        $this->assertEquals(20, $count);
        $count = $DB->count_records('ratingallocate_allocations', $params2);
        $this->assertEquals(10, $count);
    }

}
