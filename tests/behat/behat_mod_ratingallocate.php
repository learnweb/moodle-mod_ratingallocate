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
Behat\Mink\Exception\ExpectationException as ExpectationException;


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
            array_push($steps, new Given("I set the field \"id_${locator}\" to \"${value}\""));
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
     * @Given /^I add a new choices with the values:$/
     * @param TableNode $choicedata
     * @return array
     */
    public function i_add_a_new_choices_with_the_values(TableNode $choicedata) {
        $steps = array();
        array_push($steps, $this->i_add_a_new_choice());
        $choicedatahash = $choicedata->getHash();
        foreach ($choicedatahash as $entry) {
            $table = new TableNode();
            foreach ($entry as $key => $val) {
                $table->addRow(array($key, $val));
            }
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
     * @When /^I delete the choice with the id (?P<choice_id>-?\d+)$/
     *
     * @param integer $choiceid id of the choice
     */
    public function i_Delete_The_Choice_With_The_Id($choiceid) {
        return new When('I press "id_delete_choice_'.$choiceid.'"');
    }
    
    /**
     * I set the choice to inactive.
     *
     * @When /^I set the choice with the id (?P<choice_id>-?\d+) to inactive$/
     *
     * @param integer $choiceid id of the choice
     */
    public function i_Set_The_Choice_With_The_Id_To_Inactive($choiceid) {
        $checkbox = $this->find_field("id_choices_${choiceid}_active");
        $checkbox->uncheck();
    }
    
    /**
     * I set the choice to active.
     *
     * @When /^I set the choice with the id (?P<choice_id>-?\d+) to active$/
     *
     * @param integer $choiceid id of the choice
     */
    public function i_Set_The_Choice_With_The_Id_To_Active($choiceid) {
        $checkbox = $this->find_field("id_choices_${choiceid}_active");
        $checkbox->check();
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
     * The choice with id should be active.
     *
     * @Then /^the choice with name "([^"]*)" should be active$/
     *
     * @throws ExpectationException
     * @param string $choice_name title of the choice
     */
    public function the_Choice_should_be_active($choice_name) {
        $choice = $this->get_choice($choice_name);
        if (!$choice->active){
            throw new ExpectationException('The choice "' . $choice_name .
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
     * @param string $choice_name title of the choice
     */
    public function the_Choice_should_not_be_active($choice_name) {
        $choice = $this->get_choice($choice_name);
        if ($choice->active){
            throw new ExpectationException('The choice "' . $choice_name. '" should not be active',
                    $this->getSession());
        }
    }
    
    /**
     * 
     * 
     * @Then /^the choice with name "([^"]*)" should have explanation being equal to "([^"]*)"$/
     * 
     * @throws ExpectationException
     * @param string $choice_name title of the choice
     * @param string $value expected value
     */
    public function the_choice_should_have_explanation_equal($choice_name, $value){
        $choice = $this->get_choice($choice_name);
        if ($choice->explanation !== $value){
        throw new ExpectationException('The explanation of the choice '.$choice_name.' was expected to be "'.$value.'" but was "'.$choice->explanation.'".',
                $this->getSession());
        }
    }
    
    /**
     *
     *
     * @Then /^the choice with name "([^"]*)" should have maxsize being equal to ([\d]*)$/
     *
     * @throws ExpectationException
     * @param string $choice_name title of the choice
     * @param integer $value expected value
     */
    public function the_choice_should_have_maxsize_equal($choice_name, $value){
        $choice = $this->get_choice($choice_name);
        if ($choice->maxsize !== $value){
        throw new ExpectationException('The maxsize of the choice '.$choice_name.' was expected to be "'.$value.'" but was "'.$choice->explanation.'".',
                $this->getSession());
        }
    }
    
    private function get_choice($title){
        global $DB;
        $choices = $DB->get_records("ratingallocate_choices", array('title' => $title));
        if (count($choices)!=1){
            throw new ExpectationException('Excatly one choice with the name "'.$title.'" is expected but '.count($choices). ' found.',$this->getSession());
        }
        return  array_shift($choices);
    }
    
}


class bht_ratingallocate {

    const modulename = "Ratingallocate";
}
