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
 * @copyright  2011 Your Name
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
    
    return true;
}
