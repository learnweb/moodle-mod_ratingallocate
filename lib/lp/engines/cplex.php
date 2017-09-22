<?php

namespace ratingallocate\lp\engines;

class cplex extends linear_program_engine {

    /**
     * Returns the command that gets executed
     *
     * @param $input_file Name of the input file
     *
     * @returns Command as a string 
     */
    protected function execute($input_file) {
        return "cplex -c \"read $input_file\" \"optimize\" \"display solution variables -\"";
    }

    /**
     * Reads the content of the stream and returns the variables and their optimized values
     *
     * @param $stream Output stream of the program that was executed
     *
     * @return Array of variables and their values
     */
    protected function read($stream) {
        $content = stream_get_contents($stream);
        $solution = [];

        foreach(array_slice(explode("\n", substr($content, strpos($content, "Solution Value"))), 1) as $variable) {
            $parts = explode(' ', preg_replace('!\s+!', ' ', $variable));
            
            if(count($parts) > 2)
                break;
           
            $solution[$parts[0]] = intval($parts[1]);
        }
        
        return $solution;
    }

    /**
     * Creates a new CPLEX engine object
     *
     * @param $configuration Array of configuration directives
     *
     * @return CPLEX engine object
     */
    public function __construct($configuration = []) {
        parent::__construct('', '', $configuration);
    }

}