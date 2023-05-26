@mod @mod_ratingallocate
Feature: Students should only see the ratingallocate calendar events if they are able to rate.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Theo      | Teacher  | teacher1@example.com |
      | student1 | Steve     | Student  | student1@example.com |
      | student2 | Sophie    | Student  | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
      | group2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group  |
      | student1 | G1 |
      | student2 | G1 |
    And the following "activities" exist:
      | activity       | course | idnumber | name               | accesstimestart | accesstimestop | accesstype |
      | ratingallocate | C1     | ra1      | My Fair Allocation | ##1 May 2023##  | ##2 May 2023## | group1     |

  @javascript
  Scenario: As a user that is able to rate, I should see the event
    Given I log in as "student1"
    And I view the calendar for "5" "2023"
    Then I should see "My Fair Allocation opens"
    And I should see "My Fair Allocation closes"

  Scenario: As a user that is not able to rate, I should not see the event
    Given I log in as "student2"
    And I view the calendar for "5" "2023"
    Then I should not see "My Fair Allocation begins"
    And I should not see "My Fair Allocation ends"
