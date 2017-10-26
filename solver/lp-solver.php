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
 *
 * Contains a solver which distributes by using external lp solvers
 *
 * @package    mod_ratingallocate
 * @subpackage mod_ratingallocate
 * @copyright  2017 Justus Flerlage
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/solver-template.php');

require_once(dirname(__FILE__).'/../lib/user.php');
require_once(dirname(__FILE__).'/../lib/group.php');
require_once(dirname(__FILE__).'/../lib/ssh.php');
require_once(dirname(__FILE__).'/../lib/lp.php');
require_once(dirname(__FILE__).'/../lib/utility.php');

function dbg_msg($obj) {
    echo "<pre>", print_r($obj), "</pre>";
}

class solver_lp extends distributor {

    public function get_name() {
        return 'lp';
    }

    public function get_ssh_connection() {
        global $CFG;

        return new \ratingallocate\ssh\connection($CFG->ratingallocate_ssh_hostname,
                                                  new \ratingallocate\ssh\password_authentication($CFG->ratingallocate_ssh_username,
                                                                                                  $CFG->ratingallocate_ssh_password));
    }
    
    public function get_executor($engine) {
        global $CFG;

        switch($CFG->ratingallocate_executor)
        {
        case 'webservice':
            return new \ratingallocate\lp\executors\webservice\connector($engine, $CFG->ratingallocate_uri, $CFG->ratingallocate_secret);
            
        case 'ssh':
            return new \ratingallocate\lp\executors\ssh($engine, $this->get_ssh_connection(), $CFG->ratingallocate_remote_path);
        }

        return new \ratingallocate\lp\executors\local($engine, $CFG->ratingallocate_local_path);
    }

    public function get_engine() {
        global $CFG;

        $engine_path = "\\ratingallocate\\lp\\engines\\{$CFG->ratingallocate_engine}";
        return new $engine_path();
    }

    public function compute_distribution($choicerecords, $ratings, $usercount) {
        $configuration = \ratingallocate\utility::get_configuration($CFG);

        list($users, $groups) = \ratingallocate\utility::transform_to_users_and_groups($choicerecords, $ratings);

        $engine = $this->get_engine();
        $executor = $this->get_executor($engine);
        
        $executor->solve_objects($users, $groups, new \ratingallocate\lp\weighters\identity_weighter());

        return \ratingallocate\utility::transform_from_users_and_groups($users, $groups);
    }

}