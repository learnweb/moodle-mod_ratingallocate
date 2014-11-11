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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright  2014 C. Usener
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete ratingallocate structure for backup, with [file and] id annotations
 */
class backup_ratingallocate_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $ratingallocate = new backup_nested_element('ratingallocate', array('id'), array('course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'accesstimestart', 'accesstimestop', 'setting', 'strategy', 'publishdate', 'published'));

        $ratingallocate_choices = new backup_nested_element('ratingallocate_choices' . 's');
        $ratingallocate_choice = new backup_nested_element('ratingallocate_choices', array('id'), array('ratingallocateid', 'title', 'explanation', 'maxsize', 'active'));

        $ratingallocate_ratings = new backup_nested_element('ratingallocate_ratings' . 's');
        $ratingallocate_rating = new backup_nested_element('ratingallocate_ratings',  array('id'), array('choiceid', 'userid', 'rating'));

        $ratingallocate_allocations = new backup_nested_element('ratingallocate_allocations' . 's');
        $ratingallocate_allocation = new backup_nested_element('ratingallocate_allocations',  array('id'), array('userid', 'ratingallocateid', 'choiceid'));

        // Build the tree
        $ratingallocate->add_child($ratingallocate_choices);
        $ratingallocate_choices->add_child($ratingallocate_choice);

        $ratingallocate_choice->add_child($ratingallocate_ratings);
        $ratingallocate_ratings->add_child($ratingallocate_rating);

        $ratingallocate_choice->add_child($ratingallocate_allocations);
        $ratingallocate_allocations->add_child($ratingallocate_allocation);

        // Define sources
        $ratingallocate->set_source_table('ratingallocate', array('id' => backup::VAR_ACTIVITYID));
        $ratingallocate_choice->set_source_table('ratingallocate_choices', array('ratingallocateid' => backup::VAR_PARENTID), 'id ASC');

        if ($userinfo) {
            $ratingallocate_rating->set_source_table('ratingallocate_ratings', array('choiceid' => backup::VAR_PARENTID), 'id ASC');
            $ratingallocate_allocation->set_source_table('ratingallocate_allocations', array('ratingallocateid' => backup::VAR_ACTIVITYID, 'choiceid' => backup::VAR_PARENTID), 'id ASC');
        }

        // Define id annotations
        $ratingallocate_allocation->annotate_ids('user', 'userid');
        $ratingallocate_rating->annotate_ids('user', 'userid');

        // Define file annotations
        $ratingallocate->annotate_files('mod_' . ratingallocate_MOD_NAME, 'intro', null);

        // Return the root element (ratingallocate), wrapped into standard activity structure
        return $this->prepare_activity_structure($ratingallocate);
    }
}