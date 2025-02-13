@mod @mod_ratingallocate @javascript
Feature: When students rate in groups every teammember should see the rating.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G1    |
    And the following "groupings" exist:
      | name       | course | idnumber |
      | Grouping 1 | C1     | GG1      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1      | G1    |
    And the following "activities" exist:
      | activity       | course | idnumber | name               | teamvote | preventvotenotingroup | teamvotegroupingid |
      | ratingallocate | C1     | ra1      | My Fair Allocation | 1        | 1                     | GG1                |
    And I log in as "teacher1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Choices"
    And I add a new choice with the values:
      | title                  | My first choice |
      | Description (optional) | Test 1          |
      | maxsize                | 2               |
    And I add a new choice with the values:
      | title                  | My second choice |
      | Description (optional) | Test 2           |
      | maxsize                | 2                |
    And I log out

  @javascript
  Scenario: Ratings are saved for each teammember.
    When I log in as "student1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Rating"
    And I press "Save changes"
    Then the user "student1" should have ratings
    And the user "student2" should have ratings

  @javascript
  Scenario: Users without group cannot create rating.
    When I log in as "student3"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    Then I should see "This Ratingallocate requires voting in groups. You are not a member of any group, so you cannot submit a rating. Please contact your teacher to be added to a group."
    And I should not see "Edit Rating"
