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
namespace mod_ratingallocate\strategy_yesmaybeno;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

use mod_ratingallocate\manager\strategymanager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template_options.php');

/**
 * Strategy.
 *
 * @package mod_ratingallocate
 */
class strategy extends \strategytemplate_options {
    /**
     * Strategyid.
     */
    const STRATEGYID = 'strategy_yesmaybeno';
    /**
     * Maximal votes for no.
     */
    const MAXNO = 'maxno';

    /**
     * Get strategy id.
     * @return string
     */
    public function get_strategyid() {
        return self::STRATEGYID;
    }

    /**
     * Get static settingfields of strategy.
     * @return array|array[]
     * @throws \coding_exception
     */
    public function get_static_settingfields() {
        $output = [
                self::MAXNO => [// Maximum count of 'No'.
                        'int',
                        get_string(self::STRATEGYID . '_setting_maxno', RATINGALLOCATE_MOD_NAME),
                        $this->get_settings_value(self::MAXNO),
                        null,
                ],
        ];
        foreach (array_keys($this->get_choiceoptions()) as $id) {
            $output[$id] = [
                    'text',
                    get_string('strategy_settings_label', RATINGALLOCATE_MOD_NAME, $this->get_settings_default_value($id)),
                    null,
                    $this->get_settings_default_value($id),
            ];
        }
        $output += $this->get_default_strategy_option();
        return $output;
    }

    /**
     * Get dynamic settingfields.
     *
     * @return array
     */
    public function get_dynamic_settingfields() {
        return [];
    }

    /**
     * Get choiceoptions.
     *
     * @return array
     */
    public function get_choiceoptions() {
        $options = [
                0 => $this->get_settings_value(0),
                3 => $this->get_settings_value(3),
                5 => $this->get_settings_value(5),
        ];
        return $options;
    }

    /**
     * Get default settings.
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_default_settings() {
        return [
                self::MAXNO => 3,
                0 => get_string(self::STRATEGYID . '_rating_no', RATINGALLOCATE_MOD_NAME),
                3 => get_string(self::STRATEGYID . '_rating_maybe', RATINGALLOCATE_MOD_NAME),
                5 => get_string(self::STRATEGYID . '_rating_yes', RATINGALLOCATE_MOD_NAME),
                'default' => 3,
        ];
    }

    /**
     * Get validation info.
     *
     * @return array[]
     */
    protected function getvalidationinfo() {
        return [self::MAXNO => [true, 0]];
    }
}

// Register with the strategymanager.
strategymanager::add_strategy(strategy::STRATEGYID);

/**
 * View form.
 *
 * @package mod_ratingallocate
 */
class mod_ratingallocate_view_form extends \ratingallocate_options_strategyform {
    // Already specified by parent class.

    /**
     * Create new strategy.
     * @param array $strategyoptions
     * @return strategy
     */
    protected function construct_strategy($strategyoptions) {
        return new strategy($strategyoptions);
    }

    /**
     * Get all choice options.
     * @return mixed
     */
    public function get_choiceoptions() {
        return $this->get_strategy()->get_choiceoptions();
    }

    /**
     * Get maximal amount how many times a user is allowed to rate a choice with "NO".
     * @return \the|null
     */
    protected function get_max_amount_of_nos() {
        return $this->get_strategysetting(strategy::MAXNO);
    }

    /**
     * Get string identifier of max_nos.
     * @return string
     */
    protected function get_max_nos_string_identyfier() {
        return strategy::STRATEGYID . '_max_no';
    }
}
