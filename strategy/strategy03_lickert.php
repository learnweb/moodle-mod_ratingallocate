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

// Namespace is mandatory!
namespace mod_ratingallocate\strategy_lickert;

use mod_ratingallocate\manager\strategymanager;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/strategy_template_options.php');

/**
 * Strategy
 *
 * @package mod_ratingallocate
 */
class strategy extends \strategytemplate_options {
    /**
     * Strategyid.
     */
    const STRATEGYID = 'strategy_lickert';
    /**
     * Max NO.
     */
    const MAXNO = 'maxno';
    /**
     * Countlickert.
     */
    const COUNTLICKERT = 'countlickert';
    /** @var mixed $maxlickert */
    private $maxlickert;

    /**
     * Constructor.
     * @param array|null $strategysettings
     * @throws \coding_exception
     */
    public function __construct(?array $strategysettings = null) {
        parent::__construct($strategysettings);
        if (isset($strategysettings) && array_key_exists(self::COUNTLICKERT, $strategysettings)) {
            $this->maxlickert = $strategysettings[self::COUNTLICKERT];
        } else {
            $this->maxlickert = $this->get_default_settings()[self::COUNTLICKERT];
        }
    }

    /**
     * Get strategy id.
     * @return string
     */
    public function get_strategyid() {
        return self::STRATEGYID;
    }

    /**
     * Get static settingfields of strategy.
     * @return array[]
     * @throws \coding_exception
     */
    public function get_static_settingfields() {
        return [
                self::MAXNO => [// Maximum count of 'No'.
                        'int',
                        get_string(self::STRATEGYID . '_setting_maxno', RATINGALLOCATE_MOD_NAME),
                        $this->get_settings_value(self::MAXNO),
                        null,
                ],
                self::COUNTLICKERT => [// How many fields there are.
                        'int',
                        get_string(self::STRATEGYID . '_setting_maxlickert', RATINGALLOCATE_MOD_NAME),
                        $this->get_settings_value(self::COUNTLICKERT),
                        null,
                ],
        ];
    }

    /**
     * Get dynamic settingfields of strategy.
     * @return array|array[]
     * @throws \coding_exception
     */
    public function get_dynamic_settingfields() {
        $output = [];
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
     * Get choiceoptions.
     *
     * @return array
     */
    public function get_choiceoptions() {
        $options = [];
        for ($i = 0; $i <= $this->maxlickert; $i++) {
            $options[$i] = $this->get_settings_value($i);
        }
        return $options;
    }

    /**
     * Get default settings.
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_default_settings() {
        $defaults = [
                self::MAXNO => 3,
                self::COUNTLICKERT => 4,
                0 => get_string(self::STRATEGYID . '_rating_exclude', RATINGALLOCATE_MOD_NAME, "0"),
                'default' => $this->maxlickert,
        ];

        for ($i = 1; $i <= $this->maxlickert; $i++) {
            if ($i == $this->maxlickert) {
                $defaults[$i] = get_string(self::STRATEGYID . '_rating_biggestwish', RATINGALLOCATE_MOD_NAME, "$i");
            } else {
                $defaults[$i] = $i;
            }
        }
        return $defaults;
    }

    /**
     * Get validation info.
     *
     * @return array[]
     */
    protected function getvalidationinfo() {
        return [self::MAXNO => [true, 0],
                self::COUNTLICKERT => [true, 2],
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
class mod_ratingallocate_view_form extends \ratingallocate_options_strategyform {
    // Already specified by parent class.

    /**Get maximal amount how many times a user is allowed to rate a choice with "NO".
     * Create new strategy.
     * @param $strategyoptions
     * @return strategy
     * @throws \coding_exception
     */
    protected function construct_strategy($strategyoptions) {
        return new strategy($strategyoptions);
    }

    /**
     * Get choice options.
     * @return mixed
     */
    public function get_choiceoptions() {
        $params = $this->get_strategysetting(strategy::COUNTLICKERT);
        return $this->get_strategy()->get_choiceoptions($params);
    }

    /**
     *
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
