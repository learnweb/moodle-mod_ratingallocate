moodle-mod_ratingallocate
============================
Module which lets you add an activity to courses, in which users can rate choices. You may then distribute the users fairly to the choices by maximising overall 'hapiness' in terms of ratings.
This may be an alternative to the choice activity or first-come-first-served.

This plugin is based on previous work by Stefan Koegel and Alexander Bias, University of Ulm.

Installation
============
This is an activity plugin and should go into ``mod/ratingallocate``.
Obtain this plugin from https://moodle.org/plugins/view/mod_ratingallocate.

Usage
============

Add an activity instance. Set mandatory parameters are timespan, in which users can give ratings, choices, which the users will have to rate and the strategy,
which form will be presented to the users to rate.
After the rating period has finished, you can distribute the users automatically or manually. Upon publishing the results, users will be able to see which choice they have been associated with

Moodle version
======================
Tested with Moodle 2.7.2+ (20140911) and Moodle 2.8.5+ (Build: 20150319).

Algorithm
=========
This module uses a modified Edmonds-karp algorithm to solve the minimum-cost flow problem. Augmenting paths are found using Bellman-Ford, but the user ratings are multiplied with -1 first.

Worst-Case complexity is O(m^2n^2) with m,n being number of edges (#users+#choices+#ratings_users_gave) and nodes (2+#users+#choices) in the graph.
Distributing 500 users to 21 choices takes around 11sec.

