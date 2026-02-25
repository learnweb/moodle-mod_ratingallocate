## v3.10-r1 (2020111200)

### Feature
* Make user fields for downloads configurable - This only concerns additional user fields, i.e. everything except the
name. First and last name are always exported (not configurable). E-Mail, userid, username, and idnumber are configurable.
* Added for Moodle 3.10

### Minor Fixes
* Prevent Sorting of user column
* Change Query to support SQL-Server

## v3.9-r1 (2020060500)

### Technical
* Modified to work with PHP 7.4 as well.

## v3.8-r1 (2019112100)

### Technical
* Log module events on module level instead of on course level.

### Bug fixes
* Fix give-points strategy: It is no longer possible to add negative points and (through this) give points above the maximum.
* Set lastname as default sorting order of rating tables. With the previous almost random sorting order, some entries could appear on multiple pages, while others did not show up at all.
* Distribution notifications were not sent out if some choices are not rated.

## v3.7-r1 (2019052900)

### Security fixes
* There were some security issues, which allowed unauthorized users to publish the allocation or create groups.
* There were some security issues, which allowed unauthorized users to query the ratings and allocation of users (listing the userid, the numeric rating and the choiceid).

### New feature
* The plugin uses now http get requests as often as possible or redirects to get pages after a post request is processed. This way it is now in most cases possible to navigate through the plugin pages using the browsers back button.

### Bug fixes
* There was a problem with default ratings, which are lower than the max rating of all option strategies (Yes/No, Yes/Maybe/No, Lickert). In these cases, all ratings, which were higher than the default rating, were overriden by the default rating, if the user reviewed her rating.

### Testing
* Moving from hacky phpunit tests for the manual allocation form to behat tests.

## v3.6-r1 (2018112900)

### Privacy API
* Added additional privacy interfaces for Moodle 3.6
* Removed privacy polyfill

### Bug fix

* Fixed loading default strategy option value from db within mod_form

### Technical features
* Fixed behat tests for Moodle 3.6
* Removed YUI module and replaced functionality by $mform->hideIf

## v3.5-r2 (2018071000)

### Privacy API

* Bug fix when checking validity of contexts.

### New features

* New report table showing allocations only grouped by choice.

## v3.5-r1 (2018051300)

### Privacy API

* Added support for Privacy API

### New features

* It is now possible to define a default value for the strategies Yes-No, Yes-Maybe-No and Lickert.
* Improved the distribution statistic.
* The message API is now used instead of sending emails. (This could of course also result in an email)
* Users with rating who could not be allocated are now getting informed about it.
* Improved the current displayed status, highlighted the allocation of a user and added the explanation of the choice.

### Minor changes

* Minor language changes.

Thanks to Ulm for multiple contributions!

## v3.4-r1 (2017102700)

### Minor changes

* Report links are now buttons to match the overall layout.
* Minor language changes.

### Technical features

* Swtiched to new plugin-ci v2 for better travis build.


## v3.3-r1 (2017050800)

### Rework of "Ratings and Allocations Table" and the "Manual Allocations Form"

* Both are based on table_sql.
* Increased clarity and usability of both views.
* Added pagination.
* Added initial filters.
* Added sorting functionality.
* Ratingstable can be downloaded using moodle dataformats (csv, xlsx, ods, json, html).

### New features

* Students can delete their own rating.
* Fixed the index.php listing all "Fair Allocation" instances within a course.
* Within the order strategy teachers and students see a notification if too few choices exist. In this case students can not rate at all!
* Changed the naming of Yes and No within the strategy to Accept and Deny.

### Technical features

* Fixed behat tests to work with new step definitions in Moodle 3.3.

## v3.2-r1 (2017022700)

### Bug fix

* Manual Allocations, for choices a user has not rated in the first place, where not shown in the allocations table.

### Minor changes

* Added a missing lang string.
* Fixed behat tests.

## v3.1-r1 (2016081600)

### New features

* Introduced the new name "Fair Allocation", since Ratingallocate was not really understandable.
* The choices can now be created on a separate form. This enables future improvements, like sorting the choices.
* Rating & Allocation Report does now use flexible_table. This enables future improvements, like search and sort.
* Reengineered the workflow in the teacher view. In all different status of the activity the teacher gets hints, what he has to do next.
* Csv-Export does now contain e-mail adresses

### Minor changes

* Marked publish date as estimated
* Added an unselectable "Select choice" option to order strategy.
* Delayed the default start date by one day, to allow creation of choices before the rating period starts.

### Technical features

* Added travis support
* Minor bug fixes
* Some refactoring striving towards moodle coding guidelines.
* Changed behat tests to you API calls (Moodle 3.1)

___

## v3.0-r1 (2016012701)

### Technical features

* Events define fields mappings in order to be correctly restored (according to MDL-46455).
* Removed unused event data.

___

## v2.9-r3 (2016012101)

### New features

* The strategy settings are now displayed using javascript. In this way only the settings of the currently selected strategy are displayed. This makes the settings page much clearer.
* The choice description is now part of the email, which informs a student about his allocation.

### Layout features

* Maximum number of students is now displayed in all strategies.
* The ratable choices are displayed in the modules header at any time now.
*Fixed a rendering bug in the manual allocation form for the rank strategy.

### Technical features

* Removed deprecation warning of get_all_allocations.
* Fixed the ordering of the csv export.
* Removed some unused functions.

___

## v2.9-r2 (2015071401)

* Marked as stable

___

## v2.9-r1 (2015071301)

### Technical features

* Bug fix: Ratings for the strategy rank can now be edited.
* Fixed test cases to run under moodle 2.9.0

___

## v2.8-r3 (2015041301)

### Technical features:

* Fixed bug in update script.

___

## v2.8-r2 (2015041301)

### New Features:

* The possibility is added to run the algorithm by the cron after the rating period. (Default is true)
* Complete redesign of the teachers view. (Better guidance through the modules process)
* Availiable spots are now shown in the rating view of the student.
* The current occupancy of each choice is displayed in the "Ratings and Allocations" view.
* Horizontal scrolling is now possible on very large "Ratings and Allocations".
* Bug fix: Students can now cancle the editing of their rating.
* Fixed lanugage issues.


### Technical features:

* Optimized some database queries

___

## v2.8-r1 (2015031801)

### New Features:

* Randomized automatic distribution.
* Fixed and added some labels and descriptions.
* Fixed some issues with validation in the mod_form.
* Default of individual labels is null =&gt; If nothing is entered the language specific defaults are used.
* Fixed some tests to work under 2.8

### Technical features:

* Changed the structure of the form elements in mod_form (the array structure caused too many problems)

___

## v2.7-r3 (2015021001)

### New Features:

* Notify users upon the pusblished distribution.
* Create groups from the allocation.
* Additional settings for strategies availiable (especially custom names for different options).
* Cleaner layout of administration view.
* Manual allocation with filters for users with no allocation but given rating and for all enrolled users.

### Technical features:

* Added several unit and behat tests.
* Class representing database structure for typingsafe access of database fields.
* Updated to new logging machanism.
