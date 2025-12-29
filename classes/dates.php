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
 * Contains the class for fetching the important dates in mod_ratingallocate for a given module instance and a user.
 *
 * @package   mod_ratingallocate
 * @copyright 2022 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_ratingallocate;

use core\activity_dates;

/**
 * Class for fetching the important dates in mod_ratingallocate for a given module instance and a user.
 *
 * @copyright 2022 Jakob Mischke <jakob.mischke@univie.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dates extends activity_dates {
    /** @var int|null $timeopen the activity open date */
    private ?int $timeopen;

    /** @var int|null $timeclose the activity close date */
    private ?int $timeclose;

    /**
     * Returns a list of important dates in mod_ratingallocate
     *
     * @return array
     */
    protected function get_dates(): array {
        global $DB;

        $this->timeopen = null;
        $this->timeclose = null;

        $timeopen = $this->cm->customdata['accesstimestart'] ?? null;
        $timeclose = $this->cm->customdata['accesstimestop'] ?? null;

        $now = \core\di::get(\core\clock::class)->time();
        $dates = [];

        if ($timeopen) {
            $this->timeopen = (int) $timeopen;
            $openlabelid = $timeopen > $now ? 'activitydate:opens' : 'activitydate:opened';
            $dates[] = [
                'dataid' => 'timeopen',
                'label' => get_string($openlabelid, 'core_course'),
                'timestamp' => $this->timeopen,
            ];
        }

        if ($timeclose) {
            $this->timeclose = (int) $timeclose;
            $closelabelid = $timeclose > $now ? 'activitydate:closes' : 'activitydate:closed';
            $dates[] = [
                'dataid' => 'timeclose',
                'label' => get_string($closelabelid, 'core_course'),
                'timestamp' => $this->timeclose,
            ];
        }

        return $dates;
    }

    /**
     * Returns the open date data, if any.
     * @return int|null the open date timestamp or null if not set.
     */
    public function get_timeopen(): ?int {
        if (!isset($this->timeopen)) {
            $this->get_dates();
        }
        return $this->timeopen;
    }

    /**
     * Returns the close date data, if any.
     * @return int|null the close date timestamp or null if not set.
     */
    public function get_timeclose(): ?int {
        if (!isset($this->timeclose)) {
            $this->get_dates();
        }
        return $this->timeclose;
    }
}
