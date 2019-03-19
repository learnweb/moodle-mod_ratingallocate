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

global $CFG;

class raalgo_sdwithopt_rejection_by_choices_test extends advanced_testcase {

    public function test_rejection() {

        $algorithm = new raalgo_sdwithopt\algorithm_impl_testable();
        $users = [
            1 => (object) [
                'id' => 1,
                'preferencelist' => [],
                'currentchoice' => 1000,
            ],
            2 => (object) [
                'id' => 2,
                'preferencelist' => [],
                'currentchoice' => 2000,
            ],
            3 => (object) [
                'id' => 3,
                'preferencelist' => [],
                'currentchoice' => 2000,
            ],
            4 => (object) [
                'id' => 4,
                'preferencelist' => [],
                'currentchoice' => 2000,
            ],
        ];
        $globalranking = [
            1 => 0,
            2 => 1,
            3 => 3,
            4 => 2,
        ];
        $choices = [
            1000 => (object) [
                'id' => 1000,
                'minsize' => 1,
                'maxsize' => 1,
                'optional' => false,
                'waitinglist' => [
                    0 => 1,
                ],
            ],
            2000 => (object) [
                'id' => 2000,
                'minsize' => 1,
                'maxsize' => 2,
                'optional' => false,
                'waitinglist' => [
                    1 => 2,
                    2 => 4,
                    3 => 3,
                ],
            ]
        ];
        $algorithm->set_global_ranking($globalranking);
        $algorithm->set_users($users);
        $algorithm->set_choices($choices);

        $algorithm->rejection_by_choices();
        $this->assertEquals(1000, $users[1]->currentchoice);
        $this->assertEquals(2000, $users[2]->currentchoice);
        $this->assertEquals(null, $users[3]->currentchoice);
        $this->assertEquals(2000, $users[4]->currentchoice);
        $this->assertEquals([0 => 1], $choices[1000]->waitinglist); // Assert that method sorts the waiting list.
        $this->assertEquals([1 => 2, 2 => 4], $choices[2000]->waitinglist);
    }
}
