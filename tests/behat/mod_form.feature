@mod @mod_ratingallocate
Feature: Creating a new rating allocation, where new choices need to
  be added and if necessary deleted prior to submission.

Background:	
	Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Ratingallocate" to section "0"
    And I set the field "id_name" to "My Ratingallocate"
    And I add a new choice
    And I set the values of the choice with the id -1 to:
      | title       | My first choice |
      | explanation | Test 1          |
    And I set the values of the choice with the id -2 to:
      | title       | My second choice |
      | explanation | Test 2           |
    And I set the values of the choice with the id -3 to:
      | title       | My third choice |
      | explanation | Test 3  		  |

  @javascript
  Scenario: Create a new rating alloation and add an additonal new choice.
    When I add a new choice
    And I set the values of the choice with the id -4 to:
  	| title       | My fourth choice |
  	| explanation | Test 4          |
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then I should see "My first choice"
    And I should see "My second choice"
    And I should see "My third choice"
    And I should see "My fourth choice"
    
  @javascript
  Scenario: Create a new rating alloation and add two additonal new choices.
    When I add a new choice
    And I set the values of the choice with the id -4 to:
  	| title       | My fourth choice |
  	| explanation | Test 4          |
  	And I add a new choice
    And I set the values of the choice with the id -5 to:
  	| title       | My fifth choice |
  	| explanation | Test 5          |
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then I should see "My first choice"
    And I should see "My second choice"
    And I should see "My third choice"
    And I should see "My fourth choice"
    And I should see "My fifth choice"
    
  @javascript
  Scenario: Create a new rating alloation and add two additonal new choices, but delete two old and one new.
    When I add a new choice
    And I set the values of the choice with the id -4 to:
  	| title       | My fourth choice |
  	| explanation | Test 4          |
  	And I add a new choice
    And I set the values of the choice with the id -5 to:
  	| title       | My fifth choice |
  	| explanation | Test 5          |
  	And I delete the choice with the id -2
  	And I delete the choice with the id -1
  	And I delete the choice with the id -5
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then I should not see "My first choice"
    And I should not see "My second choice"
    And I should see "My third choice"
    And I should see "My fourth choice"
    And I should not see "My fifth choice"
    
  @javascript
  Scenario: Create a new rating alloation and add an additonal new choice, but delete all old.
    When I add a new choice
    And I set the values of the choice with the id -4 to:
  	| title       | My fourth choice |
  	| explanation | Test 4          |
	| maxsize     |	1337			|
	And I set the choice with the id -4 to inactive
	And I delete the choice with the id -1
  	And I delete the choice with the id -2
  	And I delete the choice with the id -3
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then I should not see "My first choice"
    And I should not see "My second choice"
    And I should not see "My third choice"
    And I should see "My fourth choice"
    And the choice with name "My fourth choice" should have explanation being equal to "Test 4"
    And the choice with name "My fourth choice" should have maxsize being equal to 1337
    And the choice with name "My fourth choice" should not be active
    
  @javascript
  Scenario: Create a new rating alloation and add an additonal new choice, but delete all old. Assert correct values.
    When I add a new choice
    And I set the values of the choice with the id -4 to:
  	| title       | My only choice |
  	| explanation | This is my discription          |
	| maxsize     |	1231243			|
  	And I delete the choice with the id -1
  	And I delete the choice with the id -2
  	And I delete the choice with the id -3
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then I should not see "My first choice"
    And I should not see "My second choice"
    And I should not see "My third choice"
    And I should see "My only choice"
    And the choice with name "My only choice" should have explanation being equal to "This is my discription"
    And the choice with name "My only choice" should have maxsize being equal to 1231243
    And the choice with name "My only choice" should be active
    
    @javascript
  	Scenario: Create a new rating alloation and add an additonal new choice as inactive, but delete all old. Change the the choice to active.
    When I add a new choice
    And I set the values of the choice with the id -4 to:
  	| title       | My only choice |
  	| explanation | This is my discription          |
	| maxsize     |	1231243			|
	| active	  | 0				|
  	And I delete the choice with the id -1
  	And I delete the choice with the id -2
  	And I delete the choice with the id -3
  	And I set the choice with the id -4 to active
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then I should not see "My first choice"
    And I should not see "My second choice"
    And I should not see "My third choice"
    And I should see "My only choice"
    And the choice with name "My only choice" should be active

  @javascript
  Scenario: Create a new rating alloation and check the field runalgorithmbycron. It should be saved as true.
    When I set the field "runalgorithmbycron" to "1"
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then the field "runalgorithmbycron" matches value "1"

  @javascript
  Scenario: Create a new rating alloation and uncheck the field runalgorithmbycron. It should be saved as false.
    When I set the field "runalgorithmbycron" to ""
    And I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then the field "runalgorithmbycron" matches value ""

  @javascript
  Scenario: Create a new rating alloation and assume the default for the field runalgorithmbycron is true.
    When I press "id_submitbutton"
    And I navigate to "Edit settings" node in "ratingallocate administration"
    Then the field "runalgorithmbycron" matches value "1"


