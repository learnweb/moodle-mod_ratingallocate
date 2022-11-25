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

namespace mod_ratingallocate\completion;
class custom_completion extends \core_completion\activity_custom_completion {

    public static function get_defined_custom_rules(): array {
        return [
            'votetrackingenabled',
            'assignedtrackingenabled'
        ];
    }

    public function get_custom_rule_descriptions(): array {
        return [
            'votetrackingenabled' => get_string('votetrackingdesc_user', 'ratingallocate'),
            'assignedtrackingenabled' => get_string('assignedtrackingdesc_user', 'ratingallocate')
        ];
    }

    public function get_sort_order(): array {
        return [
            'votetrackingenabled',
            'assignedtrackingenabled',
        ];
    }

    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $status = COMPLETION_INCOMPLETE;
        switch ($rule) {
            case "votetrackingenabled":
                $sql = "SELECT COUNT(1) FROM {ratingallocate_ratings} r JOIN {ratingallocate_choices} c ON c.id = r.choiceid"
                    . " WHERE r.userid = :userid AND c.ratingallocateid = :ratingallocateid LIMIT 1";
                $result = $DB->get_field_sql($sql, [
                    'userid' => $this->userid,
                    'ratingallocateid' => $this->cm->instance,
                ]);
                if ($result) {
                    $status = COMPLETION_COMPLETE;
                }
                break;
            case 'assignedtrackingenabled':
                $sql = "SELECT COUNT(1) FROM {ratingallocate_allocations} r WHERE r.userid = :userid AND r.ratingallocateid = :ratingallocateid LIMIT 1";
                $result = $DB->get_field_sql($sql, [
                    'userid' => $this->userid,
                    'ratingallocateid' => $this->cm->instance,
                ]);
                if ($result) {
                    $status = COMPLETION_COMPLETE;
                }
                break;
            default:
                break;
        }
        return $status;
    }
}