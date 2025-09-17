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
 * Admin settings for mod_ratingallocate
 *
 * @package    mod_ratingallocate
 * @copyright  2015 Tobias Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'ratingallocate_algorithm_timeout',
        get_string('algorithmtimeout', 'ratingallocate'),
        get_string('configalgorithmtimeout', 'ratingallocate'),
        600,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configmulticheckbox(
        'ratingallocate_download_userfields',
        new lang_string('downloaduserfields', 'ratingallocate'),
        new lang_string('configdownloaduserfields', 'ratingallocate'),
        [
                    'id' => 1,
                    'username' => 1,
                    'department' => 0,
                    'institution' => 0,
                    'email' => 1,
            ],
        [
                    'id' => new lang_string('userid', 'ratingallocate'),
                    'username' => new lang_string('username'),
                    'idnumber' => new lang_string('idnumber'),
                    'department' => new lang_string('department'),
                    'institution' => new lang_string('institution'),
                    'email' => new lang_string('email'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'ratingallocate_algorithm_force_background_execution',
        new lang_string('algorithmforcebackground', 'ratingallocate'),
        new lang_string('configalgorithmforcebackground', 'ratingallocate'),
        0
    ));
}
