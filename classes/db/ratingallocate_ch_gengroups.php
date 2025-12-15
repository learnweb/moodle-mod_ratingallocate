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

namespace mod_ratingallocate\db;

/**
 * Database structure of the Ratingallocate generated groups of
 * choices table needed by the ratingallocate module.  Grants easier
 * acces to database fields
 *
 * @package    mod_ratingallocate
 * @copyright  2014 T Reischmann, M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ratingallocate_ch_gengroups {
    /**
     * Table.
     */
    const TABLE = 'ratingallocate_ch_gengroups';
    /**
     * Id.
     */
    const ID = 'id';
    /**
     * Groupid.
     */
    const  GROUPID = 'groupid';
    /**
     * Choiceid.
     */
    const CHOICEID = 'choiceid';
}
