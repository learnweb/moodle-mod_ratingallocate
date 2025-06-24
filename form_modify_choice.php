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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');

/**
 * Offers the possibility to add or modify a choice for the ratingallocate instance.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 T Reischmann, C Usener
 * @copyright  based on code by M Schulze copyright (C) 2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modify_choice_form extends moodleform {

    /** @var $ratingallocate ratingallocate */
    private $ratingallocate;

    /** @var $choice ratingallocate_choice */
    private $choice;

    /** The form action. */
    const FORM_ACTION = 'action';
    /** @var $msgerrorrequired */
    private $msgerrorrequired;
    /** @var bool $addnew */
    private $addnew = false;

    /**
     * Constructor
     *
     * @param string $url
     * @param ratingallocate $ratingallocate
     * @param ratingallocate_choice|null $choice
     * @param array|null $customdata
     * @return void
     * @throws coding_exception
     */
    public function __construct($url, ratingallocate $ratingallocate,
            ?ratingallocate_choice $choice, ?array $customdata) {
        $this->ratingallocate = $ratingallocate;
        if ($choice) {
            $this->choice = $choice;
            // Special handling for HTML editor.
            $this->choice->explanation = [
                    'text' => $this->choice->explanation,
                    'format' => FORMAT_HTML,
            ];
        } else {
            $this->addnew = true;
        }

        parent::__construct($url, $customdata);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'choiceid'); // Save the record's id.
        $mform->setType('choiceid', PARAM_TEXT);

        $elementname = 'title';
        $mform->addElement('text', $elementname, get_string('choice_title', RATINGALLOCATE_MOD_NAME));
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addHelpButton($elementname, 'choice_title', RATINGALLOCATE_MOD_NAME);
        $mform->addRule($elementname, get_string('err_required', 'form'), 'required', null, 'server');
        $mform->addRule($elementname, get_string('title_too_long_error', RATINGALLOCATE_MOD_NAME),
            'maxlength', '255');

        $elementname = 'explanation';
        $editoroptions = [
                'enable_filemanagement' => false,
        ];
        $mform->addElement('editor', $elementname, get_string('choice_explanation', RATINGALLOCATE_MOD_NAME), $editoroptions);
        $mform->setType($elementname, PARAM_RAW);

        $elementname = 'maxsize';
        $mform->addElement('text', $elementname, get_string('choice_maxsize', RATINGALLOCATE_MOD_NAME));
        $mform->setType($elementname, PARAM_TEXT);
        $mform->addRule($elementname, get_string('err_required', 'form'), 'required', null, 'server');
        $mform->addRule($elementname, get_string('err_numeric', 'form'), 'numeric', null, 'server');
        $mform->addRule($elementname, get_string('err_positivnumber', 'ratingallocate'), 'regex', '/^[1-9][0-9]*|0/', 'server');

        $elementname = 'attachments_filemanager';
        $mform->addElement('filemanager', $elementname, get_string('uploadafile'), null, [
                'accepted_types' => '*',
                'subdirs' => false,
        ]);
        $this->set_data($this->_customdata['attachment_data']);

        $elementname = 'active';
        $mform->addElement('advcheckbox', $elementname, get_string('choice_active', RATINGALLOCATE_MOD_NAME),
                null, null, [0, 1]);
        $mform->addHelpButton($elementname, 'choice_active', RATINGALLOCATE_MOD_NAME);

        $elementname = 'usegroups';
        $mform->addelement('advcheckbox', $elementname, get_string('choice_usegroups', RATINGALLOCATE_MOD_NAME),
            null, null, [0, 1]);
        $mform->addHelpButton($elementname, 'choice_usegroups', RATINGALLOCATE_MOD_NAME);

        $elementname = 'groupselector';
        $options = $this->ratingallocate->get_group_selections();
        $selector = $mform->addelement('searchableselector', $elementname,
            get_string('choice_groupselect', RATINGALLOCATE_MOD_NAME), $options);
        $selector->setMultiple(true);
        $mform->hideIf('groupselector', 'usegroups');

        if ($this->choice) {
            $mform->setDefault('title', $this->choice->title);
            $mform->setDefault('explanation', $this->choice->explanation);
            $mform->setDefault('maxsize', $this->choice->maxsize);
            $mform->setDefault('active', $this->choice->active);
            $mform->setDefault('usegroups', $this->choice->usegroups);
            $mform->setDefault('choiceid', $this->choice->id);
            // Populate groupselector with IDs of any currently selected groups.
            $choicegroups = $this->ratingallocate->get_choice_groups($this->choice->id);
            $mform->setDefault('groupselector', array_keys($choicegroups));
        } else {
            $mform->setDefault('active', true);
        }

        $this->add_buttons();
    }

    /**
     * Add buttons to form.
     *
     * @return void
     * @throws coding_exception
     */
    public function add_buttons() {
        $mform =& $this->_form;

        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        if ($this->addnew) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton2',
                    get_string('saveandnext', RATINGALLOCATE_MOD_NAME));
        }
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
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
     *
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
