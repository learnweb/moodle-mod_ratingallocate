<?php

namespace ratingallocate\lp;

abstract class engine {
    
    private $name = '';
    private $description = '';
    private $configuration = [];
    
    /**
     * Returns an array of available engines
     *
     * @param $abstract Whether abstract engines should be included or not
     * 
     * @return Array with names of available engines
     */
    public static function get_engines($abstract = false) {
        throw new \exception('TODO');
    }

    /**
     * Creates a new engine instance
     *
     * @param $name Name of the engine
     * @param $description Description of the engine
     * @param $configuration Array of configuration directives     
     */
    public function __construct($name = '', $description = '', $configuration = []) {
        if(empty($name)) {
    		$segments = explode("\\", get_class($this));
    		$name = $segments[count($segments) - 1];
    	}
    		
        $this->name = $name;
        $this->description = $description;
        $this->configuration = $configuration;
    }

    /**
     * Returns the name of the engine
     *
     * @return Name of the engine
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Returns the description of the engine
     * 
     * @return Description of the engine
     */
    public function get_description() {
    	return $this->description;
    }

    /**
     * Returns the configuration directives of the engine
     *
     * @return Configuration directives
     */
    public function get_configuration() {
        return $this->configuration;
    }
    
    /**
     * Runs the distribution
     *
     * @param $users Array of users
     * @param $groups Array of groups
     * @param $weighter Weighter instace
     */
    abstract public function solve(&$users, &$groups, $weighter);

};