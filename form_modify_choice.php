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

    /**
     * Constructor
     * @param string $url
     * @param ratingallocate $ratingallocate
     * @param ratingallocate_choice $choice
     */
    public function __construct($url, ratingallocate $ratingallocate,
                                ratingallocate_choice $choice = null) {
        $this->ratingallocate = $ratingallocate;
        parent::__construct($url);
        $this->definition_after_data();
        if ($choice) {
            $this->choice = $choice;
        }
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $COURSE, $PAGE, $DB, $USER;

        $mform = $this->_form;
        $mform->addElement('hidden', 'id'); // Save the record's id.
        $mform->setType('id', PARAM_INT);

        $elementname = 'title';
        $mform->addElement('text', $elementname, get_string('choice_title', ratingallocate_MOD_NAME));
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addHelpButton($elementname, 'choice_title', ratingallocate_MOD_NAME);
//        $mform->addRule($elementname, $this->msgerrorrequired , 'required', null, 'server');

        $elementname = 'explanation';
        $mform->addElement('text', $elementname, get_string('choice_explanation', ratingallocate_MOD_NAME));
        $mform->setType($elementname, PARAM_TEXT);

        $elementname = 'maxsize';
        $mform->addElement('text', $elementname, get_string('choice_maxsize', ratingallocate_MOD_NAME));
        $mform->setType($elementname, PARAM_INT);
//        $mform->addRule($elementname, $this->msgerrorrequired , 'required', null, 'server');

        $elementname = 'active';
        $mform->addElement('advcheckbox', $elementname, get_string('choice_active', ratingallocate_MOD_NAME),
            null, null, array(0, 1));
        $mform->addHelpButton($elementname, 'choice_active', ratingallocate_MOD_NAME);

//        $elementname = self::DELETE_CHOICE_ACTION. $this->choice->id;
//        $mform->registerNoSubmitButton($elementname);
//        $mform->addElement('submit', $elementname  , get_string('deletechoice', self::MOD_NAME));
    }

    public function definition_after_data() {
        parent::definition_after_data();
        $mform = & $this->_form;
        if ($this->choice) {
            $mform->setDefault('title', $this->choice->title);
            $mform->setDefault('explanation', $this->choice->explanation);
            $mform->setDefault('maxsize', $this->choice->maxsize);
            $mform->setDefault('active', $this->choice->active);
            $mform->setDefault('id', $this->choice->id);
        }


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

}
