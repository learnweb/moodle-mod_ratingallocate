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
}
