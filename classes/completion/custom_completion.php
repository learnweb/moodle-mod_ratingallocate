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

declare(strict_types=1);

namespace mod_ratingallocate\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the ratingallocate activity.
 *
 * Class for defining the custom completion rules of ratingallocate and fetching the completion statuses
 * of the custom completion rules for a given ratingallocate instance and a user.
 *
 * @package mod_ratingallocate
 * @copyright Irina Hoppe Uni Münster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     * @throws \moodle_exception
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $userid = $this->userid;
        $ratingallocateid = $this->cm->instance;

        if (!$DB->get_record('ratingallocate', ['id' => $ratingallocateid])) {
            throw new \moodle_exception('Unable to find ratingallocate instance with id ' . $ratingallocateid);
        }

        if ($rule == 'completionvote') {
            $sql = "SELECT * FROM {ratingallocate_ratings} r INNER JOIN {ratingallocate_choices} c on r.choiceid=c.id " .
                "WHERE r.userid= :userid AND c.ratingallocateid= :ratingallocateid";
            $votesofuser = $DB->get_records_sql($sql, ['userid' => $userid, 'ratingallocateid' => $ratingallocateid]);
            $status = count($votesofuser) > 0;
        } else if ($rule == 'completionallocation') {
            $sql = "SELECT * FROM {ratingallocate_allocations} WHERE userid= :userid AND ratingallocateid= :ratingallocateid";
            $allocationsofuser = $DB->get_records_sql($sql, ['userid' => $userid, 'ratingallocateid' => $ratingallocateid]);
            $status = count($allocationsofuser) > 0;
        }

        return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionvote',
            'completionallocation',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionvote' => get_string('completionvote_desc', RATINGALLOCATE_MOD_NAME),
            'completionallocation' => get_string('completionallocation_desc', RATINGALLOCATE_MOD_NAME),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionvote',
            'completionallocation',
        ];
    }
}

