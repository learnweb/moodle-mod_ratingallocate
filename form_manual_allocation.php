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
    
    const FILTER_ALL = 'filter_all';
    const FILTER_ONLY_RATERS = 'filter_only_raters';
    const FILTER_STATE = 'filter_state';
    const FILTER_BUTTON = 'filter_button';
    const FORM_ACTION = 'action';
    private $filter_state = self::FILTER_ONLY_RATERS;
    

    /**
     * Constructor
     * @param type $url
     * @param ratingallocate $ratingallocate
     */
    public function __construct($url, ratingallocate $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        parent::__construct($url);
        $this->definition_after_data();
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $COURSE, $PAGE, $DB, $USER;
        
        $mform = $this->_form;
        
        $show_all=false;
        
        //Button to filter the users, which are desplayed
        $mform->registerNoSubmitButton(self::FILTER_BUTTON);
        $mform->addElement('submit',self::FILTER_BUTTON, '');
        $mform->registerNoSubmitButton(self::FILTER_BUTTON);

        $mform->addElement('hidden', self::FORM_ACTION, ACTION_ALLOCATE_PROCESS_MANUALFORM);
        $mform->setType(self::FORM_ACTION, PARAM_TEXT);
        
        //saves the current filter
        $mform->addElement('hidden', self::FILTER_STATE);
        $mform->setType(self::FILTER_STATE, PARAM_TEXT);
        
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        echo '<pre>';print_r($this->get_submitted_data());echo '</pre>';

    }
    
    public function definition_after_data(){
        parent::definition_after_data();
        $mform = & $this->_form;

        if ($this->is_submitted()) {
            if (property_exists($this->get_submitted_data(), self::FILTER_STATE)) {
                $this->filter_state = $this->get_submitted_data()->{self::FILTER_STATE};
            }
            // Only switch the filter state, if the filter button was pressed
            if (property_exists($this->get_submitted_data(), self::FILTER_BUTTON)) {
                switch ($this->filter_state) {
                    case self::FILTER_ALL:
                        $this->filter_state = self::FILTER_ONLY_RATERS;
                        break;
                    case self::FILTER_ONLY_RATERS:
                        $this->filter_state = self::FILTER_ALL;
                        break;
                }
            }
        }

        // rename filter button manual_allocation_filter_only_raters
        $filter_button_text = 'Filter';
        switch ($this->filter_state) {
            case self::FILTER_ALL:
                $filter_button_text = get_string('manual_allocation_filter_only_raters', ratingallocate_MOD_NAME);
                break;
            case self::FILTER_ONLY_RATERS:
                $filter_button_text = get_string('manual_allocation_filter_all', ratingallocate_MOD_NAME);
                break;
        }
        $mform->getElement(self::FILTER_BUTTON)->setValue($filter_button_text);
        
        
        $ratingdata = array();
        switch ($this->filter_state) {
            case self::FILTER_ALL:
                $ratingdata = $this->ratingallocate->get_ratings_for_rateable_choices();
                break;
            case self::FILTER_ONLY_RATERS:
                $ratingdata = $this->ratingallocate->get_ratings_for_rateable_choices_for_raters_without_alloc();
                break;
        }
        $empty_preferences = array();
        foreach ($this->ratingallocate->get_rateable_choices() as $choiceid => $choice){
            $empty_preferences[$choiceid] = '?';
        }
        $userdata = array();
        If ($this->filter_state==self::FILTER_ALL) {
            // Create one entry for each user choice combination
                foreach ($this->ratingallocate->get_raters_in_course() as $userid => $users) {
                    $userdata[$userid] = $empty_preferences;
                }        
        }
        
        // Add actual rating data to userdata
        foreach ($ratingdata as $rating) {
            if (!array_key_exists($rating->userid, $userdata)) {
                $userdata[$rating->userid] = $empty_preferences;
            }
            $userdata[$rating->userid][$rating->choiceid] = $rating->rating;
        }
               
        $usersincourse = $this->ratingallocate->get_raters_in_course();
        $choicesWithAllocations = $this->ratingallocate->get_choices_with_allocationcount();
        foreach ($userdata as $userid => $userdat) {
            $headerelem = 'head_ratingallocate_u' . $userid;
            $elemprefix = 'data[' . $userid . ']';
            $ratingelem = $elemprefix . '[assign]';
        
            // title anzeigen
        
//             $mform->addElement('header', $headerelem, fullname($usersincourse[$userid]));
//             $mform->setExpanded($headerelem);
        
            $radioarray = array();
            foreach ($userdat as $choiceid => $rat) {
        
                $optionname = $choicesWithAllocations [$choiceid]->title . ' [' . get_string('rated', ratingallocate_MOD_NAME) . ' ' . $rat . "] (" .
                        ($choicesWithAllocations [$choiceid]->usercount > 0 ? $choicesWithAllocations [$choiceid]->usercount : "0") . "/" . $choicesWithAllocations [$choiceid]->maxsize . ")";
                $radioarray [] = & $mform->createElement('radio', $ratingelem, '', $optionname, $choiceid, '');
            }
            
            // Adding static elements to support css
            $radioarray = $this->ratingallocate->prepare_horizontal_radio_choice($radioarray, $mform);
            
            // wichtig, einen Gruppennamen zu setzen, damit später die Errors an der korrekten Stelle angezeigt werden können.
//             $mform->addGroup($radioarray, 'radioarr_' . $userid, get_string('assign_to', ratingallocate_MOD_NAME), null, false);
            $mform->addGroup($radioarray, 'radioarr_' . $userid, fullname($usersincourse[$userid]), null, false);
            $userallocations = $this->ratingallocate->get_allocations_for_user($userid);
            $allocation = array_pop($userallocations);
            if (isset($allocation)){
                $mform->setDefault($ratingelem, $allocation->choiceid);
            }
        }
        
        if (!count($ratingdata) > 0) {
            $mform->addElement('header', 'notification', get_string('no_user_to_allocate', ratingallocate_MOD_NAME));
        } else {
            $this->add_action_buttons();
        }
        
        $mform->getElement(self::FILTER_STATE)->setValue($this->filter_state);
    }

    /**
     * Returns the forms HTML code.
     * So we don't have to call display().
     */
    public function to_html() {
        $o = '';
        $o .= $this->_form->getValidationScript();
        $o .= $this->_form->toHtml();
        return $o;
    }

}
