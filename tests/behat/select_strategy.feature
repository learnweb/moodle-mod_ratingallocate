@mod @mod_ratingallocate @javascript
Feature: When a teacher selects a strategy the appropriate options are displayed

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username |
      | teacher1 |
    And the following "course enrolments" exist:
      | course | user     | role           |
      | C1     | teacher1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Ratingallocate" to section "1"

  @javascript
  Scenario: The correct options are displayed for the default strategy (Yes-No)
    Then the field "Rating strategy" matches value "Yes-No"
    And I should see "Maximum number of choices the user can rate with \"No\""
    And I should see "Designation for \"No\""

  @javascript
  Scenario: Selecting "Likert Scale" strategy should show the correct options.
    When I select "strategy_lickert" from the "strategy" singleselect
    Then I should see "Maximum number of choices the user can rate with 0"
    And I should see "Highest number on the likert scale"
    And I should see "Designation for \"0 - Exclude\""
    And I should not see "Maximum number of choices the user can rate with \"No\""
    And I should not see "Designation for \"No\""

  @javascript
  Scenario: Selecting "Give Points" then "Yes-No" shows only the correct options.
    When I select "strategy_points" from the "strategy" singleselect
    And I should see "Maximum number of choices to which the user can give 0 points"
    And I should see "Total number of points the user can assign"
    And I select "strategy_yesno" from the "strategy" singleselect
    Then I should see "Maximum number of choices the user can rate with \"No\""
    And I should see "Designation for \"No\""
    And I should not see "Maximum number of choices to which the user can give 0 points"
    And I should not see "Total number of points the user can assign"
