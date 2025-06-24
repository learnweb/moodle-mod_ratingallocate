<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions related to mod_reallocate.
 *
 * @package mod_ratingallocate
 * @category test
 * @copyright 2014 Tobias Reischmann
 */
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
        Behat\Mink\Exception\ExpectationException as ExpectationException,
        Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Class for Behat tests
 *
 * @copyright 2014 Tobias Reischmann
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_ratingallocate
 */
class behat_mod_ratingallocate extends behat_base {

    /**
     * Creates the specified choices.
     *
     * @Given /^the following choices exist:$/
     *
     * @param TableNode $data
     * @throws PendingException
     * @throws Exception
     */
    public function the_following_choices_exist(TableNode $data) {
        global $DB;

        foreach ($data->getColumnsHash() as $record) {

            if (!isset($record['title'])) {
                throw new coding_exception(
                    'title must be present in behat_mod_ratingallocate::the_following_choices_exist() $data');
            }

            if (!isset($record['maxsize'])) {
                throw new coding_exception(
                    'maxsize must be present in behat_mod_ratingallocate::the_following_choices_exist() $data');
            }

            if (!isset($record['ratingallocate'])) {
                throw new coding_exception(
                    'ratingallocate must be present in behat_mod_ratingallocate::the_following_choices_exist() $data');
            }

            $ratingallocate = $DB->get_record('ratingallocate', ['name' => $record['ratingallocate']]);

            $record['ratingallocateid'] = $ratingallocate->id;

            $record = (object) $record;

            // Add the subscription.
            $record->id = $DB->insert_record('ratingallocate_choices', $record);

        }
    }

    /**
     * Creates the specified choices.
     *
     * @Given /^the following ratings exist:$/
     *
     * @param TableNode $data
     * @throws PendingException
     * @throws Exception
     */
    public function the_following_ratings_exist(TableNode $data) {
        global $DB;

        foreach ($data->getColumnsHash() as $record) {

            if (!isset($record['choice'])) {
                throw new coding_exception(
                    'choice must be present in behat_mod_ratingallocate::the_following_ratings_exist() $data');
            }

            if (!isset($record['user'])) {
                throw new coding_exception(
                    'user must be present in behat_mod_ratingallocate::the_following_ratings_exist() $data');
            }

            if (!isset($record['rating'])) {
                throw new coding_exception(
                    'rating must be present in behat_mod_ratingallocate::the_following_ratings_exist() $data');
            }

            $user = $DB->get_record('user', ['username' => $record['user']]);
            $choice = $DB->get_record('ratingallocate_choices', ['title' => $record['choice']]);

            $record['userid'] = $user->id;
            $record['choiceid'] = $choice->id;

            $record = (object) $record;

            // Add the subscription.
            $record->id = $DB->insert_record('ratingallocate_ratings', $record);

        }
    }

    /**
     * Fills the respective fields of a choice.
     *
     * @Given /^I set the values of the choice to:$/
     *
     * @param TableNode $choicedata with data for filling the choice
     */
    public function i_set_the_values_of_the_choice_to(TableNode $choicedata) {
        $choicedatahash = $choicedata->getRowsHash();
        // The action depends on the field type.
        foreach ($choicedatahash as $locator => $value) {
            if ($locator === 'active') {
                if ($value === 'true') {
                    $this->execute('behat_mod_ratingallocate::i_check_the_active_checkbox');
                } else {
                    $this->execute('behat_mod_ratingallocate::i_uncheck_the_active_checkbox');
                }
            } else {
                $this->execute('behat_forms::i_set_the_field_to', [$locator, $value]);
            }
        }
    }

    /**
     * Adds a new choice by first clicking on the add new choice button, filling the form and finally
     * submitting it.
     *
     * @Given /^I add a new choice with the values:$/
     * @param TableNode $choicedata
     */
    public function i_add_a_new_choice_with_the_values(TableNode $choicedata) {
        $this->i_add_a_new_choice();
        $this->i_set_the_values_of_the_choice_to($choicedata);
        $this->execute('behat_forms::press_button', ["id_submitbutton"]);
    }

    /**
     * Adds new choices by first clicking on the add new choice button, filling the form and then continually
     * adding new choices using the add next button. Finally, the last view is canceled.
     *
     * @Given /^I add new choices with the values:$/
     * @param TableNode $choicedata
     */
    public function i_add_new_choices_with_the_values(TableNode $choicedata) {
        global $CFG;
        $this->i_add_a_new_choice();
        $choicedatahash = $choicedata->getHash();
        foreach ($choicedatahash as $entry) {
            $newrows = [];
            foreach ($entry as $key => $val) {
                array_push($newrows, [$key, $val]);
            }
            // phpcs:ignore moodle.Commenting.TodoComment.MissingInfoInline
            // TODO: Ensure backward-compatibility after changed TableNode constructor in Moodle 3.1.
            if ($CFG->version < 2016052300) {
                $newrows = implode("\n", $newrows);
            }
            $table = new TableNode($newrows);
            $this->i_set_the_values_of_the_choice_to($table);
            $this->i_add_a_next_choice();
        }

        $this->execute('behat_forms::press_button', ["id_cancel"]);
    }

    /**
     * Delete the choice with the respective id.
     *
     * @When /^I delete the choice with the title "([^"]*)"$/
     *
     * @param string $choicetitle tilte of the choice
     */
    public function i_delete_the_choice_with_the_title($choicetitle) {
        $fieldxpath = "//table[@id='mod_ratingallocateshowoptions']//td[text()='$choicetitle']" .
                "//following-sibling::td/a[@title='Delete choice']";
        $link = $this->find('xpath', $fieldxpath);
        $link->click();
        $this->execute('behat_general::i_click_on', ["Yes", "button"]);
    }

    /**
     * Ensures that a user is assigned to a choice in manual allocation view.
     *
     * @Then /^I should see "([^"]*)" assigned to "([^"]*)"$/
     *
     * @param string $firstname firstname of the user
     * @param string $choicetitle title of the choice
     * @throws ExpectationException
     * @throws dml_exception
     */
    public function i_should_see_assigned_to($firstname, $choicetitle) {
        global $DB;
        $choice = $DB->get_record('ratingallocate_choices', ['title' => $choicetitle]);
        $user = $DB->get_record('user', ['firstname' => $firstname]);

        $fieldxpath = "//table[contains(concat(\" \", normalize-space(@class), \" \"), \" ratingallocate_ratings_table \")]";
        $fieldxpath .= "//td//input[@id='user_{$user->id}_alloc_{$choice->id}' and @checked]";
        try {
            $this->find('xpath', $fieldxpath);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $firstname . '" is not assigned to choice "' .
                    $choicetitle . '"', $this->getSession());
        }
    }

    /**
     * Ensures that a user is not assigned to a choice in manual allocation view.
     *
     * @Then /^I should see "([^"]*)" not assigned to "([^"]*)"$/
     *
     * @param string $firstname firstname of the user
     * @param string $choicetitle title of the choice
     * @throws ExpectationException
     * @throws dml_exception
     */
    public function i_should_see_not_assigned_to($firstname, $choicetitle) {
        global $DB;
        $choice = $DB->get_record('ratingallocate_choices', ['title' => $choicetitle]);
        $user = $DB->get_record('user', ['firstname' => $firstname]);

        $fieldxpath = "//table[contains(concat(\" \", normalize-space(@class), \" \"), \" ratingallocate_ratings_table \")]";
        $checkbox = $fieldxpath . "//td//input[@id='user_{$user->id}_alloc_{$choice->id}']";
        $checked = $fieldxpath . "//td//input[@id='user_{$user->id}_alloc_{$choice->id}' and @checked]";
        try {
            $this->find('xpath', $checkbox);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $firstname . '" or choice "' .
                    $choicetitle . '" could not be found in the table', $this->getSession());
        }
        try {
            $this->find('xpath', $checked);
        } catch (ElementNotFoundException $e) {
            return;
        }
        throw new ExpectationException('"' . $firstname . '" is assigned to the choice "' .
                $choicetitle . '"', $this->getSession());
    }

    /**
     * Ensures that a user is assigned to a choice in manual allocation view.
     *
     * @When /^I assign "([^"]*)" to choice "([^"]*)"$/
     *
     * @param string $firstname firstname of the user
     * @param string $choicetitle title of the choice
     * @throws dml_exception
     */
    public function i_assign_to_choice($firstname, $choicetitle) {
        global $DB;

        $choice = $DB->get_record('ratingallocate_choices', ['title' => $choicetitle]);
        $user = $DB->get_record('user', ['firstname' => $firstname]);

        $fieldxpath = "//input[@name='allocdata[{$user->id}]']";
        $elements = $this->find_all('xpath', $fieldxpath);
        $this->getSession()->getDriver()->selectOption($fieldxpath, $choice->id);
    }

    /**
     * Ensures that a certain choice can be seen.
     *
     * @Then /^I should see the choice with the title "([^"]*)"$/
     *
     * @param string $choicetitle tilte of the choice
     * @throws ExpectationException
     */
    public function i_should_see_the_choice_with_the_title($choicetitle) {
        $fieldxpath = "//table[@id='mod_ratingallocateshowoptions']//td[text()='$choicetitle']";
        try {
            $this->find('xpath', $fieldxpath);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $choicetitle . '" choice was not found in the page', $this->getSession());
        }
    }

    /**
     * Ensures that a certain choice can not be seen.
     *
     * @Then /^I should not see the choice with the title "([^"]*)"$/
     *
     * @param string $choicetitle tilte of the choice
     * @throws ExpectationException
     */
    public function i_should_not_see_the_choice_with_the_title($choicetitle) {
        $fieldxpath = "//table[@id='mod_ratingallocateshowoptions']//td[text()='$choicetitle']";
        try {
            $this->find('xpath', $fieldxpath);
        } catch (ElementNotFoundException $e) {
            return;
        }
        throw new ExpectationException('"' . $choicetitle . '" choice was found in the page', $this->getSession());
    }

    /**
     * I set the choice to inactive.
     *
     * @When /^I set the choice with the title "([^"]*)" to inactive$/
     *
     * @param string $choicetitle id of the choice
     */
    public function i_set_the_choice_with_the_title_to_inactive($choicetitle) {
        $this->click_tool_for_choice($choicetitle, 'Disable');
    }

    /**
     * I set the choice to active.
     *
     * @When /^I set the choice with the title "([^"]*)" to active$/
     *
     * @param string $choicetitle id of the choice
     */
    public function i_set_the_choice_with_the_title_to_active($choicetitle) {
        $this->click_tool_for_choice($choicetitle, 'Enable');
    }

    /**
     * Adds a new choice for the existing rating allocation.
     *
     * @Given /^I add a new choice$/
     */
    public function i_add_a_new_choice() {
        $this->execute("behat_forms::press_button", [get_string('newchoice', "ratingallocate")]);
    }

    /**
     * Adds a new choice for the existing rating allocation.
     *
     * @Given /^I add a next choice$/
     */
    public function i_add_a_next_choice() {
        $this->execute("behat_forms::press_button", ["id_submitbutton2"]);
    }

    /**
     * Checks the active checkbox.
     *
     * @Given /^I check the active checkbox$/
     */
    public function i_check_the_active_checkbox() {
        $checkbox = $this->find_field("id_active");
        $checkbox->check();
    }

    /**
     * Unchecks the active checkbox.
     *
     * @Given /^I uncheck the active checkbox$/
     */
    public function i_uncheck_the_active_checkbox() {
        $checkbox = $this->find_field("id_active");
        $checkbox->uncheck();
    }

    /**
     * The choice with id should be active.
     *
     * @Then /^the choice with name "([^"]*)" should be active$/
     *
     * @param string $title title of the choice
     * @throws ExpectationException
     */
    public function the_choice_should_be_active($title) {
        $choice = $this->get_choice($title);
        if (!$choice->active) {
            throw new ExpectationException('The choice "' . $title .
                    '" should be active.',
                    $this->getSession());
        }
    }

    /**
     * The choice with id should not be active.
     *
     * @Then /^the choice with name "([^"]*)" should not be active$/
     *
     * @param string $title title of the choice
     * @throws ExpectationException
     */
    public function the_choice_should_not_be_active($title) {
        $choice = $this->get_choice($title);
        if ($choice->active) {
            throw new ExpectationException('The choice "' . $title . '" should not be active',
                    $this->getSession());
        }
    }

    /**
     *
     *
     * @Then /^the choice with name "([^"]*)" should have explanation being equal to "([^"]*)"$/
     *
     * @param string $title title of the choice
     * @param string $value expected value
     * @throws ExpectationException
     */
    public function the_choice_should_have_explanation_equal($title, $value) {
        $choice = $this->get_choice($title);
        if ($choice->explanation !== $value) {
            throw new ExpectationException('The explanation of the choice ' . $title .
                    ' was expected to be "' . $value . '" but was "' . $choice->explanation . '".',
                    $this->getSession());
        }
    }

    /**
     * User should have ratings.
     *
     * @Then the user :useridentifier should have ratings
     *
     * @param string $username username of a user.
     * @throws ExpectationException
     */
    public function the_user_should_have_ratings($username) {
        $ratings = $this->get_ratings_for_username($username);
        if (count($ratings) == 0) {
            throw new ExpectationException("It was expected that the user $username has ratings, " .
                    "but there were none.",
                    $this->getSession());
        }
    }

    /**
     * User should not have ratings.
     *
     * @Then the user :useridentifier should not have ratings
     *
     * @param string $username username of a user.
     * @throws ExpectationException
     */
    public function the_user_should_not_have_ratings($username) {
        $ratings = $this->get_ratings_for_username($username);
        if (count($ratings) > 0) {
            throw new ExpectationException("It was expected that the user $username has no ratings, " .
                    "but there were some.",
                    $this->getSession());
        }
    }

    /**
     * Get ratings for a user.
     * @param string $username username of a user.
     * @return array of ratings
     * @throws Exception
     */
    private function get_ratings_for_username($username) {
        global $DB;
        $user = \core_user::get_user_by_username($username);
        return $DB->get_records("ratingallocate_ratings", ['userid' => $user->id]);
    }

    /**
     *
     *
     * @Then /^the choice with name "([^"]*)" should have maxsize being equal to ([\d]*)$/
     *
     * @param string $title title of the choice
     * @param integer $value expected value
     * @throws ExpectationException
     */
    public function the_choice_should_have_maxsize_equal($title, $value) {
        $choice = $this->get_choice($title);
        if ($choice->maxsize !== $value) {
            throw new ExpectationException('The maxsize of the choice ' . $title .
                    ' was expected to be "' . $value . '" but was "' . $choice->explanation . '".',
                    $this->getSession());
        }
    }

    /**
     * Returns the choice object from the database.
     *
     * @param string $title title of the choice.
     * @return array choice object.
     *
     * @throws ExpectationException
     */
    private function get_choice($title) {
        global $DB;
        $choices = $DB->get_records("ratingallocate_choices", ['title' => $title]);
        if (count($choices) != 1) {
            throw new ExpectationException('Excatly one choice with the name "' . $title .
                    '" is expected but ' . count($choices) . ' found.', $this->getSession());
        }
        return array_shift($choices);
    }

    /**
     * Clicks on a tool within the toolset.
     * @param string $choicetitle title of the choice
     * @param string $tooltitle title of the tool
     * @throws ElementException
     */
    private function click_tool_for_choice($choicetitle, $tooltitle) {
        $fieldxpath = "//table[@id='mod_ratingallocateshowoptions']//td[text()='$choicetitle']" .
                "//following-sibling::td/a[@title='$tooltitle']";
        $link = $this->find('xpath', $fieldxpath);
        $link->click();
    }

    /**
     * I should see the following rating form.
     *
     * @Then /^I should see the following rating form:$/
     *
     * @param TableNode $ratingdata exoected in the rating form
     */
    public function i_should_see_the_followin_rating_form(TableNode $ratingdata) {
        $ratingdatehash = $ratingdata->getRowsHash();
        // The action depends on the field type.
        foreach ($ratingdatehash as $choice => $value) {
            $fieldxpath = "//a[normalize-space(.)=\"$choice\"]/ancestor::fieldset/
                       descendant::input[@type='radio' and @checked and @value=$value]";
            try {
                $this->find('xpath', $fieldxpath);
            } catch (ElementNotFoundException $e) {
                throw new ExpectationException('"' . $choice . '" choice was not rated ' . $value, $this->getSession());
            }
        }
    }

    /**
     * I set the rating form to the following values (only works for radio buttons).
     *
     * @When /^I set the rating form to the following values:$/
     *
     * @param TableNode $ratingdata values to be set in the rating form
     */
    public function i_set_the_rating_form_to_the_following_values(TableNode $ratingdata) {
        $ratingdatehash = $ratingdata->getRowsHash();
        // The action depends on the field type.
        foreach ($ratingdatehash as $choice => $value) {
            $fieldxpath =
                    "//a[normalize-space(.)=\"$choice\"]/ancestor::fieldset/descendant::input[@type='radio' and @value=$value]";
            try {
                $option = $this->find('xpath', $fieldxpath);
                $option->click();
            } catch (ElementNotFoundException $e) {
                throw new ExpectationException('Option "' . $value . '"  was not found for choice "' . $choice . '".' . $value,
                        $this->getSession());
            }
        }
    }

    /**
     * Enter points for choices
     *
     * @When /^I rate choices with the following points:$/
     *
     * @param TableNode $ratingdata values to be set in the rating form
     */
    public function i_rate_choices_with_the_following_points(TableNode $ratingdata) {
        $ratingdatehash = $ratingdata->getRowsHash();
        // The action depends on the field type.
        foreach ($ratingdatehash as $choice => $value) {
            $fieldxpath = "//*[contains(text(), '$choice')]/ancestor::fieldset/descendant::input[@type='text']";
            try {
                $option = $this->find('xpath', $fieldxpath);
                $option->setValue($value);
            } catch (ElementNotFoundException $e) {
                throw new ExpectationException('Choice "' . $choice . '" was not found.', $this->getSession());
            }
        }
    }

    /**
     * Adds a new ratingallocate to the specified course and section and fills the form with values.
     *
     * @Given I add a ratingallocate to course :coursefullname section :sectionnum and I fill the form with:
     * @param string $courseshortname
     * @param int $sectionnumber
     * @param TableNode $data
     * @return void
     */
    public function i_add_a_ratingallocate_to_course_section_and_fill_form($courseshortname, $sectionnumber, TableNode $data) {
        global $CFG;

        if ($CFG->branch >= 404) {
            $this->execute(
                "behat_course::i_add_to_course_section_and_i_fill_the_form_with",
                [$this->escape('ratingallocate'), $this->escape($courseshortname), $this->escape($sectionnumber), $data]
            );
        } else {
            // This is the code from the deprecated behat function "i_add_to_section_and_i_fill_the_form_with".
            // Add activity to section and fill form.
            $this->execute(
                "behat_course::i_add_to_section_and_i_fill_the_form_with",
                [$this->escape('Fair Allocation'), $this->escape($sectionnumber), $data]
            );
        }
    }

    /**
     * Adds a new ratingallocate to the specified course and section.
     *
     * @Given I add a ratingallocate to course :coursefullname section :sectionnum
     * @param string $courseshortname
     * @param int $sectionnumber
     * @return void
     */
    public function i_add_a_ratingallocate_to_course_section($courseshortname, $sectionnumber) {
        global $CFG;

        if ($CFG->branch >= 404) {
            $this->execute(
                "behat_course::i_add_to_course_section",
                [$this->escape('ratingallocate'), $this->escape($courseshortname), $this->escape($sectionnumber)]
            );
        } else {
            // This is the code from the deprecated behat function "i_add_to_section".
            // Add activity to section.
            $this->execute(
                "behat_course::i_add_to_section",
                [$this->escape('Fair Allocation'), $this->escape($sectionnumber)]
            );
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "mod_ratingallocate > [page name]" page'.
     *
     * Recognised page names are:
     * | None so far!      |                                                              |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Edit'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            default:
                throw new Exception('Unrecognised mod_ratingallocate page type "' . $page . '."');
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype          | name meaning                                | description                                  |
     * | View              | Ratingallocate name                         | The ratingallocate info page                 |
     * | Edit              | Ratingallocate name                         | The edit ratingallocate page                 |
     * | Choices           | Ratingallocate name                         | The page for editing choices                 |
     * | Reports           | Ratingallocate name                         | The page for reports and statistics          |
     *
     * @param string $type identifies which type of page this is, e.g. 'mod_ratingallocate > Choices'.
     * @param string $identifier identifies the particular page, e.g. 'My Fair Allocation'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {

        switch (strtolower($type)) {
            case 'view':
                return new moodle_url('/mod/ratingallocate/view.php',
                    ['id' => $this->get_cm_by_ratingallocate_name($identifier)->id]);

            case 'edit':
                return new moodle_url('/course/modedit.php', [
                    'update' => $this->get_cm_by_ratingallocate_name($identifier)->id]);

            case 'choices':
                return new moodle_url('/mod/ratingallocate/view.php', [
                    'id' => $this->get_cm_by_ratingallocate_name($identifier)->id, 'action' => ACTION_SHOW_CHOICES,
                ]);

            case 'reports':
                return new moodle_url('/mod/ratingallocate/view.php', [
                    'id' => $this->get_cm_by_ratingallocate_name($identifier)->id,
                    'action' => ACTION_SHOW_RATINGS_AND_ALLOCATION_TABLE,
                ]);

            default:
                throw new Exception('Unrecognised ratingallocate page type "' . $type . '."');
        }
    }

    /**
     * Get a ratingallocate instance by name.
     *
     * @param string $name ratingallocate name.
     * @return stdClass the corresponding DB row.
     */
    protected function get_ratingallocate_by_name(string $name): stdClass {
        global $DB;
        return $DB->get_record('ratingallocate', ['name' => $name], '*', MUST_EXIST);
    }

    /**
     * Get a ratingallocate cmid from the ratingallocate name.
     *
     * @param string $name ratingallocate name.
     * @return stdClass cm from get_coursemodule_from_instance.
     */
    protected function get_cm_by_ratingallocate_name(string $name): stdClass {
        $ratingallocate = $this->get_ratingallocate_by_name($name);
        return get_coursemodule_from_instance('ratingallocate', $ratingallocate->id, $ratingallocate->course);
    }

}
