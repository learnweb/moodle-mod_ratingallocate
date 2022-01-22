<?php
/**
 * Admin settings for mod_ratingallocate
 *
 * @package    mod_ratingallocate
 * @copyright  2015 Tobias Reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('ratingallocate_algorithm_timeout', get_string('algorithmtimeout', 'ratingallocate'),
        get_string('configalgorithmtimeout', 'ratingallocate'), 600, PARAM_INT));

    $settings->add(new admin_setting_configmulticheckbox('ratingallocate_download_userfields',
        new lang_string('downloaduserfields', 'ratingallocate'),
        new lang_string('configdownloaduserfields', 'ratingallocate'),
        [
            'id' => 1,
            'username' => 1,
            'email' => 1
        ],
        [
            'id'          => new lang_string('userid', 'ratingallocate'),
            'username'    => new lang_string('username'),
            'idnumber'    => new lang_string('idnumber'),
            'email'       => new lang_string('email'),
        ]));

    $settings->add(new admin_setting_configcheckbox('ratingallocate_algorithm_force_background_execution',
        new lang_string('algorithmforcebackground', 'ratingallocate'),
        new lang_string('configalgorithmforcebackground', 'ratingallocate'), 0));
}
