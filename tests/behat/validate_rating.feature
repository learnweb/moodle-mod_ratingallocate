@mod @mod_ratingallocate @javascript
Feature: When a student attempts to rate choices it should be validated prior to changing.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Fair Allocation" to section "1" and I fill the form with:
      | id_name | Validated Rating |
      | strategy    | strategy_points |
      | Rating begins at | ##2 days ago## |
      | strategyopt[strategy_points][maxzero] | 2 |
    And I am on the "Validated Rating" "ratingallocate activity" page
    And I press "Edit Choices"
    And I add a new choice with the values:
      | title       | My first choice |
      | Description (optional) | Test 1          |
      | maxsize     |	2	    	  |
    And I add a new choice with the values:
      | title       | My second choice |
      | Description (optional) | Test 2           |
      | maxsize     |	2	    	   |
    And I add a new choice with the values:
      | title       | My third choice |
      | Description (optional) | Test 3  		  |
      | maxsize     |	2	    	  |
    And I add a new choice with the values:
      | title       | My fourth choice |
      | Description (optional) | Test 4  		  |
      | maxsize     |	2	    	  |
    And I log out

  Scenario: The user cannot enter values less than 0.
    When I log in as "student1"
    And I am on the "Validated Rating" "ratingallocate activity" page
    And I press "Edit Rating"
    And I rate choices with the following points:
      | My first choice | -1 |
      | My second choice | 1 |
      | My third choice | 1 |
      | My fourth choice | 99 |
    And I press "Save changes"
    Then I should see "The points that you assign to a choice must be between 0 and 100."

  Scenario: The values entered by the user must sum up to the (default) maximum.
    When I log in as "student1"
    And I am on the "Validated Rating" "ratingallocate activity" page
    And I press "Edit Rating"
    And I rate choices with the following points:
      | My first choice | 1 |
      | My second choice | 2 |
      | My third choice | 3 |
      | My fourth choice | 4 |
    And I press "Save changes"
    Then I should see "Incorrect total number of points. The sum of all points has to be 100."

  Scenario: The user may not rate more than a (default) number of choices with 0.
    When I log in as "student1"
    And I am on the "Validated Rating" "ratingallocate activity" page
    And I press "Edit Rating"
    And I rate choices with the following points:
      | My first choice | 0 |
      | My second choice | 0 |
      | My third choice | 0 |
      | My fourth choice | 100 |
    And I press "Save changes"
    Then I should see "You may give 0 points to at most 2 choice(s)."
