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
 * Choice instance for ratingallocate.
 *
 * @package   mod_ratingallocate
 * @copyright 2025 Tamaro Walter
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ratingallocate\choice;

use stdClass;

/**
 * Kapselt eine Instanz von ratingallocate_choice
 *
 * @property int $id
 * @property int $ratingallocateid
 * @property string $title
 * @property string explanation
 * @property int $maxsize
 * @property bool $active
 * @property bool $usegroups Whether to restrict the visibility of this choice to the members of specified groups.
 */
class ratingallocate_choice {
    /** @var stdClass original db record */
    public $dbrecord;

    /** Emulates the functionality as if there were explicit records by passing them to the original db record
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->dbrecord->{$name};
    }

    /** Emulates the functionality as if there were explicit records by passing them to the original db record
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
