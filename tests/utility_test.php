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

defined('MOODLE_INTERNAL') || die();

class utility_test extends basic_testcase {

    /**
     * @covers \mod_ratingallocate\local\utility::transform_to_groups
     */
    public static function test_to_group_transformation() {
        $choices = [];

        $choices[1] = new stdClass();
        $choices[1]->maxsize = 2;
        $choices[1]->id = 1;

        $choices[2] = new stdClass();
        $choices[2]->maxsize = 2;
        $choices[2]->id = 2;

        $groups = \mod_ratingallocate\local\utility::transform_to_groups($choices);

        foreach($groups as $group)
            self::assertEquals($choices[$group->get_id()]->maxsize, $group->get_limit());
    }

    /**
     * @covers \mod_ratingallocate\local\utility::transform_to_users
     */
    public static function test_to_user_transformation() {
        $ratings = [];

        $ratings = [];
        $ratings[1] = new stdClass();
        $ratings[1]->userid = 1;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 5;

        $users = \mod_ratingallocate\local\utility::transform_to_users($ratings);

        foreach($ratings as $rating)
            self::assertEquals($rating->userid, $users[$rating->userid]->get_id());
    }

    /**
     * @depends test_to_user_transformation
     * @depends test_to_group_transformation
     * @covers \mod_ratingallocate\local\utility::transform_to_users_and_groups
     */
    public static function test_to_user_and_group_transformation() {
        $choices = [];

        $choices[1] = new stdClass();
        $choices[1]->maxsize = 2;
        $choices[1]->id = 1;

        $choices[2] = new stdClass();
        $choices[2]->maxsize = 2;
        $choices[2]->id = 2;

        $ratings = [];

        $ratings[1] = new stdClass();
        $ratings[1]->userid = 2;
        $ratings[1]->choiceid = 1;
        $ratings[1]->rating = 5;

        $ratings[2] = new stdClass();
        $ratings[2]->userid = 1;
        $ratings[2]->choiceid = 2;
        $ratings[2]->rating = 5;

        list($users, $groups) = \mod_ratingallocate\local\utility::transform_to_users_and_groups($choices, $ratings);

        foreach($users as $user)
            foreach($user->get_selected_groups() as $group)
                self::assertEquals($group->get_id(), $ratings[$group->get_id()]->choiceid);
    }

    /**
     * @covers \mod_ratingallocate\local\utility::transform_from_users_and_groups
     */
    public static function test_from_transformation() {
        $users = [];
        $groups = [];

        for($i = 0; $i < 2; ++$i)
            $groups[] = new \mod_ratingallocate\local\group($i);

        for($i = 0; $i < 4; ++$i) {
            $users[] = new \mod_ratingallocate\local\user($i, $groups);
            $users[$i]->set_assigned_group($groups[rand() % count($groups)]);
        }

        $allocations = \mod_ratingallocate\local\utility::transform_from_users_and_groups($users, $groups);

        foreach($allocations as $i_key => $i) {
            foreach($i as $k) {
                self::assertSame($users[$k]->get_assigned_group(), $groups[$i_key]);
            }
        }
    }

}