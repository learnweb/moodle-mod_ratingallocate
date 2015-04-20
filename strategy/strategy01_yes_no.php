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

namespace ratingallocate\strategy_yesno;

use ratingallocate\strategy_yesno\strategy;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template_options.php');

class strategy extends \strategytemplate_options {

    const STRATEGYID = 'strategy_yesno';
    const MAXCROSSOUT = 'maxcrossout'; // maxcrossout: Anzahl maximaler abzulehnender

    public function get_strategyid() {
        return self::STRATEGYID;
    }

    public function get_static_settingfields() {
        $output =  array(
            self::MAXCROSSOUT => array(
                'int',
                get_string(self::STRATEGYID . '_setting_crossout', ratingallocate_MOD_NAME),
                $this->get_settings_value(self::MAXCROSSOUT),
                null
            )
        );
        foreach($this->get_choiceoptions() as $id => $option){
            $output[$id] = array(
                            'text',
                            get_string('strategy_settings_label', ratingallocate_MOD_NAME, $this->get_settings_default_value($id)),
                            null,
                            $this->get_settings_default_value($id)
            );
        }
        return $output;
    }
    
    public function get_dynamic_settingfields(){
        return array();
    }
    
    public function get_choiceoptions(){
        $options = array(
            0 => $this->get_settings_value(0), 
            1 => $this->get_settings_value(1)
        );
        return $options;
    }

    public function get_default_settings(){
        return array(
                        self::MAXCROSSOUT => 3,
                        0 => get_string(strategy::STRATEGYID . '_rating_crossout', ratingallocate_MOD_NAME),
                        1 => get_string(strategy::STRATEGYID . '_rating_choose', ratingallocate_MOD_NAME)
        );
    }
    
    protected function getValidationInfo(){
        return array(self::MAXCROSSOUT => array(true,0));
    }
}

// register with the strategymanager
\strategymanager::add_strategy(strategy::STRATEGYID);

class mod_ratingallocate_view_form extends \ratingallocate_options_strategyform {
    //Already specified by parent class

    protected function construct_strategy($strategyoptions){
        return new strategy($strategyoptions);
    }
    
    public function get_choiceoptions() {
        return $this->get_strategy()->get_choiceoptions();
    }
    
    protected function get_max_amount_of_nos() {
        return $this->get_strategysetting(strategy::MAXCROSSOUT);
    }

    protected function get_max_nos_string_identyfier() {
        return strategy::STRATEGYID . '_max_no';
    }
}
