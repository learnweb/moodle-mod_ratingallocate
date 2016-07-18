<?php

/**
 * Steps definitions related to mod_reallocate.
 *
 * @package mod_ratingallocate
 * @category test
 * @copyright 2014 Tobias Reischmann
 */
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
Behat\Gherkin\Node\TableNode as TableNode,
Behat\Behat\Context\Step\When as When,
Behat\Mink\Exception\ExpectationException as ExpectationException,
Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

class behat_mod_ratingallocate extends behat_base {

    /**
     * Fills the respective fields of a choice.
     *
     * @Given /^I set the values of the choice to:$/
     *
     * @param TableNode $choicedata with data for filling the choice
     * @return array step definition
     */
    public function i_set_the_values_of_the_choice_to(TableNode $choicedata) {
        $choicedatahash = $choicedata->getRowsHash();
        // The action depends on the field type.
        $steps = array();
        foreach ($choicedatahash as $locator => $value) {
            if ($locator === 'active') {
                if ($value === 'true') {
                    array_push($steps, new Given("I check the active checkbox"));
                } else {
                    array_push($steps, new Given("I uncheck the active checkbox"));
                }
            } else {
                array_push($steps, new Given("I set the field \"id_${locator}\" to \"${value}\""));
            }
        }
        return $steps;
    }

    /**
     * Adds a new choice by first clicking on the add new choice button, filling the form and finally
     * submitting it.
     *
     * @Given /^I add a new choice with the values:$/
     * @param TableNode $choicedata
     * @return array
     */
    public function i_add_a_new_choice_with_the_values(TableNode $choicedata) {
        $steps = array();
        array_push($steps, $this->i_add_a_new_choice());
        foreach ($this->i_set_the_values_of_the_choice_to($choicedata) as $step) {
            array_push($steps, $step);
        }
        array_push($steps, new Given("I press \"id_submitbutton\""));
        return $steps;
    }

    /**
     * Adds new choices by first clicking on the add new choice button, filling the form and then continually
     * adding new choices using the add next button. Finally, the last view is canceled.
     *
     * @Given /^I add new choices with the values:$/
     * @param TableNode $choicedata
     * @return array
     */
    public function i_add_new_choices_with_the_values(TableNode $choicedata) {
        global $CFG;
        $steps = array();
        array_push($steps, $this->i_add_a_new_choice());
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
            foreach ($this->i_set_the_values_of_the_choice_to($table) as $step) {
                array_push($steps, $step);
            }
            array_push($steps, $this->i_add_a_next_choice());
        }
        array_push($steps, new Given("I press \"id_cancel\""));
        return $steps;
    }

    /**
     * Delete the choice with the respective id.
     *
     * @When /^I delete the choice with the title "([^"]*)"$/
     *
     * @param string $choicetitle tilte of the choice
     *
     * @return When step.
     */
    public function i_delete_the_choice_with_the_title($choicetitle) {
        $fieldxpath = "//table[@id='mod_ratingallocateshowoptions']//td[text()='$choicetitle']".
            "//following-sibling::td/a[@title='Delete choice']";
        $link = $this->find('xpath', $fieldxpath);
        $link->click();
        return new When("I click on \"Yes\" \"button\"");
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
        return new Given('I press "'.get_string('newchoice', 'ratingallocate').'"');
    }

    /**
     * Adds a new choice for the existing rating allocation.
     *
     * @Given /^I add a next choice$/
     */
    public function i_add_a_next_choice() {
        return new Given('I press "id_submitbutton2"');
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

    const modulename = "Ratingallocate";
}
