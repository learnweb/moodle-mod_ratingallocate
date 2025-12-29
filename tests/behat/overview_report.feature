@mod @mod_ratingallocate
Feature: Testing overview integration in ratingallocate activity
  In order to summarize the ratingallocate activity
  As a user
  I need to be able to see the ratingallocate activity overview

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
    And the following "activity" exists:
      | course   | C1                      |
      | activity | ratingallocate          |
      | name     | Ratingallocate activity |
      | intro    | description             |
      | idnumber | ratingallocate1         |

  Scenario: The Ratingallocate activity overview report should generate log events
    Given the site is running Moodle version 5.0 or higher
    And I am on the "Course 1" "course > activities > ratingallocate" page logged in as "teacher1"
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'ratingallocate'"

  Scenario: The Ratingallocate activity index redirect to the activities overview
    Given the site is running Moodle version 5.0 or higher
    When I am on the "C1" "course > activities > ratingallocate" page logged in as "admin"
    Then I should see "Name" in the "ratingallocate_overview_collapsible" "region"
    And I should see "Rating begins at" in the "ratingallocate_overview_collapsible" "region"
    And I should see "Rating ends at" in the "ratingallocate_overview_collapsible" "region"
    And I should see "Ratings" in the "ratingallocate_overview_collapsible" "region"
    And I should see "Actions" in the "ratingallocate_overview_collapsible" "region"
