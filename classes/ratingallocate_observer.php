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
 * Event observer for ratingallocate.
 *
 * @package    mod_ratingallocate
 * @copyright 2023 I Hoppe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
namespace mod_ratingallocate;

class ratingallocate_observer {

    /**
     * Triggered if group_deleted event is triggered.
     *
     * @param \core\event\group_deleted $event
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function choice_group_delete (\core\event\group_deleted $event) {
        global $DB;

        $eventdata = $event->get_record_snapshot('groups', $event->objectid);
        if ($DB->record_exists(
            'ratingallocate_choice_group',
            ['groupid' => $eventdata->id])) {

            // Delete the group from ratingallocate_choice_group table.
            $DB->delete_records(
                'ratingallocate_choice_group',
                ['groupid' => $eventdata->id]
            );
        }

    }

    /**
     * Triggered if grouping_deleted event is triggered.
     *
     * @param \core\event\grouping_deleted $event
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function id_grouping_delete (\core\event\grouping_deleted $event) {
        global $DB;

        $eventdata = $event->get_record_snapshot('groupings', $event->objectid);
        if ($DB->record_exists(
            'ratingallocate_id_grouping',
            ['groupingid' => $eventdata->id])) {

            // Delete the grouping from the ratingallocate_id_grouping table.
            $DB->delete_records(
                'ratingallocate_id_grouping',
                ['groupingid' => $eventdata->id]
            );

        }
    }

}
