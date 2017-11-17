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

namespace mod_ratingallocate\local;

class user {

    private $id = -1;
    private $selected_groups = [];
    private $assigned_group = null;

    /**
     * Creates a user
     *
     * @param $id id of the user
     * @param $selected_groups Selected groups of the user
     */
    public function __construct($id, $selected_groups = []) {
        $this->id = $id;
        $this->set_selected_groups($selected_groups);
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
     * Sets selected groups of user
     *
     * @param $selected_groups Array of selected groups
     */
    public function set_selected_groups($selected_groups) {
        foreach($selected_groups as $value)
            $this->add_selected_group($value['group'], $value['priority']);
    }

    /**
     * Returns selected groups
     *
     * @return Array of selected groups
     */
    public function get_selected_groups() {
        return $this->selected_groups;
    }

    /**
     * Adds a group choice
     *
     * @param $group Group that is selected by the user
     * @param $priority Selections priority
     *
     * @throws exception If group has already been selected by the user
     */
    public function add_selected_group(&$group, $priority = 1) {
        if($this->exists_selected_group($group))
            throw new \exception('Group has already been selected by the user!');

        $this->selected_groups[$group->get_id()] = ['group' => &$group, 'priority' => 0];

        try {
            $this->set_priority($group, $priority);
        }
        catch(exception $e) {
            $this->remove_selected_group($group);
            throw $e;
        }
    }

    /**
     * Checks if a group was selected by the user
     *
     * @return True of group was selected
     */
    public function exists_selected_group($group) {
        return isset($this->selected_groups[$group->get_id()]);
    }

    /**
     * Removes a choice from selected groups
     *
     * @param $group group that gets removed from selected groups
     *
     * @throws exception If group has not been selected by the user
     */
    public function remove_selected_group($group) {
        if(!$this->exists_selected_group($group))
            throw new \exception('Group has not been selected by the user!');

        unset($this->selected_groups[$group->get_id()]);
    }

    /**
     * Sets the priority for the given group
     *
     * @param $group Group whichs priority get changed
     * @param $priority New priority
     *
     * @throws exception If group has not been selected by the user
     * @throws exception If priority is not numeric
     * @throws exception If priority is less than zero
     * @throws exception If priority is zero
     */
    public function set_priority($group, $priority) {
        if(!$this->exists_selected_group($group))
            throw new \exception('Group has not been selected by the user!');

        if(!is_numeric($priority))
            throw new \exception('Priority is not numeric!');

        if($priority < 0)
            throw new \exception('Priority is not positive!');

        if($priority == 0)
            throw new \exception('Cannot set priority to zero!');

        $this->selected_groups[$group->get_id()]['priority'] = $priority;
    }

    /**
     * Returns the priority for the given group, which is 0 if the group was not selected by the user
     *
     * @param $group Group
     *
     * @return Priority for the given group
     */
    public function get_priority($group) {
        if(!$this->exists_selected_group($group))
            return 0;

        return $this->selected_groups[$group->get_id()]['priority'];
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
            $this->assigned_group = $group;
        }
    }

    /**
     * Checks if assigned group is an element of the selected groups and therefor if the choice is satisfied by
     * the assigned group
     *
     * @return True If the choice is satisfied
     */
    public function is_choice_satisfied() {
        return (!$this->assigned_group ? false : $this->exists_selected_group($this->assigned_group));
    }

    /**
     * Returns the choice satisfaction, representing the satisfaction with the assigned group
     *
     * @return Between 0 and 1, representing how satisfying the assigned group is
     */
    public function get_choice_satisfaction() {
        if(!$this->is_choice_satisfied())
            return 0;

        return $this->get_priority($this->assigned_group) / max(array_map(function($x) { return $x['priority']; }, $this->selected_groups));
    }

}