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

namespace mod_ratingallocate;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/ratingallocate/locallib.php');
require_once($CFG->dirroot . '/mod/ratingallocate/solver/edmonds-karp.php');
require_once($CFG->dirroot . '/mod/ratingallocate/solver/ford-fulkerson-koegel.php');

/**
 * Contains unit tests for the distribution algorithm.
 *
 * @package mod_ratingallocate
 * @subpackage mod_groupdistribution
 * @group mod_ratingallocate
 * @copyright  original Version 2013 Stefan Koegel
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \solver
 */
final class mod_ratingallocate_solver_test extends \basic_testcase {

    /**
     * Perform race.
     *
     * @param int $groupsnum
     * @param int $ratersnum
     * @return array
     */
    private function perform_race($groupsnum, $ratersnum) {
        $groupsmaxsizemin = floor($ratersnum / $groupsnum);
        $groupsmaxsizemax = ceil($ratersnum / $groupsnum) + 1;

        $rateminimum = 0.7; // Every Student gives a min voting.
        $ratingmax = 5; // Rating from 1 to X.
        $result = [];
        $groups = [];

        for ($i = 1; $i < $groupsnum; $i++) {
            $groups[$i] = new \stdClass();
            $groups[$i]->id = $i;
            $groups[$i]->maxsize = rand($groupsmaxsizemin, $groupsmaxsizemax);
        }

        $ratings = [];

        for ($i = 1; $i < $ratersnum; $i++) {
            $ratingsgiven = 0;
            // Create a rating for each group (or not, but simulate...).
            for ($l = 1; $l < $groupsnum; $l++) {
                // Create a rating for this group?
                if ($l * $rateminimum > $ratingsgiven) {
                    $rating = rand(1, $ratingmax);
                } else {
                    $rating = rand(0, $ratingmax);
                }
                if ($rating > 0) {
                    $thisrating = new \stdClass();
                    $thisrating->userid = $i;
                    $thisrating->choiceid = $l;
                    $thisrating->rating = $rating;
                    $ratings[] = $thisrating;
                    $ratingsgiven++;
                }
            }
        }

        $usercount = $ratersnum;

        $solvers = ['solver_edmonds_karp', 'solver_ford_fulkerson'];
        foreach ($solvers as $solver) {
            $solver1 = new $solver;
            $timestart = microtime(true);
            $distribution1 = $solver1->compute_distribution($groups, $ratings, $usercount);
            $result[$solver1->get_name()]['elapsed_sec'] = (microtime(true) - $timestart);
            $result[$solver1->get_name()]['gesamtpunktzahl'] = $solver1->compute_target_function($ratings, $distribution1);
            $result[$solver1->get_name()]['mempeak'] = memory_get_peak_usage();
            unset($solver1);
        }

        return $result;
    }

    /**
     * Test on random.
     *
     * @return void
     */
    public function teston_random() {
        if (!PHPUNIT_LONGTEST) {
            return; // This test takes longer than 10s.
        }
        $testparams = [[5, 25], [10, 50], [10, 100],
                [20, 200]];
        $testergebnisse = [];
        foreach ($testparams as $testset) {
            $paramgroups = ceil(sqrt($testset[1]));
            $paramusers = $testset[1];
            $rundenergebnis = [];
            for ($i = 0; $i < 10; $i++) {
                $ergebnis = $this->perform_race($paramgroups, $paramusers);
                $this->assertEquals($ergebnis['ford-fulkerson Koegel2014']['gesamtpunktzahl'],
                        $ergebnis['edmonds_karp']['gesamtpunktzahl']);
                $rundenergebnis[] = $ergebnis;
            }
            $durchschnitt = [];
            $counter = 0;
            // Calculate average for each round.
            foreach ($rundenergebnis as $einzelergebnis) {
                $counter++;
                foreach ($einzelergebnis as $algname => $algresult) {
                    if (!key_exists($algname, $durchschnitt)) {
                        $durchschnitt[$algname] = 0;
                    }
                    $durchschnitt[$algname] += $algresult['elapsed_sec'];
                }
            }

            foreach ($durchschnitt as &$algresultsum) {
                $algresultsum = $algresultsum / $counter;
            }
            $durchschnitt['param_users'] = $paramusers;
            $durchschnitt['param_groups'] = $paramgroups;
            $durchschnitt['letzte_punkte'] = $ergebnis['ford-fulkerson Koegel2014']['gesamtpunktzahl'];

            // Append to the other results.
            $testergebnisse[] = $durchschnitt;
        }
    }

    /**
     * Test Edmonds-Karp algorithm.
     *
     * @return void
     * @covers \solver_edmonds_karp
     */
    public function test_edmondskarp(): void {
        $choices = [];
        $choices[1] = new \stdClass();
        $choices[1]->maxsize = 2;
        $choices[1]->id = 1;
        $choices[2] = new \stdClass();
        $choices[2]->maxsize = 2;
        $choices[2]->id = 2;

        $ratings = [];
        $ratings[1] = new \stdClass();
        $ratings[1]->userid = 1;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new \stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 3;

        $ratings[3] = new \stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 5;

        $ratings[4] = new \stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 2;

        $ratings[5] = new \stdClass();
        $ratings[5]->userid = 3;
        $ratings[5]->choiceid = 1;
        $ratings[5]->rating = 2;

        $ratings[6] = new \stdClass();
        $ratings[6]->userid = 3;
        $ratings[6]->choiceid = 2;
        $ratings[6]->rating = 0;

        $ratings[7] = new \stdClass();
        $ratings[7]->userid = 4;
        $ratings[7]->choiceid = 1;
        $ratings[7]->rating = 4;

        $ratings[8] = new \stdClass();
        $ratings[8]->userid = 4;
        $ratings[8]->choiceid = 2;
        $ratings[8]->rating = 4;

        $ratings[9] = new \stdClass();
        $ratings[9]->userid = 5;
        $ratings[9]->choiceid = 1;
        $ratings[9]->rating = 3;

        $usercount = 5;

        $solver = new \solver_edmonds_karp();
        $distribution = $solver->compute_distribution($choices, $ratings, $usercount);
        $expected = [1 => [2, 5], 2 => [4, 1]];
        $this->assertEquals($expected, $distribution);
        $this->assertEquals($solver::compute_target_function($ratings, $distribution), 15);

        // Test against Koegels solver.
        $solverkoe = new \solver_ford_fulkerson();
        $distributionkoe = $solverkoe->compute_distribution($choices, $ratings, $usercount);
        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe), 15);
        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe),
                $solver::compute_target_function($ratings, $distribution));
    }

    /**
     * Test algorithm for negativeweightcycle.
     *
     * @return void
     */
    public function test_negweightcycle(): void {
        // Experimental.
        $choices = [];
        $choices[1] = new \stdClass();
        $choices[1]->maxsize = 2;
        $choices[1]->id = 1;
        $choices[2] = new \stdClass();
        $choices[2]->maxsize = 2;
        $choices[2]->id = 2;

        $ratings = [];
        $ratings[1] = new \stdClass();
        $ratings[1]->userid = 1;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new \stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 5;

        $ratings[3] = new \stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 5;

        $ratings[4] = new \stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 1;

        $usercount = 2;

        $solver = new \solver_edmonds_karp();
        $distribution = $solver->compute_distribution($choices, $ratings, $usercount);
        $this->assertEquals($solver::compute_target_function($ratings, $distribution), 10);

        $solverkoe = new \solver_ford_fulkerson();
        $distributionkoe = $solverkoe->compute_distribution($choices, $ratings, $usercount);

        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe), 10);
        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe),
                $solver::compute_target_function($ratings, $distribution));
    }

    /**
     * Test tagetfunction.
     *
     * @return void
     */
    public function test_targetfunc(): void {
        $ratings = [];
        $ratings[1] = new \stdClass();
        $ratings[1]->userid = 1;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new \stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 4;

        $ratings[3] = new \stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 3;

        $ratings[4] = new \stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 4;

        $this->assertEquals(\distributor::compute_target_function($ratings, [1 => [1], 2 =>
            [2]]), 9);
        $this->assertEquals(\distributor::compute_target_function($ratings, [1 => [1, 2]]), 8);
        $this->assertEquals(\distributor::compute_target_function($ratings, [1 => [2], 2 => [1]]), 7);
    }

    /**
     * Test id conversions from user+choicids to graphids
     */
    public function test_setupids(): void {
        $ratings = [];
        $ratings[1] = new \stdClass();
        $ratings[1]->userid = 3;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new \stdClass();
        $ratings[2]->userid = 3;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 3;

        $ratings[3] = new \stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 5;

        $ratings[4] = new \stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 2;

        $usercount = 2;
        list($fromuserid, $touserid, $fromchoiceid, $tochoiceid) = \solver_edmonds_karp::setup_id_conversions($usercount, $ratings);

        $this->assertEquals([3 => 1, 2 => 2], $fromuserid);
        $this->assertEquals([1 => 3, 2 => 2], $touserid);

        $this->assertEquals([1 => 3, 2 => 4], $fromchoiceid);
        $this->assertEquals([3 => 1, 4 => 2], $tochoiceid);
    }

}
