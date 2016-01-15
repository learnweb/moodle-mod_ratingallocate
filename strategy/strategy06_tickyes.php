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

namespace ratingallocate\strategy_tickyes;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template.php');

class strategy extends \strategytemplate {

    const STRATEGYID = 'strategy_tickyes';
    const MINTICKYES = 'mintickyes';
    const ACCEPT_LABEL = 'accept';

    public function get_strategyid() {
        return self::STRATEGYID;
    }

    public function get_static_settingfields() {
        $output = array(
            self::MINTICKYES => array('int', 
                get_string(self::STRATEGYID . '_setting_mintickyes', ratingallocate_MOD_NAME), 
                $this->get_settings_value(self::MINTICKYES)
            )
        );
        
        $output[1] = array(
                        'text',
                        get_string('strategy_settings_label', ratingallocate_MOD_NAME, $this->get_settings_default_value(1)),
                        null,
                        $this->get_settings_default_value(1)
                        
        );
        return $output;
    }
    
    public function get_dynamic_settingfields(){
        return array();
    }
    
    public function get_accept_label(){
        return $this->get_settings_value(1);
    }

    public function get_default_settings(){
        return array(
                        self::MINTICKYES => 3,
                        1 => get_string(self::STRATEGYID . '_' . self::ACCEPT_LABEL, ratingallocate_MOD_NAME),
                        0 => get_string(self::STRATEGYID . '_not_' . self::ACCEPT_LABEL, ratingallocate_MOD_NAME)
        );
    }
    
    protected function getValidationInfo(){
        return array(self::MINTICKYES => array(true,1)
        );
    }
}

// register with the strategymanager
\strategymanager::add_strategy(strategy::STRATEGYID);

/**
 * _Users view_
 * For every group for which the user can give a rating:
 * - shows the groups name and description
 * - shows a drop down menu from which the user can choose a rating
 */
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


            // Use explanation as title/label of checkbox to align with other strategies.
            $mform->addElement('advcheckbox', $ratingelem, $data->explanation, $this->get_strategy()->get_accept_label(), null, array(0, 1));
            $mform->setType($ratingelem, PARAM_INT);

            if (is_numeric($data->rating) && $data->rating >= 0) {
                $mform->setDefault($ratingelem, $data->rating);
            } else {
                $mform->setDefault($ratingelem, 1);
            }
        }
    }

    public function describe_strategy() {
        return get_string(strategy::STRATEGYID . '_explain_mintickyes', ratingallocate_MOD_NAME, $this->get_strategysetting(strategy::MINTICKYES));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $mintickyes = $this->get_strategysetting(strategy::MINTICKYES);

        if (!array_key_exists('data', $data) or count($data ['data']) < 2) {
            return $errors;
        }

        $checkedaccept = 0;
        $ratings = $data ['data'];
        foreach ($ratings as $rating) {
            if ($rating ['rating'] == 1) {
                $checkedaccept ++;
            }
        }

        if ($checkedaccept < $mintickyes) {
            foreach ($ratings as $cid => $rating) {
                if ($rating ['rating'] == 0) {
                    $errors ['data[' . $cid . '][rating]'] = get_string(strategy::STRATEGYID . '_error_mintickyes', ratingallocate_MOD_NAME, $mintickyes);
                }
            }
        }
        return $errors;
    }

}
