@mod @mod_ratingallocate @javascript
Feature: Students should get status information according to their rating and their allocation.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Theo | Teacher | teacher1@example.com |
      | student1 | Steve |  Student | student1@example.com |
      | student2 | Sophie | Student | student2@example.com |
      | student3 | Steffanie | Student | student3@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And the following "activities" exist:
      | activity | course | idnumber | name | accesstimestart |
      | ratingallocate   | C1     | ra1  | My Fair Allocation | ##yesterday## |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "My Fair Allocation"
    And I press "Edit Choices"
    And I add a new choice with the values:
      | title       | My only choice |
      | explanation | Test           |
      | maxsize     |	1            |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    And I press "Edit Rating"
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    And I press "Edit Rating"
    And I click on "Deny" "radio"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | accesstimestart[day] | ##2 days ago##j## |
      | accesstimestart[month] | ##2 days ago##n## |
      | accesstimestart[year] | ##2 days ago##Y## |
      | accesstimestop[day] | ##yesterday##j## |
      | accesstimestop[month] | ##yesterday##n## |
      | accesstimestop[year] | ##yesterday##Y## |
    And I press "id_submitbutton"
    And I run the scheduled task "mod_ratingallocate\task\cron_task"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    And I press "Publish Allocation"
    And I log out

  @javascript
  Scenario: As a user, who rated and was allocated, I should see my allocated choice.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    Then I should see "My only choice" in the "//*[contains(@class, 'allocation')]" "xpath_element"
    And I should see "My only choice" in the "//*[contains(@class, 'alert-success')]" "xpath_element"

  @javascript
  Scenario: As a user, who rated and was not allocated, I should see a warning.
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    Then I should see "You were not allocated to any choice!" in the "//*[contains(@class, 'allocation')]" "xpath_element"
    And I should see "You could not be allocated to any choice." in the "//*[contains(@class, 'alert-danger')]" "xpath_element"

  @javascript
  Scenario: As a user, who did not rate, I should not see my allocated choice
    When I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    Then I should not see "Your Allocation"
    And I should see "The rating is over." in the "//*[contains(@class, 'alert-info')]" "xpath_element"