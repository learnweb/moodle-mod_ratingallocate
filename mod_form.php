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
    /**
     * Mod_name.
     */
    const MOD_NAME = 'ratingallocate';
    /**
     * Choice placeholder.
     */
    const CHOICE_PLACEHOLDER_IDENTIFIER = 'placeholder_for_choices';
    /**
     * Strategy options.
     */
    const STRATEGY_OPTIONS = 'strategyopt';
    /**
     * Strategyoptions placeholder.
     */
    const STRATEGY_OPTIONS_PLACEHOLDER = 'placeholder_strategyopt';
    /** @var int $newchoicecounter */
    private $newchoicecounter = 0;
    /** @var lang_string|string $msgerrorrequired */
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

        $disablestrategy = $this->get_disable_strategy();

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('ratingallocatename', self::MOD_NAME), [
                'size' => '64',
        ]);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'ratingallocatename', self::MOD_NAME);

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // -------------------------------------------------------------------------------
        $elementname = 'strategy';
        // Define options for select.
        $selectoptions = [];
        foreach (\strategymanager::get_strategies() as $strategy) {
            $selectoptions[$strategy] = get_string($strategy . '_name', self::MOD_NAME);
        }
        $mform->addElement('select', $elementname, get_string('select_strategy', self::MOD_NAME), $selectoptions,
            $disablestrategy ? ['disabled' => ''] : null);
        $mform->addHelpButton($elementname, 'select_strategy', self::MOD_NAME);
        if (!$disablestrategy) {
            // Disabled elements don't get posted so disable the required rule if strategy selection is disabled.
            $mform->addRule('strategy', null, 'required', null, 'client');
        }

        // Start/end time.
        $elementname = 'accesstimestart';
        $mform->addElement('date_time_selector', $elementname, get_string('rating_begintime', self::MOD_NAME));
        $mform->setDefault($elementname, time() + 24 * 60 * 60);
        $elementname = 'accesstimestop';
        $mform->addElement('date_time_selector', $elementname, get_string('rating_endtime', self::MOD_NAME));
        $mform->setDefault($elementname, time() + 7 * 24 * 60 * 60); // Default: now + one week.

        $elementname = 'publishdate';
        $mform->addElement('date_time_selector', $elementname, get_string($elementname, self::MOD_NAME),
               ['optional' => true]);
        $mform->setDefault($elementname, time() + 9 * 24 * 60 * 60);

        $elementname = 'runalgorithmbycron';
        $mform->addElement('advcheckbox', $elementname, get_string($elementname, self::MOD_NAME), null, null, [0, 1]);
        $mform->addHelpButton($elementname, $elementname, self::MOD_NAME);
        $mform->setDefault($elementname, 1);

        $headerid = 'strategy_fieldset';
        $mform->addElement('header', $headerid, get_string('strategyspecificoptions', RATINGALLOCATE_MOD_NAME));
        $mform->setExpanded($headerid);

        foreach (\strategymanager::get_strategies() as $strategy) {
            // Load strategy class.
            $strategyclassp = 'mod_ratingallocate\\' . $strategy . '\\strategy';
            $strategyclass = new $strategyclassp();

            // Add options fields.
            foreach ($strategyclass->get_static_settingfields() as $key => $value) {
                $fieldid = $this->get_settingsfield_identifier($strategy, $key);
                $this->add_settings_field($fieldid, $value, $strategy, $mform);
            }
            $mform->addElement('static', self::STRATEGY_OPTIONS_PLACEHOLDER . '[' . $strategy . ']', '', '');
        }

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * If ratings have already been submitted by users, the ratingallocate strategy can no longer
     * be changend.
     * @param $includeratingallocate
     * @return array|bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_disable_strategy($includeratingallocate = false) {
        $update = $this->optional_param('update', 0, PARAM_INT);
        if ($update != 0) {
            global $DB;
            $courseid = $update;
            $cm         = get_coursemodule_from_id('ratingallocate', $courseid, 0, false, MUST_EXIST);
            $course     = get_course($cm->course);
            $ratingallocatedb  = $DB->get_record('ratingallocate', ['id' => $cm->instance], '*', MUST_EXIST);
            $context = context_module::instance($cm->id);
            $ratingallocate = new ratingallocate($ratingallocatedb, $course, $cm, $context);
            $disablestrategy = $ratingallocate->get_number_of_active_raters() > 0;
        } else {
            $ratingallocate = null;
            $disablestrategy = false;
        }
        if (!$includeratingallocate) {
            return $disablestrategy;
        } else {
            return [
                'ratingallocate' => $ratingallocate,
                'disable_strategy' => $disablestrategy,
            ];
        }
    }

    /**
     * Add an settings element to the form. It is enabled only if the strategy it belongs to is selected.
     * @param string $stratfieldid id of the element to be added
     * @param array $value array with the element type and its caption
     *        (usually returned by the strategys get settingsfields methods).
     * @param string $strategyid id of the strategy it belongs to.
     * @param $mform MoodleQuickForm form object the settings field should be added to.
     */
    private function add_settings_field($stratfieldid, array $value, $strategyid, MoodleQuickForm $mform) {
        $attributes = [];
        if ($value[0] != "select" && isset($value[3])) {
            $attributes['placeholder'] = ($value[3]);
        }

        if ($value[0] == "text") {
            $mform->addElement('text', $stratfieldid, $value[1], $attributes);
            $mform->setType($stratfieldid, PARAM_TEXT);
        } else if ($value[0] == "int") {
            $mform->addElement('text', $stratfieldid, $value[1], $attributes);
            $mform->setType($stratfieldid, PARAM_TEXT);
            $mform->addRule($stratfieldid, null, 'numeric'); // TODO: Only validate if not disabled.
        } else if ($value[0] == "select") {
            $mform->addElement('select', $stratfieldid, $value[1], $value[3], $attributes);
        }
        if (isset($value[2])) {
            $mform->setDefault($stratfieldid, $value[2]);
        }
        if (isset($value[4])) {
            $mform->addHelpButton($stratfieldid, $value[4], self::MOD_NAME);
        }
        $mform->hideIf($stratfieldid, 'strategy', 'neq', $strategyid);
    }

    /**
     * Override if you need to setup the form depending on current values.
     *
     * @return void
     * @throws coding_exception
     */
    public function definition_after_data() {

        $mform = &$this->_form;

        $data = $this->current;

        if ($this->is_submitted()) {
            $subdata = $this->get_data();
            $allstrategyoptions = $subdata->{self::STRATEGY_OPTIONS};
        } else if (isset($data->setting)) {
            $allstrategyoptions = json_decode($data->setting, true);
        }
        // Add dynamic settings fields.
        foreach (\strategymanager::get_strategies() as $strategy) {
            // Load strategy class.
            $strategyclassp = 'mod_ratingallocate\\' . $strategy . '\\strategy';
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
                $buttonname = self::STRATEGY_OPTIONS . $strategy . 'refresh';
                $mform->registerNoSubmitButton($buttonname);
                $mform->addElement('submit', $buttonname, get_string('refresh'));
                $mform->insertElementBefore($mform->removeElement($buttonname, false),
                        $strategyplaceholder);
                $mform->hideIf($buttonname, 'strategy', 'neq', $strategy);

            }
            $mform->removeElement($strategyplaceholder);
        }

        // Call parent function after, in order to have completiontracking working properly.
        parent::definition_after_data();
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

        $info = $this->get_disable_strategy(true);
        $disablestrategy = $info['disable_strategy'];
        $ratingallocate = $info['ratingallocate'];

        if ($disablestrategy) {
            // If strategy selection is disabled make sure the user didn't change it.
            if ($ratingallocate->ratingallocate->dbrecord->strategy !== $data['strategy']) {
                $errors['strategy'] = get_string('strategy_altered_after_preferences', self::MOD_NAME);
            }
        }

        if (empty($data['strategy'])) {
            // User has to select one strategy.
            $errors['strategy'] = get_string('strategy_not_specified', self::MOD_NAME);
        } else {
            $strategyclassp = 'mod_ratingallocate\\' . $data['strategy'] . '\\strategy';
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

    /**
     * Add elements for setting the custom completion rules.
     *
     * @return array List of added element names.
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('advcheckbox', $this->get_suffixed_name('vote'), ' ', get_string('completionvote', RATINGALLOCATE_MOD_NAME));
        $mform->addElement('advcheckbox', $this->get_suffixed_name('allocation'), ' ', get_string('completionallocation', RATINGALLOCATE_MOD_NAME));

        // Set default to not checked.
        $mform->setDefault($this->get_suffixed_name('vote'), 0);
        $mform->setDefault($this->get_suffixed_name('allocation'), 0);

        // Add help buttons.
        $mform->addHelpButton($this->get_suffixed_name('vote'), 'completionvote', RATINGALLOCATE_MOD_NAME);
        $mform->addHelpButton($this->get_suffixed_name('allocation'), 'completionallocation', RATINGALLOCATE_MOD_NAME);

        return [$this->get_suffixed_name('vote'), $this->get_suffixed_name('allocation')];
    }

    protected function get_suffixed_name(string $fieldname): string {
        return 'completion' . $fieldname . $this->get_suffix();
    }

    /**
     * Called during validaiton to see wether some activitiy-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules are enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return ($data[$this->get_suffixed_name('vote')] == 1 || $data[$this->get_suffixed_name('allocation')] == 1);
    }

    /**
     * Allows module to modify data returned by get_moduleinfo_data() or prepare_new_moduleinfo_data() before calling set_data().
     * This method is also called in the bulk activity completion form.
     * Only available on moodleform_mod.
     *
     * @param $default_values
     * @return void
     */
    function data_preprocessing(&$default_values){
        if(empty($default_values[$this->get_suffixed_name('vote')])) {
            $default_values[$this->get_suffixed_name('vote')] = 0;
        }
        if(empty($default_values[$this->get_suffixed_name('allocation')])) {
            $default_values[$this->get_suffixed_name('allocation')] = 0;
        }
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        // Turn off completion settings if the checkboxes aren't ticked.
        if (!empty($data->completionunlocked)) {
            $completion = $data->{'completion' . $this->get_suffix()};
            $autocompletion = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{$this->get_suffixed_name('vote')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('vote')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('allocation')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('allocation')} = 0;
            }
        }
    }

}
