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

/**
 * Choice status
 *
 * @package   mod_ratingallocate
 * @copyright 2014 M. Schulze
 * @copyright 2014 C. Usener
 * @copyright 2014 T. Reischmann
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ratingallocate_choice_status implements renderable {
    /** @var $accesstimestop */
    public $accesstimestop;
    /** @var $accesstimestart */
    public $accesstimestart;
    /** @var $ispublished */
    public $ispublished;
    /** @var $publishdate */
    public $publishdate;
    /** @var $availablechoices */
    public $availablechoices;
    /** @var $necessarychoices */
    public $necessarychoices;
    /** @var $ownchoices */
    public $ownchoices;
    /** @var $allocations */
    public $allocations;
    /** @var $strategy */
    public $strategy;
    /** @var bool show_distribution_info specifies if the info regarding the distribution should be displayed. * */
    public $showdistributioninfo;
    /** @var bool show_user_info specifies if the current ratings of the user shoulld be renderer. * */
    public $showuserinfo;
    /** @var $algorithmstarttime */
    public $algorithmstarttime;
    /** @var $algorithmstatus */
    public $algorithmstatus;
}
