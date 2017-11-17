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

class group {

    private $id = '';
    private $limit = 0;
    private $assigned_users = [];

    /**
     * Creates a new group
     *
     * @param $id Id of the group
     * @param $limit Group limit
     */
    public function __construct($id, $limit = 0) {
        $this->id = $id;
        $this->set_limit($limit);
    }

    /**
     * Returns the group id
     *
     * @return Id of the group
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Checks if the group has a limit (limit is zero)
     *
     * @return True if the group has a limit
     */
    public function has_limit() {
        return $this->limit != 0;
    }

    /**
     * Returns the group limit
     *
     * @return Group limit
     */

    public function get_limit() {
        return $this->limit;
    }

    /**
     * Checks if the group is empty
     *
     * @return True if the group is empty
     */
    public function is_empty() {
        return empty($this->assigned_users);
    }

    /**
     * Checks if the group is full (limit has been reached)
     *
     * @return True if the group is full
     */
    public function is_full() {
        if($this->limit == 0)
            return false;

        return count($this->assigned_users) == $this->limit;
    }

    /**
     * Sets the group limit (zero for no limit)
     *
     * @param $limit The new group limit
     * @throws exception if the group limit is negative
     */
    public function set_limit($limit) {
        if($limit < 0)
            throw new exception('Limit cannot be negative!');

        $this->limit = $limit;
    }

    /**
     * Returns an array of users which are assigned to the group
     *
     * @return Array of users which are assigned to the group
     */
    public function get_assigned_users() {
        return $this->assigned_users;
    }

    /**
     * Adds an assigned user to the group
     *
     * @param $user User that gets added to the group
     *
     * @throws exception if the group limit has been reached or the user has been already assigned a group
     */
    public function add_assigned_user(&$user) {
        if($this->is_full())
            throw new \exception('Limit has been reached!');

        if($this->exists_assigned_user($user) || $user->get_assigned_group() == $this)
            throw new \exception('User has been already assigned to this group!');

        if($user->get_assigned_group() != null)
            throw new \exception('User has been already assigned to another group!');

        $this->assigned_users[$user->get_id()] = $user;
    }

    /**
     * Checks if user belongs to this group
     *
     * @param $user User that gets checked
     *
     * @return True if user belongs to this group
     */
    public function exists_assigned_user($user) {
        return isset($this->assigned_users[$user->get_id()]);
    }

    /**
     * Removes a user from the group
     *
     * @param $user User that gets removed from the group
     *
     * @throws exception If user was not assigned from the group
     */
    public function remove_assigned_user($user) {
        if(!$this->exists_assigned_user($user))
            throw new \exception('User has not been assigned to this group!');

        unset($this->assigned_users[$user->get_id()]);
    }

}