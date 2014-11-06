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
// namespace is mandatory!

namespace ratingallocate\strategy_lickert;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template_options.php');

class strategy extends \strategytemplate_options {

    const STRATEGYID = 'strategy_lickert';
    const MAXNO = 'maxno';
    const COUNTLICKERT = 'countlickert';

    public static function get_strategyid() {
        return self::STRATEGYID;
    }

    public static function get_static_settingfields() {
        return array(
            self::MAXNO => array(// maximale Anzahl 'kannnicht'
                'text',
                get_string(self::STRATEGYID . '_setting_maxno', 'ratingallocate')
            ),
            self::COUNTLICKERT => array(// wie viele Felder es gibt
                'text',
                get_string(self::STRATEGYID . '_setting_maxlickert', 'ratingallocate')
            )
        );
    }
    
    public static function get_dynamic_settingsfields(moodleform $mform){
        $strategyoptions = json_decode($this->ratingallocate->ratingallocate->setting, true);
        $maxlickert = intval($strategyoptions [strategy::STRATEGYID] [strategy::COUNTLICKERT]);
    }
    
    public static function get_options($maxlickert=0){
        $options = array(
                        0 => '0 - '.get_string(strategy::STRATEGYID . '_rating_exclude', 'ratingallocate')
        );
        
        for ($i = 1; $i <= $maxlickert; $i++) {
            if ($i == $maxlickert) {
                $options[$i] = $i.' - '.get_string(strategy::STRATEGYID . '_rating_biggestwish', 'ratingallocate');
            } else {
                $options[$i] = $i;
            }
        }
    }

}

// register with the strategymanager
\strategymanager::add_strategy(strategy::STRATEGYID);

class mod_ratingallocate_view_form extends \ratingallocate_options_strategyform {
    //Already specified by parent class
}