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

namespace mod_ratingallocate\courseformat;

use core\activity_dates;
use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\output\pix_icon;
use core\url;
use core_calendar\output\humandate;
use core_courseformat\local\overview\overviewitem;
use mod_ratingallocate\manager;

/**
 * Fair Allocation overview integration (for Moodle 5.1+)
 *
 * @package   mod_ratingallocate
 * @copyright 2025 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * @var manager the ratingallocate manager.
     */
    private manager $manager;

    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        /** @var \core\output\renderer_helper $rendererhelper the renderer helper */
        protected readonly \core\output\renderer_helper $rendererhelper,
    ) {
        parent::__construct($cm);
        $this->manager = manager::create_from_coursemodule($cm);
    }

    /**
     * Get the rating begins at date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_rating_accesstimestart_overview(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);
        $opendate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeopen') {
                $opendate = $date['timestamp'];
                break;
            }
        }
        if (empty($opendate)) {
            return new overviewitem(
                name: get_string('rating_begintime', 'ratingallocate'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($opendate);

        return new overviewitem(
            name: get_string('rating_begintime', 'ratingallocate'),
            value: $opendate,
            content: $content,
        );
    }

    /**
     * Get the rating ends at date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_rating_accesstimestop_overview(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);
        $closedate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeclose') {
                $closedate = $date['timestamp'];
                break;
            }
        }
        if (empty($closedate)) {
            return new overviewitem(
                name: get_string('rating_endtime', 'ratingallocate'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($closedate);

        return new overviewitem(
            name: get_string('rating_endtime', 'ratingallocate'),
            value: $closedate,
            content: $content,
        );
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'rating_begintime' => $this->get_extra_rating_accesstimestart_overview(),
            'rating_endtime' => $this->get_extra_rating_accesstimestop_overview(),
            'studentwhoresponded' => $this->get_extra_responses_overview(),
            'responded' => $this->get_extra_status_for_user(),
        ];
    }

    /**
     * Get the response status overview item.
     *
     * @return overviewitem|null An overview item or null for teachers.
     */
    private function get_extra_status_for_user(): ?overviewitem {
        if (has_capability('mod/ratingallocate:start_distribution', $this->cm->context)) {
            return null;
        }

        $status = $this->manager->has_answered();
        $statustext = get_string('notanswered', 'ratingallocate');
        if ($status) {
            $statustext = get_string('answered', 'ratingallocate');
        }
        $submittedstatuscontent = "-";
        if ($status) {
            $submittedstatuscontent = new pix_icon(
                pix: 'i/checkedcircle',
                alt: $statustext,
                component: 'core',
                attributes: ['class' => 'text-success'],
            );
        }
        return new overviewitem(
            name: get_string('answeredbystudent', 'ratingallocate'),
            value: $status,
            content: $submittedstatuscontent,
            textalign: text_align::CENTER,
        );
    }

    /**
     * Retrieves an overview of responses for the ratingallocate.
     *
     * @return overviewitem|null An overview item c, or null if the user lacks the required capability.
     */
    private function get_extra_responses_overview(): ?overviewitem {
        global $USER;

        if (!has_capability('mod/ratingallocate:start_distribution', $this->manager->get_coursemodule()->context)) {
            return null;
        }

        if (is_callable([$this, 'get_groups_for_filtering'])) {
            $groupids = array_keys($this->get_groups_for_filtering());
        } else {
            $groupids = [];
        }

        $submissions = $this->manager->count_all_users_answered($groupids);
        $total = $this->manager->count_all_users($groupids);

        $content = new action_link(
            url: new url('/mod/ratingallocate/view.php', ['id' => $this->cm->id, 'action' => 'show_ratings_and_allocation_table']),
            text: get_string(
                'count_of_total',
                'core',
                ['count' => $submissions, 'total' => $total]
            ),
            attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
        );

        return new overviewitem(
            name: get_string('ratings', 'ratingallocate'),
            value: $submissions,
            content: $content,
            textalign: text_align::CENTER,
        );
    }
}
