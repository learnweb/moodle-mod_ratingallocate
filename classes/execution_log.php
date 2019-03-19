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
 * Class for loading/storing execution_log messages from the DB.
 *
 * @package    mod_ratingallocate
 * @copyright  2019 WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate;

defined('MOODLE_INTERNAL') || die();

use core\persistent;
use lang_string;

/**
 * Class for loading/storing execution_log messages from the DB.
 *
 * @package    mod_ratingallocate
 * @copyright  2019 WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class execution_log extends persistent {

    const TABLE = 'ratingallocate_execution_log';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
                'ratingallocateid' => array(
                        'type' => PARAM_INT,
                        'message' => new lang_string('error_persistent_ratingallocateid', 'mod_ratingallocate'),
                ),
                'algorithm' => array(
                        'type' => PARAM_ALPHANUM,
                        'message' => new lang_string('error_persistent_algoname', 'mod_ratingallocate'),
                ),
                'message' => array(
                        'type' => PARAM_TEXT,
                        'message' => new lang_string('error_persistent_message', 'mod_ratingallocate'),
                )
        );
    }
}
