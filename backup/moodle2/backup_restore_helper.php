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
 * Backup restore helper.
 *
 * @package mod_ratingallocate
 * @subpackage backup-moodle2
 * @copyright  2014 C. Usener
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @param $class
 * @return array
 * @throws ReflectionException
 */
function get_fields_for_tableclass($class) {
    $class = new ReflectionClass($class);
    $constants = $class->getConstants();
    $keystoremove = ['ID', 'TABLE'];
    foreach ($constants as $key => $value) {
        if (count(array_intersect([$key], $keystoremove)) > 0) {
            unset($constants[$key]);
        }
    }
    return array_values($constants);
}

/**
 * @param $class
 * @return mixed
 * @throws ReflectionException
 */
function get_tablename_for_tableclass($class) {
    $class = new ReflectionClass($class);
    $constants = $class->getConstants();
    return $constants['TABLE'];
}

/**
 * @param $class
 * @return array
 * @throws ReflectionException
 */
function get_id_for_tableclass($class) {
    $class = new ReflectionClass($class);
    $constants = $class->getConstants();
    return [$constants['ID']];
}
