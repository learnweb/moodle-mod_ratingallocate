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
require_once(__DIR__ . '/../locallib.php');

/**
 * Tests the notifications when allocations are published.
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group      mod_ratingallocate
 * @copyright  2018 T Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ratingallocate_notification_testcase extends advanced_testcase {

    const CHOICE1 = 'Choice 1';
    const CHOICE2 = 'Choice 2';

    /**
     * Tests if publishing the allocation send messages with the right content to the right users.
     */
    public function test_allocation_notification() {
        $course = $this->getDataGenerator()->create_course();
        $students = array();
        for ($i = 1; $i <= 4; $i++) {
            $students[$i] = mod_ratingallocate_generator::create_user_and_enrol($this, $course);
        }
        $choices = array(
            array(
                'title' => self::CHOICE1,
                'maxsize' => '1',
                'active' => '1',
            ),
            array(
                'title' => self::CHOICE2,
                'maxsize' => '1',
                'active' => '1',
            )
        );
        $ratings = array(
            $students[1]->id => array(
                array(
                    'choice' => self::CHOICE1,
                    'rating' => 1
                ),
                array(
                    'choice' => self::CHOICE2,
                    'rating' => 0
                )
            ),
            $students[2]->id => array(
                array(
                    'choice' => self::CHOICE1,
                    'rating' => 0
                ),
                array(
                    'choice' => self::CHOICE2,
                    'rating' => 1
                )
            ),
            $students[3]->id => array(
                array(
                    'choice' => self::CHOICE1,
                    'rating' => 0
                ),
                array(
                    'choice' => self::CHOICE2,
                    'rating' => 0
                )
            )
        );

        $ratingallocate = mod_ratingallocate_generator::get_closed_ratingallocate_for_teacher($this, $choices,
            $course, $ratings);
        $allocations = $ratingallocate->get_allocations();
        $this->assertArrayHasKey($students[1]->id, $allocations);
        $this->assertArrayHasKey($students[2]->id, $allocations);
        $this->assertCount(2, $allocations);
        $choices = $ratingallocate->get_choices();
        $this->assertEquals(self::CHOICE1, $choices[$allocations[$students[1]->id]->choiceid]->title);
        $this->assertEquals(self::CHOICE2, $choices[$allocations[$students[2]->id]->choiceid]->title);

        $this->preventResetByRollback();
        $messagesink = $this->redirectMessages();

        // Create a notification task.
        $task = new mod_ratingallocate\task\send_distribution_notification();

        // Add custom data.
        $task->set_component('mod_ratingallocate');
        $task->set_custom_data(array(
            'ratingallocateid' => $ratingallocate->ratingallocate->id
        ));

        $this->setAdminUser();
        $task->execute();

        $messages = $messagesink->get_messages();
        $this->assertEquals(3, count($messages));
        $this->assert_message_contains($messages, $students[1]->id, self::CHOICE1);
        $this->assert_message_contains($messages, $students[2]->id, self::CHOICE2);
        $this->assert_message_contains($messages, $students[3]->id, 'could not');
        $this->assert_no_message_for_user($messages, $students[4]->id);
    }

    /**
     * Asserts that a message for a user exists and that it contains a certain search string
     * @param $messages stdClass[] received messages
     * @param $userid int id of the user
     * @param $needle string search string
     */
    private function assert_message_contains($messages, $userid, $needle) {
        $messageexists = false;
        foreach ($messages as $message) {
            if ($message->useridto == $userid) {
                $messageexists = true;
                $this->assertContains($needle, $message->fullmessage);
            }
        }
        $this->assertTrue($messageexists, 'Message for userid '. $userid . 'could not be found.' );
    }

    /**
     * Asserts that there is no message for a certain user.
     * @param $messages stdClass[] received messages
     * @param $userid int id of the user
     * @param $needle string search string
     */
    private function assert_no_message_for_user($messages, $userid) {
        $messageexists = false;
        foreach ($messages as $message) {
            if ($message->useridto == $userid) {
                $messageexists = true;
            }
        }
        $this->assertFalse($messageexists, 'There is a message for userid '. $userid . '.' );
    }
}