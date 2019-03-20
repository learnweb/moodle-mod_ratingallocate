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

namespace mod_ratingallocate;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

class algorithm_testable extends algorithm {

    /**
     * Inserts a message to the execution_log
     * @param string $message
     */
    public function append_to_log(string $message) {
        parent::append_to_log($message);
    }

    /**
     * Get name of the subplugin, without the raalgo_ prefix.
     *
     * @return string
     */
    public function get_subplugin_name() {
        return 'algorithmtestable';
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_name() {
        return 'Algorithm Testable';
    }

    protected function compute_distribution($choicerecords, $ratings, $usercount) {
        return null;
    }

    /**
     * Expected return value is an array with min and opt as key and true or false as supported or not supported.
     *
     * @return bool[]
     */
    public static function get_supported_features() {
        return array(
                'min' => false,
                'opt' => false
        );
    }
}