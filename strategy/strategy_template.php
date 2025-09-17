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

use mod_ratingallocate\ratingallocate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Template for Strategies, which present the interface in which the user votes
 * @copyright 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_ratingallocate
 */
abstract class strategytemplate {
    /** STRATEGYID string identifier, for language translation, etc. */
    const STRATEGYID = '';

    /** @var array|null $_strategy_settings */
    private $_strategy_settings;

    /**
     * Construct.
     *
     * @param array|null $strategysettings
     */
    public function __construct(?array $strategysettings = null) {
        $this->_strategy_settings = $strategysettings;
    }

    /**
     * Retrieves the value of a settings field.
     *
     * @param int $key of the settings field.
     * @param bool $default whether to return the default.
     * @return either the value of the setting the strategy was initialized with or the default value of the setting.
     */
    protected function get_settings_value($key, $default = true) {
        if (
            isset($this->_strategy_settings) && array_key_exists($key, $this->_strategy_settings) &&
                $this->_strategy_settings[$key] !== ''
        ) {
            return $value = $this->_strategy_settings[$key];
        }
        return $default ? $this->get_settings_default_value($key) : null;
    }

    /**
     * Retrieves the default value of a settings field.
     * @param int $key of the settings field.
     * @return the default value of the setting.
     */
    protected function get_settings_default_value($key) {
        $value = null;
        if (array_key_exists($key, $this->get_default_settings())) {
            $value = $this->get_default_settings()[$key];
        }
        return $value;
    }

    /**
     * Defines default settings for the different fields of the strategy
     * @return array of key-value pairs of the settings
     */
    abstract public function get_default_settings();

    /**
     * Return the dynamic Settingsfields the strategy needes
     * If any dynamic Settingsfields is returned, a refresh button will be included in the view.
     * Return object:
     * array{
     * * Value[0]: Type of settingsfield (e.g. 'text', 'int', 'select')
     * * Value[1]: Label of the settingsfield
     * * Value[2]: Default value (may be null)
     * * Value[3]: Placeholder text in case of 'text' or 'int' and options in case of 'select' (may be null)
     * * Value[4]: String for the help_icon without _help suffix. (may be null)
     * }
     */
    abstract public function get_dynamic_settingfields();

    /**
     * Return the static Settingsfields the strategy needes
     * Return object:
     * array{
     * * Value[0]: Type of settingsfield (e.g. 'text', 'int')
     * * Value[1]: Label of the settingsfield
     * * Value[2]: Default value (may be null)
     * * Value[3]: Placeholder text (may be null)
     * * Value[3]: Placeholder text in case of 'text' or 'int' and options in case of 'select' (may be null)
     * * Value[4]: String for the help_icon without _help suffix. (may be null)
     * }
     */
    abstract public function get_static_settingfields();

    /**
     * Return the name of the strategy to be displayed
     */
    public function get_strategyname() {
        return get_string($this->get_strategyid() . '_name', RATINGALLOCATE_MOD_NAME);
    }

    /**
     * Get strategyid.
     *
     * @return mixed
     */
    abstract public function get_strategyid();

    /**
     * Searches for the given array of ratings, if a setting for its title is set.
     * If so, it returns the title with the ratings value as id.
     * If not, it returns the ratings value in both id and value of the array entry.
     * @param array $ratings
     * @return array of rating titles
     */
    public function translate_ratings_to_titles(array $ratings) {
        $result = [];
        foreach ($ratings as $id => $rating) {
            $result[$rating] = $this->translate_rating_to_titles($rating);
        }
        return $result;
    }

    /**
     * Searches for the given rating, if a setting for its title is set.
     * If so, it returns the title .
     * If not, it returns the ratings value.
     * @param mixed $rating
     * @return rating title
     */
    public function translate_rating_to_titles($rating) {
        $value = is_numeric($rating) ? $this->get_settings_value($rating) : null;
        $result = is_null($value) ? $rating : $value;
        return $result;
    }

    /**
     * Validates the current settings for requried fields or value restrictions
     * @return array of validation errors. Keys are the field identifiers and values
     * are the error messages, which should be displayed.
     */
    public function validate_settings() {
        $validationinfo = $this->getValidationInfo();
        $errors = [];
        foreach ($validationinfo as $key => $info) {
            if (isset($info[0]) && $info[0] === true) {
                if (
                    array_key_exists($key, $this->_strategy_settings) &&
                        (!isset($this->_strategy_settings[$key]) || $this->_strategy_settings[$key] === "")
                ) {
                    $errors[$key] = get_string('err_required', RATINGALLOCATE_MOD_NAME);
                    break;
                }
            }
            if (isset($info[1])) {
                if (array_key_exists($key, $this->_strategy_settings) && $this->_strategy_settings[$key] < $info[1]) {
                    $errors[$key] = get_string('err_minimum', RATINGALLOCATE_MOD_NAME, $info[1]);
                    break;
                }
            }
            if (isset($info[2])) {
                if (array_key_exists($key, $this->_strategy_settings) && $this->_strategy_settings[$key] > $info[1]) {
                    $errors[$key] = get_string('err_maximum', RATINGALLOCATE_MOD_NAME, $info[2]);
                    break;
                }
            }
        }
        return $errors;
    }

    /**
     * Get validation info.
     *
     * @return array of arrays:     key - identifier of setting_dependenc
     *                              value[0] - is setting required
     *                              value[1] - min value of setting (if numeric)
     *                              value[2] - max value of setting (if numeric)
     */
    abstract protected function getvalidationinfo();
}

/**
 * Form that asks users to express their ratings for choices
 * @copyright 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_ratingallocate
 */
abstract class ratingallocate_strategyform extends \moodleform {
    /** @var ratingallocate pointer to the parent ratingallocate object */
    protected $ratingallocate;

    /** @var array|mixed $strategyoptions */
    private $strategyoptions;

    /** @var strategytemplate $strategy */
    private $strategy;

    /**
     *
     * @param string $url The page url
     * @param ratingallocate $ratingallocate The calling ratingallocate instance
     */
    public function __construct($url, ratingallocate $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        // Load strategy options.
        $allstrategyoptions = json_decode($this->ratingallocate->ratingallocate->setting, true);
        $strategyid = $ratingallocate->ratingallocate->strategy;
        if (array_key_exists($strategyid, $allstrategyoptions)) {
            $this->strategyoptions = $allstrategyoptions[$strategyid];
        } else {
            $this->strategyoptions = [];
        }
        $this->strategy = $this->construct_strategy($this->strategyoptions);
        parent::__construct($url);
    }

    /**
     * This method creates an instance of the strategy class for the form
     *
     * @param array $strategyoptions
     * @return \strategytemplate Returns a strategy class.
     */
    abstract protected function construct_strategy($strategyoptions);

    /**
     * Get strategy.
     *
     * @return \strategytemplate Returns the underlying strategy object.
     */
    protected function get_strategy() {
        return $this->strategy;
    }

    /**
     * inherited from moodleform: a child class must call parent::definition() first to execute
     * ratingallocate_strategyform::definition
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', ACTION_GIVE_RATING);
        $mform->setType('action', PARAM_TEXT);
    }

    /**
     * Erkläre, was die Strategie soll und welchen Restriktionen (Optionen) eine
     * valide Antwort unterliegt
     */
    abstract public function describe_strategy();

    /**
     * Get strategy description.
     * @return lang_string|string
     * @throws coding_exception
     */
    public function get_strategy_description_header() {
        return get_string('strategyname', RATINGALLOCATE_MOD_NAME, $this->get_strategyname());
    }

    /**
     * Returns the forms HTML code.
     * So we don't have to call display().
     */
    public function to_html() {
        /* usually $mform->display() is called which echos the form instead of returning it */
        $o = '';
        $o .= $this->_form->getValidationScript();
        $o .= $this->_form->toHtml();
        return $o;
    }

    /**
     * Get strategy name.
     * @return lang_string|string
     * @throws coding_exception
     */
    protected function get_strategyname() {
        return get_string($this->ratingallocate->ratingallocate->strategy . '_name', RATINGALLOCATE_MOD_NAME);
    }

    /**
     * returns strategy specific option for a strategy
     * @param string $key
     * @return the specific option or null if it does not exist
     */
    protected function get_strategysetting($key) {
        if (array_key_exists($key, $this->strategyoptions)) {
            return $this->strategyoptions[$key];
        }
        return null;
    }
}
