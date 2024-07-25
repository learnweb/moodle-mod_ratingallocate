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

use mod_ratingallocate\db as this_db;

/**
 *
 * @package mod_ratingallocate
 * @subpackage backup-moodle2
 * @copyright 2014 C. Usener
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ratingallocate_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the complete ratingallocate structure for restore.
     * @return mixed
     * @throws base_step_exception
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $ratingallocatepath = '/activity/' . this_db\ratingallocate::TABLE;
        $paths[] = new restore_path_element(this_db\ratingallocate::TABLE, $ratingallocatepath);
        $choicespath =
                $ratingallocatepath . '/' . this_db\ratingallocate_choices::TABLE . 's/' . this_db\ratingallocate_choices::TABLE;
        $paths[] = new restore_path_element(this_db\ratingallocate_choices::TABLE, $choicespath);
        $paths[] = new restore_path_element(this_db\ratingallocate_group_choices::TABLE,
            $choicespath .'/' . this_db\ratingallocate_group_choices::TABLE .'s/' . this_db\ratingallocate_group_choices::TABLE);
        $paths[] = new restore_path_element(this_db\ratingallocate_ch_gengroups::TABLE,
            $choicespath .'/'. this_db\ratingallocate_ch_gengroups::TABLE . 's/' . this_db\ratingallocate_ch_gengroups::TABLE);
        $paths[] = new restore_path_element(this_db\ratingallocate_groupings::TABLE,
            $ratingallocatepath . '/' . this_db\ratingallocate_groupings::TABLE . 's/' . this_db\ratingallocate_groupings::TABLE);
        if ($userinfo) {
            $paths[] = new restore_path_element(this_db\ratingallocate_ratings::TABLE,
                    $choicespath . '/' . this_db\ratingallocate_ratings::TABLE . 's/' . this_db\ratingallocate_ratings::TABLE);
            $paths[] = new restore_path_element(this_db\ratingallocate_allocations::TABLE,
                    $choicespath . '/' . this_db\ratingallocate_allocations::TABLE . 's/' .
                    this_db\ratingallocate_allocations::TABLE);
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process ratingallocate.
     *
     * @param $data
     * @return void
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_ratingallocate($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->{this_db\ratingallocate::COURSE} = $this->get_courseid();
        $data->{this_db\ratingallocate::TIMECREATED} = $this->apply_date_offset($data->{this_db\ratingallocate::TIMECREATED});
        $data->{this_db\ratingallocate::TIMEMODIFIED} = $this->apply_date_offset($data->{this_db\ratingallocate::TIMEMODIFIED});
        $data->{this_db\ratingallocate::ACCESSTIMESTART} =
                $this->apply_date_offset($data->{this_db\ratingallocate::ACCESSTIMESTART});
        $data->{this_db\ratingallocate::ACCESSTIMESTOP} = $this->apply_date_offset($data->{this_db\ratingallocate::ACCESSTIMESTOP});
        $data->{this_db\ratingallocate::PUBLISHDATE} = $this->apply_date_offset($data->{this_db\ratingallocate::PUBLISHDATE});
        $userinfo = $this->get_setting_value('userinfo');
        if (!$userinfo) {
            $data->{this_db\ratingallocate::PUBLISHED} = false;
        }

        // Insert the record.
        $newitemid = $DB->insert_record(this_db\ratingallocate::TABLE, $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore data for ratingallocate choices.
     * @param $data
     * @return void
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_ratingallocate_choices($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        $data->{this_db\ratingallocate_choices::RATINGALLOCATEID} = $this->get_new_parentid(this_db\ratingallocate::TABLE);
        $newitemid = $DB->insert_record(this_db\ratingallocate_choices::TABLE, $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
        $this->set_mapping(this_db\ratingallocate_choices::TABLE, $oldid, $newitemid);
    }

    /**
     * Restore data for the ratingallocate ratings of users.
     * @param $data
     * @return void
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_ratingallocate_ratings($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        $data->{this_db\ratingallocate_ratings::CHOICEID} = $this->get_new_parentid(this_db\ratingallocate_choices::TABLE);
        $data->{this_db\ratingallocate_ratings::USERID} =
                $this->get_mappingid('user', $data->{this_db\ratingallocate_ratings::USERID});

        $newitemid = $DB->insert_record(this_db\ratingallocate_ratings::TABLE, $data);
        $this->set_mapping(this_db\ratingallocate_ratings::TABLE, $oldid, $newitemid);
    }

    /**
     * Restore data of allocations of users to choices.
     * @param $data
     * @return void
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_ratingallocate_allocations($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        $data->{this_db\ratingallocate_allocations::CHOICEID} = $this->get_new_parentid(this_db\ratingallocate_choices::TABLE);
        $data->{this_db\ratingallocate_allocations::RATINGALLOCATEID} = $this->get_new_parentid(this_db\ratingallocate::TABLE);
        $data->{this_db\ratingallocate_allocations::USERID} =
                $this->get_mappingid('user', $data->{this_db\ratingallocate_allocations::USERID});

        $newitemid = $DB->insert_record(this_db\ratingallocate_allocations::TABLE, $data);
        $this->set_mapping(this_db\ratingallocate_allocations::TABLE, $oldid, $newitemid);
    }

    /**
     * Restore data of group restrictions of choices.
     *
     * @param array $data
     * @return void
     */
    protected function process_ratingallocate_group_choices($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->choiceid = $this->get_new_parentid(this_db\ratingallocate_choices::TABLE);
        if ((int) $data->groupid !== 0) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        }

        $newitemid = $DB->insert_record(this_db\ratingallocate_group_choices::TABLE, $data);
        $this->set_mapping(this_db\ratingallocate_group_choices::TABLE, $oldid, $newitemid);
    }

    /**
     * Restore data for generated groups based on allocations.
     * @param $data
     * @return void
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_ratingallocate_ch_gengroups($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->choiceid = $this->get_new_parentid(this_db\ratingallocate_choices::TABLE);
        if ((int) $data->groupid !== 0) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        }

        $newitemid = $DB->insert_record(this_db\ratingallocate_ch_gengroups::TABLE, $data);
        $this->set_mapping(this_db\ratingallocate_ch_gengroups::TABLE, $oldid, $newitemid);
    }

    /**
     * Restore data for generated groupings based on allocations.
     * @param $data
     * @return void
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_ratingallocate_groupings($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->ratingallocateid = $this->get_new_parentid(this_db\ratingallocate::TABLE);
        if ((int) $data->groupingid !== 0) {
            $data->groupingid = $this->get_mappingid('grouping', $data->groupingid);
        }

        $newitemid = $DB->insert_record(this_db\ratingallocate_groupings::TABLE, $data);
        $this->set_mapping(this_db\ratingallocate_groupings::TABLE, $oldid, $newitemid);
    }

    /**
     * Add ratingallocate related files.
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_' . RATINGALLOCATE_MOD_NAME, 'intro', null);
    }
}
