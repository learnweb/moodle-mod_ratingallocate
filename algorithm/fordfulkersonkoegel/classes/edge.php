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
 * @package    raalgo_fordfulkersonkoegel
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace raalgo_fordfulkersonkoegel;
defined('MOODLE_INTERNAL') || die();

/**
 * Represents an Edge in the graph to have fixed fields instead of array-fields
 */
class edge {
    /** @var from int */
    public $from;
    /** @var to int */
    public $to;
    /** @var weight int Cost for this edge (rating of user) */
    public $weight;
    /** @var space int (places left for choices) */
    public $space;

    public function __construct($from, $to, $weight, $space = 0) {
        $this->from = $from;
        $this->to = $to;
        $this->weight = $weight;
        $this->space = $space;
    }

}