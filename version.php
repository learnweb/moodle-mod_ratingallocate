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
 * Defines the version of ratingallocate
 *
 * @package    mod_ratingallocate
 * @copyright 2014 T Reischmann, C Usener
 * @copyright based on code by M Schulze copyright (C) 2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2017102700;        // The current module version (Date: YYYYMMDDXX)
$plugin->requires  = 2016052300;        // Requires this Moodle version
$plugin->cron      = 300;                 // Period for cron to check this module (secs)
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v3.4-r1';
$plugin->component = 'mod_ratingallocate';  // To check on upgrade, that module sits in correct place
