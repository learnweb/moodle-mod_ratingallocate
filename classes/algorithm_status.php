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
/**
 * The different status a ratingallocate object can be in according to its algorithm run.
 *
 * @package    mod_ratingallocate
 * @copyright  2015 Tobias Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class algorithm_status {
    const FAILURE = -1; // Algorithm did not finish correctly.
    const NOTSTARTED = 0; // Default status for new instances.
    const RUNNING = 1; // Algorithm is currently running.
    const FINISHED = 2; // Algorithm finished correctly.
}
