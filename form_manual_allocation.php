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
 * Prints a particular instance of ratingallocate
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/course/moodleform_mod.php');
defined('MOODLE_INTERNAL') || die();

/**
 * Provides a form for manual allocations
 */
class manual_alloc_form extends moodleform {

    /** @var $ratingallocate ratingallocate */
    private $ratingallocate;

    /**
     * Constructor
     * @param type $url
     * @param ratingallocate $ratingallocate
     */
    public function __construct($url, ratingallocate $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        parent::__construct($url);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $COURSE, $PAGE, $DB, $USER;

        $mform = $this->_form;

        $ratingdata = $this->ratingallocate->get_ratings_for_rateable_choices_for_raters_without_alloc();
        $choices = $this->ratingallocate->get_choices_with_allocationcount();

        $userdata = array();
        foreach ($ratingdata as $rating) {
            if (!array_key_exists($rating->userid, $userdata)) {
                $userdata[$rating->userid] = array();
            }
            $userdata[$rating->userid][$rating->choiceid] = $rating->rating;
        }

        $renderer = $PAGE->get_renderer('mod_ratingallocate');

        $mform->addElement('hidden', 'action', ACTION_ALLOCATE_MANUAL_SAVE);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        $usersincourse = $this->ratingallocate->get_raters_in_course();
        foreach ($userdata as $userid => $userdat) {
            $headerelem = 'head_ratingallocate_u' . $userid;
            $elemprefix = 'data[' . $userid . ']';
            $ratingelem = $elemprefix . '[assign]';

            // title anzeigen

            $mform->addElement('header', $headerelem, fullname($usersincourse[$userid]));
            $mform->setExpanded($headerelem);

            $radioarray = array();
            foreach ($userdat as $choiceid => $rat) {

                $optionname = $choices [$choiceid]->title . ' [' . get_string('rated', 'ratingallocate') . ' ' . $rat . "] (" .
                        ($choices [$choiceid]->usercount > 0 ? $choices [$choiceid]->usercount : "0") . "/" . $choices [$choiceid]->maxsize . ")";
                if ($rat > 0) {
                    $radioarray [] = & $mform->createElement('radio', $ratingelem, '', $optionname, $choiceid, '');
                }
            }

            // wichtig, einen Gruppennamen zu setzen, damit später die Errors an der korrekten Stelle angezeigt werden können.
            $mform->addGroup($radioarray, 'radioarr_' . $userid, get_string('assign_to', 'ratingallocate'), null, false);
        }

        if (!count($ratingdata) > 0) {
            $mform->addElement('header', 'notification', get_string('no_user_to_allocate', 'ratingallocate'));
        } else {
            $this->add_action_buttons();
        }
    }

    /**
     * Returns the forms HTML code.
     * So we don't have to call display().
     */
    public function to_html() {
        return $this->_form->toHtml();
    }

}
