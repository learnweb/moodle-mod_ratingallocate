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

class mod_ratingallocate_utility_test extends basic_testcase {

    /**
     * @covers \mod_ratingallocate\local\utility::transform_to_users_and_groups
     */
    public static function test_to_transformation() {
        $choices = [];
        $ratings = [];

        list($users, $groups) = \mod_ratingallocate\local\utility::transform_to_users_and_groups($choices, $ratings);
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