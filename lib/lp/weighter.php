<?php

namespace ratingallocate\lp;

/**
 * Abstract class which defines abstract methods for weighter 
 */
abstract class weighter
{
    
    /**
     * Returns an array of available weighters
     * 
     * @return Array with names of available weighters
     */
    public static function get_weighters() {
    	$weighters = array_diff(scandir(dirname(__FILE__).'/weighters'), array('.', '..'));
    	
    	foreach($weighters as &$weighter)
			$weighter = str_ireplace('.php', '', $weighter);
    	
    	return array_values($weighters); 
    }

    /**
     * Applys a concrete value for x
     *
     * @param $x Value for x
     *
     * @return Function value for x
     */
    abstract public function apply($x);
    
    /**
     * Returns the functional representation as a string
     *
     * @param $variable_name The name of the variable
     *
     * @return Functional representation as a string
     */
    abstract public function to_string($variable_name = 'x');

};