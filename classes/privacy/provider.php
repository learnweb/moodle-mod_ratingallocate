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
 * Privacy Subsystem implementation for mod_ratingallocate.
 *
 * @package    mod_ratingallocate
 * @copyright  2018 Tamara Gunkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ratingallocate\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Implementation of the privacy subsystem plugin provider for the ratingallocate activity module.
 *
 * @copyright  2018 Tamara Gunkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
    \core_privacy\local\metadata\provider,

        // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider,

        // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     *
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'ratingallocate_ratings',
            [
                'choiceid' => 'privacy:metadata:ratingallocate_ratings:choiceid',
                'userid' => 'privacy:metadata:ratingallocate_ratings:userid',
                'rating' => 'privacy:metadata:ratingallocate_ratings:rating',
            ],
            'privacy:metadata:ratingallocate_ratings'
        );

        $items->add_database_table('ratingallocate_allocations', [
                'userid' => 'privacy:metadata:ratingallocate_allocations:userid',
                'ratingallocateid' => 'privacy:metadata:ratingallocate_allocations:ratingallocateid',
                'choiceid' => 'privacy:metadata:ratingallocate_allocations:choiceid',
        ], 'privacy:metadata:ratingallocate_allocations');

        $items->add_user_preference(
            'flextable_mod_ratingallocate_table_filter',
            'privacy:metadata:preference:flextable_filter'
        );
        $items->add_user_preference(
            'flextable_mod_ratingallocate_manual_allocation_filter',
            'privacy:metadata:preference:flextable_manual_filter'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     *
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // Fetch all allocations.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {ratingallocate} ra ON ra.id = cm.instance
            INNER JOIN {ratingallocate_choices} choices ON choices.ratingallocateid = cm.instance
            LEFT JOIN {ratingallocate_allocations} alloc ON alloc.choiceid = choices.id
            LEFT JOIN {ratingallocate_ratings} ratings ON ratings.choiceid = choices.id
                 WHERE alloc.userid = :aluserid OR ratings.userid = :userid";

        $params = [
                'modname' => 'ratingallocate',
                'contextlevel' => CONTEXT_MODULE,
                'aluserid' => $userid,
                'userid' => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Export choices and ratings.
        $sql = "SELECT cm.id AS cmid,
                       ra.name AS name,
                       choices.title AS choice,
                       ratings.rating AS rating
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {ratingallocate} ra ON ra.id = cm.instance
            INNER JOIN {ratingallocate_choices} choices ON choices.ratingallocateid = ra.id
            INNER JOIN {ratingallocate_ratings} ratings ON ratings.choiceid = choices.id
                 WHERE c.id {$contextsql} AND ratings.userid = :userid
                 ORDER BY cm.id";

        $params = ['modname' => 'ratingallocate', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;
        $choiceanswers = $DB->get_recordset_sql($sql, $params);
        $choices = [];
        foreach ($choiceanswers as $choiceanswer) {
            $choicedata = new \stdClass();
            $choicedata->choice = $choiceanswer->choice;
            $choicedata->rating = $choiceanswer->rating;
            if (!isset($choices[$choiceanswer->cmid])) {
                $choices[$choiceanswer->cmid] = new \stdClass();
                $choices[$choiceanswer->cmid]->choices = [];
            }
            $choices[$choiceanswer->cmid]->choices[] = $choicedata;
        }
        $choiceanswers->close();

        foreach ($choices as $key => $value) {
            $area = ['ratings'];
            $context = \context_module::instance($key);

            // Fetch the generic module data for the choice.
            $contextdata = helper::get_context_data($context, $user);

            // Merge with choice data and write it.
            $contextdata = (object) array_merge((array) $contextdata, (array) $value);
            writer::with_context($context)->export_data($area, $contextdata);

            // Write generic module intro files.
            helper::export_context_files($context, $user);
        }

        // Export allocations.
        $sql = "SELECT cm.id AS cmid,
                       ra.name AS name,
                       choices.title AS choice
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {ratingallocate} ra ON ra.id = cm.instance
            INNER JOIN {ratingallocate_allocations} alloc ON alloc.ratingallocateid = ra.id
            INNER JOIN {ratingallocate_choices} choices ON choices.id = alloc.choiceid
                 WHERE c.id {$contextsql} AND alloc.userid = :userid
                 ORDER BY cm.id";

        $params = ['modname' => 'ratingallocate', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;
        $alloc = $DB->get_recordset_sql($sql, $params);
        $allocations = [];
        foreach ($alloc as $allocation) {
            $allocationdata = new \stdClass();
            $allocationdata->choice = $allocation->choice;
            if (!isset($allocations[$allocation->cmid])) {
                $allocations[$allocation->cmid] = new \stdClass();
                $allocations[$allocation->cmid]->allocations = [];
            }
            $allocations[$allocation->cmid]->allocations[] = $allocationdata;
        }
        $alloc->close();

        foreach ($allocations as $key => $value) {
            $area = ['allocations'];
            $context = \context_module::instance($key);

            // Fetch the generic module data for the choice.
            $contextdata = helper::get_context_data($context, $user);

            // Merge with choice data and write it.
            $contextdata = (object) array_merge((array) $contextdata, (array) $value);
            writer::with_context($context)->export_data($area, $contextdata);

            // Write generic module intro files.
            helper::export_context_files($context, $user);
        }
    }

    /**
     * Export user preferences based on given userid.
     * @param int $userid
     * @return void
     * @throws \coding_exception
     */
    public static function export_user_preferences(int $userid) {
        $filtertable = get_user_preferences('flextable_mod_ratingallocate_table_filter', null, $userid);
        if (null !== $filtertable) {
            $filtertabledesc = get_string('filtertabledesc', 'mod_ratingallocate');
            writer::export_user_preference(
                'mod_ratingallocate',
                'flextable_mod_ratingallocate_table_filter',
                $filtertable,
                $filtertabledesc
            );
        }

        $filtermanualtable = get_user_preferences('flextable_mod_ratingallocate_manual_allocation_filter', null, $userid);
        if (null !== $filtermanualtable) {
            $filtermanualtabledesc = get_string('filtermanualtabledesc', 'mod_ratingallocate');
            writer::export_user_preference(
                'mod_ratingallocate',
                'flextable_mod_ratingallocate_manual_allocation_filter',
                $filtermanualtable,
                $filtermanualtabledesc
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('ratingallocate', $context->instanceid);
        if (!$cm) {
            return;
        }

        // Delete Allocations.
        $DB->delete_records('ratingallocate_allocations', ['ratingallocateid' => $cm->instance]);
        // Delete Choices.
        $DB->delete_records_select(
            'ratingallocate_ratings',
            "choiceid IN (SELECT id FROM {ratingallocate_choices} WHERE ratingallocateid = :instanceid)",
            [
                        'instanceid' => $cm->instance,
                ]
        );
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                return;
            }

            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            // Delete Allocations.
            $DB->delete_records('ratingallocate_allocations', ['ratingallocateid' => $instanceid, 'userid' => $userid]);
            // Delete Choices.
            $DB->delete_records_select(
                'ratingallocate_ratings',
                "choiceid IN (SELECT id FROM {ratingallocate_choices}
                        WHERE ratingallocateid = :instanceid) AND userid = :userid",
                [
                            'instanceid' => $instanceid,
                            'userid' => $userid,
                    ]
            );
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }
        $params = [
                'instanceid' => $context->instanceid,
                'modulename' => 'ratingallocate',
        ];
        // From ratings.
        $sql = "SELECT ra.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {ratingallocate} r ON r.id = cm.instance
                  JOIN {ratingallocate_choices} ch ON ch.ratingallocateid = r.id
                  JOIN {ratingallocate_ratings} ra ON ra.choiceid = ch.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
        // From allocations.
        $sql = "SELECT a.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {ratingallocate} r ON r.id = cm.instance
                  JOIN {ratingallocate_allocations} a ON a.ratingallocateid = r.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $ratingallocate = $DB->get_record('ratingallocate', ['id' => $cm->instance]);

        [$userinsql, $userinparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['ratingallocateid' => $ratingallocate->id], $userinparams);

        // Delete Allocations.
        $DB->delete_records_select(
            'ratingallocate_allocations',
            "ratingallocateid = :ratingallocateid AND userid {$userinsql}",
            $params
        );
        // Delete Ratings.
        $DB->delete_records_select(
            'ratingallocate_ratings',
            "choiceid IN (SELECT id FROM {ratingallocate_choices} " .
            "WHERE ratingallocateid = :ratingallocateid) AND userid {$userinsql}",
            $params
        );
    }
}
