<?php

namespace ratingallocate\lp\engines;

class scip extends linear_program_engine
{
    
    /**
     * Returns the command that gets executed
     *
     * @param $input_file Name of the input file
     *
     * @returns Command as a string 
     */
    protected function execute($input_file) {
        return "scip -f $input_file";
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
        
        foreach(array_slice(explode("\n", substr($content, strpos($content, 'objective value:'))), 1) as $variable) {
            $parts = explode(' ', preg_replace('!\s+!', ' ', $variable));

            if(empty($parts[0]))
                break;
            
            $solution[$parts[0]] = $parts[1];
        }
        
        return $solution;
    }

    /**
     * Creates a new SCIP engine object
     *
     * @param $configuration Array of configuration directives
     *
     * @return SCIP engine object
     */
    public function __construct($configuration = []) {
        parent::__construct('', '', $configuration);
    }

}