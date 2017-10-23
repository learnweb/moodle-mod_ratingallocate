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
/**
 * Persistable of group_mapping
 *
 * @package    mod_ratingallocate
 * @copyright  2017 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_mapping extends \core\persistent {

    /** Table name for the persistent. */
    const TABLE = 'ratingallocate_groups';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'choiceid' => array(
                'type' => PARAM_INT,
            ),
            'groupid' => array(
                'type' => PARAM_INT,
            ),
        );
    }

    /**
     * Return the mapping of moodle groups to ratingallocate choices
     * @param int id of the ratingallocate instance.
     * @return array or groupids to choiceids.
     */
    public static function get_records_by_ratingallocate_id($ratingallocateid) {
        global $DB;
        $sql = 'SELECT g.groupid, g.choiceid
                FROM {ratingallocate_choices} c
                JOIN {ratingallocate_groups} g
                ON c.id = g.choiceid
                WHERE c.ratingallocateid = :ratingallocateid';

        return $DB->get_records_sql($sql, array("ratingallocateid" => $ratingallocateid));
    }
}