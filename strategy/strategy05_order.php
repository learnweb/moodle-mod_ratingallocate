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
 * Internal library of functions for module ratingallocate
 *
 * All the ratingallocate specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package mod_ratingallocate
 * @copyright 2014 T Reischmann, C Usener
 * @copyright based on code by M Schulze copyright (C) 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Namespace is mandatory!
namespace mod_ratingallocate\strategy_order;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template.php');

/**
 * Strategy.
 *
 * @package mod_ratingallocate
 */
class strategy extends \strategytemplate {

    /**
     * Strategyid.
     */
    const STRATEGYID = 'strategy_order';
    /**
     * Countoptions.
     */
    const COUNTOPTIONS = 'countoptions';

    /**
     * Get strategy id.
     * @return string
     */
    public function get_strategyid() {
        return self::STRATEGYID;
    }

    /**
     * Get static settingfields.
     * @return array[]
     * @throws \coding_exception
     */
    public function get_static_settingfields() {
        return [
                self::COUNTOPTIONS => [// Amount of fields.
                        'int',
                        get_string(self::STRATEGYID . '_setting_countoptions', RATINGALLOCATE_MOD_NAME),
                        $this->get_settings_value(self::COUNTOPTIONS),
                        null,
                ],
        ];
    }

    /**
     * Get dynamic settingfields.
     * @return array
     */
    public function get_dynamic_settingfields() {
        return [];
    }

    /**
     * Get default settings.
     * @return array|int[]
     * @throws \coding_exception
     */
    public function get_default_settings() {
        $defaultcountoptions = 2;
        $output = [
                self::COUNTOPTIONS => $defaultcountoptions,
        ];
        $countoptions = $this->get_settings_value(self::COUNTOPTIONS, false);
        if (is_null($countoptions)) {
            $countoptions = $defaultcountoptions;
        }
        // Variable $rating_value_counter defines the id/value of the label (first choice has a high value).
        for ($i = 1, $ratingvaluecounter = $countoptions; $i <= $countoptions; $i++, $ratingvaluecounter--) {
            $output[$ratingvaluecounter] = get_string(self::STRATEGYID . '_no_choice', RATINGALLOCATE_MOD_NAME, $i);
        }
        return $output;
    }

    /**
     * Get validation information.
     * @return array[]
     */
    protected function getvalidationinfo() {
        return [self::COUNTOPTIONS => [true, 1]];
    }

}

// Register with the strategymanager.
\strategymanager::add_strategy(strategy::STRATEGYID);

/**
 * _Users view_
 * For every group for which the user can give a rating:
 * - shows the groups name and description
 * - shows a drop down menu from which the user can choose a rating
 */
class mod_ratingallocate_view_form extends \ratingallocate_strategyform {

    /**
     * Create new strategy.
     * @param array $strategyoptions
     * @return strategy
     */
    protected function construct_strategy($strategyoptions) {
        return new strategy($strategyoptions);
    }

    /**
     * Form definition of strategy.
     * @return void
     * @throws \coding_exception
     */
    public function definition() {
        global $USER;
        parent::definition();
        $mform = $this->_form;

        $ratingdata = $this->ratingallocate->get_rating_data_for_user($USER->id);
        // Filter choices to display by groups, where 'usegroups' is true.
        $ratingdata = $this->ratingallocate->filter_choices_by_groups($ratingdata, $USER->id);

        // If we have less options because of group restrictions than configured for the strategy,
        // we have to limit it, because user cannot vote for one option multiple times.
        $choicecounter = min($this->get_strategysetting(strategy::COUNTOPTIONS), count($ratingdata));
        $choices = [];

        foreach ($ratingdata as $data) {
            $choices[$data->choiceid] = $data->title;
        }

        for ($i = 1; $i <= $choicecounter; $i++) {
            $select = $mform->createElement('select');
            $this->fill_select($select, $i, $choices);
            $mform->addElement($select);
            $mform->addRule($select->getName(), 'You must select a state.', 'required');
        }

        foreach ($ratingdata as $data) {
            // If there is a valid value in the databse, choose the according rating
            // from the dropdown.
            // Else use a default value.
            if (is_numeric($data->rating) && $data->rating >= 0 &&
                    $mform->elementExists('choice[' . ($choicecounter - ($data->rating - 1)) . ']')) {
                $mform->getElement('choice[' . ($choicecounter - ($data->rating - 1)) . ']')->setSelected($data->choiceid);
            }
        }

        $mform->addElement('header', 'choice_descriptions',
                get_string(strategy::STRATEGYID . '_header_description', RATINGALLOCATE_MOD_NAME));

        foreach ($ratingdata as $data) {
            // Show max. number of allocations.
            // phpcs:ignore moodle.Commenting.TodoComment.MissingInfoInline
            // TODO add setting in order to make this optional, as requested in issue #14.
            $mform->addElement('html', '<div class="mod-ratingallocate-choice-maxno">' .
                    '<span class="mod-ratingallocate-choice-maxno-desc">' .
                    get_string('choice_maxsize_display', RATINGALLOCATE_MOD_NAME) .
                    ':</span> <span class="mod-ratingallocate-choice-maxno-value">' . $data->maxsize . '</span></div>');
            $mform->addElement('static', 'description_' . $data->choiceid, $data->title, format_text($data->explanation));

            // Render any file attachments.
            $attachments = $this->ratingallocate->get_file_attachments_for_choice($data->choiceid);
            $mform->addElement('html', $this->ratingallocate->get_renderer()->render_attachments($attachments));
        }
    }

    /**
     * Creates a select element including disabled choices for no selection.
     * @param \HTML_QuickForm_select $select select element to be filled with choices.
     * @param int $i number of select element
     * @param array $choices choices which should be available in the select element.
     * @return \HTML_QuickForm_select select element;
     * @throws \coding_exception
     */
    private function fill_select($select, $i, array $choices) {
        $select->setName('choice[' . $i . ']');
        $select->setLabel(get_string(strategy::STRATEGYID . '_no_choice', RATINGALLOCATE_MOD_NAME, $i));
        $select->addOption(get_string(strategy::STRATEGYID . '_choice_none', RATINGALLOCATE_MOD_NAME, $i),
                '', ['disabled' => 'disabled']);
        foreach ($choices as $id => $name) {
            $select->addOption($name, $id);
        }
        $select->setSelected('');
        return $select;
    }

    /**
     * Get strategy description.
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function describe_strategy() {
        return get_string(strategy::STRATEGYID . '_explain_choices', RATINGALLOCATE_MOD_NAME);
    }

    /**
     * Override to fill with correct data format
     * @return array
     */
    public function get_data() {
        $data = parent::get_data();
        $data->data = [];

        // Necessary to initialize an empty entry for every choice to enable the deletion of ratings.
        $choices = $this->ratingallocate->get_rateable_choices();
        foreach ($choices as $id => $choice) {
            $data->data[$id]['rating'] = null;
            $data->data[$id]['choiceid'] = $id;
        }

        if (isset($data->choice)) {
            // We do assign the highest rating to choice no.1.
            $maxrating = count($data->choice);
            foreach ($data->choice as $prio => $curchoice) {
                $data->data[$curchoice]['rating'] = $maxrating - ($prio - 1);
                $data->data[$curchoice]['choiceid'] = $curchoice;
            }
        }
        return $data;
    }

    /**
     * Validate form data.
     * @param array $data
     * @param array $files
     * @return array
     * @throws \coding_exception
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $usedchoices = [];

        // No data exists, so skip.
        if (!array_key_exists('choice', $data)) {
            return $errors;
        }

        foreach ($data['choice'] as $choiceid => $choice) {
            if (array_key_exists($choice, $usedchoices) && is_numeric($choice)) {
                $errors['choice[' . $choiceid . ']'] = get_string(strategy::STRATEGYID . '_use_only_once', RATINGALLOCATE_MOD_NAME);
            }
            $usedchoices[$choice] = true;
        }
        return $errors;
    }

}
