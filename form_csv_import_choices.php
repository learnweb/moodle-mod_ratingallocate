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
class csv_import_choices_form extends moodleform {

    /**
     * Constructor
     * @param string $url
     * @param ratingallocate $ratingallocate
     * @param ratingallocate_choice $choice
     * @param array $customdata
     */
    public function __construct($url, ratingallocate $ratingallocate, $customdata = null) {
        $this->ratingallocate = $ratingallocate;
        parent::__construct($url, $customdata);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('advcheckbox', 'usestandardmaxsize', get_string('usestandardmaxsize', 'ratingallocate'), ' ');
        $mform->addElement('text', 'standardmaxsize', get_string('standardmaxsize', 'ratingallocate'));
        $mform->setType('standardmaxsize', PARAM_INT);
        $mform->addRule('standardmaxsize', get_string('err_numeric', 'form') , 'numeric', null, 'server');
        $mform->disabledIf('standardmaxsize', 'usestandardmaxsize');

        $mform->addElement('advcheckbox', 'usestandarddescription', get_string('usestandarddescription', 'ratingallocate'), ' ');
        $mform->addElement('text', 'standarddescription', get_string('standarddescription', 'ratingallocate'));
        $mform->setType('standarddescription', PARAM_TEXT);
        $mform->disabledIf('standarddescription', 'usestandarddescription');

        $mform->addElement('filepicker', 'ratingallocate_csv_choices', get_string('csv_import_file', 'ratingallocate'), null, array('accepted_types' => '*'));
        $mform->addRule('ratingallocate_csv_choices', get_string('err_required', 'form') , 'required', null, 'server');

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
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
