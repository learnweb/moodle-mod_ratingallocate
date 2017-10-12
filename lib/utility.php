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

namespace ratingallocate;

class utility {

    public static function transform_from_users_and_groups($users, $groups) {
        $allocation = array_map(function($x, $y) { return [$x => $y]; }, array_keys($groups), array_pad([], count($groups), []));

        foreach($users as $user)
            if($user->is_choice_satisfied())
                $allocation[$user->get_assigned_group()->get_id()] = $user->get_id();

        return $allocation;
    }
    
    public static function transform_to_users_and_groups($choices, $ratings) {
        $groups = self::transform_to_groups($choices);
        $users = self::transform_to_users($ratings);

        self::transform_user_selection($ratings, $users, $groups);
        
        return [&$users, &$groups];
    }
    
    public static function transform_to_users($ratings) {
        $users = [];
        
        foreach(array_unique(array_map(function($x) { return $x->userid; }, $ratings)) as $id)
            $users[$id] = new \ratingallocate\user($id);
        
        return $users;
    }

    public static function transform_to_groups($choices) {
        $groups = [];
    
        foreach($choices as $choice)
            if($choice->active)
                $groups[$choice->id] = new \ratingallocate\group($choice->id, $choice->maxsize);
        
        return $groups;
    }
    
    public static function transform_user_selection($ratings, &$users, &$groups) {
        foreach($ratings as $rating)
            if($rating->rating > 0)
                $users[$rating->userid]->add_selected_group($groups[$rating->choiceid], $rating->rating);
    }

    public static function starts_with($string, $word) {
        return substr($string, 0, strlen($word)) === $word;
    }
    
    public static function get_configuration($directives) {
        $configuration = [];

        foreach($directives as $key => $value)
            if(self::starts_with($key, 'ratingallocate_'))
                $configuration[str_replace('ratingallocate_', '', $key)] = $value;
        
        return $configuration;
    }

};