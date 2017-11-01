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

mod_ratingallocate can be configured by using the ``$CFG`` object inside the ``config.php`` file or by using the ``config`` table. Currently there are three supported solving strategies: ``edmonds_karp``, which is the default solver, ``ford_fulkerson`` and ``lp``. In order to use the lp solving strategy it is necessary to configure how mod_ratingallocate is using the external lp solver. This is done by using one of the three available executors ``local``, ``ssh`` and ``webservice``. It is recommended to avoid the usage of the ssh executor in production, but using the webservice executor. For now the only supported lp solvers are [scip](http://scip.zib.de/) and [cplex](https://www-01.ibm.com/software/commerce/optimization/cplex-optimizer/).

Webservice backend
------------
Using the webservice executor mod_ratingallocate can use an external lp solver which is not on the same machine as the moodle instance.
A working webserver with PHP is needed. Clone the mod_ratingallocate into the document root and take a look into ``webservice/config.php`` for configuring the executors backend. A strong secret and HTTPS are recommended.

Directives
-----------

    // Solver
    $CFG->ratingallocate_solver = 'lp';
    $CFG->ratingallocate_solver = 'edmonds_karp';
    $CFG->ratingallocate_solver = 'ford_fulkerson';

    // Engine configuration
    $CFG->ratingallocate_engine = 'scip';
    $CFG->ratingallocate_engine = 'cplex';

    // SSH executor
    $CFG->ratingallocate_executor = 'ssh';
    $CFG->ratingallocate_ssh_hostname = '';
    $CFG->ratingallocate_ssh_username = '';
    $CFG->ratingallocate_ssh_password = '';
    $CFG->ratingallocate_remote_path = '/tmp/file.lp';

    // Webservice executor
    $CFG->ratingallocate_executor = 'webservice';
    $CFG->ratingallocate_uri = 'https://hostname/ratingallocate/webservice';
    $CFG->ratingallocate_secret = 'strong_secret';

    // Local executor
    $CFG->ratingallocate_local_path = '/tmp/file.lp';


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

