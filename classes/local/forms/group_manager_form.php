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

namespace mod_ratingallocate\local\forms;

/**
 * Offers the possibility to manage the groups-choice allocation and
 * to create or modify the groups managed by this ratingallocate instance.
 *
 * @package    mod_ratingallocate
 * @copyright  2017 T Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
use mod_ratingallocate\group_mapping;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/../../../locallib.php');
defined('MOODLE_INTERNAL') || die();

/**
 * Provides a form to modify a single choice
 */
class group_manager_form extends \moodleform {

    /** @var $ratingallocate \mod_ratingallocate\ratingallocate */
    private $ratingallocate;

    /**
     * Constructor
     * @param string $url
     * @param \ratingallocate $ratingallocate
     */
    public function __construct($url, \ratingallocate $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        parent::__construct($url);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $COURSE, $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('hidden', 'ratingallocateid'); // Save the record's id.
        $mform->setType('ratingallocateid', PARAM_INT);

        $choices = $this->ratingallocate->get_choices_with_allocationcount();

        foreach ($choices as $choice) {
            $mform->addElement('static', 'choiceid_'.$choice->id, $choice->title,
                $choice->usercount. '/' . $choice->maxsize); // Save the record's id.
            $mform->setType('choiceid_'.$choice->id, PARAM_INT);

            $params = array('choiceid' => $choice->id);
            if (group_mapping::count_records($params) === 0) {
                $mapping = new group_mapping();
                $mapping->set('choiceid', $choice->id);
                $mapping->set('maxsize', $choice->maxsize);
                $mapping->create();
            }
            $mappings = group_mapping::get_records($params);

            $grouprecors = groups_get_all_groups($COURSE->id);
            $groups = array();
            foreach ($grouprecors as $group) {
                $groups[$group->id] = $group->name;
            }
            foreach ($mappings as $mapping) {
                $group = groups_get_group($mapping->get('groupid'));
                $formelems = array();
                $createnewid = 'mapping_new_' . $mapping->get('id');
                $newtitleid = 'mapping_title_' . $mapping->get('id');
                $groupid = 'mapping_group_' . $mapping->get('id');
                $sizeid = 'mapping_size_' . $mapping->get('id');
                $titlegroupelementid = 'titleelem_' . $mapping->get('id');
                $groupgroupelementid = 'groupelem_' . $mapping->get('id');

                $formelems[] = $mform->createElement('static', 'labelcreatenew', '',
                    get_string('createnew_label_group_form', ratingallocate_MOD_NAME));
                $formelems[] = $mform->createElement('checkbox', $createnewid, '');

                $titleelems = array();
                $titleelems[] = $mform->createElement('static', 'labeltitle',  '',
                    get_string('newtitle_label_group_form', ratingallocate_MOD_NAME));
                $titleelems[] = $mform->createElement('text', $newtitleid, '', array('size' => 10));
                $formelems[] = $mform->createElement('group', $titlegroupelementid, '', $titleelems);

                $groupelems = array();
                $groupelems[] = $mform->createElement('static', 'labelgroup', '',
                    get_string('group_label_group_form', ratingallocate_MOD_NAME));
                $groupelems[] = $mform->createElement('select', $groupid, '', $groups, array('size' => 5));
                $formelems[] = $mform->createElement('group', $groupgroupelementid, '', $groupelems);

                $formelems[] = $mform->createElement('static', 'labelsize', '',
                    get_string('size_label_group_form', ratingallocate_MOD_NAME));
                $formelems[] = $mform->createElement('text', $sizeid, '', array('size' => 5));
                $mform->setType($createnewid, PARAM_INT);
                $mform->setType($newtitleid, PARAM_TEXT);
                $mform->setType($groupid, PARAM_TEXT);
                $mform->setType($sizeid, PARAM_INT);
                if ($group) {
                    $mform->setDefault($groupid, $group->name);
                } else {
                    $mform->setDefault($newtitleid, $choice->title);
                    $mform->setDefault($createnewid, true);
                }
                $mform->setDefault($sizeid, $mapping->get('maxsize'));

                $mform->addGroup($formelems, 'groupname',
                    get_string('mapping_label_group_form', ratingallocate_MOD_NAME),
                    '', false);
                $mform->disabledIf($newtitleid, $createnewid, 'notchecked');
                $mform->disabledIf($groupid, $createnewid, 'checked');
                $mform->hideIf($titlegroupelementid, $createnewid, 'notchecked');
                $mform->hideIf($groupgroupelementid, $createnewid, 'checked');
            }
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

    /**
     * Checks that accesstimestart is before accesstimestop
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
