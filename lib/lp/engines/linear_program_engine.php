<?php

namespace ratingallocate\lp\engines;

abstract class linear_program_engine extends \ratingallocate\lp\engine {
    
    /**
     * Translates a user and a group object to a name
     *
     * @param $user User object
     * @param $group Group object
     *
     * @return Name
     */
    private function translate_to_name($user, $group) {
        return 'x_'.$user->get_id().'_'.$group->get_id(); 
    }
    
    /**
     * Translate a name to a user and a group object
     *
     * @param $name Name which gets translated
     * @param $users Array of users
     * @param $groups Array of groups
     *
     * @return Array containing translated user as the first element and translated group as the second 
     */
    private function translate_from_name($name, $users, $groups) {
        $explode = explode('_', $name);        
        return [$users[$explode[1]], $groups[$explode[2]]];
    }

    /**
     * Adds the objective function to the linear program
     *
     * @param $linear_program Linear program the objective function is added to
     * @param $users Array of users
     * @param $groups Array of groups
     * @param $weighter Weighter object for the weighting process 
     */
    private function add_objective_function(&$linear_program, $users, $groups, $weighter) {
        $objective_function = '';
        
        foreach($users as $user) {
            foreach($groups as $group) {
                if(!empty($objective_function))
                    $objective_function .= '+';
                
                $weighting = $weighter->apply($user->get_priority($group));
                
                if($weighting == 1)
                    $objective_function .= $this->translate_to_name($user, $group);
                else if($weighting != 0)
                    $objective_function .= $weighting.'*'.$this->translate_to_name($user, $group);
            }
        }
            
        $linear_program->set_objective(\distributor\LinearProgram::MAXIMIZE, $objective_function);
    }

    /**
     * Adds constraints to the linear program
     *
     * @param $linear_program Linear program the constraints are added to
     * @param $users Array of users
     * @param $groups Array of groups
     */
    private function add_constraints(&$linear_program, $users, $groups) {
        foreach($groups as $group) {
            $lhs = '';
            
            foreach($users as $user) {
                if(!empty($lhs))
                    $lhs .= '+';
                
                $lhs .= $this->translate_to_name($user, $group); 
            }
            
            $linear_program->add_constraint("$lhs <= {$group->get_limit()}");
        }
    }
    
    /**
     * Adds bounds to the linear program
     *
     * @param $linear_program Linear program the bounds are added to
     * @param $users Array of users
     * @param $groups Array of groups
     */
    private function add_bounds(&$linear_program, $users, $groups) {
        foreach($users as $user)
            foreach($groups as $group)
                $linear_program->add_bound('0 <= '.$this->translate_to_name($user, $group));
         
        foreach($users as $user) {
            $lhs = '';

            foreach($groups as $group) {
                if(!empty($lhs))
                    $lhs .= '+';
                
                $lhs .= $this->translate_to_name($user, $group);
            }
            
            $linear_program->add_constraint("$lhs = 1");
        }        
    }

    /**
     * Adds variables to the linear program
     *
     * @param $linear_program Linear program the bounds are added to
     * @param $users Array of users
     * @param $groups Array of groups
     */
    private function add_variables(&$linear_program, $users, $groups) {
        foreach($users as $user)
            foreach($groups as $group)
                $linear_program->add_variable($this->translate_to_name($user, $group));
    }

    /**
     * Creates a fully configured linear program
     *
     * @param $groups Array of groups
     * @param $users Array of $users
     * @param $weighter Weighter object
     * 
     * @return Fully configured linear program 
     */
    private function create_linear_program(&$users, &$groups, $weighter) {
        $linear_program = new \ratingallocate\lp\linear_program();

        $this->add_objective_function($linear_program, $users, $groups, $weighter);
        $this->add_constraints($linear_program, $users, $groups);
        $this->add_bounds($linear_program, $users, $groups);
        $this->add_variables($linear_program, $users, $groups);

        return $linear_program;
    }

    /**
     * Assigns a group determined by $solution to each user
     * 
     * @param $solution Array of solutions
     * @param $users Array of users
     * @param $groups Array of groups
     */
    private function assign_groups($solution, &$users, &$groups) {
        foreach($solution as $key => $value) {
            if($value) {
                list($user, $group) = $this->translate_from_name($key, $users, $groups);
                $user->set_assigned_group($group);
            }
        }
    }
     
    /**
     * Fetchs the engines output stream
     *
     * @param $temp_file Temp file handle
     *
     * @return Stream of engines output stream
     */
    private function fetch_stream($temp_file) { 
        if($this->get_configuration()['SSH']) {
            $authentication = new \ratingallocate\ssh\password_authentication($this->get_configuration()['SSH']['USERNAME'], $this->get_configuration()['SSH']['PASSWORD']);
            $connection = new \ratingallocate\ssh\connection($this->get_configuration()['SSH']['HOSTNAME'], $this->get_configuration()['SSH']['FINGERPRINT'], $authentication);
            
            $connection->send_file(stream_get_meta_data($temp_file)['uri'], $this->get_configuration()['SSH']['REMOTE_FILE']);

            return $connection->execute($this->execute($this->get_configuration()['SSH']['REMOTE_FILE']));
        }

        throw \Exception('TODO');
        
        return '';
    }
    
    /**
     * Creates a linear program engine object
     *
     * @param $name Name of the linear program engine
     * @param $description Description of the linear program engine
     * @param $configuration Array of configuration directives
     *
     * @return Lienar program engine object
     */
    public function __construct($name = '', $description = '', $configuration = []) {
        parent::__construct($name, $description, $configuration);
    }

    /**
     * Writes the linear program represented by $linear_program as a string
     *
     * @param $linear_program Object which represents a linear program
     *
     * @return String representation of the linear program
     */
    protected function write($linear_program) {
        return $linear_program->write();
    }

    /**
     * Reads the content of the stream and returns the variables and their optimized values
     *
     * @param $stream Output stream of the program that was executed
     *
     * @return Array of variables and their values
     */
    abstract protected function read($stream);

    /**
     * Returns the command that gets executed
     *
     * @param $input_file Name of the input file
     *
     * @returns Command as a string 
     */
    abstract protected function execute($input_file);
                                
    /**
     * Solves the problem
     *
     * @param $users Array of users
     * @param $groups Array of groups
     * @param $weighter Weighter object for the weighting process
     */
    public function solve(&$users, &$groups, $weighter) {
        $linear_program = $this->create_linear_program($users, $groups, $weighter);
        
        $temp_file = tmpfile();
        fwrite($temp_file, $this->write($linear_program));
        fseek($temp_file, 0);

        $this->assign_groups($this->read($this->fetch_stream($temp_file), $users, $groups), $users, $groups);
        fclose($temp_file);
    }

}