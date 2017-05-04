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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the renderable classes for the ratingallocate module
 *
 * @package mod_ratingallocate
 * @copyright 2014 C Usener, T Reischmann
 * @copyright based on code by M Schulze copyright (C) 2014 M Schulze
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Renderable header
 * @package   mod_ratingallocate
*/
class ratingallocate_header implements renderable {
    /** @var ratingallocate_db_wrapper the ratingallocate class  */
    public $ratingallocate = null;
    /** @var mixed context|null the context record  */
    public $context = null;
    /** @var bool $showintro - show or hide the intro */
    public $showintro = false;
    /** @var int coursemoduleid - The course module id */
    public $coursemoduleid = 0;

    public function __construct(ratingallocate_db_wrapper $ratingallocate, $context, $showintro, $coursemoduleid) {
        $this->ratingallocate = $ratingallocate;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
    }
}

class ratingallocate_choice_status implements renderable {

    public $accesstimestop;
    public $accesstimestart;
    public $is_published;
    public $publishdate;
    public $available_choices;
    public $necessary_choices;
    public $own_choices;
    public $allocations;
    public $strategy;
    /** @var bool show_distribution_info specifies if the info regarding the distribution should be displayed. **/
    public $show_distribution_info;
    /** @var bool show_user_info specifies if the current ratings of the user shoulld be renderer. **/
    public $show_user_info;
    public $algorithmstarttime;
    public $algorithmstatus;
}