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

namespace ratingallocate\lp;

class utility
{
    
    /**
     * Translates a user and a group object to a name
     *
     * @param $user User object
     * @param $group Group object
     *
     * @return Name
     */
    public static function translate_to_name($user, $group) {
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
    public static function translate_from_name($name, $users, $groups) {
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
    public static function add_objective_function(&$linear_program, $users, $groups, $weighter) {
        $objective_function = '';
        
        foreach($users as $user) {
            foreach($groups as $group) {
                if(!empty($objective_function))
                    $objective_function .= '+';
                
                $weighting = $weighter->apply($user->get_priority($group));
                
                if($weighting == 1)
                    $objective_function .= self::translate_to_name($user, $group);
                else if($weighting != 0)
                    $objective_function .= $weighting.'*'.self::translate_to_name($user, $group);
            }
        }
            
        $linear_program->set_objective(\ratingallocate\lp\linear_program::MAXIMIZE, $objective_function);
    }

    /**
     * Adds constraints to the linear program
     *
     * @param $linear_program Linear program the constraints are added to
     * @param $users Array of users
     * @param $groups Array of groups
     */
    public static function add_constraints(&$linear_program, $users, $groups) {
        foreach($groups as $group) {
            $lhs = '';
            
            foreach($users as $user) {
                if(!empty($lhs))
                    $lhs .= '+';
                
                $lhs .= self::translate_to_name($user, $group); 
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
    public static function add_bounds(&$linear_program, $users, $groups) {
        foreach($users as $user)
            foreach($groups as $group)
                $linear_program->add_bound('0 <= '.self::translate_to_name($user, $group));
        
        foreach($users as $user) {
            $lhs = '';

            foreach($groups as $group) {
                if(!empty($lhs))
                    $lhs .= '+';
                
                $lhs .= self::translate_to_name($user, $group);
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
    public static function add_variables(&$linear_program, $users, $groups) {
        foreach($users as $user)
            foreach($groups as $group)
                $linear_program->add_variable(self::translate_to_name($user, $group));
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
    public static function create_linear_program(&$users, &$groups, $weighter) {
        $linear_program = new \ratingallocate\lp\linear_program();

        self::add_objective_function($linear_program, $users, $groups, $weighter);
        self::add_constraints($linear_program, $users, $groups);
        self::add_bounds($linear_program, $users, $groups);
        self::add_variables($linear_program, $users, $groups);

        return $linear_program;
    }

    /**
     * Assigns a group determined by $solution to each user
     * 
     * @param $solution Array of solutions
     * @param $users Array of users
     * @param $groups Array of groups
     */
    public static function assign_groups($solution, &$users, &$groups) {
        foreach($solution as $key => $value) {
            if($value) {
                list($user, $group) = self::translate_from_name($key, $users, $groups);
                $user->set_assigned_group($group);
            }
        }
    }
}