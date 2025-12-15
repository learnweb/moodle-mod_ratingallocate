<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_ratingallocate\db;

/**
 * Database structure of the Ratingallocate table needed by the
 * ratingallocate module. Grants easier acces to database fields
 *
 * @package    mod_ratingallocate
 * @copyright  2014 T Reischmann, M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ratingallocate {
    /**
     * The ratingallocate table.
     */
    const TABLE = 'ratingallocate';
    /**
     * Ratingallocateid.
     */
    const ID = 'id';
    /**
     * The course.
     */
    const COURSE = 'course';
    /**
     * Name of the instance.
     */
    const NAME = 'name';
    /**
     * Intro.
     */
    const INTRO = 'intro';
    /**
     * The introformat.
     */
    const INTROFORMAT = 'introformat';
    /**
     * When the instance was created.
     */
    const TIMECREATED = 'timecreated';
    /**
     * Time it was modified.
     */
    const TIMEMODIFIED = 'timemodified';
    /**
     * Beginning voting.
     */
    const ACCESSTIMESTART = 'accesstimestart';
    /**
     * End of voting.
     */
    const ACCESSTIMESTOP = 'accesstimestop';
    /**
     * Setting.
     */
    const SETTING = 'setting';
    /**
     * The strategy used.
     */
    const STRATEGY = 'strategy';
    /**
     * Date to publish allocation.
     */
    const PUBLISHDATE = 'publishdate';
    /**
     * Wether its published.
     */
    const PUBLISHED = 'published';
    /**
     * Send notification to users.
     */
    const NOTIFICATIONSEND = 'notificationsend';
    /**
     * Strat time of algorithm.
     */
    const ALGORITHMSTARTTIME = 'algorithmstarttime';
    /**
     * Wether algorithm is run by cron task.
     */
    const RUNALGORITHMBYCRON = 'runalgorithmbycron';
    /**
     * Status of the most recent algorithm run.
     */
    const ALGORITHMSTATUS = 'algorithmstatus';
}
