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
 * English strings for ratingallocate
 *
 *
 * @package    mod_ratingallocate
 * @copyright  2014 M Schulze, T Reischmann, C Usener
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// <editor-fold defaultstate="collapsed" desc="General Plugin Settings">
$string['ratingallocate'] = 'ratingallocate';
$string['ratingallocatename'] = 'Name of this Voting';
$string['ratingallocatename_help'] = 'This is the content of the help tooltip associated with the ratingallocatename field. Markdown syntax is supported.';
$string['modulename'] = 'Ratingallocate';
$string['modulename_help'] = 'The ratingallocate module lets you define choices your users can then rate. You may then distribute the users to choices automatically.';
$string['modulenameplural'] = 'ratingallocates';
$string['pluginadministration'] = 'ratingallocate administration';
$string['pluginname'] = 'ratingallocate';
$string['ratingallocate:addinstance'] = 'Add new instance of ratingallocate';
$string['ratingallocate:view'] = 'View rating allocation instances';
$string['ratingallocate:give_rating'] = 'Create/edit own choice';
$string['ratingallocate:start_distribution'] = 'Start allocation of users to choices';
$string['ratingallocate:export_ratings'] = 'Ability to export user ratings';
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Rating Form for Users">
$string['choicestatusheading'] = 'Status';
$string['timeremaining'] = 'Time remaining';
$string['publishdate_estimated'] = 'Estimated publish date';
$string['rateable_choices'] = 'Rateable Choices';
$string['rating_is_over'] = 'Rating is over';
$string['ratings_saved'] = 'Your ratings have been saved.';
$string['strategyname'] = 'Strategy is "{$a}"';
$string['too_early_to_rate'] = 'Too early to rate';
$string['your_allocated_choice'] = 'Your Allocation';
$string['your_rating'] = 'Your Rating';
$string['edit_rating'] = 'Edit Rating';
$string['results_not_yet_published'] = 'Results have not yet been published';
$string['no_choice_to_rate'] = 'There is not any choice to rate for!';
$string['at_least_one_rateable_choices_needed'] = 'You need at least one choice to be active.';
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Administrator View">
$string['allocation_manual_explain_only_raters'] = 'Select a choice to be assign to each user. Only those users are listed, that rated at least one choice.';
$string['allocation_manual_explain_all'] = 'Select a choice to be assign to each user.';
$string['distribution_algorithm'] = 'Distribution Algorithm';
$string['distribution_saved'] = 'Distribution saved (in {$a}s).';
$string['download_problem_mps_format'] = 'Download Equation in mps format (txt)';
$string['download_votetest_allocation'] = 'Download Votes and Allocation (csv)';
$string['no_user_to_allocate'] = 'There is no user you could allocate';
$string['ratings_table'] = 'Ratings and Allocations';
$string['start_distribution'] = 'Run Algorithm';
$string['start_distribution_explanation'] = ' An algorithm will automatically try to fairly distribute the users by ratings given';
$string['unassigned_users'] = 'Unassigned Users';
$string['invalid_publishdate'] = 'Publishdate is invalid. Must be after vote close.';
$string['rated'] = 'rated {$a}';
$string['no_rating_given'] = 'unrated';
$string['export_options'] = 'Export Options';
$string['manual_allocation_saved'] = 'Your manual allocation has been saved.';
$string['publish_allocation'] = 'Publish allocation';
$string['distribution_published'] = 'Distribution has been published.';
$string['create_moodle_groups'] = 'Create groups from allocations';
$string['moodlegroups_created'] = 'The corresponding Moodle-Grouping and Groups have been created.';

$string['modify_allocation_group'] = 'Modify Allocation';
$string['modify_allocation_group_desc_too_early'] = 'The rating phase is currently running. You can start the allocation process after the rating phase has ended.';
$string['modify_allocation_group_desc_ready'] = 'The rating phase has endend. You can now run the algorithm for an automatic allocation.';
$string['modify_allocation_group_desc_ready_alloc_started'] = 'The rating phase has endend. Some allocations have already been created.
Rerunning the algorithm, will delete all current allocations.
You can now modify the allocations manually or proceed to pulishing the allocations.';
$string['modify_allocation_group_desc_published'] = 'The allocations have been published. You can no longer alter them.';
$string['publish_allocation_group'] = 'Publish Allocation';
$string['publish_allocation_group_desc_too_early'] = 'There are no allocations yet. Please see the modify allocation section.';
$string['publish_allocation_group_desc_ready'] = 'There are no allocations yet. Please see the modify allocation section.';
$string['publish_allocation_group_desc_ready_alloc_started'] = 'The allocations can now be published.
After publishing the allocations they can no longer be altered.
Please have a look at the current allocations by following the link in the reports section.
You can choose to create groups within your course for all allocations.
If the same groups have already been created by this modul they will be purged before refilling them.
This can be done before and after publishing the allocations.';
$string['publish_allocation_group_desc_published'] = 'The allocations are already published.
You can choose to create groups within your course for all allocations.
If the same groups have already been created by this modul they will be purged before refilling them.';
$string['reports_group'] = 'Reports';

$string['manual_allocation'] = 'Manual allocation';
$string['manual_allocation_form'] = 'Manual Allocation Form';
$string['manual_allocation_filter_only_raters'] = 'Show only users, with ratings.';
$string['manual_allocation_filter_all'] = 'Show all users.';

$string['show_table'] = 'Show Ratings and Allocations';

$string['allocation_statistics'] = 'Allocation Statistics';
$string['show_allocation_statistics'] = 'Show Allocation Statistics';
$string['allocation_statistics_description'] = 'This table gives an impression of the overall satisfaction of the allocation.
It is counting the allocations according to the rating the user has given to the respective choice.
In this case {$a->users} out of {$a->total} users got a choice they rated with "{$a->rating}".
For {$a->unassigned} users no choice has been allocated yet.';

$string['rating_raw'] = '{$a}';
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Form to edit the instance(administrator)">
$string['choice_active'] = 'Choice active';
$string['choice_active_help'] = 'Your choice gets displayed to your users only while its active. Inactive choices won\'t display';
$string['choice_explanation'] = 'Explanation (additional) Text';
$string['choice_maxsize'] = 'Max. number of members';
$string['choice_maxsize_display'] = 'Maximum number of students';
$string['choice_title'] = 'Title';
$string['choice_title_help'] = 'Title of the choice. *Attention* all available choices will be outputted ordered by title.';
$string['edit_choice'] = 'Edit Choice {$a}';
$string['rating_endtime'] = 'Rating ends at';
$string['rating_begintime'] = 'Rating begins at';
$string['newchoice'] = 'Add new Choice';
$string['newchoicetitle'] = 'New Choice {$a}';
$string['deletechoice'] = 'Delete Choice';
$string['publishdate'] = 'Publishdate';
$string['select_strategy'] = 'Rating strategy';
$string['select_strategy_help'] = 'Choose the rating strategy:

* **Yes-No** The user can rate each choice with yes or no.
* **Yes-Maybe-No** The user can rate each choice with yes, maybe or no.
* **Likert Scale** The user can rate each choice within a scale of integers. The size of the scale can be adjusted (beginning with 0). A high number means a high preference.
* **Give Points** The user can rate the choices by assigning a number of points. The maximum number of points can be set below. The choice with the most points is preferred. 
* **Rank Choices** The user has to state and order his n highest preferences. How many choices need to be rated can be set below.
* **Tick Accept**  The user can state for each choice if it is acceptable for him.';
$string['strategy_not_specified'] = 'You have to select a strategy';
$string['strategyoptions_for_strategy'] = 'Options for Strategy "{$a}"';
$string['err_required'] = 'You need to provide a value for this field.';
$string['err_minimum'] = 'The minimum value for this field is {$a}.';
$string['err_maximum'] = 'The maximum value for this field is {$a}.';
// </editor-fold>


/* Specific to Strategy01, YesNo */
$string['strategy_yesno_name'] = 'Yes-No';
$string['strategy_yesno_setting_crossout'] = 'Maximum amount the user can rate with "no"';
$string['strategy_yesno_max_no'] = 'You may only assign "No" to {$a} choices';
$string['strategy_yesno_rating_crossout'] = 'No';
$string['strategy_yesno_rating_choose'] = 'Yes';
$string['strategy_yesno_maximum_crossout'] = 'You may only assign "No" to a most {$a} choices';

/* Specific to Strategy02, YesMayBeNo */
$string['strategy_yesmaybeno_name'] = 'Yes-Maybe-No';
$string['strategy_yesmaybeno_max_no'] = 'You may only assign "No" to {$a} choices';
$string['strategy_yesmaybeno_rating_no'] = 'No';
$string['strategy_yesmaybeno_rating_yes'] = 'Yes';
$string['strategy_yesmaybeno_rating_maybe'] = 'Maybe';
$string['strategy_yesmaybeno_setting_maxno'] = 'Maximum amount the user can rate with "no"';
$string['strategy_yesmaybeno_max_count_no'] = 'You can only choose No for at most {$a} options.';

// Specific to Strategy03, Likert
$string['strategy_lickert_name'] = 'Likert Scale';
$string['strategy_lickert_setting_maxno'] = 'Max Number of choices user can rate with 0';
$string['strategy_lickert_setting_maxlickert'] = 'Highest number on the likert-scale (3, 5 or 7 are common values)';
$string['strategy_lickert_max_no'] = 'You may give 0 points to at most {$a} choice(s).';
$string['strategy_lickert_rating_biggestwish'] = 'Highly appreciated';
$string['strategy_lickert_rating_exclude'] = 'Exclude';

// Specific to Strategy04, Points
$string['strategy_points_name'] = 'Give Points';
$string['strategy_points_explain_distribute_points'] = 'Give points to each choice, you have a total of {$a} Points to distribute . Prioritize the best choice with the highest points';
$string['strategy_points_explain_max_zero'] = 'You can give 0 points to at most {$a} choices';
$string['strategy_points_incorrect_totalpoints'] = 'Incorrect number of points. They all have to sum up to {$a}';
$string['strategy_points_setting_maxzero'] = 'Max Number of choices user can give 0 points';
$string['strategy_points_setting_totalpoints'] = 'Total number of points users can assign';
$string['strategy_points_max_count_zero'] = 'You shall assign more than 0 points to at least {$a} choices';

// Specific to Strategy05, Order
$string['strategy_order_name'] = 'Rank Choices';
$string['strategy_order_no_choice'] = '{$a}. Choice';
$string['strategy_order_use_only_once'] = 'Choices cannot be selected twice and must be unique.';
$string['strategy_order_explain_choices'] = 'Select one choice in each select-box. 1st Choice gets highest priority, etc.';
$string['strategy_order_setting_countoptions'] = 'Number of fields the user is presented to vote on (smaller than number of choices!)';


// Specific to Strategy06, tickyes
$string['strategy_tickyes_name'] = 'Tick Accept';
$string['strategy_tickyes_accept'] = 'Accept';
$string['strategy_tickyes_not_accept'] = '-';
$string['strategy_tickyes_setting_mintickyes'] = 'Minimum of choices to accept';
$string['strategy_tickyes_error_mintickyes'] = 'You have to tick at least {$a} boxes';
$string['strategy_tickyes_explain_mintickyes'] = 'You have to tick a minimum of {$a} boxes.';

// As message provider, for the notification after allocation
$string['messageprovider:notifyalloc'] = 'Notification of option allocation';
$string['allocation_notification_message_subject'] = 'Notification of finished allocation for {$a}';
$string['allocation_notification_message'] = 'Concerning the "{$a->ratingallocate}", you have been assigned to the choice "{$a->choice}"';

// Logging
$string['log_rating_saved'] = 'User rating saved';
$string['log_rating_saved_description'] =  'The user with id \'{$a->userid}\' saved his rating for the ratingallocate with id \'{$a->ratingallocateid}\'.';

$string['log_rating_viewed'] = 'User rating viewed';
$string['log_rating_viewed_description'] =  'The user with id \'{$a->userid}\' viewed its rating for the ratingallocate with id \'{$a->ratingallocateid}\'.';

$string['log_allocation_published'] = 'Allocation published';
$string['log_allocation_published_description'] =  'The user with id \'{$a->userid}\' published the allocation for the ratingallocate with id \'{$a->ratingallocateid}\'.';

$string['log_distribution_triggered'] = 'Distribution triggered';
$string['log_distribution_triggered_description'] =  'The user with id \'{$a->userid}\' triggered the distribution for the ratingallocate with id \'{$a->ratingallocateid}\'. The algorithm needed {$a->time_needed}sec.';

$string['log_manual_allocation_saved'] = 'Manual allocation saved';
$string['log_manual_allocation_saved_description'] =  'The user with id \'{$a->userid}\' saved a manual allocation for the ratingallocate with id \'{$a->ratingallocateid}\'.';

$string['log_ratingallocate_viewed'] = 'Ratingallocate viewed';
$string['log_ratingallocate_viewed_description'] =  'The user with id \'{$a->userid}\' viewed the ratingallocate with id \'{$a->ratingallocateid}\'.';

$string['log_allocation_table_viewed'] = 'Allocation table viewed';
$string['log_allocation_table_viewed_description'] =  'The user with id \'{$a->userid}\' viewed the allocation table for the ratingallocate with id \'{$a->ratingallocateid}\'.';

$string['log_allocation_statistics_viewed'] = 'Allocation statistics viewed';
$string['log_allocation_statistics_viewed_description'] =  'The user with id \'{$a->userid}\' viewed the allocation statistics for the ratingallocate with id \'{$a->ratingallocateid}\'.';
