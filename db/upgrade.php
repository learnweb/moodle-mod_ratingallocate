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
 * This file keeps track of upgrades to the newmodule module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 C Usener, M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_ratingallocate\algorithm_status;

/**
 * Execute newmodule upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_ratingallocate_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2014103000) {
        try {
            $transaction = $DB->start_delegated_transaction();
            $results = $DB->get_records('ratingallocate', ['publishdate_show' => 0]);

            foreach ($results as $singleresult) {
                $singleresult->publishdate = 0;
                $DB->update_record('ratingallocate', $singleresult);
            }

            // Define field publishdate_show to be dropped from ratingallocate.
            $table = new xmldb_table('ratingallocate');
            $field = new xmldb_field('publishdate_show');

            // Conditionally launch drop field publishdate_show.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }

            // Ratingallocate savepoint reached.
            upgrade_mod_savepoint(true, 2014103000, 'ratingallocate');
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    if ($oldversion < 2014111800) {
        // Define field notification_send to be added to ratingallocate.
        $table = new xmldb_table('ratingallocate');
        $field = new xmldb_field('notificationsend', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'published');

        // Conditionally launch add field notification_send.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ratingallocate savepoint reached.
        upgrade_mod_savepoint(true, 2014111800, 'ratingallocate');
    }

    if ($oldversion < 2015041300) {
        try {
            $transaction = $DB->start_delegated_transaction();

            // Define field notification_send to be added to ratingallocate.
            $table = new xmldb_table('ratingallocate');
            $field1 = new xmldb_field('runalgorithmbycron', XMLDB_TYPE_INTEGER, '1', null, true, null, '1', 'notificationsend');

            // Conditionally launch add field run_algorithm_by_cron.
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }

            $field2 =
                    new xmldb_field('algorithmstarttime', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'runalgorithmbycron');

            // Conditionally launch add field algorithm_starttime.
            if (!$dbman->field_exists($table, $field2)) {
                $dbman->add_field($table, $field2);
            }

            $field3 = new xmldb_field('algorithmstatus', XMLDB_TYPE_INTEGER, '1', null, true, null, '0', 'algorithmstarttime');

            // Conditionally launch add field algorithm_status.
            if (!$dbman->field_exists($table, $field3)) {
                $dbman->add_field($table, $field3);
            }

            $results = $DB->get_records('ratingallocate');

            // Set status to notstarted if the instance has no allocations; otherwise to finished.
            foreach ($results as $singleresult) {
                $allocations = $DB->get_records('ratingallocate_allocations', ['ratingallocateid' => $singleresult->id]);
                $singleresult->algorithmstatus = (count($allocations) === 0 ?
                        algorithm_status::NOTSTARTED : algorithm_status::FINISHED);
                $DB->update_record('ratingallocate', $singleresult);
            }

            // Ratingallocate savepoint reached.
            upgrade_mod_savepoint(true, 2015041300, 'ratingallocate');
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    if ($oldversion < 2021062900) {
        // Changing type of field explanation on table ratingallocate_choices to text.
        $table = new xmldb_table('ratingallocate_choices');
        $field = new xmldb_field('explanation', XMLDB_TYPE_TEXT, null, null, null, null, null, 'title');

        // Launch change of type for field explanation.
        $dbman->change_field_type($table, $field);

        // Ratingallocate savepoint reached.
        upgrade_mod_savepoint(true, 2021062900, 'ratingallocate');
    }

    if ($oldversion < 2022120100) {
        // Define table ratingallocate_group_choices to be created.
        $table = new xmldb_table('ratingallocate_group_choices');

        // Adding fields to table ratingallocate_group_choices.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('choiceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ratingallocate_group_choices.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('choiceid', XMLDB_KEY_FOREIGN, ['choiceid'], 'ratingallocate_choices', ['id']);
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'groups', ['id']);

        // Conditionally launch create table for ratingallocate_group_choices.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field usegroups to be added to ratingallocate_choices.
        $table = new xmldb_table('ratingallocate_choices');
        $field = new xmldb_field('usegroups', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'active');

        // Conditionally launch add field usegroups.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ratingallocate savepoint reached.
        upgrade_mod_savepoint(true, 2022120100, 'ratingallocate');
    }

    if ($oldversion < 2023050900) {
        // Define table ratingallocate_ch_gengroups to be created.
        $table = new xmldb_table('ratingallocate_ch_gengroups');

        // Adding fields to table ratingallocate_ch_gengroups.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('choiceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ratingallocate_ch_gengroups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'groups', ['id']);
        $table->add_key('choiceid', XMLDB_KEY_FOREIGN, ['choiceid'], 'ratingallocate_choices', ['id']);

        // Conditionally launch create table for ratingallocate_ch_gengroups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table ratingallocate_groupings to be created.
        $table = new xmldb_table('ratingallocate_groupings');

        // Adding fields to table ratingallocate_groupings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ratingallocateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupingid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ratingallocate_groupings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('ratingallocateid', XMLDB_KEY_FOREIGN, ['ratingallocateid'], 'ratingallocate', ['id']);
        $table->add_key('groupingid', XMLDB_KEY_FOREIGN, ['groupingid'], 'groupings', ['id']);

        // Conditionally launch create table for ratingallocate_ch_gengroups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ratingallocate savepoint reached.
        upgrade_mod_savepoint(true, 2023050900, 'ratingallocate');
    }

    if ($oldversion < 2024080900) {
        // Define completionrules fields to be added to ratingallocate.
        $table = new xmldb_table('ratingallocate');
        $votefield = new xmldb_field('completionvote', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, false, '0');
        $allocationfield = new xmldb_field('completionallocation', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, false, '0');

        // Conditionally launch add field notification_send.
        if (!$dbman->field_exists($table, $votefield)) {
            $dbman->add_field($table, $votefield);
        }
        if (!$dbman->field_exists($table, $allocationfield)) {
            $dbman->add_field($table, $allocationfield);
        }

        // Ratingallocate savepoint reached.
        upgrade_mod_savepoint(true, 2024080900, 'ratingallocate');
    }

    return true;
}
