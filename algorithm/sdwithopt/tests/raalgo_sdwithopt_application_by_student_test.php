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

class raalgo_sdwithopt_application_by_student_test extends advanced_testcase {

    public function test_preparation() {

        $algorithm = new raalgo_sdwithopt\algorithm_impl_testable();
        $users = [
            1 => [
                'id' => 1,
                'preferencelist' => [],
            ],
            2 => [
                'id' => 2,
                'preferencelist' => [],
            ],
        ];
        $globalranking = $users;
        $choices = [
            1000 => [
                'id' => 1000,
                'minsize' => 1,
                'maxsize' => 1,
                'optional' => false,
                'waitinglist' => [],
            ],
            2000 => [
                'id' => 2000,
                'minsize' => 1,
                'maxsize' => 1,
                'optional' => false,
                'waitinglist' => [],
            ]
        ];
    }
}
