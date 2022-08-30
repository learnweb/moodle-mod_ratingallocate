@mod @mod_ratingallocate @javascript
Feature: When a student starts a rating the default values of all choices
  are set according to the instance settings.

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
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Choices"
    And I add a new choice with the values:
      | title       | My first choice |
      | Description (optional) | Test 1          |
      | maxsize     |	2	    	  |
    And I add a new choice with the values:
      | title       | My second choice |
      | Description (optional) | Test 1          |
      | maxsize     |	2	    	  |

  @javascript
  Scenario: The default rating is the max rating
    And I am on the "My Fair Allocation" "ratingallocate activity editing" page
    And I select "strategy_lickert" from the "strategy" singleselect
    And I press "id_submitbutton"
    And I log out
    When I log in as "student1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Rating"
    Then I should see the following rating form:
      | My first choice | 4 |
      | My second choice | 4 |

  @javascript
  Scenario: The default rating should be changeable to a medium rating
    And I am on the "My Fair Allocation" "ratingallocate activity editing" page
    And I select "strategy_lickert" from the "strategy" singleselect
    And I select "3" from the "strategyopt[strategy_lickert][default]" singleselect
    And I press "id_submitbutton"
    And I log out
    When I log in as "student1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Rating"
    Then I should see the following rating form:
      | My first choice | 3 |
      | My second choice | 3 |

  @javascript
  Scenario: The default rating should be changeable to the lowest rating
    And I am on the "My Fair Allocation" "ratingallocate activity editing" page
    And I select "strategy_lickert" from the "strategy" singleselect
    And I select "0" from the "strategyopt[strategy_lickert][default]" singleselect
    And I press "id_submitbutton"
    And I log out
    When I log in as "student1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Rating"
    Then I should see the following rating form:
      | My first choice | 0 |
      | My second choice | 0 |

  @javascript
  Scenario: The default rating is the max rating
    And I am on the "My Fair Allocation" "ratingallocate activity editing" page
    And I select "strategy_lickert" from the "strategy" singleselect
    And I press "id_submitbutton"
    And I log out
    When I log in as "student1"
    And I am on the "My Fair Allocation" "ratingallocate activity" page
    And I press "Edit Rating"
    And I set the rating form to the following values:
      | My first choice | 2 |
      | My second choice | 3 |
    And I press "Save changes"
    And I press "Edit Rating"
    Then I should see the following rating form:
      | My first choice | 2 |
      | My second choice | 3 |
