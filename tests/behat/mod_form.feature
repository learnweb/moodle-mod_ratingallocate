@mod @mod_ratingallocate
Feature: Creating a new rating allocation, where new choices need to
  be added and if necessary deleted prior to submission.

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
    And I add a "Fair Allocation" to section "0" and I fill the form with:
      | id_name | "My Fair Allocation" |
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

  Scenario: Create a new rating alloation and add an additonal new choice.
    Given I add a new choice with the values:
  	| title       | My fourth choice |
  	| explanation | Test 4           |
    | maxsize     |	2	    	     |
    Then I should see the choice with the title "My first choice"
    And I should see the choice with the title "My second choice"
    And I should see the choice with the title "My third choice"
    And I should see the choice with the title "My fourth choice"

  Scenario: Create a new rating alloation and add two additonal new choices using the add next button.
    Given I add new choices with the values:
  	| title            | explanation     | maxsize |
    | My fourth choice | Test 4          | 2       |
    | My fifth choice  | Test 5          | 2       |
    Then I should see the choice with the title "My first choice"
    And I should see the choice with the title "My second choice"
    And I should see the choice with the title "My third choice"
    And I should see the choice with the title "My fourth choice"
    And I should see the choice with the title "My fifth choice"

  @javascript
  Scenario: Create a new rating alloation and add two additonal new choices, but delete two old and one new.
    When I add new choices with the values:
      | title            | explanation     | maxsize |
      | My fourth choice | Test 4          | 2       |
      | My fifth choice  | Test 5          | 2       |
    And I delete the choice with the title "My first choice"
    And I delete the choice with the title "My second choice"
    And I delete the choice with the title "My fifth choice"

    Then I should not see the choice with the title "My first choice"
    And I should not see the choice with the title "My second choice"
    And I should see the choice with the title "My third choice"
    And I should see the choice with the title "My fourth choice"
    And I should not see the choice with the title "My fifth choice"

  Scenario: Create a new rating alloation and add an additonal new active choice.
    When I add a new choice with the values:
      | title       | My fourth choice |
      | explanation | Test 4          |
      | maxsize     |	1337			|
      | active      | true            |
    And I should see the choice with the title "My fourth choice"
    And the choice with name "My fourth choice" should have explanation being equal to "Test 4"
    And the choice with name "My fourth choice" should have maxsize being equal to 1337
    And the choice with name "My fourth choice" should be active

  @javascript
  Scenario: Create a new rating alloation and add an additonal new inactive choice.
    When I add a new choice with the values:
      | title       | My fourth choice |
      | explanation | Test 4          |
      | maxsize     |	1337			|
      | active      | false            |
    And I should see the choice with the title "My fourth choice"
    And the choice with name "My fourth choice" should have explanation being equal to "Test 4"
    And the choice with name "My fourth choice" should have maxsize being equal to 1337
    And the choice with name "My fourth choice" should not be active

  @javascript
  Scenario: Create a new rating alloation and add an additonal new inactive choice. Change the the choice to active.
    When I add a new choice with the values:
      | title       | My fourth choice |
      | explanation | This is my discription          |
      | maxsize     |	1231243			|
      | active	  | false				|
    Then I set the choice with the title "My fourth choice" to active
    And I should see "My fourth choice"
    And the choice with name "My fourth choice" should be active

  Scenario: Create a new rating alloation and add an additonal new active choice. Change the the choice to inactive.
    When I add a new choice with the values:
      | title       | My fourth choice |
      | explanation | This is my discription          |
      | maxsize     |	1231243			|
      | active	  | true				|
    Then I set the choice with the title "My fourth choice" to inactive
    And I should see "My fourth choice"
    And the choice with name "My fourth choice" should not be active

  @javascript
  Scenario: Create a new rating alloation and check the field runalgorithmbycron. It should be saved as true.
    When I navigate to "Edit settings" node in "Fair Allocation administration"
    And I set the field "runalgorithmbycron" to "1"
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "Fair Allocation administration"
    Then the field "runalgorithmbycron" matches value "1"

  @javascript
  Scenario: Create a new rating alloation and uncheck the field runalgorithmbycron. It should be saved as false.
    When I navigate to "Edit settings" node in "Fair Allocation administration"
    And I set the field "runalgorithmbycron" to ""
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "Fair Allocation administration"
    Then the field "runalgorithmbycron" matches value ""

  @javascript
  Scenario: Create a new rating alloation and assume the default for the field runalgorithmbycron is true.
    When I navigate to "Edit settings" node in "Fair Allocation administration"
    Then the field "runalgorithmbycron" matches value "1"