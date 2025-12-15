<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

use mod_ratingallocate\wrapper\ratingallocate_db_wrapper;
use core\output\renderable;

/**
 * Renderable header
 * @package   mod_ratingallocate
 * @copyright 2014 M. Schulze
 * @copyright 2014 C. Usener
 * @copyright 2014 T. Reischmann
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ratingallocate_header implements renderable {
    /** @var mod_ratingallocate\wrapper\ratingallocate_db_wrapper the ratingallocate class */
    public $ratingallocate = null;
    /** @var mixed context|null the context record */
    public $context = null;
    /** @var bool $showintro - show or hide the intro */
    public $showintro = false;
    /** @var int coursemoduleid - The course module id */
    public $coursemoduleid = 0;

    /**
     * Construct.
     *
     * @param mod_ratingallocate\wrapper\ratingallocate_db_wrapper $ratingallocate
     * @param \context $context
     * @param bool $showintro
     * @param int $coursemoduleid
     */
    public function __construct(ratingallocate_db_wrapper $ratingallocate, $context, $showintro, $coursemoduleid) {
        $this->ratingallocate = $ratingallocate;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
    }
}
