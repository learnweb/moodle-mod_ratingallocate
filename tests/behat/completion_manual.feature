@mod @mod_ratingallocate @core_completion
Feature: Manually mark a ratingallocate activity as completed
  In order to meet manual ratingallocate completion requirements
  As a student
  I need to be able to view and modify my ratingallocate manual completion status

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity       | course | idnumber | name               | completion |
      | ratingallocate | C1     | ra1      | My Fair Allocation | 1          |
    And I log in as "teacher1"
    And I am on the "My Fair Allocation" "mod_ratingallocate > Choices" page
    And I add a new choice with the values:
      | title                  | My first choice |
      | Description (optional) | Test 1          |
      | maxsize                | 2               |

  @javascript
  Scenario: Use manual completion as teacher
    When I log in as "teacher1"
    And I am on the "My Fair Allocation" "mod_ratingallocate > View" page
    Then the manual completion button for "My Fair Allocation" should be disabled

  @javascript
  Scenario: Use manual completion student view
    When I log in as "student1"
    And I am on the "My Fair Allocation" "mod_ratingallocate > View" page
    Then the manual completion button of "My Fair Allocation" is displayed as "Mark as done"
    And I toggle the manual completion state of "My Fair Allocation"
    And the manual completion button of "My Fair Allocation" is displayed as "Done"
