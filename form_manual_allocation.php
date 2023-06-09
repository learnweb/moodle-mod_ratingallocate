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
 * Prints a particular instance of ratingallocate
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_ratingallocate
 * @copyright  2014 T Reischmann, C Usener
 * @copyright  based on code by M Schulze copyright (C) 2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Provides a form for manual allocations
 */
class manual_alloc_form extends moodleform {

    /** @var $ratingallocate ratingallocate */
    private $ratingallocate;

    const FORM_ACTION = 'action';
    const ASSIGN = 'assign';

    /**
     * Constructor
     * @param mixed $url
     * @param ratingallocate $ratingallocate
     */
    public function __construct($url, ratingallocate $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        $url->params(array("action" => "manual_allocation"));
        parent::__construct($url->out(false));
        $this->definition_after_data();
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $COURSE;

        $mform = $this->_form;

        $mform->addElement('hidden', self::FORM_ACTION, ACTION_MANUAL_ALLOCATION);
        $mform->setType(self::FORM_ACTION, PARAM_TEXT);

        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'page', 0);
        $mform->setType('page', PARAM_INT);

        $this->render_filter();
    }

    protected function render_filter() {
        global $COURSE;
        $mform = &$this->_form;

        $mform->addElement('advcheckbox', 'hide_users_without_rating',
                get_string('filter_hide_users_without_rating', RATINGALLOCATE_MOD_NAME),
                null, array(0, 1));
        $mform->setType('show_users_with_no_rating', PARAM_BOOL);

        $mform->addElement('advcheckbox', 'show_alloc_necessary',
                get_string('filter_show_alloc_necessary', RATINGALLOCATE_MOD_NAME),
                null, array(0, 1));
        $mform->setType('show_alloc_necessary', PARAM_BOOL);

        // Filter by group.
        $choicegroups = $this->ratingallocate->get_all_groups_of_choices();
        $allgroups = array();
        foreach ($choicegroups as $choicegroup) {
            $allgroups[$choicegroup] = groups_get_group($choicegroup);
        }
        $groupsmenu[0] = get_string('allparticipants');
        $groupsmenu[-1] = get_string('nogroup', 'enrol');
        foreach ($allgroups as $gid => $unused) {
            $groupsmenu[$gid] = $allgroups[$gid]->name;
        }
        if (count($groupsmenu) > 1) {
            $mform->addElement('select', 'filtergroup', get_string('group'), $groupsmenu);
        }

        $mform->addElement('submit', 'update_filter',
                get_string('update_filter', RATINGALLOCATE_MOD_NAME));
        $mform->registerNoSubmitButton('update_filter');
    }

    public function definition_after_data() {
        parent::definition_after_data();
        global $PAGE;

        $mform = &$this->_form;

        $ratingdata = $this->ratingallocate->get_ratings_for_rateable_choices();
        $differentratings = array();
        // Add actual rating data to userdata
        foreach ($ratingdata as $rating) {
            if ($rating->rating != null) {
                $differentratings[$rating->rating] = $rating->rating;
            }
        }

        $hidenorating = null;
        $showallocnecessary = null;
        // Get filter settings.
        if ($this->is_submitted()) {
            $hidenorating = $mform->getSubmitValue('hide_users_without_rating');
            $showallocnecessary = $mform->getSubmitValue('show_alloc_necessary');
            $groupselect = $mform->getSubmitValue('filtergroup');
        }

        // Create and set up the flextable for ratings and allocations.
        $table = new mod_ratingallocate\ratings_and_allocations_table($this->ratingallocate->get_renderer(),
                $this->ratingallocate->get_options_titles($differentratings), $this->ratingallocate,
                'manual_allocation', 'mod_ratingallocate_manual_allocation', false);
        $table->setup_table($this->ratingallocate->get_rateable_choices(),
                $hidenorating, $showallocnecessary, $groupselect);

        $filter = $table->get_filter();

        $mform->setDefault('hide_users_without_rating', $filter['hidenorating']);
        $mform->getElement('hide_users_without_rating')->setChecked($filter['hidenorating']);
        $mform->setDefault('show_alloc_necessary', $filter['showallocnecessary']);
        $mform->getElement('show_alloc_necessary')->setChecked($filter['showallocnecessary']);
        $mform->setDefault('filtergroup', $filter['groupselect']);
        $mform->getElement('filtergroup')->setSelected($filter['groupselect']);

        $PAGE->requires->js_call_amd('mod_ratingallocate/radiobuttondeselect', 'init');

        // The rest must be done through output buffering due to the way flextable works.
        ob_start();
        $table->build_table_by_sql($ratingdata, $this->ratingallocate->get_allocations(), true);
        $tableoutput = ob_get_contents();
        ob_end_clean();
        $mform->addElement('html', html_writer::div($tableoutput, 'ratingallocate_ratings_table_container'));

        $mform->setDefault('page', $table->get_page_start() / $table->get_page_size());

        $this->add_special_action_buttons();
    }

    /**
     * Overriding formslib's add_action_buttons() method, to add an extra submit "save changes and continue" button.
     *
     * @param bool $cancel show cancel button
     * @param string $submitlabel null means default, false means none, string is label text
     * @param string $submit2label null means default, false means none, string is label text
     * @return void
     */
    public function add_special_action_buttons() {
        $submitlabel = get_string('savechanges');
        $submit2label = get_string('saveandcontinue', RATINGALLOCATE_MOD_NAME);

        $mform = $this->_form;

        // elements in a row need a group
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton2', $submit2label);
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
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
