@mod @mod_ratingallocate @javascript
Feature: When a student rates a rating should be saved and it should be possible to delete it again.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity       | course | idnumber | name               |
      | ratingallocate | C1     | ra1      | My Fair Allocation |
    And the following choices exist:
      | title            | maxsize | ratingallocate     |
      | My first choice  |       2 | My Fair Allocation |
      | My second choice |       2 | My Fair Allocation |
      | My third choice  |       2 | My Fair Allocation |

  @javascript
  Scenario: The user can create a rating
    When I log in as "student1"
    And I am on the "My Fair Allocation" "mod_ratingallocate > View" page
    And I press "Edit Rating"
    And I press "Save changes"
    Then the user "student1" should have ratings

  @javascript
  Scenario: The user can delete a rating
    When I log in as "student1"
    And I am on the "My Fair Allocation" "mod_ratingallocate > View" page
    And I press "Edit Rating"
    And I press "Save changes"
    Then the user "student1" should have ratings
    When I press "Delete Rating"
    Then the user "student1" should not have ratings
