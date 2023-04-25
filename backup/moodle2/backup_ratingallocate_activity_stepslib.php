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

require_once(dirname(__FILE__) . '/backup_restore_helper.php');
use ratingallocate\db as this_db;
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

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $class = 'ratingallocate\db\ratingallocate';
        $ratingallocate = new backup_nested_element(
            get_tablename_for_tableClass($class),
            get_id_for_tableClass($class),
            get_fields_for_tableClass($class)
        );

        $class = 'ratingallocate\db\ratingallocate_choices';
        $ratingallocate_choices = new backup_nested_element(get_tablename_for_tableClass($class) . 's');
        $ratingallocate_choice = new backup_nested_element(
            get_tablename_for_tableClass($class),
            get_id_for_tableClass($class),
            get_fields_for_tableClass($class)
        );

        $class = 'ratingallocate\db\ratingallocate_ratings';
        $ratingallocate_ratings = new backup_nested_element(get_tablename_for_tableClass($class) . 's');
        $ratingallocate_rating = new backup_nested_element(
            get_tablename_for_tableClass($class),
            get_id_for_tableClass($class),
            get_fields_for_tableClass($class)
        );

        $class = 'ratingallocate\db\ratingallocate_allocations';
        $ratingallocate_allocations = new backup_nested_element(get_tablename_for_tableClass($class) . 's');
        $ratingallocate_allocation = new backup_nested_element(
            get_tablename_for_tableClass($class),
            get_id_for_tableClass($class),
            get_fields_for_tableClass($class)
        );

        $groupchoiceclass = 'ratingallocate\db\ratingallocate_group_choices';
        $groupchoices = new backup_nested_element(get_tablename_for_tableClass($groupchoiceclass) . 's');
        $groupchoice = new backup_nested_element(get_tablename_for_tableClass($groupchoiceclass),
                                                 get_id_for_tableClass($groupchoiceclass),
                                                 get_fields_for_tableClass($groupchoiceclass));

        $choicegroupclass = 'ratingallocate\db\ratingallocate_choice_groups';
        $ratingallocate_choicegroups = new backup_nested_element(get_tablename_for_tableClass($choicegroupclass) . 's');
        $ratingallocate_choicegroup = new backup_nested_element(get_tablename_for_tableClass($choicegroupclass),
                                                 get_id_for_tableClass($choicegroupclass),
                                                 get_fields_for_tableClass($choicegroupclass));

        $groupingclass = 'ratingallocate\db\ratingallocate_groupings';
        $ratingallocate_groupings = new backup_nested_element(get_tablename_for_tableClass($groupingclass) . 's');
        $ratingallocate_grouping = new backup_nested_element(get_tablename_for_tableClass($groupingclass),
                                                get_id_for_tableClass($groupingclass),
                                                get_fields_for_tableClass($groupingclass));


        // Build the tree.
        $ratingallocate->add_child($ratingallocate_choices);
        $ratingallocate_choices->add_child($ratingallocate_choice);

        $ratingallocate->add_child($ratingallocate_groupings);
        $ratingallocate_groupings->add_child($ratingallocate_grouping);

        $ratingallocate_choice->add_child($ratingallocate_ratings);
        $ratingallocate_ratings->add_child($ratingallocate_rating);

        $ratingallocate_choice->add_child($ratingallocate_allocations);
        $ratingallocate_allocations->add_child($ratingallocate_allocation);

        $ratingallocate_choice->add_child($groupchoices);
        $groupchoices->add_child($groupchoice);

        $ratingallocate_choice->add_child($ratingallocate_choicegroups);
        $ratingallocate_choicegroups->add_child($ratingallocate_choicegroup);

        // Define sources.
        $ratingallocate->set_source_table(get_tablename_for_tableClass('ratingallocate\db\ratingallocate'), array(this_db\ratingallocate::ID => backup::VAR_ACTIVITYID), this_db\ratingallocate_choices::ID . ' ASC');
        $ratingallocate_choice->set_source_table(get_tablename_for_tableClass('ratingallocate\db\ratingallocate_choices'), array(this_db\ratingallocate_choices::RATINGALLOCATEID => backup::VAR_PARENTID), this_db\ratingallocate_choices::ID . ' ASC');
        $groupchoice->set_source_table(get_tablename_for_tableClass($groupchoiceclass), ['choiceid' => backup::VAR_PARENTID]);
        $ratingallocate_choicegroup->set_source_table(get_tablename_for_tableClass($choicegroupclass), ['choiceid' => backup::VAR_PARENTID]);
        $ratingallocate_grouping->set_source_table(get_tablename_for_tableClass($groupingclass), ['ratingallocateid' => backup::VAR_PARENTID]);

        if ($userinfo) {
            $ratingallocate_rating->set_source_table(get_tablename_for_tableClass('ratingallocate\db\ratingallocate_ratings'), array(this_db\ratingallocate_ratings::CHOICEID => backup::VAR_PARENTID), this_db\ratingallocate_ratings::ID . ' ASC');
            $ratingallocate_allocation->set_source_table(get_tablename_for_tableClass('ratingallocate\db\ratingallocate_allocations'), array(this_db\ratingallocate_allocations::RATINGALLOCATEID => backup::VAR_ACTIVITYID, this_db\ratingallocate_allocations::CHOICEID => backup::VAR_PARENTID), this_db\ratingallocate_allocations::ID . ' ASC');
        }

        // Define id annotations.
        $ratingallocate_allocation->annotate_ids('user', this_db\ratingallocate_allocations::USERID);
        $ratingallocate_rating->annotate_ids('user', this_db\ratingallocate_ratings::USERID);
        $groupchoice->annotate_ids('group', 'groupid');
        $ratingallocate_choicegroup->annotate_ids('group', 'groupid');
        $ratingallocate_grouping->annotate_ids('grouping', 'groupingid');

        // Define file annotations.
        $ratingallocate->annotate_files('mod_' . ratingallocate_MOD_NAME, 'intro', null);

        // Return the root element (ratingallocate), wrapped into standard activity structure.
        return $this->prepare_activity_structure($ratingallocate);
    }
}
