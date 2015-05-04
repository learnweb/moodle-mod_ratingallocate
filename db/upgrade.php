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

defined('MOODLE_INTERNAL') || die();

/**
 * Execute newmodule upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_ratingallocate_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2014103000) {
        try {
            $transaction = $DB->start_delegated_transaction();
            $results = $DB->get_records('ratingallocate', array('publishdate_show' => 0));

            foreach ($results as $single_result) {
                $single_result->publishdate = 0;
                $DB->update_record('ratingallocate', $single_result);
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
        } catch(Exception $e) {
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

        $field2 = new xmldb_field('algorithmstarttime', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'runalgorithmbycron');

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
        foreach ($results as $single_result) {
            $allocations = $DB->get_records('ratingallocate_allocations', array('ratingallocateid' => $single_result->id));
            $single_result->algorithmstatus = (count($allocations) === 0 ?
                \mod_ratingallocate\algorithm_status::notstarted : \mod_ratingallocate\algorithm_status::finished);
            $DB->update_record('ratingallocate', $single_result);
        }

        // Ratingallocate savepoint reached.
        upgrade_mod_savepoint(true, 2015041300, 'ratingallocate');
        $transaction->allow_commit();
        } catch(Exception $e) {
            $transaction->rollback($e);
            return false;
        }

    }
    
    return true;
}
