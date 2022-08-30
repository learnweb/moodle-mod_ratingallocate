@mod @mod_ratingallocate
Feature: Teachers should be able to alter the allocations manually.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Theo | Teacher | teacher1@example.com |
      | student1 | Steve |  Student | student1@example.com |
      | student2 | Sophie | Student | student2@example.com |
      | student3 | Stefanie | Student | student3@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And the following "activities" exist:
      | activity | course | idnumber | name | accesstimestart | accesstimestop |
      | ratingallocate   | C1     | ra1  | My Fair Allocation | ##yesterday## | ##yesterday## |
    And the following choices exist:
      | title | explanation | maxsize | ratingallocate     |
      | C1    | Test        | 1       | My Fair Allocation |
      | C2    | Test        | 1       | My Fair Allocation |
    And the following ratings exist:
      | choice | user     | rating |
      | C1     | student1 | 1      |
      | C1     | student2 | 0      |
      | C2     | student1 | 0      |
      | C2     | student2 | 1      |
    And I run the scheduled task "mod_ratingallocate\task\cron_task"

  Scenario: As a teacher, I want to allocate a so far not allocated user.
    And I log in as "teacher1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Manual Allocation Form"
    Then I should see "Steve" assigned to "C1"
    And I should see "Steve" not assigned to "C2"
    And I should see "Sophie" not assigned to "C1"
    And I should see "Sophie" assigned to "C2"
    And I should not see "Stefanie"
    When I assign "Steve" to choice "C2"
    And I press "Save and Continue"
    Then I should see "Steve" not assigned to "C1"
    And I should see "Steve" assigned to "C2"
    And I should see "Sophie" not assigned to "C1"
    And I should see "Sophie" assigned to "C2"
    When I assign "Sophie" to choice "C1"
    And I press "Save and Continue"
    Then I should see "Steve" not assigned to "C1"
    And I should see "Steve" assigned to "C2"
    And I should see "Sophie" assigned to "C1"
    And I should see "Sophie" not assigned to "C2"
