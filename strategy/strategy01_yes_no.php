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

    const STRATEGYNAME = 'YesNo';
    const STRATEGYID = 'strategy_yesno';
    const MAXCROSSOUT = 'maxcrossout'; // maxcrossout: Anzahl maximaler abzulehnender

    public static function get_strategyname() {
        return self::STRATEGYNAME;
    }

    public static function get_static_settingfields() {
        return array(
            self::MAXCROSSOUT => array(
                'text',
                get_string(self::STRATEGYID . '_setting_crossout', 'ratingallocate')
            )
        );
    }
    
    public static function get_options($param = null){
        $options = array(
                        0 => get_string(strategy::STRATEGYID . '_rating_crossout', 'ratingallocate'),
                        1 => get_string(strategy::STRATEGYID . '_rating_choose', 'ratingallocate')
        );
        return $options;
    }

}

// register with the strategymanager
\strategymanager::add_strategy(strategy::STRATEGYID);

class mod_ratingallocate_view_form extends \ratingallocate_options_strategyform {
    //Already specified by parent class
}
