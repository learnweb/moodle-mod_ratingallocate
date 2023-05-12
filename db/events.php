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
 * Definition of ratingallocate event observers.
 *
 * The observers defined in this file are notified when respective events are triggered.
 *
 * @package   mod_ratingallocate
 * @category  event
 * @copyright 2023 I Hoppe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();


$handlers = array();

// List of observers for group_deleted and grouping_deleted.

$observers = array(
    array(
        'eventname' => '\core\event\group_deleted',
        'callback'  => 'mod_ratingallocate\ratingallocate_observer::ch_gengroups_delete',
    ),
    array(
        'eventname' => '\core\event\grouping_deleted',
        'callback'  => 'mod_ratingallocate\ratingallocate_observer::ra_groupings_delete'
    )
);
