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

defined('MOODLE_INTERNAL') || die();

/**
 * @class ratingallocate
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

/**
 * @class  Ratingallocate choices
 */
class ratingallocate_choices {
    /**
     * Table.
     */
    const TABLE = 'ratingallocate_choices';
    /**
     * ID.
     */
    const ID = 'id';
    /**
     * Ratingallocateid.
     */
    const RATINGALLOCATEID = 'ratingallocateid';
    /**
     * Title of choice.
     */
    const TITLE = 'title';
    /**
     * Explanation.
     */
    const EXPLANATION = 'explanation';
    /**
     * Max number of users.
     */
    const MAXSIZE = 'maxsize';
    /**
     * If its active.
     */
    const ACTIVE = 'active';
    /**
     * Restrict visibility by groups.
     */
    const USEGROUPS = 'usegroups';
}

/**
 * @class ratingallocate_group_choices
 */
class ratingallocate_group_choices {
    /**
     * Table.
     */
    const TABLE = 'ratingallocate_group_choices';
    /**
     * Id.
     */
    const ID = 'id';
    /**
     * Choiceid.
     */
    const CHOICEID = 'choiceid';
    /**
     * Groupid.
     */
    const GROUPID = 'groupid';
}

/**
 * @class ratingallocate_ch_gengroups
 */
class ratingallocate_ch_gengroups {
    /**
     * Table.
     */
    const TABLE = 'ratingallocate_ch_gengroups';
    /**
     * Id.
     */
    const ID = 'id';
    /**
     * Groupid.
     */
    const  GROUPID = 'groupid';
    /**
     * Choiceid.
     */
    const CHOICEID = 'choiceid';
}

/**
 * @class ratingallocate_groupings Generated grouping by instance
 */
class ratingallocate_groupings {
    /**
     * Table.
     */
    const TABLE = 'ratingallocate_groupings';
    /**
     * Id.
     */
    const ID = 'id';
    /**
     * Ratingallocateid.
     */
    const RATINGALLOCATEID = 'ratingallocateid';
    /**
     * Groupingid.
     */
    const GROUPINGID = 'groupingid';
}

/**
 * @class ratings (map user to choice)
 */
class ratingallocate_ratings {
    /**
     * Table.
     */
    const TABLE = 'ratingallocate_ratings';
    /**
     * Id.
     */
    const ID = 'id';
    /**
     * The choiceid.
     */
    const CHOICEID = 'choiceid';
    /**
     * The userid.
     */
    const USERID = 'userid';
    /**
     * How the user rated the choice.
     */
    const RATING = 'rating';
}

/**
 * @class allocations
 */
class ratingallocate_allocations {
    /**
     * Table.
     */
    const TABLE = 'ratingallocate_allocations';
    /**
     * Id.
     */
    const ID = 'id';
    /**
     * Userid.
     */
    const USERID = 'userid';
    /**
     * Id of ratingallocate instance.
     */
    const RATINGALLOCATEID = 'ratingallocateid';
    /**
     * Choiceid.
     */
    const CHOICEID = 'choiceid';
}
