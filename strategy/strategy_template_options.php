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
 * @copyright 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template.php');

class strategytemplate_options extends \strategytemplate {

    public static function get_static_settingfields() {
        return array(
            self::MAXCROSSOUT => array(
                'text',
                get_string(self::STRATEGYID . '_setting_crossout', 'ratingallocate')
            )
        );
    }

}

/**
 * _Users view_
 * For every group for which the user can give a rating:
 * - shows the groups name and description
 * - shows a drop down menu from which the user can choose a rating
 */
class ratingallocate_options_strategyform extends \ratingallocate_strategyform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $COURSE, $PAGE, $DB, $USER;

        $mform = $this->_form;

        $ratingdata = $this->ratingallocate->get_rating_data_for_user($USER->id);

        $renderer = $PAGE->get_renderer('mod_ratingallocate');

        $mform->addElement('hidden', 'action', RATING_ALLOC_ACTION_RATE);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        foreach ($ratingdata as $data) {
            $headerelem = 'head_ratingallocate_' . $data->choiceid;
            $elemprefix = 'data[' . $data->choiceid . ']';
            $ratingelem = $elemprefix . '[rating]';
            $groupsidelem = $elemprefix . '[choiceid]';

            // save choiceid
            $mform->addElement('hidden', $groupsidelem, $data->choiceid);
            $mform->setType($groupsidelem, PARAM_INT);

            // show title
            $mform->addElement('header', $headerelem, $data->title);
            $mform->setExpanded($headerelem);

            // show explanation
            $mform->addElement('html', '<div>' . $data->explanation . '</div>');

            // options for each choice
            $options = strategy::get_options();

            $radioarray = array();
            foreach ($options as $id => $option) {
                $radioarray [] = & $mform->createElement('radio', $ratingelem, '', $option, $id, '');
            }
            // it is important to set a group name, so that later on errors can be displayed at the correct spot.
            $mform->addGroup($radioarray, 'radioarr_' . $data->choiceid, '', null, false);

			$max_rating = max(array_keys($options));
            // try to restore previous ratings
            if (is_numeric($data->rating) && $data->rating >= 0 && $data->rating <= $max_rating) {
                $mform->setDefault($ratingelem, $data->rating);
            } else {
                $mform->setDefault($ratingelem, $max_rating);
            }
            // $mform->setType($ratingelem, PARAM_INT);
        }

        if (count($ratingdata) > 0) {
            $this->add_action_buttons();
        } else {
            $box = $renderer->notification(get_string('no_groups_to_rate', 'ratingallocate'));
            $mform->addElement('html', $box);
        }
    }

    public function describe_strategy() {
        $strategyoptions = json_decode($this->ratingallocate->ratingallocate->setting, true);

        $output = get_string('strategyname', 'ratingallocate', strategy::get_strategyname()) . '<br />';
        $output .= get_string(strategy::STRATEGYID . '_max_no', 'ratingallocate', $strategyoptions [strategy::STRATEGYID] [strategy::MAXNO]);

        return $output;
    }

    public function validation($data, $files) {
        $maxno = json_decode($this->ratingallocate->ratingallocate->setting, true)[strategy::STRATEGYID][strategy::MAXNO];
        $errors = parent::validation($data, $files);

        if (!array_key_exists('data', $data) or count($data ['data']) < 2) {
            return $errors;
        }

        $impossibles = 0;
        $ratings = $data ['data'];

        foreach ($ratings as $rating) {
            if (key_exists('rating', $rating) && $rating ['rating'] == 0) {
                $impossibles ++;
            }
        }

        if ($impossibles > $maxno) {
            foreach ($ratings as $cid => $rating) {
                if ($rating ['rating'] == 0) {
                    $errors ['radioarr_' . $cid] = get_string(strategy::STRATEGYID . '_max_no', 'ratingallocate', $maxno);
                }
            }
        }
        return $errors;
    }

}