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
 * Strategymanager for ratingallocate.
 *
 * @package   mod_ratingallocate
 * @copyright 2025 Tamaro Walter
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate\manager;

/**
 * Simulate a static/singleton class that holds all the strategies that registered with him
 */
class strategymanager {
    /** @var array of string-identifier of all registered strategies */
    private static $strategies = [];

    /**
     * Add a strategy to the strategymanager
     * @param string $strategyname
     */
    public static function add_strategy($strategyname) {
        self::$strategies[] = $strategyname;
    }

    /**
     * Get the current list of strategies
     * @return array
     */
    public static function get_strategies() {
        return self::$strategies;
    }
}
