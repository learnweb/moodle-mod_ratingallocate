<?php
/**
 * Admin settings for mod_ratingallocate
 *
 * @package    mod_ratingallocate
 * @copyright  2015 Tobias Reischmann, 2017 Justus Flerlage
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $t = function($name) {
        return get_string($name, 'ratingallocate');
    };

    $settings->add(new admin_setting_heading('ratingallocate_general', $t('general'), ''));
    $options = ['edmonds_karp' => $t('edmonds_karp'),
                'ford_fulkerson' => $t('ford_fulkerson'),
                'lp' => $t('lp')];
    $settings->add(new admin_setting_configselect('ratingallocate_solver', $t('solver'), $t('solver_description'), 'edmonds_karp', $options));
    $settings->add(new admin_setting_configtext('ratingallocate_algorithm_timeout', get_string('algorithmtimeout', 'ratingallocate'),
                                                get_string('configalgorithmtimeout', 'ratingallocate'), 600, PARAM_INT));

    $settings->add(new admin_setting_heading('ratingallocate_lp', $t('lp'), ''));
    $options = ['scip' => $t('scip'),
                'cplex' => $t('cplex')];
    $settings->add(new admin_setting_configselect('ratingallocate_engine', $t('engine'), $t('engine_description'), 'scip', $options));
    $options = ['local' => $t('local'),
                'ssh' => $t('ssh'),
                'webservice' => $t('webservice')];
    $settings->add(new admin_setting_configselect('ratingallocate_executor', $t('executor'), $t('executor_description'), 'local', $options));

    $settings->add(new admin_setting_heading('ratingallocate_webservice', $t('webservice'), $t('webservice_description')));
    $settings->add(new admin_setting_configpasswordunmask('ratingallocate_secret', $t('secret'), $t('secret_description'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('ratingallocate_uri', $t('uri'), $t('uri_description'), 'http://localhost/moodle-mod_ratingallocate/webservice', PARAM_TEXT));

    $settings->add(new admin_setting_heading('ratingallocate_ssh', $t('ssh'), $t('ssh_description')));
    $settings->add(new admin_setting_configtext('ratingallocate_ssh_address', $t('ssh_address'), $t('ssh_address_description'), null, PARAM_TEXT));
    $settings->add(new admin_setting_configtext('ratingallocate_ssh_username', $t('ssh_username'), $t('ssh_username_description'), null, PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('ratingallocate_ssh_password', $t('ssh_password'), $t('ssh_password_description'), null, PARAM_TEXT));

    unset($t);
}
