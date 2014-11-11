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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2014 C. Usener
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ratingallocate_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $ratingallocate_path = '/activity/'. 'ratingallocate';
        $paths[] = new restore_path_element('ratingallocate', $ratingallocate_path );
        $choices_path = $ratingallocate_path . '/' . 'ratingallocate_choices' . 's/' . 'ratingallocate_choices';
        $paths[] = new restore_path_element('ratingallocate_choices', $choices_path);
        if ($userinfo) {
            $paths[] = new restore_path_element('ratingallocate_ratings',     $choices_path .'/' . 'ratingallocate_ratings' .'s/' . 'ratingallocate_ratings');
            $paths[] = new restore_path_element('ratingallocate_allocations', $choices_path .'/' . 'ratingallocate_allocations' .'s/' . 'ratingallocate_allocations');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_ratingallocate($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->{'course'} = $this->get_courseid();
        $data->{'timecreated'} = $this->apply_date_offset($data->{'timecreated'});
        $data->{'timemodified'} = $this->apply_date_offset($data->{'timemodified'});
        $data->{'accesstimestart'} = $this->apply_date_offset($data->{'accesstimestart'});
        $data->{'accesstimestop'} = $this->apply_date_offset($data->{'accesstimestop'});
        $data->{'publishdate'} = $this->apply_date_offset($data->{'publishdate'});

        // insert the record
        $newitemid = $DB->insert_record('ratingallocate', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_ratingallocate_choices($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        $data->{'ratingallocateid'} = $this->get_new_parentid('ratingallocate');
        $newitemid = $DB->insert_record('ratingallocate_choices', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
        $this->set_mapping('ratingallocate_choices', $oldid, $newitemid);
    }

    protected function process_ratingallocate_ratings($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        $data->{'choiceid'} = $this->get_new_parentid('ratingallocate_choices');
        $data->{'userid'} = $this->get_mappingid('user', $data->{'userid'});

        $newitemid = $DB->insert_record('ratingallocate_ratings', $data);
        $this->set_mapping('ratingallocate_ratings', $oldid, $newitemid);
    }

    protected function process_ratingallocate_allocations($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        $data->{'choiceid'} = $this->get_new_parentid('ratingallocate_choices');
        $data->{'ratingallocateid'} = $this->get_new_parentid('ratingallocate');
        $data->{'userid'} = $this->get_mappingid('user', $data->{'userid'});

        $newitemid = $DB->insert_record('ratingallocate_allocations', $data);
        $this->set_mapping('ratingallocate_allocations', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add ratingallocate related files
        $this->add_related_files('mod_' . ratingallocate_MOD_NAME, 'intro', null);
        //$this->add_related_files('mod_' . ratingallocate_MOD_NAME, ratingallocate_FILEAREA_NAME, ratingallocate_MOD_NAME);
    }
}