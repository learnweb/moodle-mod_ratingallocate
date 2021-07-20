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
 */
class mod_ratingallocate_choice_group_testcase extends advanced_testcase {

    /**
     * Return lines of text for a sample CSV file.
     *
     * @param boolean $joined Join up the lines with line breaks.
     *
     * @return array Lines of text.
     */
    private function get_choice_lines($joined=false) {
        // Whitespace should be trimmed by the importer.
        $contents = array();
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

    protected function setUp() {
        parent::setUp();

        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $this->course = $course;
        $this->teacher = mod_ratingallocate_generator::create_user_and_enrol($this, $course, true);

        // Make test groups.
        $this->green = $generator->create_group(array('name' => 'Green Group', 'courseid' => $course->id));
        $this->blue = $generator->create_group(array('name' => 'Blue Group', 'courseid' => $course->id));
        $this->red = $generator->create_group(array('name' => 'Red Group', 'courseid' => $course->id));

        $mod = mod_ratingallocate_generator::create_instance_with_choices($this, array('course' => $course));

        $this->ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($this, $mod, $this->teacher);
        $this->ratingallocateid = $mod->id;
    }

    public function test_setup() {
        $this->resetAfterTest();

        // Groups in course context.
        $groupselections = $this->ratingallocate->get_group_selections();
        $this->assertEquals(3, count($groupselections));
        $this->assertContains('Green Group', $groupselections);
        $this->assertContains('Blue Group', $groupselections);
        $this->assertContains('Red Group', $groupselections);

        $choices = $this->ratingallocate->get_choices();
        $this->assertEquals(2, count($choices), 'Generator default: two pre-existing choices.');
    }

    public function test_choice_importer_testmode() {
        $this->resetAfterTest();
        $choiceimporter = new \mod_ratingallocate\choice_importer($this->ratingallocateid, $this->ratingallocate);
        $this->assertTrue($choiceimporter instanceof \mod_ratingallocate\choice_importer);

        // Test import.
        $csv = $this->get_choice_lines(true);
        $importstatus = $choiceimporter->import($csv, false);
        $this->assertEquals($importstatus->status, \mod_ratingallocate\choice_importer::IMPORT_STATUS_OK);
        $this->assertEquals($importstatus->readcount, 4);
        $this->assertEquals($importstatus->importcount, 3);
        $this->assertEquals($importstatus->status_message,
            get_string('csvupload_test_success', 'ratingallocate', array('importcount' => $importstatus->importcount))
        );

        /* Note: delegated transaction rollback doesn't seme to be  working inside PHPUnit tests.
         * If it were, we would test that the choice list hasn't changed after the test import.
         */
    }

    public function test_choice_importer_livemode() {
        $this->resetAfterTest();
        $choiceimporter = new \mod_ratingallocate\choice_importer($this->ratingallocateid, $this->ratingallocate);
        $this->assertTrue($choiceimporter instanceof \mod_ratingallocate\choice_importer);

        // Live import.
        $csv = $this->get_choice_lines(true);
        $importstatus = $choiceimporter->import($csv);
        $this->assertEquals($importstatus->status, \mod_ratingallocate\choice_importer::IMPORT_STATUS_OK);
        $this->assertEquals($importstatus->readcount, 4);
        $this->assertEquals($importstatus->importcount, 3);
        $this->assertEquals($importstatus->status_message,
            get_string('csvupload_live_success', 'ratingallocate', array('importcount' => $importstatus->importcount))
        );
        $choices = $this->ratingallocate->get_choices();
        $this->assertEquals(5, count($choices), 'Three new choices imported');
    }

    public function test_adding_groups() {
        $this->resetAfterTest();
        $choiceimporter = new \mod_ratingallocate\choice_importer($this->ratingallocateid, $this->ratingallocate);

        $contents = $this->get_choice_lines(false);
        // Some new groups of groups, some with questionable whitespace and empty values in group names.
        $contents[] = 'New Test Choice 6,Explain New Choice 6, 100, 1," Blue Group, Green Group"';
        $contents[] = 'New Test Choice 7,Explain New Choice 7, 100, 1,"Blue Group,Green Group,Red Group "';
        $contents[] = 'New Test Choice 8,Explain New Choice 8, 100, 1,"Green Group,Red Group,, "';
        $csv = join("\n", $contents) . "\n";
        $importstatus = $choiceimporter->import($csv);
        $this->assertEquals($importstatus->status, \mod_ratingallocate\choice_importer::IMPORT_STATUS_OK);

        $choices = $this->ratingallocate->get_choices();
        $this->assertEquals(8, count($choices), 'Six new choices imported');

        // Get ordered list of keys of all added choices.
        ksort($choices);
        $keylist = array_keys($choices);

        // 3: No groups
        $choicegroups3 = array_keys($this->ratingallocate->get_choice_groups($keylist[2]));
        $this->assertEquals(count($choicegroups3), 0);

        // 4: Green
        $choicegroups4 = array_keys($this->ratingallocate->get_choice_groups($keylist[3]));
        $this->assertEquals(count($choicegroups4), 1);
        $this->assertContains($this->green->id, $choicegroups4);

        // 5: Blue
        $choicegroups5 = array_keys($this->ratingallocate->get_choice_groups($keylist[4]));
        $this->assertEquals(count($choicegroups5), 1);
        $this->assertContains($this->blue->id, $choicegroups5);

        // 6: Blue, Green
        $choicegroups6 = array_keys($this->ratingallocate->get_choice_groups($keylist[5]));
        $this->assertEquals(count($choicegroups6), 2);
        $this->assertContains($this->blue->id, $choicegroups6);
        $this->assertContains($this->green->id, $choicegroups6);

        // 7: Blue, Green, Red
        $choicegroups7 = array_keys($this->ratingallocate->get_choice_groups($keylist[6]));
        $this->assertEquals(count($choicegroups7), 3);
        $this->assertContains($this->blue->id, $choicegroups7);
        $this->assertContains($this->green->id, $choicegroups7);
        $this->assertContains($this->red->id, $choicegroups7);

        // 8: Green, Red, empty values ignored
        $choicegroups8 = array_keys($this->ratingallocate->get_choice_groups($keylist[7]));
        $this->assertEquals(count($choicegroups8), 2);
        $this->assertContains($this->green->id, $choicegroups8);
        $this->assertContains($this->red->id, $choicegroups8);
    }

    public function test_bad_group() {
        $this->resetAfterTest();
        $choiceimporter = new \mod_ratingallocate\choice_importer($this->ratingallocateid, $this->ratingallocate);

        $contents = $this->get_choice_lines(false);
        $contents[] = 'Bad Test Choice 6,Explain Bad Choice 6, 1000, 1,Blue Man Group ';
        $csv = join("\n", $contents) . "\n";
        $importstatus = $choiceimporter->import($csv);
        $this->assertEquals($importstatus->status, \mod_ratingallocate\choice_importer::IMPORT_STATUS_DATA_ERROR);
        $this->assertEquals($importstatus->readcount, 5);
        $this->assertEquals($importstatus->importcount, 4); // Will import, but no group association.
        $this->assertEquals($importstatus->errors[0], get_string('csvupload_missing_groups', 'ratingallocate', array(
            'row' => 5,
            'invalidgroups' => 'Blue Man Group',
        )));
        $this->assertEquals($importstatus->status_message,
            get_string('csvupload_live_problems', 'ratingallocate', 1)
        );
        $choices = $this->ratingallocate->get_choices();
        $this->assertEquals(6, count($choices), 'Choice with bad group still imported');
        ksort($choices);

        // Fetch the last returned choice.
        $badchoice = array_slice($choices, -1)[0];
        $this->assertEquals($badchoice->title, 'Bad Test Choice 6');
        $this->assertEquals($badchoice->usegroups, 1);

        $badgroups = $this->ratingallocate->get_choice_groups($badchoice->id);
        $this->assertEquals(count($badgroups), 0, 'Bad group has not been added');
    }

}
