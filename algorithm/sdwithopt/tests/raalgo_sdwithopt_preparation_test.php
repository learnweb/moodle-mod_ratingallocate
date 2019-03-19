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

class raalgo_sdwithopt_preparation_test extends advanced_testcase {

    public function test_preparation() {
        $this->resetAfterTest();

        $algorithm = new raalgo_sdwithopt\algorithm_impl_testable();
        $users = array();
        $numberofusers = 1000;
        for ($i = 0; $i < $numberofusers; $i++) {
            $users[] = $this->getDataGenerator()->create_user();
        }
        $algorithm->set_users($users);
        $algorithm->prepare_execution();
        $ranking = $algorithm->get_global_ranking();
        $this->assertCount($numberofusers, $ranking);
        for ($i = 0; $i < $numberofusers; $i++) {
            $this->assertTrue(array_key_exists($i, $ranking));
        }
        // Create a second global ranking and check that they differ.
        $algorithm->prepare_execution($users);
        $ranking2 = $algorithm->get_global_ranking();
        $allequal = true;
        for ($i = 0; $i < $numberofusers; $i++) {
            if ($ranking[$i] != $ranking2[$i]) {
                $allequal = false;
                break;
            }
        }
        // Run a second time since there is a probability >0 that the rankings are actually equal.
        if ($allequal) {
            $algorithm->prepare_execution($users);
            $ranking2 = $algorithm->get_global_ranking();
            for ($i = 0; $i < $numberofusers; $i++) {
                if ($ranking[$i] != $ranking2[$i]) {
                    $allequal = false;
                    break;
                }
            }
        }
        $this->assertFalse($allequal, 'Two large randomly generated global rankings were not different.');
    }
}
