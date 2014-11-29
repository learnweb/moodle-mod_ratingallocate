<?php
//Database structure of the table needed by the ratingallocate module
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
