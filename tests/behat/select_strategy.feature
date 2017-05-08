@mod @mod_ratingallocate @javascript
Feature: When a teacher selects a strategy the appropriate options are displayed

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Fair Allocation" to section "1"

  @javascript
  Scenario: The correct options are displayed for the default strategy (Yes-No)
    Then the field "Rating strategy" matches value "Accept-Deny"
    And I should see "Maximum number of choices the user can rate with \"Deny\""
    And I should see "Designation for \"Deny\""

  @javascript
  Scenario: Selecting "Likert Scale" strategy should show the correct options.
    When I select "strategy_lickert" from the "strategy" singleselect
    Then I should see "Maximum number of choices the user can rate with 0"
    And I should see "Highest number on the likert scale"
    And I should see "Designation for \"0 - Exclude\""
    And I should not see "Maximum number of choices the user can rate with \"Deny\""
    And I should not see "Designation for \"Deny\""

  @javascript
  Scenario: Selecting "Give Points" then "Yes-No" shows only the correct options.
    When I select "strategy_points" from the "strategy" singleselect
    And I should see "Maximum number of choices to which the user can give 0 points"
    And I should see "Total number of points the user can assign"
    And I select "strategy_yesno" from the "strategy" singleselect
    Then I should see "Maximum number of choices the user can rate with \"Deny\""
    And I should see "Designation for \"Deny\""
    And I should not see "Maximum number of choices to which the user can give 0 points"
    And I should not see "Total number of points the user can assign"
