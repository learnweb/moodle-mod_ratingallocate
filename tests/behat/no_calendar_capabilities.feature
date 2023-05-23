@mod @mod_ratingallocate @javascript
Feature: Ratingallocate with no calendar capabilites
  In order to allow work effectively
  As a teacher
  I need to be able to create ratingallocate activities even when I cannot edit calendar events

  Background:
    Given the following "courses" exist:
      | fullname  | shortname  | category  | groupmode  |
      | Course 1  | C1         | 0         | 1          |
    And the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
    And I log in as "admin"
    And I am on "C1" course homepage
    And I navigate to "Permissions" in current page administration
    And I override the system permissions of "editingteacher" role with:
      | capability                     | permission  |
      | moodle/calendar:manageentries  | Prohibit    |

  @javascript
  Scenario: Editing a ratingallocate activity
    Given the following "activities" exist:
      | activity       | course | idnumber | name               | accesstimestart      | accesstimestop      |
      | ratingallocate | C1     | ra1      | My Fair Allocation | ##1 January 2023##   | ##1 February 2023## |
    And I am on the "My Fair Allocation" "ratingallocate activity" page logged in as teacher1
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Rating begins at | ##yesterday## |
      | Rating ends at   | ##tomorrow##  |
    And I press "Save and return to course"
    Then I should see "My Fair Allocation"
