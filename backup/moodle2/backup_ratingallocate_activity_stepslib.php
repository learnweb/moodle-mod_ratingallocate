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

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $class = 'ratingallocate\db\ratingallocate';
        $ratingallocate = new backup_nested_element(
            get_tablename_for_tableclass($class),
            get_id_for_tableclass($class),
            get_fields_for_tableclass($class)
        );

        $class = 'ratingallocate\db\ratingallocate_choices';
        $ratingallocatechoices = new backup_nested_element(get_tablename_for_tableclass($class) . 's');
        $ratingallocatechoice = new backup_nested_element(get_tablename_for_tableclass($class), get_id_for_tableclass($class),
                get_fields_for_tableclass($class));

        $class = 'ratingallocate\db\ratingallocate_ratings';
        $ratingallocateratings = new backup_nested_element(get_tablename_for_tableclass($class) . 's');
        $ratingallocaterating = new backup_nested_element(get_tablename_for_tableclass($class), get_id_for_tableclass($class),
                get_fields_for_tableclass($class));

        $class = 'ratingallocate\db\ratingallocate_allocations';
        $ratingallocateallocations = new backup_nested_element(get_tablename_for_tableclass($class) . 's');
        $ratingallocateallocation = new backup_nested_element(get_tablename_for_tableclass($class), get_id_for_tableclass($class),
                get_fields_for_tableclass($class));

        $groupchoiceclass = 'ratingallocate\db\ratingallocate_group_choices';
        $groupchoices = new backup_nested_element(get_tablename_for_tableclass($groupchoiceclass) . 's');
        $groupchoice = new backup_nested_element(get_tablename_for_tableclass($groupchoiceclass),
                                                 get_id_for_tableclass($groupchoiceclass),
                                                 get_fields_for_tableclass($groupchoiceclass));

        $choicegroupclass = 'ratingallocate\db\ratingallocate_choice_groups';
        $ratingallocatechoicegroups = new backup_nested_element(get_tablename_for_tableclass($choicegroupclass) . 's');
        $ratingallocatechoicegroup = new backup_nested_element(get_tablename_for_tableclass($choicegroupclass),
                                                 get_id_for_tableclass($choicegroupclass),
                                                 get_fields_for_tableclass($choicegroupclass));

        $groupingclass = 'ratingallocate\db\ratingallocate_groupings';
        $ratingallocategroupings = new backup_nested_element(get_tablename_for_tableclass($groupingclass) . 's');
        $ratingallocategrouping = new backup_nested_element(get_tablename_for_tableclass($groupingclass),
                                                get_ratingallocateid_for_tableclass($groupingclass),
                                                get_fields_for_tableclass($groupingclass));

        // Build the tree.
        $ratingallocate->add_child($ratingallocatechoices);
        $ratingallocatechoices->add_child($ratingallocatechoice);

        $ratingallocate->add_child($ratingallocategroupings);
        $ratingallocategroupings->add_child($ratingallocategrouping);

        $ratingallocatechoice->add_child($ratingallocateallocations);
        $ratingallocateallocations->add_child($ratingallocateallocation);

        $ratingallocatechoice->add_child($ratingallocateratings);
        $ratingallocateratings->add_child($ratingallocaterating);

        $ratingallocatechoice->add_child($groupchoices);
        $groupchoices->add_child($groupchoice);

        $ratingallocatechoices->add_child($ratingallocatechoicegroups);
        $ratingallocatechoicegroups->add_child($ratingallocatechoicegroup);

        // Define sources
        $ratingallocate->set_source_table(get_tablename_for_tableclass('ratingallocate\db\ratingallocate'),
                array(this_db\ratingallocate::ID => backup::VAR_ACTIVITYID), this_db\ratingallocate_choices::ID . ' ASC');
        $ratingallocatechoice->set_source_table(get_tablename_for_tableclass('ratingallocate\db\ratingallocate_choices'),
                array(this_db\ratingallocate_choices::RATINGALLOCATEID => backup::VAR_PARENTID),
                this_db\ratingallocate_choices::ID . ' ASC');
        $groupchoice->set_source_table(get_tablename_for_tableclass($groupchoiceclass), ['choiceid' => backup::VAR_PARENTID]);
        $ratingallocatechoicegroup->set_source_table(get_tablename_for_tableclass($choicegroupclass), ['choiceid' => backup::VAR_PARENTID]);
        $ratingallocategrouping->set_source_table(get_tablename_for_tableclass($groupingclass), ['ratingallocateid' => backup::VAR_PARENTID]);

        if ($userinfo) {
            $ratingallocaterating->set_source_table(get_tablename_for_tableclass('ratingallocate\db\ratingallocate_ratings'),
                    array(this_db\ratingallocate_ratings::CHOICEID => backup::VAR_PARENTID),
                    this_db\ratingallocate_ratings::ID . ' ASC');
            $ratingallocateallocation->set_source_table(get_tablename_for_tableclass('ratingallocate\db\ratingallocate_allocations'),
                    array(
                            this_db\ratingallocate_allocations::RATINGALLOCATEID => backup::VAR_ACTIVITYID,
                            this_db\ratingallocate_allocations::CHOICEID => backup::VAR_PARENTID),
                    this_db\ratingallocate_allocations::ID . ' ASC'
            );
        }

        // Define id annotations
        $ratingallocateallocation->annotate_ids('user', this_db\ratingallocate_allocations::USERID);
        $ratingallocaterating->annotate_ids('user', this_db\ratingallocate_ratings::USERID);
        $groupchoice->annotate_ids('group', 'groupid');
        $ratingallocatechoicegroup->annotate_ids('group', 'groupid');
        $ratingallocategrouping->annotate_ids('grouping', 'groupingid');

        // Define file annotations
        $ratingallocate->annotate_files('mod_' . RATINGALLOCATE_MOD_NAME, 'intro', null);

        // Return the root element (ratingallocate), wrapped into standard activity structure
        return $this->prepare_activity_structure($ratingallocate);
    }
}
