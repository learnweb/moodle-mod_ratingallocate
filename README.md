moodle-mod_ratingallocate
============================
[![Build Status](https://travis-ci.org/learnweb/moodle-mod_ratingallocate.svg?branch=master)](https://travis-ci.org/learnweb/moodle-mod_ratingallocate)
[![codecov](https://codecov.io/gh/learnweb/moodle-mod_ratingallocate/branch/master/graph/badge.svg)](https://codecov.io/gh/learnweb/moodle-mod_ratingallocate)

Module which lets you add an activity to courses, in which users can rate choices. You may then distribute the users fairly to the choices by maximising overall 'hapiness' in terms of ratings.
This may be an alternative to the choice activity or other first-come-first-served plugins.

This plugin is based on previous work by Stefan Koegel and Alexander Bias, University of Ulm.

Installation
============
This is an activity plugin and should go into ``mod/ratingallocate``.
Obtain this plugin from https://moodle.org/plugins/view/mod_ratingallocate.

Usage
============

Add an activity instance. Set mandatory parameters. These are timespan, in which users can give ratings, and the strategy,
which form will be presented to the users to rate.
Next you can add choices, which the users will have to rate later on.
After the rating period has finished, you can allocate the users automatically or manually. Upon publishing the results, users will be able to see which choice they have been allocated to.
For more information please visit the [moodle wiki](https://docs.moodle.org/31/en/Ratingallocate).

Configuration
=============
General
---------
You can configure ``mod/ratingallocate`` using moodles administration interface. Three different solvers are available for selection. For LP it is necessary to configure the way it will use the external LP solver by selecting an executor.


Webservice backend
------------
Using the webservice executor mod_ratingallocate can use an external lp solver which is not on the same machine as the moodle instance.
A working webserver with PHP is needed. Clone the mod_ratingallocate into the document root and take a look into ``webservice/config.php`` for configuring the executors backend. A strong secret and HTTPS are recommended.


Moodle version
======================
The plugin is continously tested with all moodle versions, which are security supported by the moodle headquarter.
Therefore, Travis uses the most current release to build a test instance and run the behat and unit tests on them.
In addition to all stable branches the version is also tested against the master branch to support early adopters.

Algorithm
=========
Using the Edmonds-Karp algorithm the Worst-Case complexity is O(m^2n^2) with m,n being number of edges (#users+#choices+#ratings_users_gave) and nodes (2+#users+#choices) in the graph.
Distributing 500 users to 21 choices takes around 11sec.

Measurements for using the LP solver or Ford-Fulkerson are not available.

