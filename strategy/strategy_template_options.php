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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template.php');

/**
 * Strategytemplate options.
 *
 * @package mod_ratingallocate
 */
abstract class strategytemplate_options extends \strategytemplate {
    /**
     * Return the different options for each choice (including titles)
     * @return array: value_of_option => title_of_option
     */
    abstract public function get_choiceoptions();

    /**
     * Returns the strategy option array, which can be included within the child option classes.
     * @return array
     */
    protected function get_default_strategy_option() {
        return ['default' => [
                'select',
                get_string('strategy_settings_default', RATINGALLOCATE_MOD_NAME),
                $this->get_settings_value('default'),
                $this->get_choiceoptions(),
                'strategy_settings_default',
        ]];
    }
}

/**
 * _Users view_
 * For every group for which the user can give a rating:
 * - shows the groups name and description
 * - shows a drop down menu from which the user can choose a rating
 */
abstract class ratingallocate_options_strategyform extends \ratingallocate_strategyform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $USER;
        parent::definition();
        $mform = $this->_form;

        $ratingdata = $this->ratingallocate->get_rating_data_for_user($USER->id);
        // Filter choices to display by groups, where 'usegroups' is true.
        $ratingdata = $this->ratingallocate->filter_choices_by_groups($ratingdata, $USER->id);

        foreach ($ratingdata as $data) {
            $headerelem = 'head_ratingallocate_' . $data->choiceid;
            $elemprefix = 'data[' . $data->choiceid . ']';
            $ratingelem = $elemprefix . '[rating]';
            $groupsidelem = $elemprefix . '[choiceid]';

            // Save choiceid.
            $mform->addElement('hidden', $groupsidelem, $data->choiceid);
            $mform->setType($groupsidelem, PARAM_INT);

            // Show title.
            $mform->addElement('header', $headerelem, $data->title);
            $mform->setExpanded($headerelem);

            // Show max. number of allocations.
            // phpcs:ignore moodle.Commenting.TodoComment.MissingInfoInline
            // TODO add setting in order to make this optional, as requested in issue #14.
            $mform->addElement('html', '<div class="mod-ratingallocate-choice-maxno">' .
                    '<span class="mod-ratingallocate-choice-maxno-desc">' .
                    get_string('choice_maxsize_display', RATINGALLOCATE_MOD_NAME) .
                    ':</span> <span class="mod-ratingallocate-choice-maxno-value">' . $data->maxsize . '</span></div>');

            // Options for each choice.
            $choiceoptions = $this->get_choiceoptions();

            $radioarray = [];
            foreach ($choiceoptions as $id => $option) {
                $radioarray[] =& $mform->createElement('radio', $ratingelem, '', $option, $id);
            }
            // Adding static elements to support css.
            $radioarray = $this->ratingallocate->prepare_horizontal_radio_choice($radioarray, $mform);

            // It is important to set a group name, so that later on errors can be displayed at the correct spot.
            // Furthermore, use explanation as title/label of group.
            $mform->addGroup($radioarray, 'radioarr_' . $data->choiceid, format_text($data->explanation), null, false);

            // Render any file attachments.
            $attachments = $this->ratingallocate->get_file_attachments_for_choice($data->choiceid);
            $mform->addElement('html', $this->ratingallocate->get_renderer()->render_attachments($attachments));

            $defaultrating = $this->get_strategysetting('default');
            $defaultrating = $defaultrating == null ? max(array_keys($choiceoptions)) : $defaultrating;
            // Try to restore previous ratings.
            if (is_numeric($data->rating) && $data->rating >= 0 && $data->rating <= max(array_keys($choiceoptions))) {
                $mform->setDefault($ratingelem, $data->rating);
            } else {
                $mform->setDefault($ratingelem, $defaultrating);
            }
        }
    }

    /**
     * Get strategy description.
     * @return lang_string|string
     * @throws coding_exception
     */
    public function describe_strategy() {
        return get_string($this->get_max_nos_string_identyfier(), RATINGALLOCATE_MOD_NAME, $this->get_max_amount_of_nos());
    }

    /**
     * Validate form data.
     * @param array $data
     * @param array $files
     * @return array
     * @throws coding_exception
     */
    public function validation($data, $files) {
        $maxno = $this->get_max_amount_of_nos();
        $errors = parent::validation($data, $files);

        if (!array_key_exists('data', $data) || count($data['data']) < 2) {
            return $errors;
        }

        $impossibles = 0;
        $ratings = $data['data'];

        foreach ($ratings as $rating) {
            if (key_exists('rating', $rating) && $rating['rating'] == 0) {
                $impossibles++;
            }
        }

        if ($impossibles > $maxno) {
            foreach ($ratings as $cid => $rating) {
                if ($rating['rating'] == 0) {
                    $errors['radioarr_' . $cid] = get_string(
                        $this->get_max_nos_string_identyfier(),
                        RATINGALLOCATE_MOD_NAME,
                        $maxno
                    );
                }
            }
        }
        return $errors;
    }

    /**
     * Abstract function to get choiceoptions.
     * @return mixed
     */
    abstract public function get_choiceoptions();

    /**
     * Abstract function to get maximal amount how many times a user is allowed to rate a choice with "NO".
     * @return mixed
     */
    abstract protected function get_max_amount_of_nos();

    // phpcs:ignore moodle.Commenting.TodoComment.MissingInfoInline
    // TODO remove and make identifier strategy_options specific not strategy specific.

    /**
     * Abstract function to get string identifier of max_nos.
     * @return mixed
     */
    abstract protected function get_max_nos_string_identyfier();
}
