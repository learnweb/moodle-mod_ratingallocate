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
 * @copyright  2014 T Reischmann, C Usener
 * @copyright  based on code by M Schulze copyright (C) 2014 M Schulze
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
    const STRATEGY_OPTIONS = 'strategyopt';
    const STRATEGY_OPTIONS_PLACEHOLDER = 'placeholder_strategyopt';
    private $newchoicecounter = 0;
    private $msgerrorrequired;
    private $choicecheckboxes = array();

    /**
     * constructor
     * @see moodleform_mod::moodleform_mod
     */
    public function __construct($current, $section, $cm, $course) {
        // Pre parse mod data if exists (in case not new).
        if ($current && property_exists($current, 'setting')) {
            $strategyoptions = json_decode($current->setting, true);
            foreach ($strategyoptions as $stratkey => $strategy) {
                foreach ($strategy as $key => $option) {
                    $current->{$this->get_settingsfield_identifier($stratkey, $key)} = $option;
                }
            }
        }
        parent::__construct($current, $section, $cm, $course);
        $this->msgerrorrequired = get_string('err_required', 'form');
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
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

        // Adding the standard "intro" and "introformat" fields.
        $this->add_intro_editor();

        // -------------------------------------------------------------------------------
        $elementname = 'strategy';
        // Define options for select.
        $selectoptions = array();
        foreach (\strategymanager::get_strategies() as $strategy) {
            $selectoptions[$strategy] = get_string($strategy . '_name', self::MOD_NAME);
        }
        $mform->addElement('select', $elementname, get_string('select_strategy', self::MOD_NAME), $selectoptions);
        $mform->addHelpButton($elementname, 'select_strategy', self::MOD_NAME);
        $mform->addRule('strategy', null, 'required', null, 'client');

        // Start/end time.
        $elementname = 'accesstimestart';
        $mform->addElement('date_time_selector', $elementname, get_string('rating_begintime', self::MOD_NAME));
        $mform->setDefault($elementname, time());
        $elementname = 'accesstimestop';
        $mform->addElement('date_time_selector', $elementname, get_string('rating_endtime', self::MOD_NAME));
        $mform->setDefault($elementname, time() + 7 * 24 * 60 * 60); // Default: now + one week.

        $elementname = 'publishdate';
        $mform->addElement('date_time_selector', $elementname, get_string($elementname, self::MOD_NAME),
                $options = array('optional' => true));
        $mform->setDefault($elementname, time() + 9 * 24 * 60 * 60);

        $elementname = 'runalgorithmbycron';
        $mform->addElement('advcheckbox', $elementname, get_string($elementname, self::MOD_NAME), null, null, array(0, 1));
        $mform->addHelpButton($elementname, $elementname, self::MOD_NAME);
        $mform->setDefault($elementname, 1);

        $mform->addElement('static', self::CHOICE_PLACEHOLDER_IDENTIFIER, '', '');

        $mform->addElement('hidden', self::NEW_CHOICE_COUNTER, $this->newchoicecounter);
        $mform->setType(self::NEW_CHOICE_COUNTER, PARAM_INT);

        // Saves the choices about to be deleted.
        $mform->addElement('hidden', self::DELETED_CHOICE_IDS);
        // PARAM_SEQUENCE does not allow negative numbers.
        // Comma separation and integer values are enforced in definition after data.
        $mform->setType(self::DELETED_CHOICE_IDS, PARAM_TEXT);

        $elementname = self::ADD_CHOICE_ACTION;
        $mform->registerNoSubmitButton($elementname);
        $mform->addElement('submit', $elementname, get_string('newchoice', self::MOD_NAME));
        $mform->closeHeaderBefore($elementname);

        foreach (\strategymanager::get_strategies() as $strategy) {
            // Load strategy class.
            $strategyclassp = 'ratingallocate\\' . $strategy . '\\strategy';
            /* @var $strategyclass \strategytemplate */
            $strategyclass = new $strategyclassp();

            $headerid = 'strategy_' . $strategy . '_fieldset';
            $mform->addElement('header', $headerid, get_string('strategyoptions_for_strategy', self::MOD_NAME,
                    $strategyclass->get_strategyname()));
            $mform->disabledIf($headerid, 'strategy', 'neq', $strategy);

            // Add options fields.
            foreach ($strategyclass->get_static_settingfields() as $key => $value) {
                $fieldid = $this->get_settingsfield_identifier($strategy, $key);
                $this->add_settings_field($fieldid, $value, $strategy, $mform);
            }
            $mform->addElement('static', self::STRATEGY_OPTIONS_PLACEHOLDER.'[' . $strategy . ']', '', '');
        }

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Add an settings element to the form. It is enabled only if the strategy it belongs to is selected.
     * @param string $stratfieldid id of the element to be added
     * @param array $value array with the element type and its caption 
     *        (usually returned by the strategys get settingsfields methods).
     * @param string $curr_strategyid id of the strategy it belongs to
     * @param string $default default value for the element
     */
    private function add_settings_field($stratfieldid, array $value, $strategyid, MoodleQuickForm $mform, $default = null) {

        $attributes = array('size' => '20');

        if (!isset($value[2])) {
            $attributes['placeholder'] = ($value[1]);
        }

        if ($value[0] == "text") {
            $mform->addElement('text', $stratfieldid, $value[1], $attributes);
            $mform->setType($stratfieldid, PARAM_TEXT);
        } else if ($value[0] == "int") {
            $mform->addElement('text', $stratfieldid, $value[1], $attributes);
            $mform->setType($stratfieldid, PARAM_TEXT);
            $mform->addRule($stratfieldid, null, 'numeric'); // TODO: Only validate if not disabled.
        }
        if (isset($value[2])) {
            $mform->setDefault($stratfieldid, $value[2]);
        }
        $mform->disabledIf($stratfieldid, 'strategy', 'neq', $strategyid);
    }

    /**
     * method to add choice options to the form
     *
     * @param MoodleQuickForm $mform form
     * @param unknown $choice choice (new (negative id) or existing)
     */
    private function add_choice_group(MoodleQuickForm $mform, $choice) {
        $elemprefix = 'choices_' . $choice->id . '_';
        $mform->addElement('hidden', $elemprefix . 'id', $choice->id); // Save the record's id.
        $mform->setType($elemprefix . 'id', PARAM_INT);

        $elementname = 'fieldset_edit_choice' . $choice->id;
        $mform->addElement('header', $elementname, get_string('edit_choice', self::MOD_NAME, $choice->title));
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);

        $elementname = $elemprefix . 'title';
        $mform->addElement('text', $elementname, get_string('choice_title', self::MOD_NAME));
        $mform->setDefault($elementname, $choice->title);
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addHelpButton($elementname, 'choice_title', self::MOD_NAME);
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
        $mform->addRule($elementname, $this->msgerrorrequired , 'required', null, 'server');

        $elementname = $elemprefix . 'explanation';
        $mform->addElement('text', $elementname, get_string('choice_explanation', self::MOD_NAME));
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
        $mform->setDefault($elementname, $choice->explanation);
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addRule($elementname,  $this->msgerrorrequired , 'required', null, 'server');

        $elementname = $elemprefix . 'maxsize';
        $mform->addElement('text', $elementname, get_string('choice_maxsize', self::MOD_NAME));
        $mform->insertElementBefore($mform->removeElement($elementname, false), self::CHOICE_PLACEHOLDER_IDENTIFIER);
        $mform->setDefault($elementname, $choice->maxsize);
        $mform->setType($elementname, PARAM_INT);
        $mform->addRule($elementname, $this->msgerrorrequired , 'required', null, 'server');

        $elementname = $elemprefix . 'active';
        $checkbox = $mform->addElement('advcheckbox', $elementname, get_string('choice_active', self::MOD_NAME), null, null, array(0, 1));
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
    private function create_empty_choice($i) {
        $id = -($i);
        return (object) array(
                'id' => $id,
                'title' => get_string('newchoicetitle', ratingallocate_MOD_NAME, $i),
                'explanation' => '',
                'maxsize' => 20,
                'active' => 1
        );
    }

    // Override if you need to setup the form depending on current values.
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = & $this->_form;

        $data = $this->current;

        $choices = array();

        if (!empty($data->id)) {
            global $DB;
            /* The method $this->get_submitted_data() does only return values for fields that are already
               defined->load ids from db -> create fields(values are overwritten by submitted data). */
            $choices = $DB->get_records('ratingallocate_choices', array(
                    'ratingallocateid' => $data->id
            ), 'title ASC');
        } else {
            $this->newchoicecounter = 2;
        }
        // If there is already submitted data?
        if ($this->is_submitted()) {
            // Load new_choice_counter.
            if (property_exists($this->get_submitted_data(), self::NEW_CHOICE_COUNTER)) {
                $this->newchoicecounter = $this->get_submitted_data()->{self::NEW_CHOICE_COUNTER};
            }
            // Increment new choice counter if add_new_choice button was pressed.
            if (property_exists($this->get_submitted_data(), self::ADD_CHOICE_ACTION)) {
                $this->newchoicecounter++;
            }
        }

        for ($i = 0; $i < $this->newchoicecounter; $i++) {
            $choices[] = $this->create_empty_choice($i + 1);
        }

        // Generate array with ids of all choices.
        $choiceids = array_map(function($elem) {
            return (Integer) $elem->id;
        }, $choices);

        // Initialize variable.
        $deletechoicearray = array();

        // If delete choice button was pressed.
        if ($this->is_submitted()) {
            // Retrieve ids of choices to be deleted from the static field.
            if (property_exists($this->get_submitted_data(), self::DELETED_CHOICE_IDS)) {
                $deletedchoiceids = $this->get_submitted_data()->{self::DELETED_CHOICE_IDS};
            }
            // If the string is not empty the array of choice ids is exploded from it.
            if (!empty($deletedchoiceids)) {
                $deletechoicearray = explode(',', $deletedchoiceids);
            }

            // Parses all delete choice ids to integers.
            $integercheck = function($elem) {
                return (integer)$elem;
            };
            array_map($integercheck, $deletechoicearray);

            // Retrieve id of choice to be deleted if delete button was pressed.
            $matches = preg_grep('/' . self::DELETE_CHOICE_ACTION . '([-]?[0-9]+)/',
                    array_keys($mform->getSubmitValues()));
            // Only proceed if exaclty one delete button was found in the submitted data.
            if (count($matches) == 1) {
                // Retrieve the id as an Integer from the button name.
                $elem = array_pop($matches);
                $parts = explode('_', $elem);
                $deletechoiceid = (integer) array_pop($parts);

                // If the id matches one of the choices add it to the choices to be deleted.
                if (in_array($deletechoiceid, $choiceids)) {
                    $deletechoicearray[] = $deletechoiceid;
                }
            }
        }

        // Clean array to only contain feasible ids.
        $deletechoicearray = array_intersect($deletechoicearray, $choiceids);

        // Create fields for all choices.
        foreach ($choices as $id => $choice) {

            if (!in_array($choice->id, $deletechoicearray)) {
                $this->add_choice_group($mform, $choice);
            } else {
                // The nosubmit button has to be added since the form uses it for no_submit_button_pressed().
                $mform->registerNoSubmitButton(self::DELETE_CHOICE_ACTION . $choice->id);
            }
        }

        if ($this->is_submitted()) {
            $subdata = $this->get_submitted_data();
            $allstrategyoptions = $subdata->{self::STRATEGY_OPTIONS};
        } else if (isset($data->setting)) {
            $allstrategyoptions = json_decode($data->setting, true);
        }
        // Add dynamic settings fields.
        foreach (\strategymanager::get_strategies() as $strategy) {
            // Load strategy class.
            $strategyclassp = 'ratingallocate\\' . $strategy . '\\strategy';
            /* @var $strategyclass \strategytemplate */
            if (isset($allstrategyoptions) && array_key_exists($strategy, $allstrategyoptions)) {
                $strategyclass = new $strategyclassp($allstrategyoptions[$strategy]);
            } else {
                $strategyclass = new $strategyclassp();
            }
            $strategyplaceholder = self::STRATEGY_OPTIONS_PLACEHOLDER . '[' . $strategy . ']';
            // Add options fields.
            $dynamicsettingsfields = $strategyclass->get_dynamic_settingfields();
            foreach ($dynamicsettingsfields as $key => $value) {
                $fieldid = $this->get_settingsfield_identifier($strategy, $key);
                $this->add_settings_field($fieldid, $value, $strategy, $mform);
                $mform->insertElementBefore($mform->removeElement($fieldid, false),
                    $strategyplaceholder);
            }
            // If any dynamic field is present, add a no submit button to refresh the page.
            if (count($dynamicsettingsfields) > 0) {
                $buttonname = self::STRATEGY_OPTIONS.$strategy.'refresh';
                $mform->registerNoSubmitButton($buttonname);
                $mform->addElement('submit', $buttonname, get_string('refresh'));
                $mform->insertElementBefore($mform->removeElement($buttonname, false),
                    $strategyplaceholder);
            }
            $mform->removeElement($strategyplaceholder);
        }

        // UPDATE OF FORM VALUES NEEDS TO BE EXECUTED IN THE END!!!
        // Update new_choice_counter.
        $mform->getElement(self::NEW_CHOICE_COUNTER)->setValue($this->newchoicecounter);

        // Update delete_choice_string.
        if (!empty($deletechoicearray)) {
            $deletedchoiceids = implode(',', $deletechoicearray);
            $mform->getElement(self::DELETED_CHOICE_IDS)->setValue($deletedchoiceids);
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

        // User has to select one strategy.
        if (empty($data['strategy'])) {
            $errors['strategy'] = get_string('strategy_not_specified', self::MOD_NAME);
        } else {
            $strategyclassp = 'ratingallocate\\' . $data['strategy'] . '\\strategy';
            if (array_key_exists($data['strategy'], $data['strategyopt'])) {
                $strategyclass = new $strategyclassp($data['strategyopt'][$data['strategy']]);
                $settingerrors = $strategyclass->validate_settings();
                foreach ($settingerrors as $id => $error) {
                    $errors[$this->get_settingsfield_identifier($data['strategy'], $id)] = $error;
                }
            }
        }
        return $errors;
    }
    /**
     * Returns a valid identifier for a settings field
     * @param $strategy identifier of the strategy
     * @param $key identifier of the key
     * @return string
     */
    private function get_settingsfield_identifier($strategy, $key) {
        return self::STRATEGY_OPTIONS . '[' . $strategy . '][' . $key . ']';
    }

}
