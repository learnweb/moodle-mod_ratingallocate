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
 * Contains unit tests for the sdwithopt algorithm.
 *
 * @package    raalgo_sdwithopt
 * @copyright  2019 WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class raalgo_sdwithopt_algorithm_test extends advanced_testcase {

    public function test_algorithm() {

        $algorithm = new raalgo_sdwithopt\algorithm_impl_testable();
        $users = array();
        $globalranking = array();
        for ($i = 0; $i < 10; $i++) {
            $user = new \raalgo_sdwithopt\user();
            $user->id = $i + 1;
            $users[$i + 1] = $user;
        }

        $choice1 = new \raalgo_sdwithopt\choice();
        $choice1->id = 1000;
        $choice1->minsize = 2;
        $choice1->maxsize = 3;
        $choice1->optional = true;
        $choice1->waitinglist = [];

        $choice2 = new \raalgo_sdwithopt\choice();
        $choice2->id = 2000;
        $choice2->minsize = 5;
        $choice2->maxsize = 5;
        $choice2->optional = true;
        $choice2->waitinglist = [1 => 2];

        $choice3 = new \raalgo_sdwithopt\choice();
        $choice3->id = 3000;
        $choice3->minsize = 2;
        $choice3->maxsize = 4;
        $choice3->optional = true;
        $choice3->waitinglist = [];

        $choice4 = new \raalgo_sdwithopt\choice();
        $choice4->id = 4000;
        $choice4->minsize = 3;
        $choice4->maxsize = 6;
        $choice4->optional = false;
        $choice4->waitinglist = [];

        $choices = [
            1000 => $choice1,
            2000 => $choice2,
            3000 => $choice3,
            4000 => $choice4,
        ];

        $ratings = array();
        foreach ($users as $user) {
            $randomchoices = [1000, 2000, 3000, 4000];
            shuffle($randomchoices);
            for ($i = 3; $i > 0; $i--) {
                $choiceid = array_pop($randomchoices);
                $rating = new stdClass();
                $rating->userid = $user->id;
                $rating->choiceid = $choiceid;
                $rating->rating = $i;
                $ratings[] = $rating;
            }
        }

        $allocation = $algorithm->compute_distribution($choices, $ratings, $users);

        $sumofallocations = 0;
        foreach ($choices as $choice) {
            $feasibleoptional = $choice->optional && count($allocation[$choice->id]) == 0;
            $feasiblemax = $choice->maxsize >= count($allocation[$choice->id]);
            $feasiblemin = $choice->minsize <= count($allocation[$choice->id]);
            $this->assertTrue($feasibleoptional || ($feasiblemax && $feasiblemin));
            $sumofallocations += count($allocation[$choice->id]);
        }
        $this->assertEquals(count($users), $sumofallocations);
    }
}
