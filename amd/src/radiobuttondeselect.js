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
 * This class manages the deselection of radiobuttons, which is necessary for deleting allocations
 *
 * @module    mod_ratingallocate/radiobuttondeselect
 * @class     radiobuttondeselect
 * @package   mod_ratingallocate
 * @copyright 2017 Tobias Reischmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    /**
     * @alias module:mod_ratingallocate/radiobuttondeselect
     */
    var t = {

        /**
         * Create the click events for deselecting radiobuttons.
         */
        init: function() {
            $('.ratingallocate_checkbox_label:checked').on('click', this.deselect(this));
            $('.ratingallocate_checkbox_label:not(:checked)').on('click', this.callrefresh(this));
        },

        deselect: function(that) {
            return function (event) {
                if (event.target.checked) {
                    event.target.checked = false;
                }
                that.refresh(that);
            };
        },

        refresh: function(that){
            $('.ratingallocate_checkbox_label').off('click');
            $('.ratingallocate_checkbox_label:checked').on('click', that.deselect(that));
            $('.ratingallocate_checkbox_label:not(:checked)').on('click', that.callrefresh(that));
        },

        callrefresh: function(that){
            return function() {
                that.refresh(that);
            };
        }
    };

    return t;
});
