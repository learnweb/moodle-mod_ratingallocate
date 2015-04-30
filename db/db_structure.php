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
 * Database structure of the table needed by the ratingallocate module
 * Grants easier acces to database fields
 *
 * @package    mod_ratingallocate
 * @copyright  2014 T Reischmann, M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace ratingallocate\db;
class ratingallocate {
    const TABLE = 'ratingallocate';
    const ID = 'id';
    const COURSE = 'course';
    const NAME = 'name';
    const INTRO = 'intro';
    const INTROFORMAT = 'introformat';
    const TIMECREATED = 'timecreated';
    const TIMEMODIFIED = 'timemodified';
    const ACCESSTIMESTART = 'accesstimestart';
    const ACCESSTIMESTOP = 'accesstimestop';
    const SETTING = 'setting';
    const STRATEGY = 'strategy';
    const PUBLISHDATE = 'publishdate';
    const PUBLISHED = 'published';
    const NOTIFICATIONSEND = 'notificationsend';
    const ALGORITHMSTARTTIME = 'algorithmstarttime';
    const RUNALGORITHMBYCRON = 'runalgorithmbycron';
    const ALGORITHMSTATUS = 'algorithmstatus';
}
class ratingallocate_choices {
    const TABLE = 'ratingallocate_choices';
    const ID = 'id';
    const RATINGALLOCATEID = 'ratingallocateid';
    const TITLE = 'title';
    const EXPLANATION = 'explanation';
    const MAXSIZE = 'maxsize';
    const ACTIVE = 'active';
}
class ratingallocate_ratings {
    const TABLE = 'ratingallocate_ratings';
    const ID = 'id';
    const CHOICEID = 'choiceid';
    const USERID = 'userid';
    const RATING = 'rating';
}
class ratingallocate_allocations {
    const TABLE = 'ratingallocate_allocations';
    const ID = 'id';
    const USERID = 'userid';
    const RATINGALLOCATEID = 'ratingallocateid';
    const CHOICEID = 'choiceid';
}
