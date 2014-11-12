<?php
use Symfony\Component\Validator\Constraints\Optional;
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
require_once ($CFG->libdir.'/formslib.php');
/**
 * Template for Strategies, which present the interface in which the user votes
 * @copyright 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class strategytemplate {

    /** @const STRATEGYID string identifier, for language translation, etc.*/
    const STRATEGYID = '';
        
    private $_strategy_settings;
    
    public function __construct(array $strategy_settings = null){
        $this->_strategy_settings = $strategy_settings;
    }
    
    /**
     * Retrieves the value of a settings field.
     * @param $key of the settings field\
     * @return either the value of the setting the strategy was initialized with or the default value of the setting.
     */
    protected function get_settings_value($key){
        if (isset($this->_strategy_settings) && array_key_exists($key, $this->_strategy_settings)) {
            return $value = $this->_strategy_settings[$key];
        }
        return $this->get_settings_default_value($key);
    }
    
    /**
     * Retrieves the default value of a settings field.
     * @param $key of the settings field\
     * @return the default value of the setting.
     */
    protected function get_settings_default_value($key){
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
    public abstract function get_default_settings();
    
    /**
     * Return the dynamic Settingsfields the strategy needes
     * If any dynamic Settingsfields is returned, a refresh button will be included in the view.
     * @param $mform The required data can be drawn from the moodleform
     */
    public abstract function get_dynamic_settingfields();

    /**
     * Return the static Settingsfields the strategy needes
     */
    public abstract function get_static_settingfields();

    /**
     * Return the name of the strategy to be displayed
     */
    public function get_strategyname() {
        return get_string($this->get_strategyid().'_name',ratingallocate_MOD_NAME);
    }

    public abstract function get_strategyid();
}


/**
 * Form that asks users to express their ratings for choices
 * @copyright 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ratingallocate_strategyform extends \moodleform  {
    /** @var \ratingallocate pointer to the parent \ratingallocate object*/
    protected $ratingallocate;

    private $strategyoptions;
    
    private $strategy;

    /**
     *
     * @param string $url The page url
     * @param \ratingallocate $ratingallocate The calling ratingallocate instance
     */
    public function __construct($url, \ratingallocate $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        //load strategy options
        $allstrategyoptions = json_decode($this->ratingallocate->ratingallocate->setting, true);
        $strategyid = $ratingallocate->ratingallocate->strategy;
        if(array_key_exists($strategyid, $allstrategyoptions)) {
            $this->strategyoptions = $allstrategyoptions[$strategyid];
        } else {
            $this->strategyoptions = array();
        }
        $this->strategy=$this->construct_strategy($this->strategyoptions);
        parent::__construct($url);
    }
    
    /**
     * This method creates an instance of the strategy class for the form
     * @return \strategytemplate
     */
    protected abstract function construct_strategy($strategyoptions);
    
    /**
     * @return \strategytemplate Returns the underlying strategy object.
     */
    protected function get_strategy(){
        return $this->strategy;
    }

    /**
     * inherited from moodleform: a child class must call parent::definition() first to execute
     * ratingallocate_strategyform::definition
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', RATING_ALLOC_ACTION_RATE);
        $mform->setType('action', PARAM_TEXT);
    }

    /**
     * Erkläre, was die Strategie soll und welchen Restriktionen (Optionen) eine
     * valide Antwort unterliegt
     */
    public abstract function describe_strategy();

    public function get_strategy_description_header() {
        return get_string('strategyname', ratingallocate_MOD_NAME, $this->get_strategyname());
    }

    /**
     * Returns the forms HTML code.
     * So we don't have to call display().
     */
    public function to_html() {
        /* usually $mform->display() is called which echos the form instead of returning it */
        $o = '';
        $this->definition_after_data();
        $o .= $this->_form->getValidationScript();
        $o .= $this->_form->toHtml();
        return $o;
    }

    protected function get_strategyname() {
        return get_string($this->ratingallocate->ratingallocate->strategy.'_name',ratingallocate_MOD_NAME);
    }

    /**
     * returns strategy specific option for a strategy
     * @param string $key
     * @returns the specific option or null if it does not exist
     */
    protected function get_strategysetting($key) {
        if(array_key_exists($key, $this->strategyoptions))  {
            return $this->strategyoptions[$key];
        }
        return null;
    }
}