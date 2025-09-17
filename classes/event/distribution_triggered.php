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
 * The mod_ratingallocate distribution_triggered event.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 Tobias Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ratingallocate\event;

/**
 * The mod_ratingallocate distribution_triggered event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - array allocations: created allocations
 *      - double time_needed: time needed to finish the distribution
 * }
 *
 * @since     Moodle 2.7
 * @copyright 2014 Tobias Reischmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class distribution_triggered extends \core\event\base {
    /**
     * Create simple distribution_triggered event.
     *
     * @param \stdClass $modulecontext
     * @param int $ratingallocateid
     * @param float $timeneeded
     * @return \core\event\base
     * @throws \coding_exception
     */
    public static function create_simple($modulecontext, $ratingallocateid, $timeneeded) {
        $timeneededjsonvalid = json_decode(json_encode($timeneeded), true);
        return self::create(['context' => $modulecontext, 'objectid' => $ratingallocateid,
                'other' => ['time_needed' => $timeneededjsonvalid]]);
    }

    /**
     * Initialize data.
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'ratingallocate_ratings';
    }

    /**
     * Get event name.
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public static function get_name() {
        return get_string('log_distribution_triggered', 'mod_ratingallocate');
    }

    /**
     * Get event description.
     * @return \lang_string|string|null
     * @throws \coding_exception
     */
    public function get_description() {
        return get_string(
            'log_distribution_triggered_description',
            'mod_ratingallocate',
            ['userid' => $this->userid,
                        'ratingallocateid' => $this->objectid,
            'time_needed' => $this->other['time_needed']]
        );
    }

    /**
     * Get event url.
     * @return \moodle_url
     * @throws \moodle_exception
     */
    public function get_url() {
        return new \moodle_url('/mod/ratingallocate/view.php', ['m' => $this->objectid]);
    }

    /**
     * Get event mapping.
     * @return string[]
     */
    public static function get_objectid_mapping() {
        return ['db' => 'ratingallocate', 'restore' => 'ratingallocate'];
    }

    /**
     * No other mappings available.
     * @return false
     */
    public static function get_other_mapping() {
        return false;
    }
}
