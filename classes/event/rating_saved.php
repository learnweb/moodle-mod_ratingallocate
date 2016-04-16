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
 * The mod_ratingallocate rating_saved event.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 Tobias Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The mod_ratingallocate rating_saved event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - array rating: new values of rating
 * }
 *
 * @since     Moodle 2.7
 * @copyright 2014 Tobias Reischmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class rating_saved extends \core\event\base {
    
    public static function create_simple($coursecontext, $ratingallocateid, $rating){
        // the values of other need to be encoded since the base checks for equality of a decoded encoded other instance with the original.
        // this is not given for nested arrays
        $rating_json_valid = json_decode(json_encode($rating),true);
        return self::create(array('context' => $coursecontext, 'objectid' => $ratingallocateid, 'other' => array('ratings'=>$rating_json_valid)));
    }
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ratingallocate_ratings';
    }
 
    public static function get_name() {
        return get_string('log_rating_saved', 'mod_ratingallocate');
    }
 
    public function get_description() {
        return get_string('log_rating_saved_description', 'mod_ratingallocate', array('userid' => $this->userid, 'ratingallocateid' => $this->objectid));
    }
 
    public function get_url() {
        return new \moodle_url('/mod/ratingallocate/view.php', array('m' => $this->objectid));
    }

    public static function get_objectid_mapping() {
        return array('db' => 'ratingallocate', 'restore' => 'ratingallocate');
    }

    public static function get_other_mapping() {
        return false;
    }
}
