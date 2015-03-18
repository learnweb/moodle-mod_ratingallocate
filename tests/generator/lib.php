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
                'choices_-1_title' => 'Choice 1',
                'choices_-1_explanation' => 'Some explanatory text for choice 1',
                'choices_-1_maxsize' => '10',
                'choices_-1_active' => true,
                'choices_-2_title' => 'Choice 2',
                'choices_-2_explanation' => 'Some explanatory text for choice 2',
                'choices_-2_maxsize' => '5',
                'choices_-2_active' => false
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
     * @param stdClass $user userobject to enrol.
     * @return stdClass
     */
    public static function create_user_and_enrol(advanced_testcase $tc, $course, $is_teacher = false, $user=NULL) {
        $user = $tc->getDataGenerator()->create_user();

        if ($is_teacher) {
            if(empty(self::$teacher_role)) {
                global $DB;
                // enrole teacher and student
                self::$teacher_role = $DB->get_record('role',
                        array('shortname' => 'editingteacher'
                        ));
            }
            $enroled = $tc->getDataGenerator()->enrol_user($user->id, $course->id,
                    self::$teacher_role->id);
        } else {
            $enroled = $tc->getDataGenerator()->enrol_user($user->id, $course->id);
        }
        $tc->assertTrue($enroled, 'trying to enrol already enroled user');
        return $user;
    }
    private static $teacher_role;

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
    
    public static function get_open_ratingallocate_for_teacher(advanced_testcase $tc) {
        return self::get_ratingallocate_for_teacher_open_in(0, $tc);
    }
    
    public static function get_closed_ratingallocate_for_teacher(advanced_testcase $tc) {
        return self::get_ratingallocate_for_teacher_open_in(-7, $tc);
    }
    
    private static function get_ratingallocate_for_teacher_open_in($num_days, advanced_testcase $tc) {
        $record = mod_ratingallocate_generator::get_default_values();
        $record['accesstimestart'] = time() + ($num_days * 24 * 60 * 60);
        $record['accesstimestop'] = time() + (($num_days + 6) * 24 * 60 * 60);
        $record['publishdate'] = time() + (($num_days + 7) * 24 * 60 * 60);
        $test_module = new mod_ratingallocate_generated_module($tc,$record);
        return mod_ratingallocate_generator::get_ratingallocate_for_user($tc, $test_module->mod_db, $test_module->teacher);
    }
}

class mod_ratingallocate_generated_module {
    var $mod_db;
    var $teacher;
    var $students = array();
    var $course;
    var $ratings;
    var $choices;
    var $allocations;

    /**
     * Generates a fully set up mod_ratingallocate module
     * @param advanced_testcase $tc
     * @param array $record
     * @param boolean $assert_intermediate_result
     */
    public function __construct(advanced_testcase $tc, $record = null,
            $assert_intermediate_result = true) {
        global $DB;
        $tc->resetAfterTest();
        $tc->setAdminUser();

        if (is_null($record)) {
            $record = array();
        } elseif (!is_array($record)) {
            $tc->fail('$record must be null or an array');
        }
        if (!array_key_exists('course', $record)) {
            $record['course'] = $tc->getDataGenerator()->create_course();
        }
        $this->course = $record['course'];

        $this->teacher = mod_ratingallocate_generator::create_user_and_enrol($tc, $this->course, true);
        $tc->setUser($this->teacher);
        if ($assert_intermediate_result) {
            $tc->assertFalse(
                    $DB->record_exists(this_db\ratingallocate::TABLE,
                            array(this_db\ratingallocate::COURSE => $this->course->id
                            )), 'There should not be any module for that course first');
        }

        // create activity
        $this->mod_db = $tc->getDataGenerator()->create_module(ratingallocate_MOD_NAME, $record);
        $tc->assertEquals(2, $DB->count_records(this_db\ratingallocate_choices::TABLE), array(this_db\ratingallocate_choices::ID => $this->mod_db->id));
        // create students
        $num_students = array_key_exists('num_students', $record) ? $record['num_students'] : 20;
        for ($i = 0; $i < $num_students; $i++) {
            $this->students[$i] = mod_ratingallocate_generator::create_user_and_enrol($tc,
                    $this->course);
        }

        // load choices
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($tc,
                $this->mod_db, $this->teacher);
        $this->choices = $choices = $ratingallocate->get_rateable_choices();
        $choices_nummerated = array_values($choices);
        $num_choices = sizeof($choices_nummerated);

        // create students' preferences as array
        //    array ('Choice 1' => 1 )
        if (!array_key_exists('ratings', $record)) {
            $record['ratings'] = array();
            for ($i = 0; $i < $num_students; $i++) {
                $record['ratings'][$i] = array(
                    $choices_nummerated[$i % $num_choices]->{this_db\ratingallocate_choices::TITLE} => 1
                );
            }
        }
        $this->ratings = $record['ratings'];

        // Create preferences
        $prefers_non = array();
        $choice_id_by_title = array();
        foreach ($choices as $choice) {
            $prefers_non[$choice->{this_db\ratingallocate_choices::ID}] = array(
                this_db\ratingallocate_ratings::CHOICEID => $choice->{this_db\ratingallocate_choices::ID},
                    this_db\ratingallocate_ratings::RATING => 0
            );
            $choice_id_by_title[$choice->{this_db\ratingallocate_choices::TITLE}] = $choice->{this_db\ratingallocate_choices::ID};
        }

        // rate for student
        for ($i = 0; $i < $num_students; $i++) {
            $rating = json_decode(json_encode($prefers_non), true);

            // create user's rating according to the modules specifications
            foreach ($this->ratings[$i] as $choice_name => $preference) {
                $choice_id = $choice_id_by_title[$choice_name];
                $rating[$choice_id][this_db\ratingallocate_ratings::RATING] = $preference;
            }

            // assign preferences
            mod_ratingallocate_generator::save_rating_for_user($tc, $this->mod_db,
                    $this->students[$i], $rating);
            if ($assert_intermediate_result) {
                $alloc = mod_ratingallocate_generator::get_ratingallocate_for_user($tc,
                        $this->mod_db, $this->students[$i]);
                $saved_ratings = $alloc->get_rating_data_for_user($this->students[$i]->id);
                $saved_rating_arr = array();
                foreach ($saved_ratings as $saved_rat) {
                    if(!$saved_rat->{this_db\ratingallocate_ratings::RATING} == 0)
                        $saved_rating_arr[$saved_rat->{this_db\ratingallocate_choices::TITLE}] = $saved_rat->{this_db\ratingallocate_ratings::RATING};
                }
                $tc->assertEquals($this->ratings[$i], $saved_rating_arr);
            }
        }

        // allocate choices
        $ratingallocate = mod_ratingallocate_generator::get_ratingallocate_for_user($tc,
                $this->mod_db, $this->teacher);
        $time_needed = $ratingallocate->distrubute_choices();
        $tc->assertGreaterThan(0, $time_needed);
        $tc->assertLessThan(0.2, $time_needed, 'Allocation is very slow');
        $this->allocations = $ratingallocate->get_allocations();
    }

    private static function filter_allocations_by_choice($allocations, $choiceid) {
        $filter_choice_id = function ($elem) use($choiceid) {
            return $elem->{this_db\ratingallocate_allocations::CHOICEID} == $choiceid;
        };
        return array_filter($allocations, $filter_choice_id);
    }
}
