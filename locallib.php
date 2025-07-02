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
 * Internal library of functions for module ratingallocate
 *
 * All the ratingallocate specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package mod_ratingallocate
 * @copyright 2014 M Schulze
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/form_manual_allocation.php');
require_once(dirname(__FILE__) . '/form_modify_choice.php');
require_once(dirname(__FILE__) . '/form_upload_choices.php');
require_once(dirname(__FILE__) . '/renderable.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once(__DIR__ . '/classes/algorithm_status.php');

// Takes care of loading all the solvers.
require_once(dirname(__FILE__) . '/solver/ford-fulkerson-koegel.php');
require_once(dirname(__FILE__) . '/solver/edmonds-karp.php');

// Now come all the strategies.
require_once(dirname(__FILE__) . '/strategy/strategy01_yes_no.php');
require_once(dirname(__FILE__) . '/strategy/strategy02_yes_maybe_no.php');
require_once(dirname(__FILE__) . '/strategy/strategy03_lickert.php');
require_once(dirname(__FILE__) . '/strategy/strategy04_points.php');
require_once(dirname(__FILE__) . '/strategy/strategy05_order.php');
require_once(dirname(__FILE__) . '/strategy/strategy06_tickyes.php');

define('ACTION_GIVE_RATING', 'give_rating');
define('ACTION_DELETE_RATING', 'delete_rating');
define('ACTION_SHOW_CHOICES', 'show_choices');
define('ACTION_EDIT_CHOICE', 'edit_choice');
define('ACTION_UPLOAD_CHOICES', 'upload_choices');
define('ACTION_ENABLE_CHOICE', 'enable_choice');
define('ACTION_DISABLE_CHOICE', 'disable_choice');
define('ACTION_DELETE_CHOICE', 'delete_choice');
define('ACTION_START_DISTRIBUTION', 'start_distribution');
define('ACTION_DELETE_ALL_RATINGS', 'delete_all_ratings');
define('ACTION_MANUAL_ALLOCATION', 'manual_allocation');
define('ACTION_DISTRIBUTE_UNALLOCATED_FILL', 'distribute_unallocated_fill');
define('ACTION_DISTRIBUTE_UNALLOCATED_EQUALLY', 'distribute_unallocated_equally');
define('ACTION_PUBLISH_ALLOCATIONS', 'publish_allocations'); // Make them displayable for the users.
define('ACTION_SOLVE_LP_SOLVE', 'solve_lp_solve'); // Instead of only generating the mps-file, let it solve.
define('ACTION_SHOW_RATINGS_AND_ALLOCATION_TABLE', 'show_ratings_and_allocation_table');
define('ACTION_SHOW_ALLOCATION_TABLE', 'show_allocation_table');
define('ACTION_SHOW_STATISTICS', 'show_statistics');
define('ACTION_ALLOCATION_TO_GROUPING', 'allocation_to_gropuping');

/**
 * Remove all users (or one user) from one group, invented by MxS by copying from group/lib.php
 * because it didn't exist there
 *
 * @param int $groupid
 * @return bool success
 */
function groups_delete_group_members_by_group($groupid): bool {
    global $DB;

    if (is_bool($groupid)) {
        debugging('Incorrect groupid function parameter');
        return false;
    }

    // Select * so that the function groups_remove_member() gets the whole record.
    $groups = $DB->get_recordset('groups', ['id' => $groupid]);

    foreach ($groups as $group) {
        $userids = $DB->get_fieldset_select('groups_members', 'userid', 'groupid = :groupid',
                ['groupid' => $group->id]);

        // Very ugly hack because some group-management functions are not provided in lib/grouplib.php
        // but does not add too much overhead since it does not include more files...
        require_once(dirname(dirname(dirname(__FILE__))) . '/group/lib.php');
        foreach ($userids as $id) {
            groups_remove_member($group, $id);
        }
    }
    return true;
}
