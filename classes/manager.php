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

use cm_info;
use context_module;
use stdClass;

/**
 * Class manager for ratingallocate activity
 *
 * @package   mod_ratingallocate
 * @copyright 2025 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** Module name. */
    public const MODULE = 'ratingallocate';

    /** @var context_module the current context. */
    private $context;

    /** @var stdClass $course record. */
    private $course;

    /** @var \moodle_database the database instance. */
    private \moodle_database $db;

    /**
     * Class constructor.
     *
     * @param cm_info $cm course module info object
     * @param stdClass $instance activity instance object.
     */
    public function __construct(
        /** @var cm_info $cm the given course module info */
        private cm_info $cm,
        /** @var stdClass $instance activity instance object */
        private stdClass $instance
    ) {
        $this->context = context_module::instance($cm->id);
        $this->db = \core\di::get(\moodle_database::class);
        $this->course = $cm->get_course();
    }

    /**
     * Create a manager instance from an instance record.
     *
     * @param stdClass $instance an activity record
     * @return manager
     */
    public static function create_from_instance(stdClass $instance): self {
        $cm = get_coursemodule_from_instance(self::MODULE, $instance->id);
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        return new self($cm, $instance);
    }

    /**
     * Create a manager instance from a course_modules record.
     *
     * @param stdClass|cm_info $cm an activity record
     * @return manager
     */
    public static function create_from_coursemodule(stdClass|cm_info $cm): self {
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        $db = \core\di::get(\moodle_database::class);
        $instance = $db->get_record(self::MODULE, ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($cm, $instance);
    }

    /**
     * Return the current context.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->context;
    }

    /**
     * Return the current instance.
     *
     * @return stdClass the instance record
     */
    public function get_instance(): stdClass {
        return $this->instance;
    }

    /**
     * Return the current cm_info.
     *
     * @return cm_info the course module
     */
    public function get_coursemodule(): cm_info {
        return $this->cm;
    }

    /**
     * Check if the current user has responded in the ratingallocate.
     *
     * @return bool true if the user has answered, false otherwise
     */
    public function has_answered(): bool {
        global $USER;

        $sql = 'SELECT 1 FROM {ratingallocate_ratings} r
            WHERE r.userid = :userid
              AND r.choiceid IN (
                  SELECT c.id FROM {ratingallocate_choices} c
                  WHERE c.ratingallocateid = :ratingallocateid
              )';
        $params = ['userid' => $USER->id, 'ratingallocateid' => $this->instance->id];
        return $this->db->record_exists_sql($sql, $params);
    }


    /**
     * Return the count of users who can submit ratings to this ratingallocate module, that the current user can see.
     *
     * @param int[] $groupids the group identifiers to filter by, empty array means no filtering
     * @return int the number of answers that the user can see
     */
    public function count_all_users(
        array $groupids = [],
    ): int {
        if (!has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            return 0;
        }

        // Get all users with the capability to give ratings in this context.
        $users = get_users_by_capability($this->context, 'mod/ratingallocate:give_rating', 'u.id');

        if (empty($users)) {
            return 0;
        }

        // No group filtering requested: simply count users with the capability.
        if (empty($groupids)) {
            return count($users);
        }

        // With group filtering: count users that are in any of the requested groups
        // or that are not in any group (group 0 semantics).
        $groupids = array_unique($groupids);
        $count = 0;
        foreach ($users as $userid => $unused) {
            $usergroups = groups_get_user_groups($this->course->id, $userid);
            $ug = isset($usergroups[0]) ? $usergroups[0] : [];
            if (!empty(array_intersect($ug, $groupids)) || empty($ug)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Return the current count of users who have submitted ratings to this ratingallocate module, that the current user can see.
     *
     * @param int[] $groupids the group identifiers to filter by, empty array means no filtering
     * @return int the number of answers that the user can see
     */
    public function count_all_users_answered(
        array $groupids = [],
    ): int {
        if (!has_capability('mod/ratingallocate:start_distribution', $this->context)) {
            return 0;
        }

        $tableprefix = empty($groupids) ? '' : 'r.';
        $select = $tableprefix . 'choiceid IN (
            SELECT c.id FROM {ratingallocate_choices} c
            WHERE c.ratingallocateid = :ratingallocateid
        )';
        $params = [
            'ratingallocateid' => $this->instance->id,
        ];

        if (empty($groupids)) {
            // No groups filtering, count all users answered.
            return $this->db->count_records_select(
                'ratingallocate_ratings',
                $select,
                $params,
                'COUNT(DISTINCT userid)'
            );
        }

        // Groups filtering is applied.
        [$gsql, $gparams] = $this->db->get_in_or_equal($groupids, SQL_PARAMS_NAMED);
        $query = "SELECT COUNT(DISTINCT r.userid)
                FROM {ratingallocate_ratings} r, {groups_members} gm
               WHERE $select
                     AND (gm.groupid $gsql OR gm.groupid = 0)
                     AND r.userid = gm.userid";
        return $this->db->count_records_sql($query, $params + $gparams);
    }
}
