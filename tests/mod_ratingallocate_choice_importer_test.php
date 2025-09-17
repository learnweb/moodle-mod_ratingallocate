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
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/generator/lib.php');
require_once(__DIR__ . '/../locallib.php');

/**
 * Tests CSV Upload for ratingallocate choices.
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group      mod_ratingallocate
 * @copyright  2021 Catalyst IT
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \choice_importer
 */
final class mod_ratingallocate_choice_importer_test extends \advanced_testcase {
    /** @var object The environment that will be used for testing
     * This Class contains:
     * - A course
     * - A teacher
     * - Three groups: Green, Blue, Red
     * - A ratingallocate instance
     * - The ratingallocate instance ID
     */
    private $env;

    /**
     * Return lines of text for a sample CSV file.
     *
     * @param boolean $joined Join up the lines with line breaks.
     *
     * @return array Lines of text.
     */
    private function get_choice_lines($joined = false) {
        // Whitespace should be trimmed by the importer.
        $contents = [];
        $contents[] = 'title, explanation, maxsize, active, groups';
        $contents[] = 'New Test Choice 3,Explain New Choice 3, 10, 1,';
        $contents[] = 'New Test Choice 4,Explain New Choice 4, 100, 1,Green Group';
        $contents[] = 'New Test Choice 5,Explain New Choice 5, 1000, 0,Blue Group';

        if ($joined) {
            return join("\n", $contents) . "\n";
        } else {
            return $contents;
        }
    }

    protected function setUp(): void {
        parent::setUp();

        $this->env = new stdClass();

        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $this->env->course = $course;
        $this->env->teacher = \mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);

        // Make test groups.
        $this->env->green = $generator->create_group(['name' => 'Green Group', 'courseid' => $course->id]);
        $this->env->blue = $generator->create_group(['name' => 'Blue Group', 'courseid' => $course->id]);
        $this->env->red = $generator->create_group(['name' => 'Red Group', 'courseid' => $course->id]);

        $mod = \mod_ratingallocate_generator::create_instance_with_choices($this, ['course' => $course]);

        $this->env->ratingallocate = \mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->env->teacher);
        $this->env->ratingallocateid = $mod->id;
    }

    public function test_setup(): void {
        $this->resetAfterTest();

        // Groups in course context.
        $groupselections = $this->env->ratingallocate->get_group_selections();
        $this->assertEquals(3, count($groupselections));
        $this->assertContains('Green Group', $groupselections);
        $this->assertContains('Blue Group', $groupselections);
        $this->assertContains('Red Group', $groupselections);

        $choices = $this->env->ratingallocate->get_choices();
        $this->assertEquals(2, count($choices), 'Generator default: two pre-existing choices.');
    }

    public function test_choice_importer_testmode(): void {
        $this->resetAfterTest();
        $choiceimporter = new choice_importer($this->env->ratingallocateid, $this->env->ratingallocate);
        $this->assertTrue($choiceimporter instanceof choice_importer);

        // Test import.
        $csv = $this->get_choice_lines(true);
        $importstatus = $choiceimporter->import($csv, false);
        $this->assertEquals($importstatus->status, choice_importer::IMPORT_STATUS_OK);
        $this->assertEquals($importstatus->readcount, 4);
        $this->assertEquals($importstatus->importcount, 3);
        $this->assertEquals(
            $importstatus->status_message,
            get_string('csvupload_test_success', 'ratingallocate', ['importcount' => $importstatus->importcount])
        );

        /* Note: delegated transaction rollback doesn't seme to be  working inside PHPUnit tests.
         * If it were, we would test that the choice list hasn't changed after the test import.
         */
    }

    public function test_choice_importer_livemode(): void {
        $this->resetAfterTest();
        $choiceimporter = new choice_importer($this->env->ratingallocateid, $this->env->ratingallocate);
        $this->assertTrue($choiceimporter instanceof choice_importer);

        // Live import.
        $csv = $this->get_choice_lines(true);
        $importstatus = $choiceimporter->import($csv);
        $this->assertEquals($importstatus->status, choice_importer::IMPORT_STATUS_OK);
        $this->assertEquals($importstatus->readcount, 4);
        $this->assertEquals($importstatus->importcount, 3);
        $this->assertEquals(
            $importstatus->status_message,
            get_string('csvupload_live_success', 'ratingallocate', ['importcount' => $importstatus->importcount])
        );
        $choices = $this->env->ratingallocate->get_choices();
        $this->assertEquals(5, count($choices), 'Three new choices imported');
    }

    public function test_adding_groups(): void {
        $this->resetAfterTest();
        $choiceimporter = new choice_importer($this->env->ratingallocateid, $this->env->ratingallocate);

        $contents = $this->get_choice_lines(false);
        // Some new groups of groups, some with questionable whitespace and empty values in group names.
        $contents[] = 'New Test Choice 6,Explain New Choice 6, 100, 1," Blue Group, Green Group"';
        $contents[] = 'New Test Choice 7,Explain New Choice 7, 100, 1,"Blue Group,Green Group,Red Group "';
        $contents[] = 'New Test Choice 8,Explain New Choice 8, 100, 1,"Green Group,Red Group,, "';
        $contents[] = 'New Test Choice 9,Explain New Choice 9, 100, 1,Green Group ;Red Group;;; ';
        $contents[] = 'New Test Choice 10,Explain New Choice 10, 100, 1," Green Group; Red Group; Blue Group"';
        // Also add choices with semicolon as groups delimiter. Usually, comma and semicolon are not mixed up in the same
        // file, but it is being supported anyway, so we can test this right here.
        $csv = join("\n", $contents) . "\n";
        $importstatus = $choiceimporter->import($csv);
        $this->assertEquals($importstatus->status, choice_importer::IMPORT_STATUS_OK);

        $choices = $this->env->ratingallocate->get_choices();
        $this->assertEquals(10, count($choices), 'Eight new choices imported');

        // Get ordered list of keys of all added choices.
        ksort($choices);
        $keylist = array_keys($choices);

        // 3: No groups
        $choicegroups3 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[2]));
        $this->assertEquals(count($choicegroups3), 0);

        // 4: Green
        $choicegroups4 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[3]));
        $this->assertEquals(count($choicegroups4), 1);
        // Convert to integers here, because later PHP versions check type more strictly.
        $this->assertContains(intval($this->env->green->id), $choicegroups4);

        // 5: Blue
        $choicegroups5 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[4]));
        $this->assertEquals(count($choicegroups5), 1);
        $this->assertContains(intval($this->env->blue->id), $choicegroups5);

        // 6: Blue, Green
        $choicegroups6 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[5]));
        $this->assertEquals(count($choicegroups6), 2);
        $this->assertContains(intval($this->env->blue->id), $choicegroups6);
        $this->assertContains(intval($this->env->green->id), $choicegroups6);

        // 7: Blue, Green, Red
        $choicegroups7 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[6]));
        $this->assertEquals(count($choicegroups7), 3);
        $this->assertContains(intval($this->env->blue->id), $choicegroups7);
        $this->assertContains(intval($this->env->green->id), $choicegroups7);
        $this->assertContains(intval($this->env->red->id), $choicegroups7);

        // 8: Green, Red, empty values ignored
        $choicegroups8 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[7]));
        $this->assertEquals(count($choicegroups8), 2);
        $this->assertContains(intval($this->env->green->id), $choicegroups8);
        $this->assertContains(intval($this->env->red->id), $choicegroups8);

        // 9: Green, Red, empty values ignored
        $choicegroups9 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[8]));
        $this->assertEquals(count($choicegroups9), 2);
        $this->assertContains(intval($this->env->green->id), $choicegroups9);
        $this->assertContains(intval($this->env->red->id), $choicegroups9);

        // 10: Red, Green, Blue with whitespaces and quotation marks
        $choicegroups10 = array_keys($this->env->ratingallocate->get_choice_groups($keylist[9]));
        $this->assertEquals(count($choicegroups10), 3);
        $this->assertContains(intval($this->env->green->id), $choicegroups10);
        $this->assertContains(intval($this->env->red->id), $choicegroups10);
        $this->assertContains(intval($this->env->blue->id), $choicegroups10);
    }

    public function test_bad_group(): void {
        $this->resetAfterTest();
        $choiceimporter = new choice_importer($this->env->ratingallocateid, $this->env->ratingallocate);

        $contents = $this->get_choice_lines(false);
        $contents[] = 'Bad Test Choice 6,Explain Bad Choice 6, 1000, 1,Blue Man Group ';
        $csv = join("\n", $contents) . "\n";
        $importstatus = $choiceimporter->import($csv);
        $this->assertEquals($importstatus->status, choice_importer::IMPORT_STATUS_DATA_ERROR);
        $this->assertEquals($importstatus->readcount, 5);
        $this->assertEquals($importstatus->importcount, 4); // Will import, but no group association.
        $this->assertEquals($importstatus->errors[0], get_string('csvupload_missing_groups', 'ratingallocate', [
            'row' => 5,
            'invalidgroups' => 'Blue Man Group',
        ]));
        $this->assertEquals(
            $importstatus->status_message,
            get_string('csvupload_live_problems', 'ratingallocate', 1)
        );
        $choices = $this->env->ratingallocate->get_choices();
        $this->assertEquals(6, count($choices), 'Choice with bad group still imported');
        ksort($choices);

        // Fetch the last returned choice.
        $badchoice = array_slice($choices, -1)[0];
        $this->assertEquals($badchoice->title, 'Bad Test Choice 6');
        $this->assertEquals($badchoice->usegroups, 1);

        $badgroups = $this->env->ratingallocate->get_choice_groups($badchoice->id);
        $this->assertEquals(count($badgroups), 0, 'Bad group has not been added');
    }
}
