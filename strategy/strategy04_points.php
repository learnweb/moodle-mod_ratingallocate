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
namespace mod_ratingallocate\strategy_points;

use mod_ratingallocate\manager\strategymanager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template.php');

/**
 * Strategy
 *
 * @package mod_ratingallocate
 */
class strategy extends \strategytemplate {
    /**
     * Strategyid.
     */
    const STRATEGYID = 'strategy_points';
    /**
     * Max zero.
     */
    const MAXZERO = 'maxzero';
    /**
     * Totalpoints.
     */
    const TOTALPOINTS = 'totalpoints';
    /**
     * Max per choice.
     */
    const MAXPERCHOICE = 'maxperchoice';

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
                self::MAXZERO => [ // Maximum count of 'No'.
                        'int',
                        get_string(self::STRATEGYID . '_setting_maxzero', RATINGALLOCATE_MOD_NAME),
                        $this->get_settings_value(self::MAXZERO),
                        null,
                ],
                self::TOTALPOINTS => [ // Amount of fields.
                        'int',
                        get_string(self::STRATEGYID . '_setting_totalpoints', RATINGALLOCATE_MOD_NAME),
                        $this->get_settings_value(self::TOTALPOINTS),
                        null,
                ],
                self::MAXPERCHOICE => [// Maximum amount of points the student can give per choice.
                    'int',
                    get_string(self::STRATEGYID . '_setting_maxperchoice', RATINGALLOCATE_MOD_NAME),
                    $this->get_settings_value(self::MAXPERCHOICE),
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
     * @return int[]
     */
    public function get_default_settings() {
        return [
                self::MAXZERO => 3,
                self::TOTALPOINTS => 100,
                self::MAXPERCHOICE => 100,
        ];
    }

    /**
     * Get validation information.
     * @return array[]
     */
    protected function getvalidationinfo() {
        return [self::MAXZERO => [true, 0],
                self::TOTALPOINTS => [true, 1],
                self::MAXPERCHOICE => [true, 1],
        ];
    }
}

// Register with the strategymanager.
strategymanager::add_strategy(strategy::STRATEGYID);

/**
 * View form.
 *
 * @package mod_ratingallocate
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
     * Form definition for this strategy.
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

        foreach ($ratingdata as $data) {
            $headerelem = 'head_ratingallocate_' . $data->choiceid;
            $elemprefix = 'data[' . $data->choiceid . ']';
            $ratingelem = $elemprefix . '[rating]';
            $groupsidelem = $elemprefix . '[choiceid]';

            // Set choiceid.
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

            // Use explanation as title/label of group to align with other strategies.
            $mform->addElement('text', $ratingelem, format_text($data->explanation));
            $mform->setType($ratingelem, PARAM_INT);

            // Render any file attachments.
            $attachments = $this->ratingallocate->get_file_attachments_for_choice($data->choiceid);
            $mform->addElement('html', $this->ratingallocate->get_renderer()->render_attachments($attachments));

            // Try to restore previous ratings.
            if (is_numeric($data->rating) && $data->rating >= 0) {
                $mform->setDefault($ratingelem, $data->rating);
            } else {
                $mform->setDefault($ratingelem, 0);
            }
        }
    }

    /**
     * Get strategy description.
     * @return string
     * @throws \coding_exception
     */
    public function describe_strategy() {
        $output = get_string(
            strategy::STRATEGYID . '_explain_distribute_points',
            RATINGALLOCATE_MOD_NAME,
            $this->get_strategysetting(strategy::TOTALPOINTS)
        );
        $output .= '<br />';
        $output .= get_string(
            strategy::STRATEGYID . '_explain_max_zero',
            RATINGALLOCATE_MOD_NAME,
            $this->get_strategysetting(strategy::MAXZERO)
        );
        $output .= '<br />';
        $output .= get_string(
            strategy::STRATEGYID . '_explain_max_per_choice',
            RATINGALLOCATE_MOD_NAME,
            $this->get_strategysetting(strategy::MAXPERCHOICE)
        );
        return $output;
    }

    /**
     *  Validate form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     * @throws \coding_exception
     */
    public function validation($data, $files) {
        $maxcrossout = $this->get_strategysetting(strategy::MAXZERO);
        $totalpoints = $this->get_strategysetting(strategy::TOTALPOINTS);
        $maxperchoice = $this->get_strategysetting(strategy::MAXPERCHOICE);
        $errors = parent::validation($data, $files);

        if (!array_key_exists('data', $data) || count($data['data']) < 2) {
            return $errors;
        }

        $impossibles = 0;
        $ratings = $data['data'];
        $currentpoints = 0;
        foreach ($ratings as $cid => $rating) {
            if ($rating['rating'] < 0 || $rating['rating'] > $totalpoints || $rating['rating'] > $maxperchoice) {
                $maxpoints = min($maxperchoice, $totalpoints);
                $errors['data[' . $cid . '][rating]'] =
                        get_string(strategy::STRATEGYID . '_illegal_entry', RATINGALLOCATE_MOD_NAME, $maxpoints);
            } else if ($rating['rating'] == 0) {
                $impossibles++;
            }
            $currentpoints += $rating['rating'];
        }

        if ($impossibles > $maxcrossout) {
            foreach ($ratings as $cid => $rating) {
                if ($rating['rating'] == 0) {
                    $errors['data[' . $cid . '][rating]'] =
                            get_string(strategy::STRATEGYID . '_max_count_zero', RATINGALLOCATE_MOD_NAME, $maxcrossout);
                }
            }
        }

        if ($currentpoints <> $totalpoints) {
            foreach ($ratings as $cid => $rating) {
                $errors['data[' . $cid . '][rating]'] =
                        get_string(strategy::STRATEGYID . '_incorrect_totalpoints', RATINGALLOCATE_MOD_NAME, $totalpoints);
            }
        }
        return $errors;
    }
}
