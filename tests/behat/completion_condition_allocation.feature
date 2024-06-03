@mod @mod_ratingallocate @core_completion
Feature: Set a ratingallocate activity marked as completed when a user has been allocated
  In order to ensure a student has been allocated
  As a teacher
  I need to set the ratingallocate to complete when the student has an allocation

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
      | activity       | course | idnumber | name               | completion | completionallocation |
      | ratingallocate | C1     | ra1      | My Fair Allocation | 2          | 1                    |
    And the following choices exist:
      | title | explanation | maxsize | ratingallocate     |
      | C1    | Test        | 1       | My Fair Allocation |
      | C2    | Test        | 0       | My Fair Allocation |
    And the following ratings exist:
      | choice | user     | rating |
      | C1     | student1 | 1      |
      | C1     | student2 | 0      |
      | C2     | student1 | 0      |
      | C2     | student2 | 0      |
    And I log in as "teacher1"
    And I am on the "My Fair Allocation" "mod_ratingallocate > View" page
    And I run the scheduled task "mod_ratingallocate\task\cron_task"
    And I press "Publish Allocation"
    And I log out

  @javascript
  Scenario: User completes ratingallocate only if they have been allocated
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    Then "Completed" "icon" should exist in the "Student 1" "table_row"
    And "Completed" "icon" should not exist in the "Student 2" "table_row"
