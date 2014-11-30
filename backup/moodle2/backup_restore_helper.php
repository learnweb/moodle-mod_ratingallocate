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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright  2014 C. Usener
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_fields_for_tableClass($class) {
    $class = new ReflectionClass($class);
    $constants = $class->getConstants();
    $keys_to_remove = array('ID', 'TABLE');
    foreach($constants as $key => $value) {
        if (count(array_intersect(array($key), $keys_to_remove)) > 0)
            unset($constants[$key]);
    }
    return array_values($constants);
}

function get_tablename_for_tableClass($class) {
    $class = new ReflectionClass($class);
    $constants = $class->getConstants();
    return $constants['TABLE'];
}

function get_id_for_tableClass($class) {
    $class = new ReflectionClass($class);
    $constants = $class->getConstants();
    return array($constants['ID']);
}