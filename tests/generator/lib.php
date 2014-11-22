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

require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * mod_dsbuilder generator tests
*
* @package    mod_ratingallocate
* @category   test
* @copyright  usener
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

use ratingallocate\db as this_db;

class mod_ratingallocate_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        $default_values = self::get_default_values();

        // set default values for unspecified attributes
        foreach ($default_values as $key => $value) {
            if (!isset($record[$key])) {
                $record[$key] = $value;
            }
        }
        return parent::create_instance($record, (array) $options);
    }

    public static function get_default_values() {
        if (empty(self::$_default_value)) {
            self::$_default_value = array(
                'name' => 'Rating Allocation',
                'accesstimestart' => time() + (0 * 24 * 60 * 60),
                'accesstimestop' => time() + (6 * 24 * 60 * 60),
                'publishdate' => time() + (7 * 24 * 60 * 60),
                'strategyopt' => array('strategy_yesno' => array('maxcrossout' => '1')),
                'strategy' => 'strategy_yesno',
                'choices' => array(
                    '-1' => array(
                        'title' => 'Choice 1',
                        'explanation' => 'Some explanatory text for choice 1',
                        'maxsize' => '10',
                        'active' => true
                    ),
                    '-2' => array(
                        'title' => 'Choice 2',
                        'explanation' => 'Some explanatory text for choice 2',
                        'maxsize' => '5',
                        'active' => false
                    )
                )
            );
        }
        return self::$_default_value;
    }
    private static $_default_value;

    /**
     * creates a user and enroles him into the given course as teacher or student
     * @param advanced_testcase $tc
     * @param unknown $course
     * @param string $is_teacher
     * @return stdClass
     */
    public static function create_user_and_enrol(advanced_testcase $tc, $course, $is_teacher = false) {
        $user = $tc->getDataGenerator()->create_user();

        if ($is_teacher) {
            global $DB;
            // enrole teacher and student
            $teacher_role = $DB->get_record('role',
                    array('shortname' => 'editingteacher'
                    ));
            $enroled = $tc->getDataGenerator()->enrol_user($user->id, $course->id,
                    $teacher_role->id);
        } else {
            $enroled = $tc->getDataGenerator()->enrol_user($user->id, $course->id);
        }
        $tc->assertTrue($enroled, 'trying to enrol already enroled user');
        return $user;
    }

    /**
     * login with given user and save his rating
     * @param advanced_testcase $tc
     * @param unknown $mod_ratingallocate
     * @param unknown $user
     * @param unknown $rating
     */
    public static function save_rating_for_user(advanced_testcase $tc, $mod_ratingallocate, $user,
            $rating) {
        $ratingallocate = self::get_ratingallocate_for_user($tc, $mod_ratingallocate, $user);
        $ratingallocate->save_ratings_to_db($user->id, $rating);
    }

    /**
     * login the given user and return ratingallocate object for him.
     *
     * @param advanced_testcase $tc
     * @param unknown $ratingallocate_db db object representing ratingallocate object
     * @param unknown $user
     * @return ratingallocate
     */
    public static function get_ratingallocate_for_user(advanced_testcase $tc, $ratingallocate_db,
            $user) {
        $tc->setUser($user);
        $cm = get_coursemodule_from_instance(ratingallocate_MOD_NAME,
                $ratingallocate_db->{this_db\ratingallocate::ID});
        $course = get_course($cm->course);
        $context = context_module::instance($cm->id);

        return new ratingallocate($ratingallocate_db, $course, $cm, $context);
    }
}
