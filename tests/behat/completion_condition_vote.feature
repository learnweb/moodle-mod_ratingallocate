@mod @mod_ratingallocate @core_completion
Feature: Set a ratingallocate activity marked as completed when a user submits a vote
  In order to ensure a student has voted in the activity
  As a teacher
  I need to set the ratingallocate to complete when the student has voted

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity       | course | idnumber | name               | completion | completionvote |
      | ratingallocate | C1     | ra1      | My Fair Allocation | 2          | 1              |
    And I log in as "teacher1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Choices"
    And I add a new choice with the values:
      | title                  | My first choice |
      | Description (optional) | Test 1          |
      | maxsize                | 2               |

  @javascript
  Scenario: User completes ratingallocate only if they voted
    When I log in as "student1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Rating"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    Then "Completed" "icon" should exist in the "Student 1" "table_row"
    And "Completed" "icon" should not exist in the "Student 2" "table_row"
