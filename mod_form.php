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
 * The main ratingallocate configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package mod_ratingallocate
 * @copyright 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');

/**
 * Module instance settings form
 */
class mod_ratingallocate_mod_form extends moodleform_mod {
    const MOD_NAME = 'ratingallocate';
    const CHOICE_PLACEHOLDER_IDENTIFIER = 'placeholder_for_choices';
    const NEW_CHOICE_COUNTER = 'new_choice_counter';
    const ADD_CHOICE_ACTION = 'add_new_choice';
    const DELETE_CHOICE_ACTION = 'delete_choice_';
    const DELETED_CHOICE_IDS = 'deleted_choice_ids';
    private $new_choice_counter = 0;

    /**
     * constructor
     * @see moodleform_mod::moodleform_mod
     */
    function mod_ratingallocate_mod_form($current, $section, $cm, $course) {
        // pre parse mod data if exists (in case not new)
        if($current && property_exists($current, 'setting')) {
            $strategyoptions = json_decode($current->setting, true);
            $current->strategyopt = $strategyoptions;
        }
        parent::moodleform_mod($current, $section, $cm, $course);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        /* @var $DB moodle_database */
        global $DB, $COURSE;

        $mform = $this->_form;

        // -------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('ratingallocatename', self::MOD_NAME), array(
            'size' => '64'
        ));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'ratingallocatename', self::MOD_NAME);

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        // -------------------------------------------------------------------------------
        $elementname = 'strategy';
        // define options for select
        $select_options = array();
        foreach (\strategymanager::get_strategies() as $strategy) {
            $select_options[$strategy] = get_string($strategy . '_name', self::MOD_NAME);
        }
        $mform->addElement('select', $elementname, get_string('select_strategy', self::MOD_NAME), $select_options);
        $mform->addHelpButton($elementname, 'select_strategy', self::MOD_NAME);
        $mform->addRule('strategy', null, 'required', null, 'client');

        // start/end time
        $elementname = 'accesstimestart';
        $mform->addElement('date_time_selector', $elementname, get_string('rating_begintime', self::MOD_NAME));
        $mform->setDefault($elementname, time());
        $elementname = 'accesstimestop';
        $mform->addElement('date_time_selector', $elementname, get_string('rating_endtime', self::MOD_NAME));
        $mform->setDefault($elementname, time() + 7 * 24 * 60 * 60); // default: now + one week

        $elementname = 'publishdate';
        $mform->addElement('date_time_selector', $elementname, get_string($elementname, self::MOD_NAME),$options = array('optional' => true));
        $mform->setDefault($elementname, time() + 9 * 24 * 60 * 60);

        $mform->addElement('static', self::CHOICE_PLACEHOLDER_IDENTIFIER, '', '');

        $mform->addElement('hidden', self::NEW_CHOICE_COUNTER, $this->new_choice_counter);
        $mform->setType(self::NEW_CHOICE_COUNTER, PARAM_INT);

        // saves the choices about to be deleted
        $mform->addElement('hidden', self::DELETED_CHOICE_IDS);
        $mform->setType(self::DELETED_CHOICE_IDS, PARAM_SEQUENCE);

        $elementname = self::ADD_CHOICE_ACTION;
        $mform->registerNoSubmitButton($elementname);
        $mform->addElement('submit', $elementname, get_string('newchoice', self::MOD_NAME));
        $mform->closeHeaderBefore($elementname);

        // create strategy fields for each strategy
        $attributes = array('size' => '20');
        
        foreach (\strategymanager::get_strategies() as $strategy) {
            // load strategy class
            $strategyclassp = 'ratingallocate\\' . $strategy . '\\strategy';
            /* @var $strategyclass \strategytemplate */
            $strategyclass = new $strategyclassp();

            $headerid = 'strategy_' . $strategy . '_fieldset';
            $mform->addElement('header', $headerid, get_string('strategyoptions_for_strategy', self::MOD_NAME, $strategyclass::get_strategyname()));
            $mform->disabledIf($headerid, 'strategy', 'neq', $strategy);

            // Add options fields
            foreach($strategyclass::get_static_settingfields() as $key => $value) {
                // currently only text supported
                if ($value[0] == "text") {
                    $curstratid = 'strategyopt[' . $strategy . '][' . $key . ']';
                    $mform->addElement('text', $curstratid, $value[1], $attributes);
                    $mform->setType($curstratid, PARAM_TEXT);
                    if (isset($strategyoptions) && key_exists($strategy, $strategyoptions)) {
                        $mform->setDefault($curstratid, $strategyoptions[$strategy][$key]);
                    }
                    $mform->disabledIf($curstratid, 'strategy', 'neq', $strategy);
                }
            }
        }
        // -------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        // -------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    /**
     * method to add choice options to the form
     *
     * @param MoodleQuickForm $mform form
     * @param unknown $choice choice (new (negative id) or existing)
     */
    private function addChoiceGroup(MoodleQuickForm $mform, $choice) {
        $elemprefix = 'choices[' . $choice->id . ']';
        $mform->addElement('hidden', $elemprefix . '[id]', $choice->id); // Save the record's id
        $mform->setType($elemprefix . '[id]', PARAM_INT);

        $elementname = 'fieldset_edit_choice' . $choice->id;
        $mform->addElement('header', $elementname, get_string('edit_choice', self::MOD_NAME, $choice->title));
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);

        $elementname = $elemprefix . '[title]';
        $mform->addElement('text', $elementname, get_string('choice_title', self::MOD_NAME));
        $mform->setDefault($elementname, $choice->title);
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addHelpButton($elementname, 'choice_title', self::MOD_NAME);
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
        $mform->addRule($elementname, null, 'required', null, 'server');

        $elementname = $elemprefix . '[explanation]';
        $mform->addElement('text', $elementname, get_string('choice_explanation', self::MOD_NAME));
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
        $mform->setDefault($elementname, $choice->explanation);
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addRule($elementname, null, 'required', null, 'server');

        $elementname = $elemprefix . '[maxsize]';
        $mform->addElement('text', $elementname, get_string('choice_maxsize', self::MOD_NAME));
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
        $mform->setDefault($elementname, $choice->maxsize);
        $mform->setType($elementname, PARAM_INT);
        $mform->addRule($elementname, null, 'required', null, 'server');

        $elementname = $elemprefix . '[active]';
        $mform->addElement('checkbox', $elementname, get_string('choice_active', self::MOD_NAME));
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
        $mform->setDefault($elementname, $choice->active);
        $mform->addHelpButton($elementname, 'choice_active', self::MOD_NAME);

        $elementname = self::DELETE_CHOICE_ACTION. $choice->id;
        $mform->registerNoSubmitButton($elementname);
        $mform->addElement('submit', $elementname  , get_string('deletechoice', self::MOD_NAME));
        $mform->insertElementBefore( $mform->removeElement($elementname , false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
    }

    /**
     * Creates a new unsaved choice (id is negative)
     * @param integer $i
     * @return StdClass representing new choice
     */
    private function createEmptyChoice($i) {
        $id = -($i);
        return (object) array(
                'id' => $id,
                'title' => 'New Choice '.$i,
                'explanation' => '',
                'maxsize' => 20,
                'active' => true
        );
    }

    // override if you need to setup the form depending on current values
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = & $this->_form;

        $data = $this->current;

        $choices = array();

        if (!empty($data->id)) {
            global $DB;
            // $this->get_submitted_data() does only return values for fields that are already
            // defined->load ids from db -> create fields(values are overwritten by submitted data)
            $choices = $DB->get_records('ratingallocate_choices', array(
                    'ratingallocateid' => $data->id
            ), 'title ASC');
        } else {
            $this->new_choice_counter = 2;
        }
        // If there is already submitted data?
        if($this->is_submitted()) {
            // load new_choice_counter
            if(property_exists($this->get_submitted_data(), self::NEW_CHOICE_COUNTER)) {
                $this->new_choice_counter = $this->get_submitted_data()->{self::NEW_CHOICE_COUNTER};
            }
            // increment new choice counter if add_new_choice button was pressed
            if(property_exists($this->get_submitted_data(), self::ADD_CHOICE_ACTION)) {
                $this->new_choice_counter++;
            }
        }
        
        for($i = 0; $i < $this->new_choice_counter; $i++) {
            $choices[] = $this->createEmptyChoice($i+1);
        }

        // generate array with ids of all choices
        $choice_ids = array_map(function($elem) {return (Integer) $elem->id;}, $choices);
        
        // initialize variable
        $delete_choice_array = array();
        
        // If delete choice button was pressed
        if ($this->is_submitted()) {
            // retrieve ids of choices to be deleted from the static field
            if (property_exists($this->get_submitted_data(), self::DELETED_CHOICE_IDS)) {
                $deleted_choice_ids = $this->get_submitted_data()->{self::DELETED_CHOICE_IDS};
            }
            // if the string is not empty the array of choice ids is exploded from it
            if (!empty($deleted_choice_ids))
                $delete_choice_array = explode(',', $deleted_choice_ids);
                
            // retrieve id of choice to be deleted if delete button was pressed
            $matches = preg_grep('/' . self::DELETE_CHOICE_ACTION . '([-]?[0-9]+)/', 
                    array_keys($mform->getSubmitValues()));
            // only proceed if exaclty one delete button was found in the submitted data
            if (count($matches) == 1) {
            	// retrieve the id as an Integer from the button name
            	$elem = array_pop($matches);
                $parts = explode('_', $elem);
                $delete_choice_id = (integer) array_pop($parts);
                
                // if the id matches one of the choices add it to the choices to be deleted
                if (in_array($delete_choice_id, $choice_ids)) {
                    $delete_choice_array[] = $delete_choice_id;
                }
            }
        }
        
        // clean array to only contain feasible ids
        $delete_choice_array = array_intersect($delete_choice_array,$choice_ids);
            
            // create fields for all choices
        foreach ($choices as $id => $choice) {
            
            if (!in_array($choice->id, $delete_choice_array)) {
                $this->addChoiceGroup($mform, $choice);
            } else {
            	// the nosubmit button has to be added since the form uses it for no_submit_button_pressed()
                $mform->registerNoSubmitButton(self::DELETE_CHOICE_ACTION . $choice->id);
            }
        }

        // update delete_choice_string
        if (!empty($delete_choice_array)) {
            $deleted_choice_ids = implode(',', $delete_choice_array);
            $mform->getElement(self::DELETED_CHOICE_IDS)->setValue($deleted_choice_ids);
            $myvar=$mform->getElement(self::DELETED_CHOICE_IDS)->getValue();
        }
        
        // update new_choice_counter
        $mform->getElement(self::NEW_CHOICE_COUNTER)->setValue($this->new_choice_counter);

        //make strategy fields for selected strategy required (server-side validation)
        $strategy = $mform->getElementValue('strategy');
        if (!empty($strategy)) { // Make sure the strategy's options are now mandatory
            $strategy = array_shift($strategy);
            $strategyclassp = 'ratingallocate\\' . $strategy . '\\strategy';
            /* @var $strategyclass \strategytemplate */
            $strategyclass = new $strategyclassp();
            foreach(array_keys($strategyclass::get_static_settingfields()) as $key) {
                $mform->addRule('strategyopt[' . $strategy . '][' . $key . ']', null, 'required', null, 'server');
            }
        }
    }

    /**
     * Checks that accesstimestart is before accesstimestop
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['accesstimestop'] <= $data['accesstimestart']) {
            $errors['accesstimestart'] = get_string('invalid_dates', self::MOD_NAME);
        }

        if ($data['publishdate'] && $data['publishdate'] <= $data['accesstimestart']) {
            $errors['publishdate'] = get_string('invalid_publishdate', self::MOD_NAME);
        }

        // User has to select one strategy
        if (empty($data['strategy'])) {
            $errors['strategy'] = get_string('strategy_not_specified', self::MOD_NAME);
        }

        return $errors;
    }
}
