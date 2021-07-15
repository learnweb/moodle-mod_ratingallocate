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
 * Bulk upload one or more choices for the ratingallocate instance via CSV file.
 *
 * @package    mod_ratingallocate
 * @copyright  2021 David Thompson <david.thompson@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');

/**
 * A form to upload multiple choices
 */
class upload_choices_form extends moodleform {

    /** @var $ratingallocate ratingallocate */
    private $ratingallocate;

    /**
     * Constructor
     * @param string $url
     * @param ratingallocate $ratingallocate
     */
    public function __construct($url, ratingallocate $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        parent::__construct($url);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $requiredfields = \mod_ratingallocate\choice_importer::print_fields();
        $elementname = 'description';
        $mform->addElement('static', $elementname, get_string('upload_choices_required_fields', 'ratingallocate'),
        get_string('upload_choices_fields_desc', 'ratingallocate', $requiredfields));

        $elementname = 'uploadfile';
        $mform->addElement('filepicker', $elementname, get_string('csvupload', 'ratingallocate'), array(
            'accepted_types' => 'text/csv'
        ));
        $mform->addRule($elementname, get_string('err_required', 'form') , 'required', null, 'server');

        $elementname = 'testimport';
        $mform->addElement('advcheckbox', $elementname, get_string('csvupload_test_upload', 'ratingallocate'),
            null, null, array(0, 1));
        $mform->addHelpButton($elementname, 'csvupload_test_upload', 'ratingallocate');

        $this->add_buttons();
    }

    public function add_buttons() {
        $mform =& $this->_form;

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('csvupload', 'ratingallocate'));
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
}
