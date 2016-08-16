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
    const STRATEGY_OPTIONS = 'strategyopt';
    const STRATEGY_OPTIONS_PLACEHOLDER = 'placeholder_strategyopt';
    private $newchoicecounter = 0;
    private $msgerrorrequired;

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
        global $CFG, $PAGE;
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
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'ratingallocatename', self::MOD_NAME);

        // Adding the standard "intro" and "introformat" fields.
        //TODO: Ensure backward-compatibility after deprecated method in Moodle 2.9 caused by MDL-49101
        if (method_exists($this, 'standard_intro_elements')){
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

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

        $mform->addElement('html', '<div id="selected_strategy_options"></div>');

        // Start/end time.
        $elementname = 'accesstimestart';
        $mform->addElement('date_time_selector', $elementname, get_string('rating_begintime', self::MOD_NAME));
        $mform->setDefault($elementname, time() + 24 * 60 * 60);
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

        $PAGE->requires->yui_module('moodle-mod_ratingallocate-strategyselect', 'M.mod_ratingallocate.strategyselect.init');

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

        if (isset($value[3])) {
            $attributes['placeholder'] = ($value[3]);
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

    // Override if you need to setup the form depending on current values.
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = & $this->_form;

        $data = $this->current;

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
