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
 *
 * Contains the algorithm for the distribution
 *
 * @package    raalgo_sdwithopt
 * @copyright  2019 Wwu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace raalgo_sdwithopt;
defined('MOODLE_INTERNAL') || die();

class algorithm_impl extends \mod_ratingallocate\algorithm {

    /** @var array */
    protected $globalranking;

    public function get_name() {
        return 'sdwithopt';
    }

    /**
     * Computes the distribution of students to choices based on the students ratings.
     * @param $choicerecords array[] array of all choices which are ratable in this ratingallocate.
     * @param $ratings array[] array of all relevant ratings.
     * @param $raters array[] array of all raters in course.
     * @return array mapping of choice ids to array of user ids.
     */
    public function compute_distribution($choicerecords, $ratings, $raters) {
        // minsize, maxsize, opt

        // Compute global ranking.
        $this->prepare_execution($raters);


        return array();
    }

    /**
     * @param $raters
     */
    protected function prepare_execution($raters) {
        // Compute global ranking:
        $userids = array_keys($raters);
        shuffle($userids);
        $this->globalranking = array();
        foreach ($userids as $userid) {
            $this->globalranking[] = $raters[$userid];
        }
    }
}
