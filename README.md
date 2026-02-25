moodle-mod_ratingallocate
============================

Module which lets you add an activity to courses, in which users can rate choices. You may then distribute the users fairly to the choices by maximising overall 'hapiness' in terms
of ratings.
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
After the rating period has finished, you can allocate the users automatically or manually. Upon publishing the results, users will be able to see which choice they have been
allocated to.
For more information please visit the [moodle wiki](https://docs.moodle.org/31/en/Ratingallocate).

Ratingallocate versions and Moodle versions
=========================================

The plugin is continously tested with all Moodle versions, which are
security supported by the Moodle Headquarters.  GitHub Actions are
used to run the PHPUnit and Behat tests of the most current release
against the supported Moodle versions.

Ratingallocate follows a three‑part versioning format inspired by
Semantic Versioning:

```
  MAJOR.MINOR.PATCH.
```

MAJOR is increased only when we drop support for a Moodle core version
(for example, when the plugin no longer works with Moodle 4.5). This
signals a breaking change for environments that still rely on the
removed Moodle version.  MINOR is increased when we add new
functionality (new features, settings, or APIs) or when we add support
for a new Moodle version while keeping existing compatibility. This
indicates additional capabilities without breaking existing behavior.
PATCH is increased for bug‑fixes only that do not alter the plugin’s
public API or feature set. This denotes a maintenance release that
improves stability without changing functionality.

Here are some examples how versions could change.

| Version | Reason for the bump                                                                |
| 5.0.0   | Initial release under the new scheme                                               |
| 5.0.1   | Fixed a PHP notice. Same features, more stable – safe to upgrade for bug‑fix only. |
| 5.1.0   | Add support for Moodle 5.2                                                         |
| 6.0.0   | Drop support for Moodle 4.5                                                        |
| 6.1.0   | Add a new distribution algorithm                                                   |

These examples illustrate how the three numbers convey at a glance
whether a release is a patch (bug‑only), a minor (new features or
added Moodle support), or a major (dropping support for an older
Moodle version). Use this information to decide how urgently you need
to upgrade your installation.

Algorithm
=========
This module uses a modified Edmonds-karp algorithm to solve the minimum-cost flow problem. Augmenting paths are found using Bellman-Ford, but the user ratings are multiplied with
-1 first.

Worst-Case complexity is O(m^2n^2) with m,n being number of edges (#users+#choices+#ratings_users_gave) and nodes (2+#users+#choices) in the graph.
Distributing 500 users to 21 choices takes around 11sec.

Changelog
=========

The list of user-visible changes for every release are listed in
[CHANGES.md](CHANGES.md). Previous (older) ChangeLogs can be found in
[CHANGES.old.md](CHANGES.old.md).
