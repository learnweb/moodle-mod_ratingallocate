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

namespace mod_ratingallocate;

use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/generator/lib.php');
require_once(__DIR__ . '/../locallib.php');

/**
 * Tests that choices can only be changed through their own instance.
 *
 * @package    mod_ratingallocate
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(ratingallocate::class)]
final class mod_ratingallocate_choice_scope_test extends \advanced_testcase {
    /**
     * Create an instance with the default choices in a new course.
     *
     * @return \stdClass The ratingallocate record.
     */
    private function create_instance(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        return \mod_ratingallocate_generator::create_instance_with_choices($this, ['course' => $course->id]);
    }

    /**
     * Get the first choice of an instance.
     *
     * @param \stdClass $instance The ratingallocate record.
     * @return \stdClass The choice record.
     */
    private function get_first_choice(\stdClass $instance): \stdClass {
        global $DB;
        $choices = $DB->get_records('ratingallocate_choices', ['ratingallocateid' => $instance->id], 'id');
        return reset($choices);
    }

    /**
     * Saving the choice form with a choice id of another instance must not change that choice.
     */
    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    public function test_save_choice_of_other_instance_is_rejected(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate($this->create_instance());
        $foreign = $this->get_first_choice($this->create_instance());

        try {
            $ratingallocate->save_modify_choice_form((object) [
                'choiceid' => $foreign->id, 'title' => 'Hijacked', 'explanation' => '', 'maxsize' => 1, 'active' => true,
            ]);
            $this->fail('Expected dml_missing_record_exception');
        } catch (\dml_missing_record_exception $e) {
            $this->assertSame('invalidrecord', $e->errorcode);
        }
        $this->assertEquals($foreign, $DB->get_record('ratingallocate_choices', ['id' => $foreign->id]));
    }

    /**
     * Saving the choice form with an own choice id still updates the choice.
     */
    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    public function test_save_own_choice(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $instance = $this->create_instance();
        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate($instance);
        $own = $this->get_first_choice($instance);

        $ratingallocate->save_modify_choice_form((object) [
            'choiceid' => $own->id, 'title' => 'Renamed', 'explanation' => '', 'maxsize' => 1, 'active' => true,
        ]);

        $this->assertSame('Renamed', $DB->get_field('ratingallocate_choices', 'title', ['id' => $own->id]));
    }
}
