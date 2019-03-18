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
 * Privacy provider tests.
 *
 * @package    mod_ratingallocate
 * @copyright  2018 Tamara Gunkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use mod_ratingallocate\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @package    mod_ratingallocate
 * @copyright  2018 Tamara Gunkel
 * @group      mod_ratingallocate
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_algorithm_subplugins_testcase extends basic_testcase {

    public function test_default_algorithms_present() {
        $algorithms = \mod_ratingallocate\algorithm::get_available_algorithms();
        $this->assertGreaterThan(2, count($algorithms));
        $this->assertArrayHasKey('edmondskarp', $algorithms);
        $this->assertArrayHasKey('fordfulkersonkoegel', $algorithms);
    }

    public function test_loads_assignment_subtype() {
        $algorithm = \mod_ratingallocate\algorithm::get_instance('edmondskarp');
        $this->assertInstanceOf(\mod_ratingallocate\algorithm::class, $algorithm);
    }
}
