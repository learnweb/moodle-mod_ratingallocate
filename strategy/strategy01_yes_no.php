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
                get_string(self::STRATEGYID . '_setting_crossout', ratingallocate_MOD_NAME)
            )
        );
        foreach($this->get_choiceoptions($consider_dafault=true) as $id => $option){
            $output[$id] = array(
                            'text',
                            $option
            );
        }
        return $output;
    }
    
    public function get_dynamic_settingfields(){
        return array();
    }
    
    public function get_choiceoptions($consider_dafault=false, $consider_custom=true, $param = null){
        $options = array(
            0 => $this->get_settings_value(0, $consider_dafault,$consider_custom), 
            1 => $this->get_settings_value(1, $consider_dafault,$consider_custom)
        );
        return $options;
    }


    public function get_default_settings($param = null){
        return array(
                        self::MAXCROSSOUT => 3,
                        0 => get_string(strategy::STRATEGYID . '_rating_crossout', ratingallocate_MOD_NAME),
                        1 => get_string(strategy::STRATEGYID . '_rating_choose', ratingallocate_MOD_NAME)
        );
    }
}

// register with the strategymanager
\strategymanager::add_strategy(strategy::STRATEGYID);

class mod_ratingallocate_view_form extends \ratingallocate_options_strategyform {
    //Already specified by parent class

    public function get_choiceoptions($params = null) {
        return strategy::get_choiceoptions($params);
    }
    
    protected function get_max_amount_of_nos() {
        return $this->get_strategysetting(strategy::MAXCROSSOUT);
    }

    protected function get_max_nos_string_identyfier() {
        return strategy::STRATEGYID . '_max_no';
    }
}
