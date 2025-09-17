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
 * Wrapper for db-record for ratingallocate.
 *
 * @package   mod_ratingallocate
 * @copyright 2025 Tamaro Walter
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate\wrapper;

use stdClass;

/**
 * Wrapper for db-record to have IDE autocomplete feature of fields
 * @property int $id
 * @property int $course
 * @property string $name
 * @property string $intro
 * @property string $strategy
 * @property int $accesstimestart
 * @property int $accesstimestop
 * @property int $publishdate
 * @property int $published
 * @property int $notificationsend
 * @property int $runalgorithmbycron
 * @property int $algorithmstarttime
 * @property int $algorithmstatus
 * -1 failure while running algorithm;
 * 0 algorithm has not been running;
 * 1 algorithm running;
 * 2 algorithm finished;
 * @property string $setting
 */
class ratingallocate_db_wrapper {
    /** @var stdClass */
    public $dbrecord;

    /** Emulates the functionality as if there were explicit records by passing them to the original db record
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->dbrecord->{$name};
    }


    /**
     * Emulates the functionality as if there were explicit records by passing them to the original db record
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->dbrecord->{$name} = $value;
    }

    /**
     * Construct.
     *
     * @param stdClass $record
     */
    public function __construct($record) {
        $this->dbrecord = $record;
    }
}
