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
 * This module disables algorithms if they do not support any of the features required by the instance.
 *
 * @module    mod_ratingallocate/algorithmselection
 * @class     algorithmselection
 * @package   mod_ratingallocate
 * @copyright 2019 Jan Dagefoerde
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/log'], function($, log) {

    /**
     * @alias module:mod_ratingallocate/algorithmselection
     */
    var t = {

        /**
         * Create the click events for deselecting radiobuttons.
         */
        init: function(features) {
            log.info(features);
            //$('.ratingallocate_checkbox_label:checked').on('click', this.deselect(this));
        },

        deselect: function(that) {
            return function (event) {
                // ...
            };
        }
    };

    return t;
});
