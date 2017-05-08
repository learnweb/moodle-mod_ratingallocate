@mod @mod_ratingallocate @javascript
Feature: When a student rates a rating should be saved and it should be possible to delete it again.

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
    And the following "activities" exist:
      | activity | course | idnumber | name |
      | ratingallocate   | C1     | ra1  | My Fair Allocation |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "My Fair Allocation"
    And I press "Edit Choices"
    And I add a new choice with the values:
      | title       | My first choice |
      | explanation | Test 1          |
      | maxsize     |	2	    	  |
    And I add a new choice with the values:
      | title       | My second choice |
      | explanation | Test 2           |
      | maxsize     |	2	    	   |
    And I add a new choice with the values:
      | title       | My third choice |
      | explanation | Test 3  		  |
      | maxsize     |	2	    	  |
    And I log out

  @javascript
  Scenario: The user can create a rating
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    And I press "Edit Rating"
    And I press "Save changes"
    Then the user "student1" should have ratings

  @javascript
  Scenario: The user can delete a rating
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My Fair Allocation"
    And I press "Edit Rating"
    And I press "Save changes"
    Then the user "student1" should have ratings
    When I press "Delete Rating"
    Then the user "student1" should not have ratings