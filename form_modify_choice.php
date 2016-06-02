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
 * Offers the possibility to add or modify a choice for the ratingallocate instance.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 T Reischmann, C Usener
 * @copyright  based on code by M Schulze copyright (C) 2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');
defined('MOODLE_INTERNAL') || die();

/**
 * Provides a form to modify a single choice
 */
class modify_choice_form extends moodleform {

    /** @var $ratingallocate ratingallocate */
    private $ratingallocate;

    /** @var $choice ratingallocate_choice */
    private $choice;

    const FORM_ACTION = 'action';
    private $msgerrorrequired;
    private $addnew = false;

    /**
     * Constructor
     * @param string $url
     * @param ratingallocate $ratingallocate
     * @param ratingallocate_choice $choice
     */
    public function __construct($url, ratingallocate $ratingallocate,
                                ratingallocate_choice $choice = null) {
        $this->ratingallocate = $ratingallocate;
        if ($choice) {
            $this->choice = $choice;
        } else {
            $this->addnew = true;
        }
        parent::__construct($url);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'choiceid'); // Save the record's id.
        $mform->setType('choiceid', PARAM_TEXT);

        $elementname = 'title';
        $mform->addElement('text', $elementname, get_string('choice_title', ratingallocate_MOD_NAME));
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addHelpButton($elementname, 'choice_title', ratingallocate_MOD_NAME);
        $mform->addRule($elementname, get_string('err_required', 'form') , 'required', null, 'server');

        $elementname = 'explanation';
        $mform->addElement('text', $elementname, get_string('choice_explanation', ratingallocate_MOD_NAME));
        $mform->setType($elementname, PARAM_TEXT);

        $elementname = 'maxsize';
        $mform->addElement('text', $elementname, get_string('choice_maxsize', ratingallocate_MOD_NAME));
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addRule($elementname, get_string('err_required', 'form') , 'required', null, 'server');
        $mform->addRule($elementname, get_string('err_numeric', 'form') , 'numeric', null, 'server');
        $mform->addRule($elementname, get_string('err_positivnumber', 'ratingallocate') , 'regex', '/^[1-9][0-9]*|0/', 'server');

        $elementname = 'active';
        $mform->addElement('advcheckbox', $elementname, get_string('choice_active', ratingallocate_MOD_NAME),
            null, null, array(0, 1));
        $mform->addHelpButton($elementname, 'choice_active', ratingallocate_MOD_NAME);

        if ($this->choice) {
            $mform->setDefault('title', $this->choice->title);
            $mform->setDefault('explanation', $this->choice->explanation);
            $mform->setDefault('maxsize', $this->choice->maxsize);
            $mform->setDefault('active', $this->choice->active);
            $mform->setDefault('choiceid', $this->choice->id);
        } else {
            $mform->setDefault('active', true);
        }

        $this->add_buttons();
    }

    public function add_buttons() {
        $mform =& $this->_form;

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        if ($this->addnew) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton2',
                get_string('saveandnext', ratingallocate_MOD_NAME));
        }
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Returns the forms HTML code.
     * So we don't have to call display().
     */
    public function to_html() {
        $o = '';
        $o .= $this->_form->getValidationScript();
        $o .= $this->_form->toHtml();
        return $o;
    }

    /**
     * Checks that accesstimestart is before accesstimestop
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
