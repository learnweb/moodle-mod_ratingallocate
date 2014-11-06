@mod @mod_ratingallocate
Feature: Creating a new rating allocation it new choices need to
  be added and if necessary deleted prior to submission.

  @javascript @wip
  Scenario: Create a new rating alloation and add three choices but delete the second.
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Ratingallocate" to section "0"
    And I add a new choice
    And I set the values of the choice with the id -1 to:
      | title       | My first choice |
      | explanation | Test 1          |
    And I set the values of the choice with the id -2 to:
      | title       | My second choice |
      | explanation | Test 2           |
    And I set the values of the choice with the id -3 to:
      | title       | My third choice |
      | explanation | Test 3          |
    When I delete the choice with the id -2
    And I press "id_submitbutton2"
    #The following assumtions access view tables instead of the db. 
    Then the following should exist in the "mdl_ratingallocate_choices" table:
      | title           | explanation |
      | My first choice | Test 1      |
      | My third choice | Test 3      |
      And the following should not exist in the "mdl_ratingallocate_choices" table:
      | title           | explanation |
      | My second choice | Test 2      |

