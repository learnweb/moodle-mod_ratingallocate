<?php

namespace ratingallocate;

class user {
    
	private $id = -1;
	private $chosen_groups = [];
    private $assigned_group = null;

    /**
     * Creates a user
     *
     * @param $id id of the user
     * @param $chosen_groups Chosen groups of the user
     */
	public function __construct($id, $chosen_groups = []) {
		$this->id = $id;
        $this->set_chosen_groups($chosen_groups);
	}
	
    /**
     * Returns the id of the user
     *
     * @return Id of the user
     */
	public function get_id() {
		return $this->id;
	}

    /**
     * Sets chosen groups of user
     *
     * @param $chosen_groups Array of chosen groups
     */
    public function set_chosen_groups($chosen_groups) {
        foreach($chosen_groups as & $group)
            $this->add_chosen_group($group);
    }

    /**
     * Returns chosen groups
     *
     * @return Array of chosen groups 
     */
	public function get_chosen_groups() {
		return $this->chosen_groups;
	}

    /**
     * Adds a group choice
     *
     * @param $group Groups that gets added to chosen groups
     *
     * @throws Exception If group has been already chosen by the user
     */
    public function add_chosen_group(&$group) {
        if($this->exists_chosen_group($group))
            throw new \Exception('Group has been already chosen by the user!');

        $this->chosen_groups[$group->get_id()] = & $group;
    }

    /**
     * Checks if a group was chosen by the user
     *
     * @return True of group was chosen
     */
    public function exists_chosen_group($group) {
        return isset($this->chosen_groups[$group->get_id()]);
    }

    /**
     * Removes a choice from chosen groups
     *
     * @param $group group that gets removed from chosen groups
     *
     * @throws Exception If group has not been chosen by the user
     */
    public function remove_chosen_group($group) {
        if(!$this->exists_chosen_group($group))
            throw new \Exception('Group has not been chosen by the user!');

        unset($this->chosen_groups[$group->get_id()]);
    }

    /**
     * Returns the priority for the given group, which is 0 if the group was not chosen by the user
     *
     * @param $group Group
     *
     * @return Priority for the given group
     */
    public function get_priority($group) {
        $return = array_search($group->get_id(), array_keys($this->chosen_groups));
        return $return === false ? 0 : $return + 1;
    }
    
    /**
     * Returns the assigned group
     *
     * @return Assigned group(null for none)
     */
    public function get_assigned_group() {
        return $this->assigned_group;
    }

    /**
     * Assigns a group to the user, removing the user from the current assigned group and adding
     * the user to the newly assigned group
     *
     * @param $group Group that the user gets added to
     */
    public function set_assigned_group(&$group) {
        if($this->assigned_group)
            $this->assigned_group->remove_assigned_user($this);  

        $this->assigned_group = null;
    
        if($group) {
            $group->add_assigned_user($this);
            $this->assigned_group = &$group;
        }
    }

    /**
     * Checks if assigned group is an element of the chosen groups and therefor if the choice is satisfied by
     * the assigned group
     *
     * @return True is the choice is satisfied
     */
    public function is_choice_satisfied() {
        return (!$this->assigned_group ? false : $this->exists_chosen_group($this->assigned_group));
    }

    /**
     * Returns the choice satisfactions, representing the satisfaction of the assigned group  
     *
     * @return 0 for no satisfaction, otherwise a value which represents the satisfaction (1 for best satisfaction)
     */
    public function get_choice_satisfaction() {
        if(!$this->assigned_group)
            return 0;
        
        return array_search($this->assigned_group->get_id(), array_keys($this->chosen_groups)) + 1;
    }

}