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
// namespace is mandatory!

namespace ratingallocate\strategy_points;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template.php');

class strategy extends \strategytemplate {

    const STRATEGYID = 'strategy_points';
    const MAXZERO = 'maxzero';
    const TOTALPOINTS = 'totalpoints';


    public function get_strategyid() {
        return self::STRATEGYID;
    }

    public function get_static_settingfields() {
        return array(
            self::MAXZERO => array( // maximale Anzahl 'kannnicht'
                'int', 
                get_string(self::STRATEGYID . '_setting_maxzero', ratingallocate_MOD_NAME), 
                $this->get_settings_value(self::MAXZERO),
                null
            ), 
            self::TOTALPOINTS => array( // wie viele Felder es gibt
                'int', 
                get_string(self::STRATEGYID . '_setting_totalpoints', ratingallocate_MOD_NAME), 
                $this->get_settings_value(self::TOTALPOINTS),
                null
            )
        );
    }
    
    public function get_dynamic_settingfields(){
        return array();
    }
    
    public function get_default_settings(){
        return array(
                        self::MAXZERO => 3,
                        self::TOTALPOINTS => 100
        );
    }
    
    protected function getValidationInfo(){
        return array(self::MAXZERO => array(true,0),
                     self::TOTALPOINTS => array(true,1)
        );
    }

}

// register with the strategymanager
\strategymanager::add_strategy(strategy::STRATEGYID);

class mod_ratingallocate_view_form extends \ratingallocate_strategyform {

    protected function construct_strategy($strategyoptions){
        return new strategy($strategyoptions);
    }
    
    public function definition() {
        global $USER;
        parent::definition();

        $mform = $this->_form;

        $ratingdata = $this->ratingallocate->get_rating_data_for_user($USER->id);

        foreach ($ratingdata as $data) {
            $headerelem = 'head_ratingallocate_' . $data->choiceid;
            $elemprefix = 'data[' . $data->choiceid . ']';
            $ratingelem = $elemprefix . '[rating]';
            $groupsidelem = $elemprefix . '[choiceid]';

            // choiceid ablegen
            $mform->addElement('hidden', $groupsidelem, $data->choiceid);
            $mform->setType($groupsidelem, PARAM_INT);

            // title anzeigen
            $mform->addElement('header', $headerelem, $data->title);
            $mform->setExpanded($headerelem);

            // Show max. number of allocations.
            // TODO add setting in order to make this optional, as requested in issue #14.
            $mform->addElement('html', '<div class="mod-ratingallocate-choice-maxno">' .
                '<span class="mod-ratingallocate-choice-maxno-desc">' .
                get_string('choice_maxsize_display', ratingallocate_MOD_NAME) .
                ':</span> <span class="mod-ratingallocate-choice-maxno-value">' . $data->maxsize . '</span></div>');

            // Use explanation as title/label of group to align with other strategies.
            $mform->addElement('text', $ratingelem, $data->explanation );
            $mform->setType($ratingelem, PARAM_INT);

            // try to restore previous ratings
            if (is_numeric($data->rating) && $data->rating >= 0) {
                $mform->setDefault($ratingelem, $data->rating);
            } else {
                $mform->setDefault($ratingelem, 1);
            }
        }
    }

    public function describe_strategy() {
        $output = get_string(strategy::STRATEGYID . '_explain_distribute_points', ratingallocate_MOD_NAME, $this->get_strategysetting(strategy::TOTALPOINTS));
        $output .= '<br />';
        $output .= get_string(strategy::STRATEGYID . '_explain_max_zero', ratingallocate_MOD_NAME, $this->get_strategysetting(strategy::MAXZERO));
        return $output;
    }

    public function validation($data, $files) {
        $maxcrossout = $this->get_strategysetting(strategy::MAXZERO);
        $totalpoints = $this->get_strategysetting(strategy::TOTALPOINTS);
        $errors = parent::validation($data, $files);

        if (!array_key_exists('data', $data) or count($data ['data']) < 2) {
            return $errors;
        }

        $impossibles = 0;
        $ratings = $data ['data'];
        $currentpoints = 0;
        foreach ($ratings as $rating) {
            if ($rating ['rating'] == 0) {
                $impossibles ++;
            }
            $currentpoints += $rating['rating'];
        }

        if ($impossibles > $maxcrossout) {
            foreach ($ratings as $cid => $rating) {
                if ($rating ['rating'] == 0) {
                    $errors ['data[' . $cid . '][rating]'] = get_string(strategy::STRATEGYID . '_max_count_zero', ratingallocate_MOD_NAME, $maxcrossout);
                }
            }
        }

        if ($currentpoints <> $totalpoints) {
            foreach ($ratings as $cid => $rating) {
                $errors ['data[' . $cid . '][rating]'] = get_string(strategy::STRATEGYID . '_incorrect_totalpoints', ratingallocate_MOD_NAME, $totalpoints);
            }
        }
        return $errors;
    }

}
