<?php

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

class behat_mod_ratingallocate extends behat_base {

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
                $this->execute('behat_forms::i_set_the_field_to', array($locator, $value));
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
        $this->execute('behat_forms::press_button', array("id_submitbutton"));
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
            $newrows = array();
            foreach ($entry as $key => $val) {
                array_push($newrows, array($key, $val));
            }
            //TODO: Ensure backward-compatibility after changed TableNode constructor in Moodle 3.1
            if ($CFG->version < 2016052300) {
                $newrows = implode("\n", $newrows);
            }
            $table = new TableNode($newrows);
            $this->i_set_the_values_of_the_choice_to($table);
            $this->i_add_a_next_choice();
        }

        $this->execute('behat_forms::press_button', array("id_cancel"));
    }

    /**
     * Delete the choice with the respective id.
     *
     * @When /^I delete the choice with the title "([^"]*)"$/
     *
     * @param string $choicetitle tilte of the choice
     */
    public function i_delete_the_choice_with_the_title($choicetitle) {
        $fieldxpath = "//table[@id='mod_ratingallocateshowoptions']//td[text()='$choicetitle']".
            "//following-sibling::td/a[@title='Delete choice']";
        $link = $this->find('xpath', $fieldxpath);
        $link->click();
        $this->execute('behat_general::i_click_on', array("Yes", "button"));
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
        $this->execute("behat_forms::press_button", array(get_string('newchoice', "ratingallocate")));
    }

    /**
     * Adds a new choice for the existing rating allocation.
     *
     * @Given /^I add a next choice$/
     */
    public function i_add_a_next_choice() {
        $this->execute("behat_forms::press_button", array("id_submitbutton2"));
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
     * @throws ExpectationException
     * @param string $title title of the choice
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
     * @throws ExpectationException
     * @param string $title title of the choice
     */
    public function the_choice_should_not_be_active($title) {
        $choice = $this->get_choice($title);
        if ($choice->active) {
            throw new ExpectationException('The choice "' . $title. '" should not be active',
                    $this->getSession());
        }
    }

    /**
     *
     *
     * @Then /^the choice with name "([^"]*)" should have explanation being equal to "([^"]*)"$/
     *
     * @throws ExpectationException
     * @param string $title title of the choice
     * @param string $value expected value
     */
    public function the_choice_should_have_explanation_equal($title, $value) {
        $choice = $this->get_choice($title);
        if ($choice->explanation !== $value) {
            throw new ExpectationException('The explanation of the choice '.$title.
                ' was expected to be "'.$value.'" but was "'.$choice->explanation.'".',
                $this->getSession());
        }
    }

    /**
     * @Then the user :useridentifier should have ratings
     *
     * @throws ExpectationException
     * @param string $username username of a user.
     */
    public function the_user_should_have_ratings($username) {
        $ratings = $this->get_ratings_for_username($username);
        if (count($ratings) == 0) {
            throw new ExpectationException("It was expected that the user $username has ratings, ".
                "but there were none.",
                $this->getSession());
        }
    }

    /**
     * @Then the user :useridentifier should not have ratings
     *
     * @throws ExpectationException
     * @param string $username username of a user.
     */
    public function the_user_should_not_have_ratings($username) {
        $ratings = $this->get_ratings_for_username($username);
        if (count($ratings) > 0) {
            throw new ExpectationException("It was expected that the user $username has no ratings, ".
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
        return $DB->get_records("ratingallocate_ratings", array('userid' => $user->id));
    }

    /**
     *
     *
     * @Then /^the choice with name "([^"]*)" should have maxsize being equal to ([\d]*)$/
     *
     * @throws ExpectationException
     * @param string $title title of the choice
     * @param integer $value expected value
     */
    public function the_choice_should_have_maxsize_equal($title, $value) {
        $choice = $this->get_choice($title);
        if ($choice->maxsize !== $value) {
            throw new ExpectationException('The maxsize of the choice '.$title.
            ' was expected to be "'.$value.'" but was "'.$choice->explanation.'".',
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
        $choices = $DB->get_records("ratingallocate_choices", array('title' => $title));
        if (count($choices) != 1) {
            throw new ExpectationException('Excatly one choice with the name "'.$title.
                '" is expected but '.count($choices). ' found.', $this->getSession());
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
        $fieldxpath = "//table[@id='mod_ratingallocateshowoptions']//td[text()='$choicetitle']".
            "//following-sibling::td/a[@title='$tooltitle']";
        $link = $this->find('xpath', $fieldxpath);
        $link->click();
    }

}


class bht_ratingallocate {

    const modulename = "Fair Allocation";
}
