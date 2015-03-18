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
 * Contains unit tests for the distribution algorithm.
 *
 * @package    mod
 * @subpackage mod_groupdistribution
 * @group mod_ratingallocate
 * @copyright  original Version 2013 Stefan Koegel
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/ratingallocate/locallib.php');
require_once($CFG->dirroot . '/mod/ratingallocate/solver/edmonds-karp.php');
require_once($CFG->dirroot . '/mod/ratingallocate/solver/ford-fulkerson-koegel.php');

class edmonds_karp_test extends basic_testcase {

    private function perform_race($groupsnum, $ratersnum) {
        $groupsmaxsizemin = floor($ratersnum / $groupsnum);
        $groupsmaxsizemax = ceil($ratersnum / $groupsnum) + 1;

        $rateminimum = 0.7; // jeder Student gibt mind. votings ab
        $ratingmax = 5; // Rating von 1-X
        $result = array();
        $groups = array();

        for ($i = 1; $i < $groupsnum; $i++) {
            $groups [$i] = new stdClass();
            $groups[$i]->id = $i;
            $groups[$i]->maxsize = rand($groupsmaxsizemin, $groupsmaxsizemax);
        }

        $ratings = array();

        for ($i = 1; $i < $ratersnum; $i++) {
            $ratingsgiven = 0;
            // create a rating for each group (or not, but simulate...)
            for ($l = 1; $l < $groupsnum; $l++) {
                // create a rating for this group?
                if ($l * $rateminimum > $ratingsgiven) {
                    $rating = rand(1, $ratingmax);
                } else {
                    $rating = rand(0, $ratingmax);
                }
                if ($rating > 0) {
                    $thisrating = new stdClass();
                    $thisrating->userid = $i;
                    $thisrating->choiceid = $l;
                    $thisrating->rating = $rating;
                    $ratings[] = $thisrating;
                    $ratingsgiven++;
                }
            }
        }

        $usercount = $ratersnum;

        $solvers = array('solver_edmonds_karp', 'solver_ford_fulkerson');
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

    public function teston_random() {
        if (!PHPUNIT_LONGTEST) {
            return; // this test takes longer than 10s
        }
        $testparams = array(array(5, 25), array(10, 50), array(10, 100), array(20, 200)); // , array(40, 400), array(45, 600), array(85, 1000));
        // $testparams = array(array(10,25), array(3,25), array(29,200), array(8,200), array(64,1000), array(16,1000));
        $testergebnisse = array();
        foreach ($testparams as $testset) {
            // $paramgroups = $testset[0];
            $paramgroups = ceil(sqrt($testset[1]));
            $paramusers = $testset[1];
            // $paramusers = ceil($testset[1] / 2);
            $rundenergebnis = array();
            for ($i = 0; $i < 10; $i++) {
                $ergebnis = $this->perform_race($paramgroups, $paramusers);
                $this->assertEquals($ergebnis['ford-fulkerson Koegel2014']['gesamtpunktzahl'], $ergebnis['edmonds_karp']['gesamtpunktzahl']);
                $rundenergebnis[] = $ergebnis;
            }
            $durchschnitt = array();
            $counter = 0;
            // Durchschnitt der Runde berechnen
            foreach ($rundenergebnis as $einzelergebnis) {
                $counter ++;
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

            // an die ganzen Testergebnisse anhängen
            $testergebnisse[] = $durchschnitt;
        }
        // print_r($testergebnisse);
    }

    public function test_edmondskarp() {
        $choices = array();
        $choices[1] = new stdClass();
        $choices[1]->maxsize = 2;
        $choices[1]->id = 1;
        $choices[2] = new stdClass();
        $choices[2]->maxsize = 2;
        $choices[2]->id = 2;

        $ratings = array();
        $ratings[1] = new stdClass();
        $ratings[1]->userid = 1;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 3;

        $ratings[3] = new stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 5;

        $ratings[4] = new stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 2;

        $ratings[5] = new stdClass();
        $ratings[5]->userid = 3;
        $ratings[5]->choiceid = 1;
        $ratings[5]->rating = 2;

        $ratings[6] = new stdClass();
        $ratings[6]->userid = 3;
        $ratings[6]->choiceid = 2;
        $ratings[6]->rating = 0;

        $ratings[7] = new stdClass();
        $ratings[7]->userid = 4;
        $ratings[7]->choiceid = 1;
        $ratings[7]->rating = 4;

        $ratings[8] = new stdClass();
        $ratings[8]->userid = 4;
        $ratings[8]->choiceid = 2;
        $ratings[8]->rating = 4;

        $ratings[9] = new stdClass();
        $ratings[9]->userid = 5;
        $ratings[9]->choiceid = 1;
        $ratings[9]->rating = 3;

        $usercount = 5;

        $solver = new solver_edmonds_karp();
        $distribution = $solver->compute_distribution($choices, $ratings, $usercount);
        $expected = array(1 => array(2, 5), 2 => array(4, 1));
        // echo "gesamtpunktzahl: " . $solver->compute_target_function($choices, $ratings, $distribution);
        // echo "solver-name: " . $solver->get_name();
        $this->assertEquals($expected, $distribution);
        $this->assertEquals($solver::compute_target_function($ratings, $distribution), 15);

        // test against Koegels solver
        $solverkoe = new solver_ford_fulkerson();
        $distributionkoe = $solverkoe->compute_distribution($choices, $ratings, $usercount);
        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe), 15);
        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe), $solver::compute_target_function($ratings, $distribution));
    }

    public function test_negweightcycle() {
        // experimental
        $choices = array();
        $choices[1] = new stdClass();
        $choices[1]->maxsize = 2;
        $choices[1]->id = 1;
        $choices[2] = new stdClass();
        $choices[2]->maxsize = 2;
        $choices[2]->id = 2;

        $ratings = array();
        $ratings[1] = new stdClass();
        $ratings[1]->userid = 1;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 5;

        $ratings[3] = new stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 5;

        $ratings[4] = new stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 1;

        $usercount = 2;

        $solver = new solver_edmonds_karp();
        $distribution = $solver->compute_distribution($choices, $ratings, $usercount);
        $this->assertEquals($solver::compute_target_function($ratings, $distribution), 10);

        $solverkoe = new solver_ford_fulkerson();
        $distributionkoe = $solverkoe->compute_distribution($choices, $ratings, $usercount);

        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe), 10);
        $this->assertEquals($solverkoe::compute_target_function($ratings, $distributionkoe), $solver::compute_target_function($ratings, $distribution));
    }

    public function test_targetfunc() {
        $ratings = array();
        $ratings[1] = new stdClass();
        $ratings[1]->userid = 1;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 4;

        $ratings[3] = new stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 3;

        $ratings[4] = new stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 4;

        $this->assertEquals(distributor::compute_target_function($ratings, array(1 => array(1), 2 => array(2))), 9);
        $this->assertEquals(distributor::compute_target_function($ratings, array(1 => array(1, 2))), 8);
        $this->assertEquals(distributor::compute_target_function($ratings, array(1 => array(2), 2 => array(1))), 7);
    }

    /**
     * Test id conversions from user+choicids to graphids
     */
    public function test_setupids() {
        $ratings = array();
        $ratings[1] = new stdClass();
        $ratings[1]->userid = 3;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new stdClass();
        $ratings[2]->userid = 3;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 3;

        $ratings[3] = new stdClass();
        $ratings[3]->userid = 2;
        $ratings[3]->choiceid = 1;
        $ratings[3]->rating = 5;

        $ratings[4] = new stdClass();
        $ratings[4]->userid = 2;
        $ratings[4]->choiceid = 2;
        $ratings[4]->rating = 2;

        $usercount = 2;
        list($fromuserid, $touserid, $fromchoiceid, $tochoiceid) = solver_edmonds_karp::setup_id_conversions($usercount, $ratings);

        $this->assertEquals(array(3 => 1, 2 => 2), $fromuserid);
        $this->assertEquals(array(1 => 3, 2 => 2), $touserid);

        $this->assertEquals(array(1 => 3, 2 => 4), $fromchoiceid);
        $this->assertEquals(array(3 => 1, 4 => 2), $tochoiceid);
    }

}
